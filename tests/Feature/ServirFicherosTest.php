<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Como salen el modelo y el fondo 360: con cache de verdad, y por nginx.
 *
 * La auditoria del 29-sep-2026 los encontro saliendo por PHP, con una hora
 * de cache y sin ETag: cada visita del modelo eran 35 MB leidos por un
 * proceso php-fpm, y a la hora el navegador los volvia a pedir. Y un video
 * de 315 MB se aceptaba tal cual, cuatro veces.
 */
class ServirFicherosTest extends TestCase
{
    use RefreshDatabase;

    private Project $proyecto;

    private ProjectFile $fondo;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();

        $promotora = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);
        CompanyProfile::create([
            'user_id' => $promotora->id,
            'company_name' => 'Promotora Bahia',
            'slug' => 'promotora-bahia',
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL,
            'max_projects' => 5,
        ]);
        $this->proyecto = Project::create([
            'name' => 'Residencial Bahia',
            'slug' => 'residencial-bahia',
            'status' => 'public',
            'created_by' => $promotora->id,
        ]);
        $promotora->assignedProjects()->attach($this->proyecto->id);

        Storage::put('x/fondo.jpg', 'un fondo cualquiera');
        $this->fondo = ProjectFile::create([
            'project_id' => $this->proyecto->id,
            'file_type' => 'image_360',
            'original_name' => 'fondo.jpg',
            'storage_path' => 'x/fondo.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 19,
            'upload_complete' => true,
        ]);
    }

    public function test_la_direccion_lleva_la_version_del_fichero(): void
    {
        $url = $this->proyecto->urlDeFichero('image_360');

        $this->assertStringStartsWith('/api/projects/residencial-bahia/files/image_360?v=', $url);
        $this->assertStringContainsString('v='.$this->fondo->version(), $url);
        $this->assertNull($this->proyecto->urlDeFichero('model_3d'), 'sin fichero no hay direccion');

        // Y la pagina del visor la usa: sin esto la cabecera inmutable no
        // se activa nunca.
        $this->get(route('viewer.show', $this->proyecto))
            ->assertOk()
            ->assertSee('image_360?v='.$this->fondo->version(), false);
    }

    public function test_con_la_version_en_la_direccion_es_inmutable_un_ano(): void
    {
        $r = $this->get($this->proyecto->urlDeFichero('image_360'));

        $r->assertOk();
        // Symfony reordena la cabecera: se mira lo que dice, no como lo ordena.
        $this->assertStringContainsString('immutable', $r->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=31536000', $r->headers->get('Cache-Control'));
        $this->assertSame('"'.$this->fondo->version().'"', $r->headers->get('ETag'));
        $this->assertNotNull($r->headers->get('Last-Modified'));
    }

    public function test_sin_version_una_hora_y_con_etag(): void
    {
        $r = $this->get('/api/projects/residencial-bahia/files/image_360');

        $r->assertOk();
        $this->assertStringContainsString('max-age=3600', $r->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('immutable', $r->headers->get('Cache-Control'));
        $this->assertSame('"'.$this->fondo->version().'"', $r->headers->get('ETag'));
    }

    public function test_una_version_vieja_no_es_inmutable(): void
    {
        // Si se reemplaza el fichero, la direccion vieja sigue funcionando
        // pero no se puede declarar inmutable: apunta a otra cosa.
        $r = $this->get('/api/projects/residencial-bahia/files/image_360?v=1-0');

        $this->assertStringContainsString('max-age=3600', $r->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('immutable', $r->headers->get('Cache-Control'));
    }

    public function test_si_el_navegador_ya_lo_tiene_se_le_dice_304(): void
    {
        $this->withHeader('If-None-Match', '"'.$this->fondo->version().'"')
            ->get($this->proyecto->urlDeFichero('image_360'))
            ->assertStatus(304);
    }

    public function test_por_nginx_php_no_manda_el_fichero_sino_la_orden(): void
    {
        config(['ficheros.por_nginx' => true]);

        $r = $this->get($this->proyecto->urlDeFichero('image_360'));

        $r->assertOk();
        $this->assertSame('/_ficheros/x/fondo.jpg', $r->headers->get('X-Accel-Redirect'));
        $this->assertSame('image/jpeg', $r->headers->get('Content-Type'));
        $this->assertSame('', $r->getContent(), 'PHP mando el fichero ademas de la orden');
    }

    public function test_por_php_manda_el_fichero_de_verdad(): void
    {
        config(['ficheros.por_nginx' => false]);

        $r = $this->get($this->proyecto->urlDeFichero('image_360'));

        $r->assertOk();
        $this->assertNull($r->headers->get('X-Accel-Redirect'));
    }

    public function test_el_muro_del_plan_sigue_delante_de_todo_esto(): void
    {
        $this->proyecto->assignedAgencies()->first()->companyProfile->update(['plan_tier' => CompanyProfile::PLAN_STARTER]);
        config(['ficheros.por_nginx' => true]);

        $this->get($this->proyecto->fresh()->urlDeFichero('image_360'))->assertNotFound();
    }

    public function test_un_video_de_mas_de_lo_permitido_no_se_acepta(): void
    {
        $equipo = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);

        $this->actingAs($equipo)
            ->postJson(route('admin.projects.upload.init', $this->proyecto), [
                'file_type' => 'video_360',
                'original_name' => 'paseo.mp4',
                'total_size' => 315 * 1048576,
                'total_chunks' => 63,
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['error' => __('ficheros.demasiado_grande', ['tipo' => 'video_360', 'mb' => 100])]);

        // Y por debajo, entra.
        $this->actingAs($equipo)
            ->postJson(route('admin.projects.upload.init', $this->proyecto), [
                'file_type' => 'video_360',
                'original_name' => 'paseo.mp4',
                'total_size' => 80 * 1048576,
                'total_chunks' => 16,
            ])
            ->assertOk();
    }
}
