<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La cola de trabajo del equipo: en que estado va cada visor, quien lo lleva,
 * para cuando y cuantas horas lleva.
 *
 * "Pedido" y "montado" eran los dos unicos estados y entre medias no se
 * sabia nada: ni si alguien lo habia cogido, ni cuanto costaba montar uno.
 * Y eso ultimo es lo que decide si el negocio da dinero.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('visor_estado', 20)->nullable()->after('viewer_requested_by')->index();
            $table->timestamp('visor_estado_en')->nullable()->after('visor_estado');
            $table->foreignId('visor_asignado_a')->nullable()->after('visor_estado_en')->constrained('users')->nullOnDelete();
            $table->date('visor_objetivo')->nullable()->after('visor_asignado_a');
            $table->decimal('visor_horas', 5, 1)->nullable()->after('visor_objetivo');
        });

        // Lo que ya estaba pedido entra en la cola como pedido, desde que se pidio.
        DB::table('projects')
            ->whereNotNull('viewer_requested_at')
            ->whereNull('visor_estado')
            ->update(['visor_estado' => 'pedido', 'visor_estado_en' => DB::raw('viewer_requested_at')]);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('visor_asignado_a');
            $table->dropColumn(['visor_estado', 'visor_estado_en', 'visor_objetivo', 'visor_horas']);
        });
    }
};
