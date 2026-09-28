<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Unit;
use App\Models\User;
use App\Support\Publicacion\PrimerosPasos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lo primero que ve una promotora al entrar.
 *
 * Antes de esto, alguien recien registrado entraba al panel y veia un menu y
 * nada mas: ni un boton ni una indicacion. Tenia que adivinar que empezaba
 * creando un proyecto, en un producto que promete que ponerlo en marcha cuesta
 * poco trabajo.
 */
class PrimerosPasosTest extends TestCase
{
    use RefreshDatabase;

    private User $promotora;

    protected function setUp(): void
    {
        parent::setUp();

        $this->promotora = User::factory()->create(['role' => 'inmobiliaria']);
        CompanyProfile::create([
            'user_id' => $this->promotora->id,
            'company_name' => 'Promotora Nueva',
            'slug' => 'promotora-nueva',
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL,
            'max_projects' => 5,
        ]);
    }

    private function pasos(): array
    {
        return PrimerosPasos::de($this->promotora->fresh())->pasos()->all();
    }

    private function actual(): string
    {
        foreach ($this->pasos() as $p) {
            if ($p['actual']) {
                return $p['clave'];
            }
        }

        return 'ninguno';
    }

    private function proyecto(): Project
    {
        $p = Project::create([
            'name' => 'Residencial', 'slug' => 'residencial',
            'status' => 'draft', 'created_by' => $this->promotora->id,
        ]);
        $this->promotora->assignedProjects()->attach($p->id);

        return $p;
    }

    private function vivienda(Project $p): void
    {
        Unit::create([
            'project_id' => $p->id, 'identifier' => 'A-101', 'floor' => 1,
            'bedrooms' => 2, 'bathrooms' => 2, 'area_m2' => 85,
            'price' => 185000, 'status' => 'available', 'sort_order' => 1,
        ]);
    }

    private function visor(Project $p): void
    {
        ProjectFile::create([
            'project_id' => $p->id, 'file_type' => 'image_360',
            'original_name' => 'f.png', 'storage_path' => 'x/f.png',
            'mime_type' => 'image/png', 'file_size' => 10, 'upload_complete' => true,
        ]);
    }

    // --- El recorrido, paso a paso ----------------------------------------

    public function test_recien_registrada_le_toca_crear_el_proyecto(): void
    {
        $this->assertSame('crear_proyecto', $this->actual());
    }

    public function test_con_proyecto_le_toca_cargar_las_viviendas(): void
    {
        $this->proyecto();

        $this->assertSame('cargar_viviendas', $this->actual());
    }

    public function test_con_viviendas_le_toca_pedir_el_visor(): void
    {
        $this->vivienda($this->proyecto());

        $this->assertSame('pedir_visor', $this->actual());
    }

    public function test_pedido_el_visor_deja_de_estar_pendiente_de_ella(): void
    {
        // Mientras el equipo lo monta, el paso no debe seguir marcado como suyo:
        // no hay nada que pueda hacer y parecerian deberes sin cumplir.
        $p = $this->proyecto();
        $this->vivienda($p);
        $p->update(['viewer_requested_at' => now()]);

        $this->assertSame('publicar', $this->actual());
    }

    public function test_con_el_visor_montado_le_toca_publicar(): void
    {
        $p = $this->proyecto();
        $this->vivienda($p);
        $this->visor($p);

        $this->assertSame('publicar', $this->actual());
    }

    public function test_publicado_ya_no_se_enseñan(): void
    {
        $p = $this->proyecto();
        $this->vivienda($p);
        $this->visor($p);
        $p->update(['status' => 'public']);

        $pasos = PrimerosPasos::de($this->promotora->fresh());

        $this->assertTrue($pasos->terminado());
        $this->assertFalse($pasos->hayQueEnseñarlos());
    }

    // --- A quien se le enseñan --------------------------------------------

    public function test_al_equipo_no_se_le_enseñan(): void
    {
        // El superadmin no esta dando de alta su promotora: le estorbaria.
        $jefe = User::factory()->create(['role' => 'superadmin']);

        $this->assertFalse(PrimerosPasos::de($jefe)->hayQueEnseñarlos());
    }

    public function test_solo_hay_un_boton_cada_vez(): void
    {
        // Cuatro botones a la vez es otra vez no decirle por donde empezar.
        $this->proyecto();

        $conBoton = collect($this->pasos())->filter(fn ($p) => $p['actual'] && $p['enlace']);

        $this->assertCount(1, $conBoton);
    }

    public function test_el_panel_lo_enseña(): void
    {
        $this->actingAs($this->promotora)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('primeros_pasos.titulo'))
            ->assertSee(__('primeros_pasos.crear_proyecto'));
    }
}
