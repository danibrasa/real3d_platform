<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Una promotora tiene que poder montar su parte sola.
 *
 * El reparto acordado: ella gestiona lo comercial -sus proyectos, sus viviendas,
 * sus precios, sus compradores- y el equipo monta el 3D. Hasta ahora no podia
 * hacer NADA de lo primero: los permisos solo admitian superadmin y gestor, asi
 * que se registraba, pagaba y llegaba a un panel donde no podia crear nada.
 *
 * Estos tests estan escritos en positivo a proposito. Los que habia comprobaban
 * solo que una promotora ajena NO entra, y eso pasa en verde igual de bien
 * cuando el producto esta cerrado para todo el mundo, que era el caso. Un
 * permiso necesita las dos mitades: quien no debe no entra, y quien debe si.
 */
class PromotoraAutonomaTest extends TestCase
{
    use RefreshDatabase;

    private User $promotora;

    private CompanyProfile $perfil;

    protected function setUp(): void
    {
        parent::setUp();

        $this->promotora = User::factory()->create(['role' => 'inmobiliaria']);
        $this->perfil = CompanyProfile::create([
            'user_id' => $this->promotora->id,
            'company_name' => 'Promotora Autonoma',
            'slug' => 'promotora-autonoma',
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL,
            'max_projects' => 5,
        ]);
    }

    private function proyectoSuyo(string $nombre = 'Residencial Propio'): Project
    {
        $p = Project::create([
            'name' => $nombre,
            'slug' => str($nombre)->slug()->toString(),
            'status' => 'draft',
            'created_by' => $this->promotora->id,
        ]);
        $this->promotora->assignedProjects()->attach($p->id);

        return $p;
    }

    // --- Lo comercial: suyo ------------------------------------------------

    public function test_puede_crear_su_primer_proyecto(): void
    {
        $this->actingAs($this->promotora)
            ->get(route('admin.projects.create'))
            ->assertOk();

        $this->actingAs($this->promotora)
            ->post(route('admin.projects.store'), [
                'name' => 'Residencial Las Palmas',
                'description' => 'Obra nueva en Bavaro.',
                'location' => 'Punta Cana',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('projects', ['name' => 'Residencial Las Palmas']);
    }

    public function test_el_proyecto_que_crea_le_queda_asignado(): void
    {
        // Sin la asignacion lo crearia y lo veria desaparecer: el acceso se
        // resuelve por la tabla de asignaciones, no por created_by.
        $this->actingAs($this->promotora)
            ->post(route('admin.projects.store'), ['name' => 'Residencial Visible']);

        $proyecto = Project::where('name', 'Residencial Visible')->firstOrFail();

        $this->assertTrue($this->promotora->fresh()->canAccessProject($proyecto));
        $this->assertSame(1, $this->promotora->fresh()->accessibleProjects()->count());
    }

    public function test_puede_importar_sus_viviendas_desde_un_excel(): void
    {
        // Esta es la funcion que se construyo para ella y que no podia usar.
        $proyecto = $this->proyectoSuyo();

        $this->actingAs($this->promotora)
            ->get(route('admin.projects.units.import.create', $proyecto))
            ->assertOk();
    }

    public function test_puede_anadir_una_vivienda_a_mano(): void
    {
        $proyecto = $this->proyectoSuyo();

        $this->actingAs($this->promotora)
            ->get(route('admin.projects.units.create', $proyecto))
            ->assertOk();
    }

    public function test_puede_gestionar_las_tipologias_de_su_proyecto(): void
    {
        $proyecto = $this->proyectoSuyo();

        $this->actingAs($this->promotora)
            ->get(route('admin.projects.typologies.create', $proyecto))
            ->assertOk();
    }

    // --- El 3D: del equipo -------------------------------------------------

    public function test_no_puede_tocar_los_ajustes_del_visor(): void
    {
        // Encuadre de camara, rotacion y escala del modelo: panel de artista 3D.
        $this->assertFalse(
            Gate::forUser($this->promotora)
                ->allows('edit-viewer-settings')
        );
    }

    public function test_no_puede_subir_el_modelo_3d(): void
    {
        $this->assertFalse(
            Gate::forUser($this->promotora)
                ->allows('upload-files')
        );
    }

    // --- Y sigue sin poder tocar lo ajeno ---------------------------------

    public function test_no_puede_importar_en_el_proyecto_de_otra(): void
    {
        // Abrir el permiso no puede haber abierto la puerta de al lado.
        $otra = User::factory()->create(['role' => 'inmobiliaria']);
        CompanyProfile::create([
            'user_id' => $otra->id,
            'company_name' => 'Otra Promotora',
            'slug' => 'otra-promotora',
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL,
            'max_projects' => 5,
        ]);
        $ajeno = Project::create([
            'name' => 'Proyecto Ajeno', 'slug' => 'proyecto-ajeno',
            'status' => 'draft', 'created_by' => $otra->id,
        ]);
        $otra->assignedProjects()->attach($ajeno->id);

        $this->actingAs($this->promotora)
            ->get(route('admin.projects.units.import.create', $ajeno))
            ->assertForbidden();

        $this->actingAs($this->promotora)
            ->get(route('admin.projects.units.create', $ajeno))
            ->assertForbidden();
    }

    // --- El tope del plan se explica, no se estrella -----------------------

    public function test_al_llegar_al_tope_del_plan_se_le_explica(): void
    {
        $this->perfil->update(['max_projects' => 1]);
        $this->proyectoSuyo();

        $this->actingAs($this->promotora)
            ->get(route('admin.projects.create'))
            ->assertRedirect(route('admin.projects.index'))
            ->assertSessionHas('error');
    }
}
