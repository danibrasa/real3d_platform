<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Que el plan que acaba en la base de datos sea el que se ha pagado.
 *
 * El mismo agujero que se cerro en SubscriptionController::success seguia
 * abierto en el alta: onboarding/complete leia el plan de ?plan= y aplicaba
 * sus limites sin preguntarle nada a Stripe. Estando registrado bastaba con
 * visitar la URL a mano.
 *
 * Y habia un segundo camino, mas discreto: si el identificador de precio de
 * Stripe no estaba configurado, selectPlan activaba el plan pedido "directamente"
 * en vez de fallar. Un despliegue sin las variables de Stripe regalaba Enterprise
 * a quien lo pidiera, y no lo decia.
 */
class AltaDePlanTest extends TestCase
{
    use RefreshDatabase;

    private function promotoraRecienRegistrada(): User
    {
        $user = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);

        $limites = CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_STARTER];

        CompanyProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Promotora Recien Llegada',
            'slug' => 'promotora-recien-llegada',
            'plan_tier' => CompanyProfile::PLAN_STARTER,
            'max_projects' => $limites['max_projects'],
            'max_storage_bytes' => $limites['max_storage_bytes'],
        ]);

        return $user->fresh();
    }

    public function test_no_se_puede_pedir_un_plan_por_la_url(): void
    {
        $user = $this->promotoraRecienRegistrada();

        $this->actingAs($user)->get(route('onboarding.complete').'?plan=enterprise');

        $perfil = $user->companyProfile->fresh();

        $this->assertSame(CompanyProfile::PLAN_STARTER, $perfil->plan_tier,
            'visitar la URL de vuelta con ?plan=enterprise ha dado Enterprise sin pagar');
        $this->assertSame(
            CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_STARTER]['max_projects'],
            $perfil->max_projects);
    }

    public function test_sin_stripe_configurado_no_se_regala_el_plan(): void
    {
        config(['stripe.plans.professional.price_monthly_id' => null]);
        config(['stripe.plans.professional.price_yearly_id' => null]);

        $user = $this->promotoraRecienRegistrada();

        $this->actingAs($user)->post(route('onboarding.plan.select'), [
            'plan' => 'professional',
            'interval' => 'monthly',
        ]);

        $this->assertSame(CompanyProfile::PLAN_STARTER,
            $user->companyProfile->fresh()->plan_tier,
            'sin precio de Stripe se ha activado Professional igualmente');
    }

    public function test_el_plan_de_entrada_si_se_puede_elegir_porque_es_gratis(): void
    {
        $user = $this->promotoraRecienRegistrada();

        $respuesta = $this->actingAs($user)->post(route('onboarding.plan.select'), [
            'plan' => 'starter',
            'interval' => 'monthly',
        ]);

        $respuesta->assertRedirect(route('admin.dashboard'));
        $this->assertSame(CompanyProfile::PLAN_STARTER,
            $user->companyProfile->fresh()->plan_tier);
    }
}
