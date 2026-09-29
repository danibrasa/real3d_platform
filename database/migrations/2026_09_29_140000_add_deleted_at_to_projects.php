<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Papelera de proyectos: treinta dias entre borrar y perder.
 *
 * Borrar proyectos era solo del equipo, y una promotora del plan gratuito
 * (un proyecto) no podia quitar el suyo para crear otro. Ahora puede, y como
 * puede, hace falta poder deshacerlo: un clic de mas no puede costar un
 * visor que tardo dias en montarse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
