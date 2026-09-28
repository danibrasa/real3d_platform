<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuando una promotora pide que le monten el visor.
 *
 * El reparto acordado es que ella hace lo comercial y el equipo el 3D, pero
 * hasta ahora no habia forma de avisar: terminaba de cargar sus viviendas y se
 * quedaba mirando un aviso que decia "lo hace el equipo de Real3D", sin boton.
 * Y el equipo no tenia ninguna lista de proyectos esperando.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->timestamp('viewer_requested_at')->nullable()->after('status');
            $table->foreignId('viewer_requested_by')->nullable()->after('viewer_requested_at')
                ->constrained('users')->nullOnDelete();

            // Se consulta "los que esperan, por antiguedad": quien lleva mas
            // tiempo esperando es a quien hay que atender antes.
            $table->index('viewer_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['viewer_requested_at']);
            $table->dropConstrainedForeignId('viewer_requested_by');
            $table->dropColumn('viewer_requested_at');
        });
    }
};
