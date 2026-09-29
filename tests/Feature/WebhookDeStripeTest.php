<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Lo que Stripe cuenta por el webhook llega al plan de la promotora.
 *
 * El webhook es quien concede de verdad: llega firmado por Stripe y es la
 * unica fuente que no se puede falsear desde el navegador. Se atendian dos
 * avisos, alta y baja, y no el tercero, el de cambio: una promotora que
 * subiera de plan desde el portal de Stripe pagaba el caro y seguia con los
 * limites del barato, y una que bajara al reves. Y no habia ningun test de
 * los tres.
 *
 * Se prueba por la ruta, con el cuerpo que manda Stripe, y no llamando a los
 * metodos: asi cuenta tambien que el tipo del evento llegue al metodo que es.
 */
class WebhookDeStripeTest extends TestCase
{
    use RefreshDatabase;

    private User $promotora;

    private CompanyProfile $perfil;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        config([
            'stripe.plans.professional.price_monthly_id' => 'price_pro_mensual',
            'stripe.plans.enterprise.price_monthly_id' => 'price_ent_mensual',
        ]);

        $this->promotora = User::factory()->create([
            'role' => 'inmobiliaria',
            'stripe_id' => 'cus_de_prueba',
        ]);

        $this->perfil = CompanyProfile::create([
            'user_id' => $this->promotora->id,
            'company_name' => 'Promotora de prueba',
            'slug' => 'promotora-de-prueba',
            'plan_tier' => CompanyProfile::PLAN_STARTER,
            'max_projects' => CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_STARTER]['max_projects'],
        ]);
    }

    /** Un evento de suscripcion como lo manda Stripe, con lo que Cashier necesita leer. */
    private function evento(string $tipo, string $precio): array
    {
        return [
            'id' => 'evt_de_prueba',
            'type' => $tipo,
            'data' => [
                'object' => [
                    'id' => 'sub_de_prueba',
                    'customer' => 'cus_de_prueba',
                    'status' => 'active',
                    'cancel_at_period_end' => false,
                    'items' => [
                        'data' => [
                            ['id' => 'si_de_prueba', 'price' => ['id' => $precio, 'product' => 'prod_de_prueba'], 'quantity' => 1],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function llega(string $tipo, string $precio): void
    {
        $this->postJson(route('stripe.webhook'), $this->evento($tipo, $precio))->assertOk();
        $this->perfil->refresh();
    }

    public function test_el_alta_concede_el_plan_del_precio_pagado(): void
    {
        $this->llega('customer.subscription.created', 'price_pro_mensual');

        $this->assertSame(CompanyProfile::PLAN_PROFESSIONAL, $this->perfil->plan_tier);
        $this->assertSame(CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_PROFESSIONAL]['max_projects'], $this->perfil->max_projects);
    }

    public function test_un_cambio_de_plan_desde_stripe_llega_a_la_promotora(): void
    {
        // Lo que faltaba. Subir desde el portal de Stripe se cobraba y no se
        // concedia; bajar se concedia de mas.
        $this->llega('customer.subscription.created', 'price_pro_mensual');
        $this->llega('customer.subscription.updated', 'price_ent_mensual');

        $this->assertSame(CompanyProfile::PLAN_ENTERPRISE, $this->perfil->plan_tier);
        $this->assertSame(CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_ENTERPRISE]['max_projects'], $this->perfil->max_projects);

        $this->llega('customer.subscription.updated', 'price_pro_mensual');

        $this->assertSame(CompanyProfile::PLAN_PROFESSIONAL, $this->perfil->plan_tier);
        $this->assertSame(CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_PROFESSIONAL]['max_projects'], $this->perfil->max_projects);
    }

    public function test_un_precio_que_no_es_nuestro_no_cambia_nada(): void
    {
        // Un precio desconocido es senal de que algo no cuadra, no un plan
        // basico: se deja como estaba y no se regala ni se quita.
        $this->llega('customer.subscription.created', 'price_pro_mensual');
        $this->llega('customer.subscription.updated', 'price_de_otra_cuenta');

        $this->assertSame(CompanyProfile::PLAN_PROFESSIONAL, $this->perfil->plan_tier);
    }

    public function test_la_baja_devuelve_al_plan_gratuito(): void
    {
        $this->llega('customer.subscription.created', 'price_ent_mensual');
        $this->llega('customer.subscription.deleted', 'price_ent_mensual');

        $this->assertSame(CompanyProfile::PLAN_STARTER, $this->perfil->plan_tier);
        $this->assertSame(CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_STARTER]['max_projects'], $this->perfil->max_projects);
    }

    public function test_un_cliente_que_no_es_nuestro_no_rompe_nada(): void
    {
        $evento = $this->evento('customer.subscription.updated', 'price_pro_mensual');
        $evento['data']['object']['customer'] = 'cus_desconocido';

        $this->postJson(route('stripe.webhook'), $evento)->assertOk();

        $this->assertSame(CompanyProfile::PLAN_STARTER, $this->perfil->fresh()->plan_tier);
    }
}
