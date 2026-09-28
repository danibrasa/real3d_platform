<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Storage;

/**
 * Borra lo que deja el recorrido de alta.
 *
 * El recorrido se da de alta como promotora y crea un proyecto cada vez que
 * corre. Sin esto, en un mes hay treinta promotoras fantasma en desarrollo y
 * nadie sabe cuales son de verdad.
 *
 * Borra tambien los ficheros, que antes no. La fila se iba y el directorio
 * projects/<id> se quedaba, asi que desarrollo acumulo cientos de ficheros
 * sueltos; y un dia un proyecto nuevo reutilizo un id y se encontro dentro el
 * visor del anterior. La aplicacion si los borra al eliminar un proyecto
 * (ProjectController::destroy); el que no lo hacia era este.
 *
 * Uso: php limpiar-recorrido.php /ruta/de/la/app
 */
$ruta = rtrim($argv[1] ?? '/var/www/dev', '/');

require $ruta.'/vendor/autoload.php';
$app = require $ruta.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// El mismo dominio que usa tools/recorrido-alta.py. Si cambia alli, cambia aqui.
const MARCA = '@recorrido-automatico.invalid';

$usuarios = User::where('email', 'like', '%'.MARCA)->get();
$proyectos = 0;
$viviendas = 0;

foreach ($usuarios as $u) {
    foreach ($u->assignedProjects as $p) {
        $viviendas += $p->units()->count();
        $p->units()->delete();
        $p->assignedAgencies()->detach();
        $p->settings()?->delete();
        Storage::deleteDirectory("projects/{$p->id}");
        $p->delete();
        $proyectos++;
    }

    $u->companyProfile?->delete();
    $u->assignedProjects()->detach();
    $u->delete();
}

// Por si algun recorrido murio a medias y dejo el proyecto sin dueño.
$huerfanos = Project::where('name', 'like', 'Recorrido automatico%')->get();
foreach ($huerfanos as $p) {
    $viviendas += $p->units()->count();
    $p->units()->delete();
    $p->settings()?->delete();
    Storage::deleteDirectory("projects/{$p->id}");
    $p->delete();
    $proyectos++;
}

printf(
    "limpieza: %d promotora(s), %d proyecto(s), %d vivienda(s)\n",
    $usuarios->count(), $proyectos, $viviendas
);
