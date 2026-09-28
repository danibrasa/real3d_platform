<?php

namespace Tests\Feature;

use App\Mail\VisorMontado;
use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use App\Support\Facturacion\PruebaGratuita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\SuscripcionDeMentira;
use Tests\TestCase;

/**
 * Cuando empiezan a contar los catorce dias.
 *
 * Empezaban al pagar. Como el visor lo montamos nosotros, entre que la
 * promotora lo pide y lo tiene pueden pasar dias en los que su panel esta
 * vacio y su pagina no se puede publicar: no hay nada que probar. Una espera
 * de cinco dias dejaba nueve de prueba y catorce de cobro.
 *
 * Ahora la prueba se ancla cuando el visor se da por montado. Lo que se
 * comprueba aqui son las reglas, que es lo que puede equivocarse; la llamada a
 * Stripe va aparte a proposito para no tener que simular una pasarela para
 * saber si una condicion esta bien escrita.
 */
class PruebaGratuitaTest extends TestCase
{
    use RefreshDatabase;

    private function perfil(?string $pruebaDesde = null): CompanyProfile
    {
        $user = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);

        return CompanyProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Promotora Bahia',
            'slug' => 'promotora-bahia-'.$user->id,
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL,
            'max_projects' => 5,
            'prueba_desde' => $pruebaDesde,
        ]);
    }

    public function test_se_ancla_cuando_hay_suscripcion(): void
    {
        $this->assertTrue(PruebaGratuita::debeAnclarse($this->perfil(), haySuscripcion: true));
    }

    public function test_la_espera_larga_no_deja_a_nadie_sin_prueba(): void
    {
        // El fallo de la primera version: se exigia que la suscripcion siguiera
        // en prueba al montar el visor. Si tardabamos mas que la prueba, al
        // llegar el visor ya se le estaba cobrando, no se anclaba nada, y la
        // promotora se quedaba pagando sin haber podido probar -- justo lo que
        // esto venia a evitar.
        $perfil = $this->perfil();

        // La espera larga, dicha en el propio caso y no dada por supuesta: la
        // prueba original ya vencio cuando por fin hay visor.
        $suscripcion = new SuscripcionDeMentira(enPrueba: false);

        $fin = PruebaGratuita::anclar($perfil, $suscripcion);

        $this->assertNotNull($fin,
            'con la prueba original vencida no se anclo nada: la promotora se queda pagando sin haber probado');
        $this->assertSame(1, $suscripcion->veces);
        $this->assertNotNull($perfil->fresh()->prueba_desde);
    }

    public function test_la_prueba_termina_a_los_dias_configurados(): void
    {
        $perfil = $this->perfil();
        $suscripcion = new SuscripcionDeMentira;

        $fin = PruebaGratuita::anclar($perfil, $suscripcion);

        $esperado = now()->addDays((int) config('stripe.trial_days'));

        $this->assertNotNull($suscripcion->pedida, 'no se le pidio a Stripe mover la fecha');
        $this->assertSame($esperado->toDateString(), $suscripcion->pedida->toDateString());
        $this->assertSame($esperado->toDateString(), $fin->toDateString());
        $this->assertNotNull($perfil->fresh()->prueba_desde);
    }

    public function test_si_stripe_falla_no_queda_marcada_la_prueba(): void
    {
        // Al reves, la empresa constaria como "prueba empezada" mientras la
        // pasarela sigue con la fecha vieja y le cobra al dia siguiente.
        $perfil = $this->perfil();
        $suscripcion = new SuscripcionDeMentira(revienta: true);

        try {
            PruebaGratuita::anclar($perfil, $suscripcion);
            $this->fail('deberia haber propagado el fallo de Stripe');
        } catch (\RuntimeException $e) {
            // Lo esperado: quien llama decide, y marcarMontado lo registra y
            // sigue para no dejar sin aviso a la promotora.
        }

        $this->assertNull($perfil->fresh()->prueba_desde);
    }

    public function test_una_suscripcion_cancelada_no_ancla_nada(): void
    {
        $perfil = $this->perfil();
        $suscripcion = new SuscripcionDeMentira(cancelada: true);

        $this->assertNull(PruebaGratuita::anclar($perfil, $suscripcion));
        $this->assertSame(0, $suscripcion->veces);
        $this->assertNull($perfil->fresh()->prueba_desde);
    }

    public function test_el_segundo_visor_no_estira_la_prueba(): void
    {
        $perfil = $this->perfil();
        $primera = new SuscripcionDeMentira;

        PruebaGratuita::anclar($perfil, $primera);

        // Otro proyecto de la misma empresa, dado por montado despues.
        $segunda = new SuscripcionDeMentira;
        $this->assertNull(PruebaGratuita::anclar($perfil->fresh(), $segunda));
        $this->assertSame(0, $segunda->veces, 'se volvio a mover la fecha en Stripe');
    }

    public function test_no_se_ancla_dos_veces(): void
    {
        // El segundo proyecto que se da por montado no regala otros catorce
        // dias: si lo hiciera, una promotora con proyectos seguidos no llegaria
        // a pagar nunca.
        $perfil = $this->perfil(pruebaDesde: now()->subDays(3)->toDateTimeString());

        $this->assertFalse(PruebaGratuita::debeAnclarse($perfil, haySuscripcion: true));
    }

    public function test_sin_suscripcion_no_hay_nada_que_anclar(): void
    {
        // El plan gratuito no tiene prueba porque no se acaba nunca.
        $this->assertFalse(PruebaGratuita::debeAnclarse($this->perfil(), haySuscripcion: false));
        $this->assertNull(PruebaGratuita::anclar($this->perfil(), null));
    }

    public function test_quien_no_tiene_empresa_no_rompe_nada(): void
    {
        $suelto = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);

        $this->assertNull(PruebaGratuita::anclarAlMontarVisor($suelto));
        $this->assertNull(PruebaGratuita::anclarAlMontarVisor(null));
    }

    public function test_sin_suscripcion_no_se_llama_a_stripe_ni_se_marca(): void
    {
        // Sin suscripcion no hay reloj que mover, y sobre todo: no se sale a la
        // red. Si se marcara igualmente, el dia que contratara ya constaria
        // como prueba gastada sin haberla tenido.
        $perfil = $this->perfil();

        $this->assertNull(PruebaGratuita::anclarAlMontarVisor($perfil->user));
        $this->assertNull($perfil->fresh()->prueba_desde);
    }

    /**
     * Un proyecto que de verdad se puede dar por montado.
     *
     * Hace falta el fichero: sin modelo ni fondo, marcarMontado se corta antes
     * con "aun no hay visor" y no llega a ejecutar nada de lo que se quiere
     * comprobar. La primera version de este test no lo ponia, pasaba en verde,
     * y lo unico que demostraba es que la ruta devuelve una redireccion.
     */
    private function proyectoConVisorPedido(CompanyProfile $perfil): Project
    {
        $proyecto = Project::create([
            'name' => 'Residencial Bahia',
            'slug' => 'residencial-bahia-'.$perfil->id,
            'status' => 'draft',
            'created_by' => $perfil->user_id,
            'viewer_requested_at' => now(),
            'viewer_requested_by' => $perfil->user_id,
        ]);

        ProjectFile::create([
            'project_id' => $proyecto->id,
            'file_type' => 'image_360',
            'original_name' => 'fondo.png',
            'storage_path' => 'x/fondo.png',
            'mime_type' => 'image/png',
            'file_size' => 100,
            'upload_complete' => true,
        ]);

        return $proyecto;
    }

    public function test_dar_por_montado_funciona_aunque_no_haya_suscripcion(): void
    {
        // El caso de hoy: todavia no hay clientes de pago. Dar por montado un
        // visor tiene que seguir cerrando la solicitud y avisando.
        Mail::fake();
        $equipo = User::factory()->create(['role' => User::ROLE_GESTOR]);
        $perfil = $this->perfil();
        $proyecto = $this->proyectoConVisorPedido($perfil);

        $this->actingAs($equipo)->post(route('admin.projects.visor.montado', $proyecto));

        // Que haya salido de la cola es lo que prueba que llego al final y no
        // se corto en el "aun no hay visor".
        $this->assertNull($proyecto->fresh()->viewer_requested_at);
        Mail::assertQueued(VisorMontado::class);

        // Y sin suscripcion no se marca prueba: el dia que contrate no puede
        // constarle como gastada sin haberla tenido.
        $this->assertNull($perfil->fresh()->prueba_desde);
    }

    public function test_sin_visor_montado_no_se_toca_la_prueba(): void
    {
        $equipo = User::factory()->create(['role' => User::ROLE_GESTOR]);
        $perfil = $this->perfil();

        // Sin el fichero: el equipo se equivoca de boton y lo da por montado
        // antes de montarlo.
        $proyecto = Project::create([
            'name' => 'Residencial Sin Nada',
            'slug' => 'residencial-sin-nada-'.$perfil->id,
            'status' => 'draft',
            'created_by' => $perfil->user_id,
            'viewer_requested_at' => now(),
            'viewer_requested_by' => $perfil->user_id,
        ]);

        $this->actingAs($equipo)->post(route('admin.projects.visor.montado', $proyecto));

        // Sigue en la cola y la prueba no ha empezado: seria lo peor de los dos
        // mundos, gastar dias de prueba sobre un visor que no existe.
        $this->assertNotNull($proyecto->fresh()->viewer_requested_at);
        $this->assertNull($perfil->fresh()->prueba_desde);
    }
}
