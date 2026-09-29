<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\MaterialDelProyecto;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use App\Support\Publicacion\PrimerosPasos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * La promotora entrega el material desde el panel, y el equipo lo encuentra.
 *
 * Hasta ahora llegaba por fuera -- correo, WhatsApp, un enlace de Drive -- y
 * no habia forma de saber que habia llegado ni de que faltaba. Esto es la
 * mitad de la promotora de la costura del reparto: ella entrega, el equipo
 * monta.
 */
class MaterialTest extends TestCase
{
    use RefreshDatabase;

    private User $ana;

    private User $otra;

    private User $equipo;

    private Project $suyo;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();

        $this->ana = $this->promotora('ana');
        $this->otra = $this->promotora('otra');
        $this->equipo = User::factory()->create(['role' => User::ROLE_GESTOR]);

        $this->suyo = Project::create([
            'name' => 'Residencial Bahia',
            'slug' => 'residencial-bahia',
            'status' => 'draft',
            'created_by' => $this->ana->id,
        ]);
        $this->ana->assignedProjects()->attach($this->suyo->id);
    }

    private function promotora(string $nombre): User
    {
        $user = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);
        CompanyProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Promotora de '.$nombre,
            'slug' => 'promotora-de-'.$nombre,
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL,
            'max_projects' => 5,
            'max_storage_bytes' => 10_000_000,
        ]);

        return $user->fresh();
    }

    private function entregar(User $quien, string $tipo = 'planos', ?UploadedFile $fichero = null, ?string $enlace = null)
    {
        return $this->actingAs($quien)->post(route('admin.projects.material.store', $this->suyo), array_filter([
            'tipo' => $tipo,
            'fichero' => $fichero,
            'enlace' => $enlace,
        ]));
    }

    public function test_entrega_un_fichero_y_queda_guardado_con_fecha_y_autor(): void
    {
        $this->entregar($this->ana, 'planos', UploadedFile::fake()->create('planta-1.pdf', 300, 'application/pdf'))
            ->assertRedirect(route('admin.projects.material.index', $this->suyo));

        $pieza = MaterialDelProyecto::firstOrFail();
        $this->assertSame('planos', $pieza->tipo);
        $this->assertSame('planta-1.pdf', $pieza->original_name);
        $this->assertSame($this->ana->id, $pieza->subido_por);
        Storage::assertExists($pieza->storage_path);

        // Y cuenta en su cuota, como cualquier fichero suyo.
        $this->assertSame(300 * 1024, $this->ana->companyProfile->fresh()->storage_used_bytes);
    }

    public function test_lo_que_no_cabe_va_como_enlace(): void
    {
        $this->entregar($this->ana, 'video_360', null, 'https://drive.example.com/video-360')
            ->assertRedirect();

        $pieza = MaterialDelProyecto::firstOrFail();
        $this->assertTrue($pieza->esEnlace());
        $this->assertNull($pieza->storage_path);

        $this->actingAs($this->equipo)
            ->get(route('admin.projects.material.descargar', [$this->suyo, $pieza]))
            ->assertRedirect('https://drive.example.com/video-360');
    }

    public function test_sin_fichero_ni_enlace_no_se_entrega_nada(): void
    {
        $this->entregar($this->ana, 'renders')->assertSessionHasErrors();
        $this->assertSame(0, MaterialDelProyecto::count());
    }

    public function test_el_equipo_lo_descarga_y_otra_promotora_no(): void
    {
        $this->entregar($this->ana, 'renders', UploadedFile::fake()->image('fachada.png'));
        $pieza = MaterialDelProyecto::firstOrFail();

        $this->actingAs($this->equipo)
            ->get(route('admin.projects.material.descargar', [$this->suyo, $pieza]))
            ->assertOk()
            ->assertDownload('fachada.png');

        $this->actingAs($this->otra)
            ->get(route('admin.projects.material.descargar', [$this->suyo, $pieza]))
            ->assertForbidden();
        $this->actingAs($this->otra)
            ->get(route('admin.projects.material.index', $this->suyo))
            ->assertForbidden();
        $this->entregar($this->otra, 'planos', UploadedFile::fake()->create('ajeno.pdf', 10))
            ->assertForbidden();
    }

    public function test_quitar_una_pieza_borra_el_fichero_y_devuelve_la_cuota(): void
    {
        $this->entregar($this->ana, 'planos', UploadedFile::fake()->create('planta-1.pdf', 300));
        $pieza = MaterialDelProyecto::firstOrFail();
        $ruta = $pieza->storage_path;

        $this->actingAs($this->ana)
            ->delete(route('admin.projects.material.destroy', [$this->suyo, $pieza]))
            ->assertRedirect();

        $this->assertDatabaseMissing('project_material', ['id' => $pieza->id]);
        Storage::assertMissing($ruta);
        $this->assertSame(0, $this->ana->companyProfile->fresh()->storage_used_bytes);
    }

    public function test_con_la_cuota_llena_no_entra_mas(): void
    {
        $this->ana->companyProfile->update(['storage_used_bytes' => 10_000_000]);

        $this->entregar($this->ana, 'planos', UploadedFile::fake()->create('planta-1.pdf', 300))
            ->assertSessionHas('error');

        $this->assertSame(0, MaterialDelProyecto::count());
    }

    public function test_la_pagina_dice_que_falta_y_que_hay(): void
    {
        $this->entregar($this->ana, 'planos', UploadedFile::fake()->create('planta-1.pdf', 10));

        $this->actingAs($this->ana)->get(route('admin.projects.material.index', $this->suyo))
            ->assertOk()
            ->assertSee('planta-1.pdf')
            ->assertSee(__('material.tipo_renders'))
            ->assertSee(__('material.imprescindible'));
    }

    public function test_es_un_paso_de_los_primeros_pasos_entre_viviendas_y_visor(): void
    {
        $actual = fn () => PrimerosPasos::de($this->ana->fresh())->pasos()->firstWhere('actual', true)['clave'];

        Unit::create([
            'project_id' => $this->suyo->id, 'identifier' => 'A-1', 'floor' => 1,
            'bedrooms' => 1, 'bathrooms' => 1, 'area_m2' => 50, 'price' => 100000,
            'status' => 'available', 'sort_order' => 1,
        ]);
        $this->assertSame('entregar_material', $actual());

        $this->entregar($this->ana, 'planos', UploadedFile::fake()->create('planta-1.pdf', 10));
        $this->assertSame('pedir_visor', $actual());
    }

    public function test_la_cola_del_equipo_ve_cuanto_material_hay(): void
    {
        $this->entregar($this->ana, 'planos', UploadedFile::fake()->create('planta-1.pdf', 10));
        $this->entregar($this->ana, 'renders', UploadedFile::fake()->image('fachada.png'));
        $this->suyo->update(['viewer_requested_at' => now(), 'viewer_requested_by' => $this->ana->id]);

        $this->actingAs($this->equipo)->get(route('admin.visores.pendientes'))
            ->assertOk()
            ->assertSee(route('admin.projects.material.index', $this->suyo), false)
            ->assertSee('2 ');
    }
}
