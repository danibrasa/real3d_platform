<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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
