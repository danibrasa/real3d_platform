<?php

namespace Tests\Feature;

use App\Mail\InvitacionDeAgente;
use App\Models\CompanyProfile;
use App\Models\User;
use App\Support\Agentes\Invitacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Una promotora invita a su comercial sin tocar contraseñas, el comercial
 * entra por el enlace, y el tope de agentes del plan se enseña antes de
 * chocar con el.
 */
class InvitacionDeAgentesTest extends TestCase
{
    use RefreshDatabase;

    private User $promotora;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->promotora = $this->promotora('ana', CompanyProfile::PLAN_PROFESSIONAL);
    }

    private function promotora(string $nombre, string $plan): User
    {
        $user = User::factory()->create(['role' => User::ROLE_INMOBILIARIA, 'name' => ucfirst($nombre).' Promotora', 'email' => $nombre.'@ejemplo.invalid']);
        CompanyProfile::create(['user_id' => $user->id, 'company_name' => 'Promotora '.ucfirst($nombre), 'slug' => $nombre, 'plan_tier' => $plan, 'max_projects' => 5]);

        return $user;
    }

    private function invitar(string $correo = 'luis@ejemplo.invalid', ?User $quien = null)
    {
        return $this->actingAs($quien ?? $this->promotora)->post(route('admin.users.store'), [
            'name' => 'Luis Comercial', 'email' => $correo, 'role' => 'agente',
        ]);
    }

    public function test_la_promotora_invita_con_nombre_y_correo_y_el_agente_recibe_el_enlace(): void
    {
        $this->invitar()->assertRedirect(route('admin.users.index'))->assertSessionHas('success');

        $agente = User::where('email', 'luis@ejemplo.invalid')->first();
        $this->assertNotNull($agente);
        $this->assertSame(User::ROLE_AGENTE, $agente->role);
        $this->assertSame($this->promotora->id, $agente->agency_id);
        $this->assertTrue(Invitacion::pendiente($agente));

        Mail::assertQueued(InvitacionDeAgente::class, fn ($m) => $m->hasTo('luis@ejemplo.invalid') && $m->agente->is($agente));
        $correo = (new InvitacionDeAgente($agente, $this->promotora))->render();
        $this->assertStringContainsString(route('invitacion.mostrar', $agente->invitacion_token), $correo);
        $this->assertStringContainsString('Promotora Ana', $correo);

        // Sin aceptar no se entra: no hay contraseña que valga.
        auth()->logout();
        $this->post(route('login'), ['email' => 'luis@ejemplo.invalid', 'password' => 'password'])->assertSessionHasErrors('email');
    }

    public function test_el_agente_elige_contraseña_acepta_las_condiciones_y_queda_dentro(): void
    {
        $agente = Invitacion::invitar($this->promotora, 'Luis Comercial', 'luis@ejemplo.invalid');
        $token = $agente->invitacion_token;

        $this->get(route('invitacion.mostrar', $token))->assertOk()
            ->assertSee('Promotora Ana')->assertSee('luis@ejemplo.invalid')->assertSee('name="acepto"', false);

        $this->post(route('invitacion.aceptar', $token), [
            'password' => 'UnaClaveLarga2026!', 'password_confirmation' => 'UnaClaveLarga2026!', 'acepto' => '1',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($agente->fresh());
        $agente->refresh();
        $this->assertFalse(Invitacion::pendiente($agente));
        $this->assertNotNull($agente->invitacion_aceptada_en);
        $this->assertNotNull($agente->email_verified_at);
        $this->assertNotNull($agente->legal_aceptado_en);
        $this->assertSame(config('legal.version'), $agente->legal_version);
        $this->assertTrue(Hash::check('UnaClaveLarga2026!', $agente->password));

        // El enlace se usa una vez.
        $this->post(route('logout'));
        $this->get(route('invitacion.mostrar', $token))->assertStatus(410);
    }

    public function test_sin_aceptar_las_condiciones_no_entra(): void
    {
        $agente = Invitacion::invitar($this->promotora, 'Luis Comercial', 'luis@ejemplo.invalid');

        $this->post(route('invitacion.aceptar', $agente->invitacion_token), [
            'password' => 'UnaClaveLarga2026!', 'password_confirmation' => 'UnaClaveLarga2026!',
        ])->assertSessionHasErrors('acepto');

        $this->assertGuest();
        $this->assertTrue(Invitacion::pendiente($agente->fresh()));
    }

    public function test_un_enlace_de_hace_ocho_dias_no_vale_y_reenviar_da_uno_nuevo(): void
    {
        $agente = Invitacion::invitar($this->promotora, 'Luis Comercial', 'luis@ejemplo.invalid');
        $viejo = $agente->invitacion_token;
        $agente->forceFill(['invitado_en' => now()->subDays(8)])->save();

        $this->get(route('invitacion.mostrar', $viejo))->assertStatus(410)->assertSee(__('agentes.ya_no_vale'));

        $this->actingAs($this->promotora)->post(route('admin.users.reenviarInvitacion', $agente))
            ->assertRedirect(route('admin.users.index'))->assertSessionHas('success');

        $nuevo = $agente->fresh()->invitacion_token;
        $this->assertNotSame($viejo, $nuevo);
        auth()->logout(); // el enlace lo abre el agente, no la promotora
        $this->get(route('invitacion.mostrar', $nuevo))->assertOk();
        $this->get(route('invitacion.mostrar', $viejo))->assertStatus(410);
        Mail::assertQueued(InvitacionDeAgente::class, 2);
    }

    public function test_otra_promotora_no_reenvia_ni_ve_a_los_agentes_ajenos(): void
    {
        $agente = Invitacion::invitar($this->promotora, 'Luis Comercial', 'luis@ejemplo.invalid');
        $otra = $this->promotora('otra', CompanyProfile::PLAN_PROFESSIONAL);

        $this->actingAs($otra)->post(route('admin.users.reenviarInvitacion', $agente))->assertForbidden();
        $this->actingAs($otra)->get(route('admin.users.index'))->assertOk()->assertDontSee('luis@ejemplo.invalid');
    }

    public function test_el_cupo_del_plan_se_ve_antes_de_chocar_y_al_llenarse_no_se_ofrece_invitar(): void
    {
        $this->actingAs($this->promotora)->get(route('admin.users.index'))
            ->assertOk()->assertSee(__('agentes.cupo', ['usados' => 0, 'tope' => 5]))->assertSee(__('agentes.invitar'));

        foreach (range(1, 5) as $i) {
            Invitacion::invitar($this->promotora, "Agente $i", "agente$i@ejemplo.invalid");
        }

        $pagina = $this->actingAs($this->promotora)->get(route('admin.users.index'))->assertOk();
        $pagina->assertSee(__('agentes.cupo', ['usados' => 5, 'tope' => 5]))
            ->assertSee(__('agentes.cupo_lleno', ['tope' => 5]))
            ->assertDontSee(route('admin.users.create'));
        $pagina->assertSee(__('agentes.pendiente_desde', ['fecha' => now()->format('d/m/Y')]));

        $this->actingAs($this->promotora)->get(route('admin.users.create'))->assertRedirect(route('admin.users.index'));
        $this->invitar('seis@ejemplo.invalid')->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'seis@ejemplo.invalid']);
    }

    public function test_el_plan_gratuito_dice_que_no_incluye_agentes_en_vez_de_un_boton_que_falla(): void
    {
        $gratis = $this->promotora('gratis', CompanyProfile::PLAN_STARTER);

        $this->actingAs($gratis)->get(route('admin.users.index'))
            ->assertOk()->assertSee(__('agentes.sin_agentes', ['profesional' => 5]))->assertDontSee(route('admin.users.create'));
        $this->invitar('uno@ejemplo.invalid', $gratis)->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'uno@ejemplo.invalid']);
    }

    public function test_la_pagina_de_invitar_no_pide_contraseña_a_la_promotora(): void
    {
        $this->actingAs($this->promotora)->get(route('admin.users.create'))
            ->assertOk()->assertSee(__('agentes.invitar'))->assertDontSee('name="password"', false);
    }
}
