<?php

use App\Support\Salud\Comprobaciones;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Las subidas que se quedan a medias no las recoge nadie: no hay ninguna
// pantalla donde alguien vaya a verlas. Un video 360 abandonado en el trozo
// cuarenta deja doscientos megas tirados.
//
// A las 4:00, antes del recorrido de comprobacion de las 4:15.
Schedule::command('subidas:limpiar --horas=24')
    ->dailyAt('04:00')
    ->withoutOverlapping();

// El latido que lee /salud. Si el planificador deja de correr -- el timer
// parado, o arrancando en un directorio que un despliegue dejo atras --
// ninguna de las tareas de arriba corre y nada lo dice: no fallan, no
// ocurren. Con esto se nota en un cuarto de hora.
Artisan::command('salud:latido', function () {
    Cache::put(Comprobaciones::CLAVE_LATIDO, time());
    $this->info('latido');
})->purpose('Deja constancia de que el planificador corre, para /salud');

Schedule::command('salud:latido')->everyFiveMinutes();

// El segundo aviso de un lead: el que lleva un dia sin que nadie le conteste.
Schedule::command('leads:sin-atender --horas=24')
    ->dailyAt('09:00')
    ->withoutOverlapping();

// La papelera de proyectos se vacia sola: lo que lleva mas de treinta dias
// se borra del todo, ficheros incluidos.
Schedule::command('proyectos:vaciar-papelera --dias='.config('proyectos.dias_en_papelera'))
    ->dailyAt('04:30')
    ->withoutOverlapping();
