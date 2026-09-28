<?php

/**
 * Borra lo que deja el recorrido de alta.
 *
 * El recorrido se da de alta como promotora y crea un proyecto cada vez que
 * corre. Sin esto, en un mes hay treinta promotoras fantasma en desarrollo y
 * nadie sabe cuales son de verdad.
 *
 * Uso: php limpiar-recorrido.php /ruta/de/la/app
 */
$ruta = rtrim($argv[1] ?? '/var/www/dev', '/');

require $ruta.'/vendor/autoload.php';
$app = require $ruta.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// El mismo dominio que usa tools/recorrido-alta.py. Si cambia alli, cambia aqui.
const MARCA = '@recorrido-automatico.invalid';

$usuarios = App\Models\User::where('email', 'like', '%'.MARCA)->get();
$proyectos = 0;
$viviendas = 0;

foreach ($usuarios as $u) {
    foreach ($u->assignedProjects as $p) {
        $viviendas += $p->units()->count();
        $p->units()->delete();
        $p->assignedAgencies()->detach();
        $p->settings()?->delete();
        $p->delete();
        $proyectos++;
    }

    $u->companyProfile?->delete();
    $u->assignedProjects()->detach();
    $u->delete();
}

// Por si algun recorrido murio a medias y dejo el proyecto sin dueño.
$huerfanos = App\Models\Project::where('name', 'like', 'Recorrido automatico%')->get();
foreach ($huerfanos as $p) {
    $viviendas += $p->units()->count();
    $p->units()->delete();
    $p->settings()?->delete();
    $p->delete();
    $proyectos++;
}

printf(
    "limpieza: %d promotora(s), %d proyecto(s), %d vivienda(s)\n",
    $usuarios->count(), $proyectos, $viviendas
);
