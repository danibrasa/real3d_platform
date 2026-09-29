<?php

namespace Tests\Feature;

use App\Models\ChatbotConversation;
use App\Models\CompanyProfile;
use App\Models\Inquiry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use Tests\TestCase;

/**
 * Llevarse los datos y borrar la cuenta, sin escribirnos.
 *
 * Es lo que promete la politica de privacidad y lo que exigen el RGPD y la
 * Ley 172-13. Y borrar la cuenta tiene una trampa: con una suscripcion de
 * pago activa, borrar al usuario de nuestra base no borra nada en Stripe, y
 * el cobro seguiria cada mes a una cuenta que ya no existe.
 */
class DatosPersonalesTest extends TestCase
{
    use RefreshDatabase;

    private User $ana;

    private User $otra;

    private Project $suyo;

    private Project $ajeno;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->ana, $this->suyo] = $this->promotoraConProyecto('ana', 'Residencial Bahia');
        [$this->otra, $this->ajeno] = $this->promotoraConProyecto('otra', 'Torre Ajena');
    }

    private function promotoraConProyecto(string $nombre, string $proyecto): array
    {
        $user = User::factory()->create(['role' => User::ROLE_INMOBILIARIA, 'email' => $nombre.'@ejemplo.invalid']);
        CompanyProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Promotora de '.$nombre,
            'slug' => 'promotora-de-'.$nombre,
            'plan_tier' => CompanyProfile::PLAN_STARTER,
            'max_projects' => 5,
        ]);
        $p = Project::create([
            'name' => $proyecto,
            'slug' => str($proyecto)->slug()->toString(),
            'status' => 'public',
            'created_by' => $user->id,
        ]);
        $user->assignedProjects()->attach($p->id);

        Inquiry::create([
            'project_id' => $p->id,
            'name' => 'Comprador de '.$proyecto,
            'email' => 'comprador.'.$nombre.'@ejemplo.invalid',
            'message' => 'Me interesa.',
            'read' => false,
        ]);
        ChatbotConversation::create([
            'project_id' => $p->id,
            'session_id' => 'sesion-'.$nombre,
            'locale' => 'es',
        ]);

        return [$user, $p];
    }

    public function test_se_lleva_lo_suyo_y_nada_de_otra_promotora(): void
    {
        $respuesta = $this->actingAs($this->ana)->get(route('profile.exportar'));

        $respuesta->assertOk();
        $this->assertStringContainsString('application/json', $respuesta->headers->get('content-type'));
        $this->assertStringContainsString('attachment', $respuesta->headers->get('content-disposition'));

        $datos = json_decode($respuesta->streamedContent(), true);

        $this->assertSame('ana@ejemplo.invalid', $datos['cuenta']['correo']);
        $this->assertSame('Promotora de ana', $datos['empresa']['company_name']);
        $this->assertSame('Residencial Bahia', $datos['proyectos'][0]['proyecto']['name']);
        $this->assertSame('comprador.ana@ejemplo.invalid', $datos['consultas'][0]['email']);
        $this->assertSame('sesion-ana', $datos['conversaciones'][0]['session_id']);

        $texto = json_encode($datos);
        $this->assertStringNotContainsString('Torre Ajena', $texto);
        $this->assertStringNotContainsString('comprador.otra', $texto);
        $this->assertStringNotContainsString('sesion-otra', $texto);
    }

    public function test_sin_sesion_no_hay_nada_que_llevarse(): void
    {
        $this->get(route('profile.exportar'))->assertRedirect(route('login'));
    }

    public function test_borrar_la_cuenta_se_lleva_lo_suyo_y_deja_lo_ajeno(): void
    {
        $this->actingAs($this->ana)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $this->ana->id]);
        $this->assertDatabaseMissing('company_profiles', ['user_id' => $this->ana->id]);
        $this->assertDatabaseMissing('projects', ['id' => $this->suyo->id]);
        $this->assertDatabaseMissing('inquiries', ['project_id' => $this->suyo->id]);
        $this->assertDatabaseMissing('chatbot_conversations', ['project_id' => $this->suyo->id]);

        $this->assertDatabaseHas('projects', ['id' => $this->ajeno->id]);
        $this->assertDatabaseHas('inquiries', ['project_id' => $this->ajeno->id]);
    }

    public function test_con_una_suscripcion_de_pago_activa_no_se_borra(): void
    {
        // Borrar al usuario de nuestra base no borra nada en Stripe: el cobro
        // seguiria cada mes a una cuenta que ya no existe. Primero se cancela
        // desde facturacion, y entonces si.
        Subscription::create([
            'user_id' => $this->ana->id,
            'type' => 'default',
            'stripe_id' => 'sub_de_prueba',
            'stripe_status' => 'active',
            'stripe_price' => 'price_x',
            'quantity' => 1,
        ]);

        $this->actingAs($this->ana)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('suscripcion', null, 'userDeletion');

        $this->assertDatabaseHas('users', ['id' => $this->ana->id]);
        $this->assertDatabaseHas('projects', ['id' => $this->suyo->id]);
    }

    public function test_con_un_cobro_en_reintento_tampoco(): void
    {
        // past_due: Stripe sigue intentando cobrar. Cashier no la llama activa
        // y aun asi no se puede borrar la cuenta debajo de ese cobro.
        Subscription::create([
            'user_id' => $this->ana->id,
            'type' => 'default',
            'stripe_id' => 'sub_de_prueba',
            'stripe_status' => 'past_due',
            'stripe_price' => 'price_x',
            'quantity' => 1,
        ]);

        $this->actingAs($this->ana)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertSessionHasErrors('suscripcion', null, 'userDeletion');

        $this->assertDatabaseHas('users', ['id' => $this->ana->id]);
    }

    public function test_la_exportacion_no_lleva_lo_que_apuntamos_nosotros(): void
    {
        $datos = json_decode($this->actingAs($this->ana)->get(route('profile.exportar'))->streamedContent(), true);

        $this->assertArrayNotHasKey('plan_tier', $datos['empresa']);
        $this->assertArrayNotHasKey('storage_used_bytes', $datos['empresa']);
        $this->assertArrayNotHasKey('is_verified', $datos['empresa']);
    }

    public function test_con_la_suscripcion_ya_cancelada_si_se_borra(): void
    {
        Subscription::create([
            'user_id' => $this->ana->id,
            'type' => 'default',
            'stripe_id' => 'sub_de_prueba',
            'stripe_status' => 'canceled',
            'stripe_price' => 'price_x',
            'quantity' => 1,
            'ends_at' => now()->subDay(),
        ]);

        $this->actingAs($this->ana)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $this->ana->id]);
    }

    public function test_el_panel_ofrece_las_dos_cosas(): void
    {
        $this->actingAs($this->ana)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee(route('profile.exportar'), false)
            ->assertSee(route('profile.destroy'), false);
    }
}
