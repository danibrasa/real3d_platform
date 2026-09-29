<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * La cola de trabajo del equipo: en que estado va cada visor, quien lo
 * lleva, para cuando, cuantas horas, y cuales llevan demasiado parados.
 *
 * "Pedido" y "montado" eran los unicos estados y entre medias no se sabia
 * nada. Cuanto cuesta montar un visor es lo que decide si el negocio da
 * dinero, y no lo apuntaba nadie.
 */
class ColaDelEquipoTest extends TestCase
{
    use RefreshDatabase;

    private User $promotora;

    private User $gestora;

    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->promotora = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);
        CompanyProfile::create([
            'user_id' => $this->promotora->id, 'company_name' => 'Promotora Bahia', 'slug' => 'promotora-bahia',
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL, 'max_projects' => 5,
        ]);
        $this->gestora = User::factory()->create(['role' => User::ROLE_GESTOR, 'name' => 'Gestora Uno']);

        $this->proyecto = Project::create([
            'name' => 'Residencial Bahia', 'slug' => 'residencial-bahia', 'status' => 'draft', 'created_by' => $this->promotora->id,
        ]);
        $this->promotora->assignedProjects()->attach($this->proyecto->id);

        $this->actingAs($this->promotora)->post(route('admin.projects.visor.pedir', $this->proyecto));
        $this->proyecto->refresh();
    }

    public function test_pedirlo_lo_deja_en_la_cola_como_pedido(): void
    {
        $this->assertSame(Project::VISOR_PEDIDO, $this->proyecto->visor_estado);
        $this->assertNotNull($this->proyecto->visor_estado_en);
    }

    public function test_el_equipo_lo_coge_le_pone_fecha_y_horas(): void
    {
        $this->actingAs($this->gestora)
            ->patch(route('admin.projects.visor.actualizar', $this->proyecto), [
                'estado' => 'en_preparacion',
                'asignado_a' => $this->gestora->id,
                'objetivo' => now()->addDays(4)->toDateString(),
                'horas' => 2.5,
            ])
            ->assertRedirect();

        $p = $this->proyecto->fresh();
        $this->assertSame('en_preparacion', $p->visor_estado);
        $this->assertSame($this->gestora->id, $p->visor_asignado_a);
        $this->assertSame(now()->addDays(4)->toDateString(), $p->visor_objetivo->toDateString());
        $this->assertSame(2.5, (float) $p->visor_horas);
    }

    public function test_cambiar_de_estado_reinicia_el_reloj_y_dejar_el_mismo_no(): void
    {
        $this->proyecto->forceFill(['visor_estado_en' => now()->subDays(3)])->save();

        $this->actingAs($this->gestora)->patch(route('admin.projects.visor.actualizar', $this->proyecto), ['estado' => 'pedido', 'horas' => 1]);
        $this->assertEqualsWithDelta(3, $this->proyecto->fresh()->visor_estado_en->diffInDays(now()), 0.01, 'sin cambiar de estado no debia moverse el reloj');

        $this->actingAs($this->gestora)->patch(route('admin.projects.visor.actualizar', $this->proyecto), ['estado' => 'para_revisar']);
        $this->assertLessThan(1, $this->proyecto->fresh()->visor_estado_en->diffInMinutes(now()));
    }

    public function test_cambiar_solo_el_estado_no_borra_lo_demas(): void
    {
        $this->proyecto->forceFill(['visor_asignado_a' => $this->gestora->id, 'visor_objetivo' => now()->addDays(2), 'visor_horas' => 4])->save();

        $this->actingAs($this->gestora)->patch(route('admin.projects.visor.actualizar', $this->proyecto), ['estado' => 'para_revisar']);

        $p = $this->proyecto->fresh();
        $this->assertSame('para_revisar', $p->visor_estado);
        $this->assertSame($this->gestora->id, $p->visor_asignado_a, 'un cambio de estado borro quien lo lleva');
        $this->assertNotNull($p->visor_objetivo);
        $this->assertSame(4.0, (float) $p->visor_horas);

        // Y mandar el campo vacio si lo quita, que es la otra mitad.
        $this->actingAs($this->gestora)->patch(route('admin.projects.visor.actualizar', $this->proyecto), ['estado' => 'para_revisar', 'asignado_a' => '']);
        $this->assertNull($this->proyecto->fresh()->visor_asignado_a);
    }

    public function test_un_pedido_sin_estado_tambien_cuenta_como_atascado(): void
    {
        // Dato viejo o inconsistente: pedido pero sin estado. NULL != 'montado'
        // no es verdadero en SQL y se quedaba fuera del aviso.
        $this->proyecto->forceFill(['visor_estado' => null, 'visor_estado_en' => null, 'viewer_requested_at' => now()->subDays(9)])->save();

        $this->artisan('visores:atascados', ['--dias' => 5])
            ->expectsOutputToContain('Residencial Bahia')
            ->assertFailed();
    }

    public function test_solo_se_asigna_a_gente_del_equipo(): void
    {
        $this->actingAs($this->gestora)
            ->patch(route('admin.projects.visor.actualizar', $this->proyecto), ['estado' => 'en_preparacion', 'asignado_a' => $this->promotora->id])
            ->assertSessionHasErrors('asignado_a');

        $this->assertNull($this->proyecto->fresh()->visor_asignado_a);
    }

    public function test_la_promotora_no_toca_la_cola(): void
    {
        $this->actingAs($this->promotora)
            ->patch(route('admin.projects.visor.actualizar', $this->proyecto), ['estado' => 'montado'])
            ->assertForbidden();

        $this->assertSame(Project::VISOR_PEDIDO, $this->proyecto->fresh()->visor_estado);
    }

    public function test_montado_solo_se_llega_dandolo_por_montado(): void
    {
        // Con el boton de siempre, que comprueba que hay algo montado.
        $this->actingAs($this->gestora)
            ->patch(route('admin.projects.visor.actualizar', $this->proyecto), ['estado' => 'montado'])
            ->assertSessionHasErrors('estado');
    }

    public function test_la_cola_enseña_estado_quien_y_dias_parado(): void
    {
        $this->proyecto->forceFill([
            'visor_estado' => 'en_preparacion', 'visor_asignado_a' => $this->gestora->id,
            'visor_estado_en' => now()->subDays(6), 'visor_horas' => 3,
        ])->save();

        $this->actingAs($this->gestora)->get(route('admin.visores.pendientes'))
            ->assertOk()
            ->assertSee(__('visor.estado_en_preparacion'))
            ->assertSee('Gestora Uno')
            ->assertSee(__('visor.parado_dias', ['dias' => 6]));
    }

    public function test_la_promotora_ve_en_que_va_lo_suyo(): void
    {
        $this->proyecto->forceFill(['visor_estado' => 'en_preparacion', 'visor_objetivo' => now()->addDays(3)])->save();

        $this->actingAs($this->promotora)->get(route('admin.projects.edit', $this->proyecto))
            ->assertOk()
            ->assertSee(__('visor.estado_en_preparacion'))
            ->assertSee(__('visor.previsto_para', ['fecha' => now()->addDays(3)->translatedFormat('j \d\e F')]));
    }

    public function test_los_visores_parados_saltan_en_la_nocturna(): void
    {
        $this->artisan('visores:atascados', ['--dias' => 5])->assertSuccessful();

        $this->proyecto->forceFill(['visor_estado_en' => now()->subDays(6)])->save();
        $this->artisan('visores:atascados', ['--dias' => 5])
            ->expectsOutputToContain('Residencial Bahia')
            ->assertFailed();

        // Montado ya no cuenta, por viejo que sea el reloj.
        $this->proyecto->forceFill(['visor_estado' => Project::VISOR_MONTADO])->save();
        $this->artisan('visores:atascados', ['--dias' => 5])->assertSuccessful();
    }
}
