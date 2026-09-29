<?php

/**
 * Comprueba que la cola se vacia y que el aviso llego a quien tenia que llegar.
 *
 * Un formulario que responde 200 no prueba nada. El aviso a la promotora se
 * manda con ->queue(), asi que si el worker esta parado, o arranco con la
 * configuracion vieja en memoria, el lead se pierde igual y nadie se entera.
 * Eso paso en produccion y duro semanas.
 *
 * Y que la cola se vacie tampoco prueba que el aviso saliera: prueba que el
 * worker ejecuto algo. Durante un tiempo esto decia "el aviso salio de la
 * cola sin errores" mientras el correo de desarrollo iba a un log que lo
 * descartaba por nivel -- y habria dicho lo mismo si nadie hubiera encolado
 * nada. Por eso, si se le da el correo de quien debe recibir el aviso, mira
 * ademas que ese correo aparezca como destinatario en lo que se escribio.
 * Solo se puede mirar cuando el mailer es 'log'; con un proveedor de verdad
 * se dice que no se ha podido y no se finge.
 *
 * Y si se le da ademas una marca -- un texto que solo lleve ESTE aviso, como
 * el asunto con el nombre del comprador --, exige que aparezca. Sin eso, con
 * dos caminos que avisan a la misma promotora, el aviso del formulario
 * habria contado por el del chatbot: la comprobacion del segundo pasaria
 * aunque el chatbot hubiera dejado de avisar, que es justo lo que vigila.
 *
 * Uso: php comprobar-cola.php /ruta/de/la/app [segundos] [correo-esperado] [marca]
 */

use App\Support\Salud\CorreoEnElLog;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$ruta = rtrim($argv[1] ?? '/var/www/dev', '/');
$espera = (int) ($argv[2] ?? 40);
$esperado = trim($argv[3] ?? '');
$marca = trim($argv[4] ?? '');

require $ruta.'/vendor/autoload.php';
$app = require $ruta.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$fallidosAntes = DB::table('failed_jobs')->count();
$limite = time() + $espera;

while (time() < $limite && DB::table('jobs')->count() > 0) {
    sleep(2);
}

$pendientes = DB::table('jobs')->count();
$fallidos = DB::table('failed_jobs')->count();

if ($fallidos > $fallidosAntes) {
    $ultimo = DB::table('failed_jobs')->orderByDesc('id')->first();
    fwrite(STDERR, "el worker no pudo enviar el aviso:\n  "
        .substr($ultimo->exception ?? 'sin detalle', 0, 300)."\n");
    exit(1);
}

if ($pendientes > 0) {
    fwrite(STDERR, "el aviso sigue encolado tras {$espera}s: el worker no lo coge\n");
    exit(1);
}

if ($esperado === '') {
    echo "el aviso salio de la cola sin errores\n";
    exit(0);
}

if (config('mail.default') !== 'log') {
    echo "el aviso salio de la cola sin errores (correo real: no se puede leer a quien fue)\n";
    exit(0);
}

// El fichero donde escribe el mailer 'log'. Sin MAIL_LOG_CHANNEL escribe en el
// canal por defecto, y ahi, con LOG_LEVEL por encima de debug, no escribe
// nada: eso es lo que pasaba y lo que esta comprobacion ya no deja pasar.
$canal = config('mail.mailers.log.channel') ?: config('logging.default');
if (config("logging.channels.{$canal}.driver") === 'stack') {
    $canal = config("logging.channels.{$canal}.channels")[0] ?? $canal;
}
$fichero = config("logging.channels.{$canal}.path");

// El canal rota por dias, y el fichero del dia lleva la fecha en el nombre.
if (config("logging.channels.{$canal}.driver") === 'daily') {
    $fichero = preg_replace('/\.log$/', '', $fichero).'-'.date('Y-m-d').'.log';
}

if (! $fichero || ! is_readable($fichero)) {
    fwrite(STDERR, "el correo va al canal '{$canal}' y no se puede leer lo que escribe\n");
    fwrite(STDERR, "  (con MAIL_MAILER=log hace falta MAIL_LOG_CHANNEL=correo en el .env)\n");
    exit(1);
}

// La direccion lleva el sello de esta vuelta, asi que basta con que aparezca
// en cualquier sitio del fichero: no hay que adivinar desde que byte mirar, y
// da igual que el worker fuera mas rapido que nosotros. Pero destinatario y
// marca se exigen sobre el MISMO mensaje: mirados por separado, el aviso del
// formulario (a la promotora) mas cualquier cosa con la marca del chatbot
// daban por bueno un aviso del chatbot que podia no haber ido a nadie.
$contenido = file_get_contents($fichero);

if (! CorreoEnElLog::hayAvisoPara($contenido, $esperado)) {
    fwrite(STDERR, "la cola se vacio pero ningun correo va dirigido a {$esperado}\n");
    fwrite(STDERR, "  (mirado en {$fichero})\n");
    exit(1);
}

if ($marca !== '' && ! CorreoEnElLog::hayAvisoPara($contenido, $esperado, $marca)) {
    fwrite(STDERR, "hay correo para {$esperado}, pero ninguno es este: falta '{$marca}'\n");
    fwrite(STDERR, "  (mirado en {$fichero})\n");
    exit(1);
}

echo "el aviso salio de la cola y va dirigido a {$esperado}\n";
if ($marca !== '') {
    echo "  y es el suyo: lleva '{$marca}'\n";
}
