<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\User;
use App\Support\Facturacion\AccesoAlVisor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Que el plan gratuito no de lo mismo que el de pago.
 *
 * El visor 3D es lo unico del producto que cuesta dinero hacer -- lo monta el
 * equipo, uno a uno -- y es la razon entera de que haya un plan de pago. No lo
 * comprobaba nadie: una promotora en el plan gratuito pedia su visor, el equipo
 * recibia el aviso, se lo montaba, y publicaba. Los dos planes daban lo mismo y
 * el codigo no decia lo contrario en ningun sitio.
 *
 * Se prueba por los dos lados. Que el gratuito no pueda es la mitad facil; la
 * que de verdad importa es que el de pago siga pudiendo, porque una puerta
 * cerrada de mas no se nota hasta que un cliente se queja.
 */
class AccesoAlVisorTest extends TestCase
{
    use RefreshDatabase;

    private function promotora(string $plan): User
    {
        $user = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);

        CompanyProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Promotora Bahia',
            'slug' => 'promotora-bahia-'.$user->id,
            'plan_tier' => $plan,
            'max_projects' => 5,
        ]);

        return $user->fresh();
    }

    private function proyectoDe(User $user): Project
    {
        $proyecto = Project::create([
            'name' => 'Residencial Bahia',
            'slug' => 'residencial-bahia-'.$user->id,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $user->assignedProjects()->attach($proyecto->id);

        return $proyecto;
    }

    // --- La regla, sin pasar por el navegador -----------------------------

    public function test_el_plan_gratuito_no_llega_al_visor(): void
    {
        $this->assertFalse(AccesoAlVisor::puedePedirlo($this->promotora(CompanyProfile::PLAN_STARTER)));
    }

    public function test_los_planes_de_pago_si(): void
    {
        foreach ([CompanyProfile::PLAN_PROFESSIONAL, CompanyProfile::PLAN_ENTERPRISE] as $plan) {
            $this->assertTrue(AccesoAlVisor::puedePedirlo($this->promotora($plan)),
                "el plan {$plan} no llega al visor y deberia");
        }
    }

    public function test_el_equipo_no_tiene_que_comprar_nada(): void
    {
        // Monta visores; seria absurdo pedirle plan. Y ademas sin ficha de
        // empresa, que es como esta el equipo de verdad.
        foreach ([User::ROLE_SUPERADMIN, User::ROLE_GESTOR] as $papel) {
            $this->assertTrue(AccesoAlVisor::puedePedirlo(
                User::factory()->create(['role' => $papel])));
        }
    }

    public function test_sin_empresa_y_sin_sesion_no_se_pide(): void
    {
        // Una promotora sin ficha de empresa no tiene plan que mirar, y sin
        // plan la respuesta segura es que no. Y sin sesion, tampoco.
        $suelto = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);

        $this->assertFalse(AccesoAlVisor::puedePedirlo($suelto));
        $this->assertFalse(AccesoAlVisor::puedePedirlo(null));
    }

    // --- Y por donde entra de verdad --------------------------------------

    public function test_pedirlo_con_el_plan_gratuito_no_avisa_al_equipo(): void
    {
        Mail::fake();
        $promotora = $this->promotora(CompanyProfile::PLAN_STARTER);
        $proyecto = $this->proyectoDe($promotora);

        $this->actingAs($promotora)
            ->post(route('admin.projects.visor.pedir', $proyecto))
            ->assertRedirect();

        // Lo que importa no es el codigo de respuesta: es que el proyecto no
        // haya entrado en la cola y que nadie del equipo se ponga a montarlo.
        $this->assertNull($proyecto->fresh()->viewer_requested_at,
            'un proyecto del plan gratuito entro en la cola del equipo');
        Mail::assertNothingQueued();
    }

    public function test_pedirlo_con_plan_de_pago_sigue_funcionando(): void
    {
        Mail::fake();
        $promotora = $this->promotora(CompanyProfile::PLAN_PROFESSIONAL);
        $proyecto = $this->proyectoDe($promotora);

        $this->actingAs($promotora)
            ->post(route('admin.projects.visor.pedir', $proyecto));

        $this->assertNotNull($proyecto->fresh()->viewer_requested_at);
    }

    public function test_el_panel_no_ensena_un_boton_que_va_a_fallar(): void
    {
        $promotora = $this->promotora(CompanyProfile::PLAN_STARTER);
        $proyecto = $this->proyectoDe($promotora);

        $respuesta = $this->actingAs($promotora)
            ->get(route('admin.projects.edit', $proyecto));

        // Se mira la direccion del formulario, no la etiqueta del boton. Con la
        // etiqueta, el dia que alguien renombre la clave de traduccion el test
        // seguiria en verde sin mirar nada: __() devolveria el nombre de la
        // clave, que no aparece en ningun HTML, y assertDontSee pasaria siempre.
        $respuesta->assertOk();
        $respuesta->assertDontSee(route('admin.projects.visor.pedir', $proyecto), false);
        $respuesta->assertSee(route('admin.subscription.index'), false);
    }

    public function test_con_plan_de_pago_el_panel_si_ensena_el_boton(): void
    {
        // La mitad simetrica: sin esto, una vista que no ensenara el boton a
        // nadie pasaria el test de arriba tan tranquila.
        $promotora = $this->promotora(CompanyProfile::PLAN_PROFESSIONAL);
        $proyecto = $this->proyectoDe($promotora);

        $respuesta = $this->actingAs($promotora)
            ->get(route('admin.projects.edit', $proyecto));

        $respuesta->assertOk();
        $respuesta->assertSee(route('admin.projects.visor.pedir', $proyecto), false);
    }
}
