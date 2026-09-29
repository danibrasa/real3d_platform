<?php

namespace Tests\Feature;

use App\Mail\VisorRevisado;
use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * La promotora ve su visor antes de que se publique y da el visto bueno.
 *
 * Un proyecto en borrador solo lo veia el equipo: la promotora se enteraba
 * del resultado al publicarlo. Ahora ve lo suyo, aprueba o pide cambios, y
 * queda quien y cuando.
 */
class VistoBuenoTest extends TestCase
{
    use RefreshDatabase;

    private User $ana;

    private User $otra;

    private User $gestora;

    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake();

        $this->ana = $this->promotora('ana');
        $this->otra = $this->promotora('otra');
        $this->gestora = User::factory()->create(['role' => User::ROLE_GESTOR, 'email' => 'gestora@real3d.invalid']);

        $this->proyecto = Project::create([
            'name' => 'Residencial Bahia', 'slug' => 'residencial-bahia', 'status' => 'draft', 'created_by' => $this->ana->id,
        ]);
        $this->ana->assignedProjects()->attach($this->proyecto->id);
        Storage::put('x/fondo.jpg', 'fondo');
        ProjectFile::create([
            'project_id' => $this->proyecto->id, 'file_type' => 'image_360', 'original_name' => 'fondo.jpg',
            'storage_path' => 'x/fondo.jpg', 'mime_type' => 'image/jpeg', 'file_size' => 5, 'upload_complete' => true,
        ]);
        $this->proyecto->forceFill([
            'viewer_requested_at' => now(), 'viewer_requested_by' => $this->ana->id,
            'visor_estado' => 'para_revisar', 'visor_estado_en' => now(),
        ])->save();
    }

    private function promotora(string $nombre): User
    {
        $user = User::factory()->create(['role' => User::ROLE_INMOBILIARIA, 'email' => $nombre.'@ejemplo.invalid']);
        CompanyProfile::create([
            'user_id' => $user->id, 'company_name' => 'Promotora de '.$nombre, 'slug' => 'promotora-de-'.$nombre,
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL, 'max_projects' => 5,
        ]);

        return $user->fresh();
    }

    public function test_la_promotora_ve_su_visor_en_borrador_y_los_demas_no(): void
    {
        $this->actingAs($this->ana)->get(route('viewer.show', $this->proyecto))->assertOk();
        $this->actingAs($this->ana)->get($this->proyecto->urlDeFichero('image_360'))->assertOk();

        $this->actingAs($this->otra)->get(route('viewer.show', $this->proyecto))->assertNotFound();
        $this->actingAs($this->otra)->get($this->proyecto->urlDeFichero('image_360'))->assertNotFound();
        $this->get(route('viewer.show', $this->proyecto))->assertNotFound();
    }

    public function test_su_ficha_le_ofrece_verlo_y_decidir(): void
    {
        $this->actingAs($this->ana)->get(route('admin.projects.edit', $this->proyecto))
            ->assertOk()
            ->assertSee(route('viewer.show', $this->proyecto), false)
            ->assertSee(__('visor.aprobar'))
            ->assertSee(__('visor.pedir_cambios'));
    }

    public function test_aprobar_deja_quien_y_cuando_y_avisa_al_equipo(): void
    {
        $this->actingAs($this->ana)
            ->post(route('admin.projects.visor.revisado', $this->proyecto), ['veredicto' => 'aprobado'])
            ->assertRedirect();

        $p = $this->proyecto->fresh();
        $this->assertNotNull($p->visor_aprobado_en);
        $this->assertSame($this->ana->id, $p->visor_aprobado_por);
        $this->assertSame('para_revisar', $p->visor_estado, 'aprobar no cambia el estado: eso lo hace el equipo al darlo por montado');

        Mail::assertQueued(VisorRevisado::class, fn ($m) => $m->hasTo('gestora@real3d.invalid') && $m->aprobado);

        $this->actingAs($this->gestora)->get(route('admin.visores.pendientes'))
            ->assertOk()
            ->assertSee(__('visor.aprobado_por', ['quien' => $this->ana->name]));
    }

    public function test_pedir_cambios_devuelve_el_visor_a_preparacion_con_el_comentario(): void
    {
        $this->actingAs($this->ana)
            ->post(route('admin.projects.visor.revisado', $this->proyecto), ['veredicto' => 'cambios', 'comentario' => 'La piscina esta donde va el parking.'])
            ->assertRedirect();

        $p = $this->proyecto->fresh();
        $this->assertSame('en_preparacion', $p->visor_estado);
        $this->assertNull($p->visor_aprobado_en);
        $this->assertSame('La piscina esta donde va el parking.', $p->visor_comentario);

        Mail::assertQueued(VisorRevisado::class, fn ($m) => ! $m->aprobado && $m->comentario === 'La piscina esta donde va el parking.');

        $this->actingAs($this->gestora)->get(route('admin.visores.pendientes'))
            ->assertOk()
            ->assertSee('La piscina esta donde va el parking.');
    }

    public function test_pedir_cambios_sin_decir_cuales_no_vale(): void
    {
        $this->actingAs($this->ana)
            ->post(route('admin.projects.visor.revisado', $this->proyecto), ['veredicto' => 'cambios'])
            ->assertSessionHasErrors('comentario');
    }

    public function test_otra_promotora_no_aprueba_lo_ajeno(): void
    {
        $this->actingAs($this->otra)
            ->post(route('admin.projects.visor.revisado', $this->proyecto), ['veredicto' => 'aprobado'])
            ->assertForbidden();

        $this->assertNull($this->proyecto->fresh()->visor_aprobado_en);
    }

    public function test_el_correo_se_pinta(): void
    {
        $html = (new VisorRevisado($this->proyecto, $this->ana, false, 'Falta la piscina.'))->render();

        $this->assertStringContainsString('Piden cambios', $html);
        $this->assertStringContainsString('Falta la piscina.', $html);
        $this->assertStringContainsString('Residencial Bahia', $html);
    }
}
