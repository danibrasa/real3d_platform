<?php

namespace Tests\Feature;

use App\Support\Salud\Comprobaciones;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Lo que la maquina cuenta de si misma, y a quien.
 *
 * Existe porque el vigilante vive en la otra VM y no ve dentro de esta: no sabe
 * si la cola avanza ni si el proveedor de correo contesta. Las dos cosas han
 * fallado en silencio y las dos costaron semanas, con la web respondiendo 200
 * todo el rato.
 */
class SaludTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'un-token-de-prueba-suficientemente-largo';

    public function test_el_vigilante_entra_por_su_ip_sin_token(): void
    {
        // Es como entra de verdad la comprobacion nocturna. Si esto solo
        // funcionara con token, encenderla exigiria meter un secreto a mano en
        // el .env de produccion, y se quedaria apagada hasta que alguien se
        // acordara.
        config(['app.salud_token' => null, 'app.salud_ips' => ['10.9.9.9']]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.9.9.9'])
            ->get('/salud')
            ->assertOk();
    }

    public function test_otra_ip_no_entra(): void
    {
        config(['app.salud_token' => null, 'app.salud_ips' => ['10.9.9.9']]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.9.9.10'])
            ->get('/salud')
            ->assertNotFound();
    }

    public function test_sin_token_ni_lista_no_entra_nadie(): void
    {
        config(['app.salud_token' => null, 'app.salud_ips' => []]);

        $this->get('/salud')->assertNotFound();
    }

    public function test_sin_token_configurado_la_ruta_no_existe(): void
    {
        // Cerrada mientras no se configure, no abierta. Al reves es como se
        // publica algo sin querer.
        config(['app.salud_token' => null, 'app.salud_ips' => []]);

        $this->get('/salud')->assertNotFound();
    }

    public function test_con_token_configurado_pero_sin_darlo_tampoco(): void
    {
        config(['app.salud_token' => self::TOKEN]);

        $this->get('/salud')->assertNotFound();
        $this->get('/salud?clave=otra-cosa')->assertNotFound();
    }

    public function test_con_el_token_correcto_cuenta_como_va(): void
    {
        config(['app.salud_token' => self::TOKEN]);

        $this->withHeader('X-Salud', self::TOKEN)
            ->get('/salud')
            ->assertOk()
            ->assertJsonStructure([
                'ok',
                'entorno',
                'comprobado',
                'comprobaciones' => ['base', 'correo', 'cola'],
            ]);
    }

    public function test_una_cola_atascada_da_503(): void
    {
        // El fallo de verdad: la web responde, los tests pasan, y los avisos
        // llevan horas sin salir. Un trabajo pendiente de hace dos horas es
        // exactamente eso.
        config(['app.salud_token' => self::TOKEN]);

        // Listo desde hace dos horas y nadie lo ha cogido: eso es un worker
        // muerto, no un trabajo reciente.
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => time() - 7200,
            'created_at' => time() - 7200,
        ]);

        $respuesta = $this->withHeader('X-Salud', self::TOKEN)->get('/salud');

        $respuesta->assertStatus(503);
        $respuesta->assertJsonPath('ok', false);
        $respuesta->assertJsonPath('comprobaciones.cola.ok', false);

        // Y que diga cuanto lleva, no solo que va mal.
        $this->assertStringContainsString('120 min',
            $respuesta->json('comprobaciones.cola.detalle'));
    }

    public function test_una_cola_recien_encolada_no_alarma(): void
    {
        // Lo contrario importa igual: un aviso que salta con cada trabajo
        // normal se deja de leer, y entonces tampoco se lee el que importa.
        config(['app.salud_token' => self::TOKEN]);

        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => time(),
            'created_at' => time() - 30,
        ]);

        $this->assertTrue(Comprobaciones::todas()['cola']['ok']);
    }

    public function test_un_trabajo_aplazado_no_da_falsa_alarma(): void
    {
        // Un recordatorio programado para dentro de una semana lleva encolado
        // siete dias y no tiene nada de malo. Midiendo por cuando se encolo, en
        // vez de por cuando quedo listo, habria dado alarma cada vez. Y una
        // alarma que salta sin motivo se deja de leer, que es como se pierde la
        // siguiente de verdad.
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => time() + 604800,
            'created_at' => time() - 7200,
        ]);

        $this->assertTrue(Comprobaciones::todas()['cola']['ok'],
            'un trabajo aplazado a proposito se conto como cola atascada');
    }

    public function test_un_trabajo_en_curso_no_da_falsa_alarma(): void
    {
        // Un worker lo cogio hace un rato y lo esta procesando. Sin mirar
        // reserved_at, un trabajo largo contaba como cola muerta.
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 1,
            'reserved_at' => time() - 60,
            'available_at' => time() - 7200,
            'created_at' => time() - 7200,
        ]);

        $this->assertTrue(Comprobaciones::todas()['cola']['ok'],
            'un trabajo que se esta procesando se conto como cola atascada');
    }

    public function test_el_correo_se_comprueba_de_verdad_cuando_hay_smtp(): void
    {
        // phpunit.xml neutraliza el canal de alertas para que la suite no
        // marque al proveedor de pago en cada ejecucion. El efecto secundario
        // es que la rama que de verdad conecta quedaba sin ejercitar en el CI:
        // codigo muerto donde mas importa. Aqui se enciende a proposito contra
        // destinos que fallan al instante.
        foreach ([
            'host' => ['host' => 'no.existe.invalido', 'port' => 587],
            'puerto' => ['host' => '127.0.0.1', 'port' => 1],
        ] as $caso => $config) {
            config([
                'mail.mailers.alertas.transport' => 'smtp',
                'mail.mailers.alertas.host' => $config['host'],
                'mail.mailers.alertas.port' => $config['port'],
            ]);
            Mail::clearResolvedInstances();
            app()->forgetInstance('mail.manager');

            $correo = Comprobaciones::todas()['correo'];

            $this->assertFalse($correo['ok'], "no detecto el fallo de {$caso}");
            $this->assertNotEmpty($correo['detalle'], "no dijo que paso con el {$caso}");
        }
    }

    public function test_sin_smtp_lo_dice_en_vez_de_fingir_que_comprueba(): void
    {
        // En desarrollo el correo va a un log a proposito. Eso no es un fallo,
        // pero tampoco es una comprobacion, y llamarlo "ok" a secas seria
        // decir que se miro algo que no se miro.
        config(['mail.mailers.alertas.transport' => 'array']);
        Mail::clearResolvedInstances();
        app()->forgetInstance('mail.manager');

        $correo = Comprobaciones::todas()['correo'];

        $this->assertTrue($correo['ok']);
        $this->assertStringContainsString('sin SMTP', $correo['detalle']);
    }

    public function test_un_worker_muerto_con_el_trabajo_cogido_se_nota(): void
    {
        // El punto ciego que me deje al arreglar la falsa alarma anterior. Un
        // worker que muere sujetando el trabajo deja el registro reservado para
        // siempre: la cola entera parada y esto decia "sin trabajo atascado".
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 1,
            'reserved_at' => time() - 10800,
            'available_at' => time() - 10800,
            'created_at' => time() - 10800,
        ]);

        $cola = Comprobaciones::todas()['cola'];

        $this->assertFalse($cola['ok'],
            'un worker muerto con el trabajo cogido paso por cola sana');
        $this->assertStringContainsString('no lo suelta', $cola['detalle']);
    }

    public function test_la_base_caida_se_nota(): void
    {
        DB::shouldReceive('select')->andThrow(new \RuntimeException('no hay base'));
        DB::shouldReceive('table')->andThrow(new \RuntimeException('no hay base'));

        $comprobaciones = Comprobaciones::todas();

        $this->assertFalse($comprobaciones['base']['ok']);
        $this->assertFalse(Comprobaciones::haySalud($comprobaciones));
    }
}
