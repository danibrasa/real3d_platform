<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El visto bueno de la promotora antes de dar el visor por montado.
 *
 * Quien lo aprobo y cuando, o que cambios pidio. Sin esto el equipo daba
 * por montado y la promotora se enteraba del resultado al publicarlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->timestamp('visor_aprobado_en')->nullable()->after('visor_horas');
            $table->foreignId('visor_aprobado_por')->nullable()->after('visor_aprobado_en')->constrained('users')->nullOnDelete();
            $table->text('visor_comentario')->nullable()->after('visor_aprobado_por');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('visor_aprobado_por');
            $table->dropColumn(['visor_aprobado_en', 'visor_comentario']);
        });
    }
};
