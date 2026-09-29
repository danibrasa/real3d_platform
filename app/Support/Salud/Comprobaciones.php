<?php

namespace App\Support\Salud;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

/**
 * Lo que solo se ve desde dentro de la maquina.
 *
 * Que la web responda 200 no dice nada de las dos cosas que de verdad han
 * fallado: que el correo salga y que la cola se vacie. Las dos rompen en
 * silencio -- la web sigue funcionando, los tests siguen verdes -- y las dos
 * nos costaron semanas.
 *
 * El vigilante vive en la otra maquina y no puede mirar dentro de esta, asi
 * que esta se examina y publica el resultado. Con el correo hay ademas una
 * pescadilla: si el correo esta roto, el aviso no puede ir por correo.
 */
class Comprobaciones
{
    /** Con la cola parada mas de esto, un lead lleva sin avisarse demasiado. */
    private const MINUTOS_DE_COLA = 5;

    /** Un trabajo cogido mas de esto es un worker que murio sujetandolo. */
    private const MINUTOS_COLGADO = 15;

    /** Sin latido del planificador mas de esto, sus tareas no estan corriendo. */
    private const MINUTOS_SIN_LATIDO = 15;

    /** Donde deja el latido la tarea salud:latido, cada cinco minutos. */
    public const CLAVE_LATIDO = 'salud.latido';

    /** Por debajo de cualquiera de los dos, el disco cuenta como lleno. */
    private const GB_MINIMOS = 2;

    private const PORCENTAJE_MINIMO = 10;

    /**
     * @return array<string, array{ok: bool, detalle: string}>
     */
    public static function todas(): array
    {
        return [
            'base' => self::base(),
            'correo' => self::correo(),
            'cola' => self::cola(),
            'latido' => self::latido(),
            'disco' => self::disco(),
        ];
    }

    public static function haySalud(array $comprobaciones): bool
    {
        foreach ($comprobaciones as $c) {
            if (! $c['ok']) {
                return false;
            }
        }

        return true;
    }

    private static function base(): array
    {
        try {
            DB::select('select 1');

            return ['ok' => true, 'detalle' => 'responde'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'detalle' => self::corto($e)];
        }
    }

    /**
     * El proveedor de correo, sin gastar un envio.
     *
     * start() abre la conexion y autentica; despues se cierra sin mandar nada.
     * Coge las tres formas de romperse que hemos tenido o podriamos tener --
     * host, puerto y credenciales -- y no cuesta ni un correo del cupo, que es
     * lo que permite mirarlo todos los dias.
     */
    private static function correo(): array
    {
        try {
            $transporte = Mail::mailer(config('mail.mailers.alertas') ? 'alertas' : null)
                ->getSymfonyTransport();

            if (! $transporte instanceof EsmtpTransport) {
                // En desarrollo el mailer por defecto es 'log' a proposito. Eso
                // no es un fallo, pero tampoco es una comprobacion: se dice.
                return ['ok' => true, 'detalle' => 'sin SMTP que comprobar ('.class_basename($transporte).')'];
            }

            $inicio = microtime(true);
            $transporte->start();
            $transporte->stop();

            return ['ok' => true, 'detalle' => 'conecta y autentica en '.round((microtime(true) - $inicio) * 1000).' ms'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'detalle' => self::corto($e)];
        }
    }

    /**
     * Si la cola se esta vaciando.
     *
     * No se puede ver el proceso del worker desde aqui, asi que se mira su
     * rastro: el trabajo pendiente mas viejo. Un worker muerto, o arrancado
     * con la configuracion vieja en memoria, deja la cola creciendo mientras
     * todo lo demas responde perfectamente. Eso paso, y duro semanas.
     */
    private static function cola(): array
    {
        try {
            // Lo que se mide es cuanto lleva algo LISTO sin que nadie lo coja,
            // no cuanto hace que se encolo. Un trabajo aplazado a proposito --
            // un recordatorio para dentro de una semana -- lleva encolado
            // siete dias y no tiene nada de malo; con created_at habria dado
            // una alarma falsa cada vez, y una alarma que salta sin motivo se
            // deja de leer.
            $ahora = time();
            // Dos formas de estar atascada, y hay que mirar las dos.
            //
            // La primera: trabajo listo que nadie coge. La segunda, que se me
            // escapo al arreglar la primera: un worker que muere CON el trabajo
            // cogido. Ese registro se queda reservado para siempre, y
            // excluyendo los reservados la cola entera podia estar parada
            // mientras esto decia "sin trabajo esperando". El punto ciego
            // estaba justo en el fallo que se vigila.
            $esperando = DB::table('jobs')
                ->whereNull('reserved_at')
                ->where('available_at', '<=', $ahora)
                ->min('available_at');

            // A un trabajo en curso se le da mas margen que a uno sin coger:
            // hay trabajos legitimamente largos, y una alarma que salta con
            // ellos se deja de leer.
            $colgado = DB::table('jobs')
                ->whereNotNull('reserved_at')
                ->where('reserved_at', '<=', $ahora - self::MINUTOS_COLGADO * 60)
                ->min('reserved_at');

            $pendientes = DB::table('jobs')
                ->where(fn ($q) => $q->whereNotNull('reserved_at')
                    ->orWhere('available_at', '<=', $ahora))
                ->count();
            $fallidos = DB::table('failed_jobs')->count();

            if (! $esperando && ! $colgado) {
                return ['ok' => true, 'detalle' => "sin trabajo atascado, {$fallidos} fallidos historicos"];
            }

            if ($colgado) {
                $minutos = (int) round(($ahora - (int) $colgado) / 60);

                return [
                    'ok' => false,
                    'detalle' => "un trabajo lleva {$minutos} min cogido por un worker que no lo suelta",
                ];
            }

            $minutos = (int) round(($ahora - (int) $esperando) / 60);

            return [
                'ok' => $minutos <= self::MINUTOS_DE_COLA,
                'detalle' => "{$pendientes} en la cola, el mas viejo esperando desde hace {$minutos} min",
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'detalle' => self::corto($e)];
        }
    }

    /**
     * Si el planificador corre.
     *
     * Las tareas programadas -- limpiar subidas a medias, y las que vengan --
     * no fallan cuando el planificador no corre: no ocurren. El timer parado,
     * o arrancando en un directorio que un despliegue dejo atras, y nada lo
     * dice. Por eso una de las tareas es dejar un latido, y esto lo lee.
     */
    private static function latido(): array
    {
        try {
            $ultimo = (int) Cache::get(self::CLAVE_LATIDO, 0);

            if ($ultimo === 0) {
                return [
                    'ok' => false,
                    'detalle' => 'el planificador no ha dado señales nunca: sin timer de schedule:run, o sin salud:latido en la agenda',
                ];
            }

            $minutos = (int) round((time() - $ultimo) / 60);

            return [
                'ok' => $minutos <= self::MINUTOS_SIN_LATIDO,
                'detalle' => "el planificador dio señales hace {$minutos} min",
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'detalle' => self::corto($e)];
        }
    }

    /**
     * Si queda sitio donde se guardan los ficheros.
     *
     * Un disco lleno no tira la web: las paginas siguen saliendo, y lo que
     * falla es la subida del visor de una promotora, a medias, con un error
     * que ella no entiende y que nosotros no vemos.
     */
    private static function disco(): array
    {
        try {
            $ruta = storage_path();

            return self::evaluarDisco((int) disk_free_space($ruta), (int) disk_total_space($ruta));
        } catch (\Throwable $e) {
            return ['ok' => false, 'detalle' => self::corto($e)];
        }
    }

    /** Aparte y publica para probarla con numeros: el disco de los tests no se llena a voluntad. */
    public static function evaluarDisco(int $libre, int $total): array
    {
        $gb = round($libre / 1024 ** 3, 1);
        $porcentaje = $total > 0 ? (int) round($libre * 100 / $total) : 0;
        $escaso = $libre < self::GB_MINIMOS * 1024 ** 3 || $porcentaje < self::PORCENTAJE_MINIMO;

        return [
            'ok' => ! $escaso,
            'detalle' => "{$gb} GB libres ({$porcentaje}%)".($escaso ? ': se esta llenando' : ''),
        ];
    }

    /** El mensaje de un fallo, sin la novela: esto acaba en un correo. */
    private static function corto(\Throwable $e): string
    {
        return substr(preg_replace('/\s+/', ' ', $e->getMessage()), 0, 160);
    }
}
