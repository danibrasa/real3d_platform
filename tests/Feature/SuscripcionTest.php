<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\User;
use App\Support\Facturacion\PlanDeStripe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El plan no se regala por lo que ponga en la barra de direcciones.
 *
 * La pagina de vuelta del pago leia `?plan=` y aplicaba esos limites sin
 * preguntarle nada a Stripe, asi que cualquiera con una cuenta podia darse el
 * plan mas caro visitando una URL. Estos tests dejan cerrada esa puerta.
 */
class SuscripcionTest extends TestCase
{
    use RefreshDatabase;

    private User $promotora;

    private CompanyProfile $perfil;

    protected function setUp(): void
    {
        parent::setUp();

        $this->promotora = User::factory()->create(['role' => 'inmobiliaria']);

        // Sin ficha de empresa, el middleware de alta la manda a completarla y
        // no llegaria a la pagina que se quiere probar.
        $this->perfil = CompanyProfile::create([
            'user_id' => $this->promotora->id,
            'company_name' => 'Promotora de prueba',
            'slug' => 'promotora-de-prueba',
            'plan_tier' => CompanyProfile::PLAN_STARTER,
            'max_projects' => 1,
        ]);
    }

    public function test_no_se_puede_subir_de_plan_escribiendo_la_url(): void
    {
        $this->actingAs($this->promotora)
            ->get(route('admin.subscription.success', ['plan' => 'enterprise']));

        $this->perfil->refresh();

        $this->assertSame(CompanyProfile::PLAN_STARTER, $this->perfil->plan_tier);
        $this->assertSame(1, $this->perfil->max_projects);
    }

    public function test_una_sesion_de_pago_inventada_no_concede_nada(): void
    {
        // Sin cliente en Stripe no hay sesion que pueda ser suya, asi que no se
        // sale siquiera a preguntar.
        $this->assertNull($this->promotora->stripe_id);

        $this->actingAs($this->promotora)
            ->get(route('admin.subscription.success', ['session_id' => 'cs_test_inventada']));

        $this->perfil->refresh();

        $this->assertSame(CompanyProfile::PLAN_STARTER, $this->perfil->plan_tier);
        $this->assertSame(1, $this->perfil->max_projects);
    }

    public function test_la_traduccion_de_precio_a_plan_no_se_inventa_nada(): void
    {
        // Un precio que no es de los nuestros devuelve null, no "starter": un
        // precio desconocido es una señal de que algo no cuadra, no un plan.
        $this->assertNull(PlanDeStripe::desdePrecio('price_de_otro'));
        $this->assertNull(PlanDeStripe::desdePrecio(null));
    }
}
