<?php

/**
 * Monta el visor subiendo un fichero por el camino de verdad, en trozos.
 *
 * Sustituye al atajo anterior, que creaba el registro a mano en la base de
 * datos. Aquel se saltaba justo el camino mas complejo del producto -init,
 * trozos, ensamblado, cuota, reemplazo- que es donde han aparecido los tres
 * fallos de hoy. Un comprobador que esquiva lo que suele romperse no comprueba
 * gran cosa.
 *
 * Uso: php subir-visor-de-prueba.php /ruta/de/la/app <id del proyecto>
 */

use App\Http\Controllers\Admin\FileUploadController;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

$ruta = rtrim($argv[1] ?? '/var/www/dev', '/');
$idProyecto = (int) ($argv[2] ?? 0);

if (! $idProyecto) {
    fwrite(STDERR, "uso: php subir-visor-de-prueba.php /ruta/de/la/app <id>\n");
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

// Solo el equipo puede subir el 3D: se actua como tal, que es lo que pasa de
// verdad. Si se hiciera como la promotora, el permiso lo rechazaria y con razon.
$equipo = User::whereIn('role', ['superadmin', 'gestor'])->first();
if (! $equipo) {
    fwrite(STDERR, "no hay nadie del equipo para subir el visor\n");
    exit(1);
}

auth()->login($equipo);

// Un PNG de 1x1 partido en tres, para que el ensamblado tenga trabajo.
$contenido = base64_decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk'
    .'+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
);
$trozos = str_split($contenido, (int) ceil(strlen($contenido) / 3));

$peticion = fn (array $datos, array $ficheros = []) => Request::create(
    '/', 'POST', $datos, [], $ficheros, ['CONTENT_TYPE' => 'multipart/form-data']
);

$controlador = app(FileUploadController::class);

$inicio = $controlador->initUpload($peticion([
    'file_type' => 'image_360',
    'original_name' => 'fondo-de-prueba.png',
    'total_size' => strlen($contenido),
    'total_chunks' => count($trozos),
]), $proyecto);

$id = json_decode($inicio->getContent(), true)['upload_id'] ?? null;
if (! $id) {
    fwrite(STDERR, 'init no devolvio identificador: '.$inicio->getContent()."\n");
    exit(1);
}

foreach ($trozos as $i => $trozo) {
    $temporal = tempnam(sys_get_temp_dir(), 'trozo');
    file_put_contents($temporal, $trozo);

    $controlador->uploadChunk(
        $peticion(['upload_id' => $id, 'chunk_index' => $i], [
            'chunk' => new UploadedFile($temporal, "chunk_{$i}", 'application/octet-stream', null, true),
        ]),
        $proyecto
    );
}

$final = $controlador->completeUpload($peticion(['upload_id' => $id]), $proyecto);

if ($final->getStatusCode() !== 200) {
    fwrite(STDERR, 'completar fallo ('.$final->getStatusCode().'): '.$final->getContent()."\n");
    exit(1);
}

// Que lo que ha quedado en disco sea lo que se subio: un modelo con un byte
// cambiado no carga, y eso no lo dice ningun codigo de estado.
$fichero = $proyecto->files()->where('file_type', 'image_360')->latest('id')->first();

if (! $fichero || Storage::get($fichero->storage_path) !== $contenido) {
    fwrite(STDERR, "el fichero reconstruido NO coincide con el subido\n");
    exit(1);
}

if ($proyecto->latitude === null || $proyecto->longitude === null) {
    $proyecto->update(['latitude' => 18.5820, 'longitude' => -68.4055]);
}

echo "visor subido por trozos e identico al original ({$fichero->file_size} bytes, ".count($trozos)." trozos)\n";
