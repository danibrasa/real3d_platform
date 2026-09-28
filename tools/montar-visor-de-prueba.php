<?php

/**
 * Monta un visor minimo en un proyecto, simulando el trabajo del equipo.
 *
 * En el reparto acordado la promotora no sube el modelo 3D ni el video 360: eso
 * lo hace el equipo de Real3D. El recorrido automatico se queda ahi parado, y
 * con el se queda sin comprobar el tramo mas importante del producto: que
 * alguien pregunte por una vivienda y a la promotora le entre el aviso.
 *
 * Esto pone un fondo 360 de mentira para poder seguir. NO prueba la subida por
 * trozos: crea el registro y un fichero pequeño. La subida de verdad sigue sin
 * cubrir, y conviene recordarlo.
 *
 * Uso: php montar-visor-de-prueba.php /ruta/de/la/app <id del proyecto>
 */

use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Storage;

$ruta = rtrim($argv[1] ?? '/var/www/dev', '/');
$idProyecto = (int) ($argv[2] ?? 0);

if (! $idProyecto) {
    fwrite(STDERR, "uso: php montar-visor-de-prueba.php /ruta/de/la/app <id>\n");
    exit(2);
}

require $ruta.'/vendor/autoload.php';
$app = require $ruta.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$proyecto = Project::find($idProyecto);
if (! $proyecto) {
    fwrite(STDERR, "no existe el proyecto #{$idProyecto}\n");
    exit(1);
}

// Un PNG de 1x1 gris, suficiente para que exista el fichero. El visor no se
// juzga aqui; lo que se quiere es que la pagina publica se sirva.
$png = base64_decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk'
    .'+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
);

$destino = "projects/{$proyecto->id}/image/fondo-de-prueba.png";
Storage::put($destino, $png);

$fichero = ProjectFile::updateOrCreate(
    ['project_id' => $proyecto->id, 'file_type' => 'image_360'],
    [
        'original_name' => 'fondo-de-prueba.png',
        'storage_path' => $destino,
        'mime_type' => 'image/png',
        'file_size' => strlen($png),
        'upload_complete' => true,
    ]
);

// Sin coordenadas el proyecto se publica pero no sale en el portal, y el
// recorrido tiene que poder comprobar tambien esa parte.
if ($proyecto->latitude === null || $proyecto->longitude === null) {
    $proyecto->update(['latitude' => 18.5820, 'longitude' => -68.4055]);
}

echo "visor de prueba montado en el proyecto #{$proyecto->id} (fichero #{$fichero->id})\n";
