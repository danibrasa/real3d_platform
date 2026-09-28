<?php

/**
 * Comprueba que la cola se vacia: que los avisos encolados salen de verdad.
 *
 * Un formulario que responde 200 no prueba nada. El aviso a la promotora se
 * manda con ->queue(), asi que si el worker esta parado, o arranco con la
 * configuracion vieja en memoria, el lead se pierde igual y nadie se entera.
 * Eso paso en produccion y duro semanas.
 *
 * Uso: php comprobar-cola.php /ruta/de/la/app [segundos]
 */
$ruta = rtrim($argv[1] ?? '/var/www/dev', '/');
$espera = (int) ($argv[2] ?? 40);

require $ruta.'/vendor/autoload.php';
$app = require $ruta.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

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

echo "el aviso salio de la cola sin errores\n";
