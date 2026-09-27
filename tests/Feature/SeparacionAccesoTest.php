<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Cada promotora ve lo suyo y nada mas.
 *
 * Es un SaaS con varias inmobiliarias dentro: que una vea los proyectos, las
 * ventas o los compradores de otra seria el fallo mas grave posible aqui. No
 * basta con que el listado filtre; hay que comprobar que tampoco se entra
 * escribiendo la URL a mano.
 */
class SeparacionAccesoTest extends TestCase
{
    use RefreshDatabase;

    private User $promotoraA;

    private User $promotoraB;

    private User $adminSaas;

    private Project $proyectoA;

    private Project $proyectoB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminSaas = User::factory()->create(['role' => 'superadmin']);
        $this->promotoraA = $this->promotora('Inmobiliaria A');
        $this->promotoraB = $this->promotora('Inmobiliaria B');

        $this->proyectoA = $this->proyecto('Proyecto de A', 'proyecto-a', $this->promotoraA);
        $this->proyectoB = $this->proyecto('Proyecto de B', 'proyecto-b', $this->promotoraB);
    }

    /**
     * Una promotora con su ficha de empresa.
     *
     * Sin companyProfile, el middleware de onboarding la manda a completar el
     * alta y no llega a tocar ninguna pantalla del panel: el test daria 302 en
     * vez de 403 y pareceria que la separacion falla cuando no es asi.
     */
    private function promotora(string $nombre): User
    {
        $u = User::factory()->create(['role' => 'inmobiliaria', 'name' => $nombre]);

        CompanyProfile::create([
            'user_id' => $u->id,
            'company_name' => $nombre,
            'slug' => Str::slug($nombre),
            'plan_tier' => 'professional',
            'max_projects' => 5,
        ]);

        return $u;
    }

    private function proyecto(string $nombre, string $slug, User $dueno): Project
    {
        $p = Project::create([
            'name' => $nombre, 'slug' => $slug, 'status' => 'draft',
            'created_by' => $dueno->id,
        ]);
        $dueno->assignedProjects()->attach($p->id);

        return $p;
    }

    // --- El listado --------------------------------------------------------

    public function test_cada_promotora_solo_ve_sus_proyectos_en_el_listado(): void
    {
        $this->actingAs($this->promotoraA)
            ->get(route('admin.projects.index'))
            ->assertOk()
            ->assertSee('Proyecto de A')
            ->assertDontSee('Proyecto de B');
    }

    public function test_el_admin_del_saas_ve_todos(): void
    {
        $this->actingAs($this->adminSaas)
            ->get(route('admin.projects.index'))
            ->assertOk()
            ->assertSee('Proyecto de A')
            ->assertSee('Proyecto de B');
    }

    // --- Escribiendo la URL a mano ----------------------------------------

    public function test_no_puede_abrir_el_proyecto_de_otra(): void
    {
        $this->actingAs($this->promotoraA)
            ->get(route('admin.projects.edit', $this->proyectoB))
            ->assertForbidden();
    }

    public function test_no_puede_ver_las_viviendas_de_otra(): void
    {
        $this->actingAs($this->promotoraA)
            ->get(route('admin.projects.units.index', $this->proyectoB))
            ->assertForbidden();
    }

    public function test_no_puede_tocar_los_ajustes_de_otra(): void
    {
        $this->actingAs($this->promotoraA)
            ->put(route('admin.projects.update', $this->proyectoB), [
                'name' => 'Secuestrado', 'status' => 'draft',
            ])
            ->assertForbidden();

        $this->assertSame('Proyecto de B', $this->proyectoB->fresh()->name);
    }

    // --- Los agentes heredan el acceso de su agencia -----------------------

    public function test_un_agente_ve_los_proyectos_de_su_promotora(): void
    {
        $agente = User::factory()->create([
            'role' => 'agente',
            'agency_id' => $this->promotoraA->id,
        ]);

        $this->actingAs($agente)
            ->get(route('admin.projects.index'))
            ->assertOk()
            ->assertSee('Proyecto de A')
            ->assertDontSee('Proyecto de B');
    }

    public function test_un_agente_sin_agencia_no_ve_nada(): void
    {
        $huerfano = User::factory()->create(['role' => 'agente', 'agency_id' => null]);

        $this->assertSame(0, $huerfano->accessibleProjects()->count());
    }

    // --- Quien no es de la casa -------------------------------------------

    public function test_un_usuario_normal_no_entra_al_panel(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get(route('admin.projects.index'))
            ->assertForbidden();
    }
}
