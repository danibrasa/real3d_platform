<?php

namespace Tests\Feature;

use App\Mail\InvitacionDeAgente;
use App\Mail\VisorRevisado;
use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use App\Support\Agentes\Invitacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Lo que dijo la revision (en sesion, sin API) de #115 a #121, y que ahora
 * queda atado: cada test de aqui fallaba con el codigo de antes.
 */
class HallazgosDeRevisionTest extends TestCase
{
    use RefreshDatabase;

    private User $ana;

    private User $gestora;

    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->ana = $this->promotora('ana');
        $this->gestora = User::factory()->create(['role' => User::ROLE_GESTOR, 'email' => 'gestora@real3d.invalid']);
        $this->proyecto = Project::create(['name' => 'Residencial Bahia', 'slug' => 'residencial-bahia', 'status' => 'draft', 'created_by' => $this->ana->id,
            'viewer_requested_at' => now()->subDays(9), 'visor_estado' => 'para_revisar', 'visor_estado_en' => now()]);
        $this->ana->assignedProjects()->attach($this->proyecto->id);
    }

    private function promotora(string $nombre): User
    {
        $u = User::factory()->create(['role' => User::ROLE_INMOBILIARIA, 'email' => $nombre.'@ejemplo.invalid', 'name' => ucfirst($nombre)]);
        CompanyProfile::create(['user_id' => $u->id, 'company_name' => ucfirst($nombre), 'slug' => $nombre, 'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL, 'max_projects' => 5]);

        return $u;
    }

    private function vivienda(string $id, ?float $precio, string $estado = 'available'): Unit
    {
        return Unit::create(['project_id' => $this->proyecto->id, 'identifier' => $id, 'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 1, 'area_m2' => 80, 'price' => $precio, 'status' => $estado]);
    }

    // --- #115 la cola del equipo ---------------------------------------------

    public function test_un_pedido_de_ayer_sin_estado_no_esta_atascado(): void
    {
        $this->proyecto->forceFill(['visor_estado' => null, 'visor_estado_en' => null, 'viewer_requested_at' => now()->subDay()])->save();

        $this->artisan('visores:atascados', ['--dias' => 5])->assertSuccessful();
    }

    public function test_el_comando_dice_que_base_mira(): void
    {
        $this->artisan('visores:atascados', ['--dias' => 5])
            ->expectsOutputToContain('base: '.config('database.connections.'.config('database.default').'.database'));
    }

    public function test_la_cola_marca_al_asignado_en_el_select(): void
    {
        $this->proyecto->forceFill(['visor_asignado_a' => $this->gestora->id])->save();

        $this->actingAs($this->gestora)->get(route('admin.visores.pendientes'))->assertOk()
            ->assertSee('value="'.$this->gestora->id.'" selected', false);
    }

    // --- #116 el visto bueno ---------------------------------------------------

    public function test_el_visto_bueno_solo_vale_mientras_esta_para_revisar(): void
    {
        $this->proyecto->forceFill(['visor_estado' => 'montado'])->save();

        $this->actingAs($this->ana)->post(route('admin.projects.visor.revisado', $this->proyecto), ['veredicto' => 'cambios', 'comentario' => 'Tarde'])
            ->assertRedirect()->assertSessionHas('error');

        $this->assertSame('montado', $this->proyecto->fresh()->visor_estado, 'un "cambios" tardio devolvio a preparacion un visor montado');
        Mail::assertNotQueued(VisorRevisado::class);
    }

    public function test_el_equipo_no_se_da_el_visto_bueno_a_si_mismo(): void
    {
        $this->actingAs($this->gestora)->post(route('admin.projects.visor.revisado', $this->proyecto), ['veredicto' => 'aprobado'])
            ->assertForbidden();
        $this->assertNull($this->proyecto->fresh()->visor_aprobado_en);
    }

    public function test_otra_vuelta_de_revision_empieza_sin_el_comentario_anterior(): void
    {
        $this->actingAs($this->ana)->post(route('admin.projects.visor.revisado', $this->proyecto), ['veredicto' => 'cambios', 'comentario' => 'La fachada sale verde']);
        $this->assertSame('La fachada sale verde', $this->proyecto->fresh()->visor_comentario);

        $this->actingAs($this->gestora)->patch(route('admin.projects.visor.actualizar', $this->proyecto), ['estado' => 'para_revisar'])->assertSessionHasNoErrors();

        $p = $this->proyecto->fresh();
        $this->assertSame('para_revisar', $p->visor_estado);
        $this->assertNull($p->visor_comentario, 'la peticion de cambios anterior seguia como vigente');
        $this->assertNull($p->visor_aprobado_en);
    }

    public function test_el_comentario_al_aprobar_se_lee_en_la_cola(): void
    {
        $this->actingAs($this->ana)->post(route('admin.projects.visor.revisado', $this->proyecto), ['veredicto' => 'aprobado', 'comentario' => 'Perfecto, gracias']);

        $this->actingAs($this->gestora)->get(route('admin.visores.pendientes'))->assertOk()->assertSee('Perfecto, gracias');
    }

    public function test_los_datos_del_visor_en_borrador_por_la_api_solo_para_su_promotora(): void
    {
        $this->actingAs($this->ana)->get('/api/projects/residencial-bahia')->assertOk();
        $this->actingAs($this->promotora('otra'))->get('/api/projects/residencial-bahia')->assertNotFound();
        auth()->logout();
        $this->get('/api/projects/residencial-bahia')->assertNotFound();
    }

    // --- #118 edicion rapida ---------------------------------------------------

    public function test_un_lote_sin_marcadas_no_guarda_las_filas_por_el_camino(): void
    {
        $a = $this->vivienda('A-101', 100000);

        $this->actingAs($this->ana)->patch(route('admin.projects.units.lote', $this->proyecto), [
            'v' => [$a->id => ['price' => '120000', 'status' => 'sold']],
            'lote_accion' => 'estado', 'lote_estado' => 'sold',
        ])->assertSessionHasErrors('sel');

        $this->assertSame('available', $a->fresh()->status, 'se guardo la fila y luego se dijo que no se habia hecho nada');
        $this->assertSame(100000.0, $a->fresh()->price);
    }

    public function test_al_volver_con_error_la_tabla_conserva_lo_editado(): void
    {
        $a = $this->vivienda('A-101', 100000);

        $this->actingAs($this->ana)->from(route('admin.projects.units.index', $this->proyecto))
            ->patch(route('admin.projects.units.lote', $this->proyecto), [
                'v' => [$a->id => ['price' => '123456', 'status' => 'reserved']],
                'sel' => [$a->id], 'lote_accion' => 'porcentaje', 'lote_porcentaje' => '500',
            ])->assertSessionHasErrors('lote_porcentaje');

        $html = $this->actingAs($this->ana)->get(route('admin.projects.units.index', $this->proyecto))->assertOk()->getContent();
        $this->assertStringContainsString('value="123456"', $html, 'el precio tecleado se perdio al volver');
        $this->assertStringContainsString('<option value="reserved" selected>', $html);
        $this->assertMatchesRegularExpression('/name="sel\[\]" value="'.$a->id.'"[^>]*checked/', $html);
        $this->assertSame(100000.0, $a->fresh()->price, 'con el lote rechazado no debia guardarse nada');
    }

    public function test_reenviar_la_tabla_sin_tocarla_no_guarda_nada(): void
    {
        $a = $this->vivienda('A-101', 214500);
        $a->forceFill(['updated_at' => now()->subDay()])->save();
        $antes = $a->fresh()->updated_at;

        $this->actingAs($this->ana)->patch(route('admin.projects.units.lote', $this->proyecto), [
            'v' => [$a->id => ['price' => '214500', 'status' => 'available']],
        ])->assertSessionHas('edicion_rapida', fn ($r) => $r['guardadas'] === 0 && $r['sin_cambios'] === 1);

        $this->assertTrue($antes->equalTo($a->fresh()->updated_at), 'se reescribio una fila sin cambios');
    }

    public function test_una_vivienda_editada_en_fila_y_en_lote_cuenta_una_vez(): void
    {
        $a = $this->vivienda('A-101', 100000);

        $this->actingAs($this->ana)->patch(route('admin.projects.units.lote', $this->proyecto), [
            'v' => [$a->id => ['price' => '100000', 'status' => 'sold']],
            'sel' => [$a->id], 'lote_accion' => 'estado', 'lote_estado' => 'sold',
        ])->assertSessionHas('edicion_rapida', fn ($r) => $r['guardadas'] === 1 && $r['sin_cambios'] === 0);
    }

    public function test_el_filtro_por_estado_filtra(): void
    {
        $this->vivienda('A-101', 100000, 'sold');
        $this->vivienda('A-102', 100000, 'available');

        $this->actingAs($this->ana)->get(route('admin.projects.units.index', ['project' => $this->proyecto, 'status' => 'sold']))
            ->assertOk()->assertSee('A-101')->assertDontSee('A-102');
    }

    // --- #120 agentes por invitacion ------------------------------------------

    public function test_un_agente_pendiente_no_entra_por_olvide_mi_contraseña(): void
    {
        Notification::fake();
        $agente = Invitacion::invitar($this->ana, 'Luis', 'luis@ejemplo.invalid');
        $token = $agente->invitacion_token;

        $this->post(route('password.email'), ['email' => 'luis@ejemplo.invalid'])->assertSessionHas('status');
        Notification::assertNothingSent();
        Mail::assertQueued(InvitacionDeAgente::class, 2);
        $this->assertNotSame($token, $agente->fresh()->invitacion_token, 'se le reenvia la invitacion, con enlace nuevo');

        // Y con un token de reset legitimo, tampoco.
        $reset = Password::createToken($agente);
        $this->post(route('password.store'), ['token' => $reset, 'email' => 'luis@ejemplo.invalid', 'password' => 'UnaClaveLarga2026!', 'password_confirmation' => 'UnaClaveLarga2026!'])
            ->assertSessionHasErrors('email');
        $this->assertFalse(Hash::check('UnaClaveLarga2026!', $agente->fresh()->password));
        $this->assertTrue(Invitacion::pendiente($agente->fresh()));
    }

    public function test_la_promotora_no_le_pone_contraseña_a_un_agente_pendiente(): void
    {
        $agente = Invitacion::invitar($this->ana, 'Luis', 'luis@ejemplo.invalid');

        $this->actingAs($this->ana)->put(route('admin.users.update', $agente), [
            'name' => 'Luis', 'email' => 'luis@ejemplo.invalid', 'role' => 'agente',
            'password' => 'UnaClaveLarga2026!', 'password_confirmation' => 'UnaClaveLarga2026!',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertFalse(Hash::check('UnaClaveLarga2026!', $agente->fresh()->password));
        $this->assertTrue(Invitacion::pendiente($agente->fresh()));
    }

    public function test_la_invitacion_sale_en_el_idioma_de_quien_invita(): void
    {
        $this->actingAs($this->ana)->withHeaders(['Accept-Language' => 'en'])->post(route('admin.users.store'), [
            'name' => 'Luis', 'email' => 'luis@ejemplo.invalid', 'role' => 'agente',
        ]);

        Mail::assertQueued(InvitacionDeAgente::class, fn ($m) => $m->locale === 'en');
    }

    // --- #121 el panel sin jerga ------------------------------------------------

    public function test_el_aviso_de_no_poder_borrar_la_cuenta_se_lee(): void
    {
        $this->ana->subscriptions()->create(['type' => 'default', 'stripe_id' => 'sub_x', 'stripe_status' => 'active', 'stripe_price' => 'price_x', 'quantity' => 1]);

        $this->actingAs($this->ana)->from('/profile')->delete('/profile', ['password' => 'password'])
            ->assertRedirect('/profile')->assertSessionHasErrorsIn('userDeletion', ['suscripcion']);

        $html = $this->actingAs($this->ana)->get('/profile')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/text-red-600">\s*\S/', $html, 'el parrafo del aviso salia vacio');
        $this->assertNotNull(User::find($this->ana->id));
    }

    public function test_publica_es_un_verbo(): void
    {
        $this->assertSame('Publica el proyecto', __('primeros_pasos.publicar', [], 'es'));
        $this->assertStringStartsWith('Publica ', __('portal.list_property', [], 'es'));
    }
}
