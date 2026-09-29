<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las versiones derivadas de un fichero del visor: por ahora, el fondo 360 en 2K.
 *
 * Van en la misma fila y no en filas nuevas: el fichero es uno, con su
 * version y su cache; lo que cambia es el tamaño que se pide (?tam=2k).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->json('variantes')->nullable()->after('upload_complete');
        });
    }

    public function down(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->dropColumn('variantes');
        });
    }
};
