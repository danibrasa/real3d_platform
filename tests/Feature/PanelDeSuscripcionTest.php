<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use Tests\TestCase;

/**
 * Lo que el panel dice sobre la prueba, que son dos estados y no uno.
 *
 * La suscripcion nace con una prueba larga que no es una oferta: es el plazo
 * que nos damos para montar el visor. Mientras eso dura, trial_ends_at lleva
 * una fecha a sesenta dias vista que no es la prueba de nadie. Enseniarla
 * seria prometer dos meses gratis por un descuido de implementacion.
 *
 * Asi que mientras no hay visor no se da fecha, se dice de que depende. En
 * cuanto lo hay, la fecha es la de verdad.
 */
class PanelDeSuscripcionTest extends TestCase
{
    use RefreshDatabase;

    private function promotora(?string $pruebaDesde, ?int $diasDePrueba): User
    {
        $user = User::factory()->create([
            'role' => User::ROLE_INMOBILIARIA,
            'stripe_id' => 'cus_de_prueba',
        ]);

        CompanyProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Promotora Bahia',
            'slug' => 'promotora-bahia-panel',
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL,
            'max_projects' => 5,
            'prueba_desde' => $pruebaDesde,
        ]);

        // Una suscripcion en la tabla local de Cashier: onTrial() y
        // trial_ends_at se leen de aqui, sin salir a la red.
        Subscription::create([
            'user_id' => $user->id,
            'type' => 'default',
            'stripe_id' => 'sub_de_prueba',
            'stripe_status' => 'trialing',
            'stripe_price' => 'price_x',
            'quantity' => 1,
            'trial_ends_at' => $diasDePrueba ? now()->addDays($diasDePrueba) : null,
        ]);

        return $user->fresh();
    }

    public function test_esperando_el_visor_no_se_da_una_fecha(): void
    {
        // Sesenta dias: el plazo de montaje, no una prueba de dos meses.
        $user = $this->promotora(pruebaDesde: null, diasDePrueba: 60);

        $respuesta = $this->actingAs($user)->get(route('admin.subscription.index'));

        $respuesta->assertOk();
        $respuesta->assertSee(__('billing.trial_waiting'));
        $respuesta->assertDontSee(now()->addDays(60)->translatedFormat('j M Y'));
    }

    public function test_con_el_visor_montado_se_da_la_fecha_de_verdad(): void
    {
        $user = $this->promotora(
            pruebaDesde: now()->subDay()->toDateTimeString(),
            diasDePrueba: 13,
        );

        $respuesta = $this->actingAs($user)->get(route('admin.subscription.index'));

        $respuesta->assertOk();
        $respuesta->assertSee(now()->addDays(13)->translatedFormat('j M Y'));
        $respuesta->assertDontSee(__('billing.trial_waiting'));
    }

    public function test_sin_prueba_no_se_habla_de_pruebas(): void
    {
        $user = $this->promotora(pruebaDesde: null, diasDePrueba: null);

        $respuesta = $this->actingAs($user)->get(route('admin.subscription.index'));

        $respuesta->assertOk();
        $respuesta->assertDontSee(__('billing.trial_waiting'));
    }
}
