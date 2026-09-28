<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lo que una promotora lee justo antes de decidir.
 *
 * Habia dos cosas mal dichas en esa pantalla, y las dos empujaban a irse:
 *
 *  - El plan de entrada anunciaba 49 USD al mes y no se cobraban. Quien lo
 *    elegia descubria que era gratis; quien no llegaba a elegirlo se iba
 *    creyendo que lo mas barato costaba 49.
 *  - La prueba de 14 dias solo se contaba en la portada, que es donde
 *    todavia no hay que decidir nada. Aqui, que es donde se decide, el plan
 *    de pago parecia un cobro inmediato.
 *
 * Se comprueba el texto que sale, no que la vista responda 200: una pantalla
 * que carga bien y dice un precio que no existe funciona perfectamente.
 */
class PantallaDePlanesTest extends TestCase
{
    use RefreshDatabase;

    private function recienRegistrada(): User
    {
        $user = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);

        CompanyProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Promotora Bahia',
            'slug' => 'promotora-bahia-planes',
            'plan_tier' => CompanyProfile::PLAN_STARTER,
            'max_projects' => 1,
        ]);

        return $user->fresh();
    }

    public function test_el_plan_de_entrada_se_anuncia_gratis_y_no_a_49(): void
    {
        $respuesta = $this->actingAs($this->recienRegistrada())->get(route('onboarding.plan'));

        $respuesta->assertOk();
        $respuesta->assertSee(__('billing.free'));
        $respuesta->assertDontSee('$49');
        $respuesta->assertDontSee('$470');
    }

    public function test_la_prueba_gratuita_se_ve_al_elegir_plan(): void
    {
        $respuesta = $this->actingAs($this->recienRegistrada())->get(route('onboarding.plan'));

        $respuesta->assertSee(
            __('billing.trial_days_free', ['dias' => config('stripe.trial_days')])
        );
    }

    public function test_los_planes_de_pago_siguen_mostrando_su_precio(): void
    {
        // Que lo de arriba no se lleve por delante lo que si hay que cobrar.
        $respuesta = $this->actingAs($this->recienRegistrada())->get(route('onboarding.plan'));

        $respuesta->assertSee('$'.config('stripe.plans.professional.price_monthly'), false);
        $respuesta->assertSee('$'.config('stripe.plans.enterprise.price_monthly'), false);
    }

    public function test_el_plan_gratis_no_tiene_precio_en_stripe(): void
    {
        // Si alguien le pusiera un precio, selectPlan lo mandaria a pagar por
        // lo que la pantalla anuncia como gratis.
        $this->assertNull(config('stripe.plans.starter.price_monthly_id'));
        $this->assertNull(config('stripe.plans.starter.price_yearly_id'));
        $this->assertSame(0, config('stripe.plans.starter.price_monthly'));
        $this->assertTrue(config('stripe.plans.starter.gratis'));
    }
}
