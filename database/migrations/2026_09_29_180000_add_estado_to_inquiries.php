<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un lead tiene estado, no solo "leido".
 *
 * Leido no dice nada de lo que importa: si alguien le ha escrito, si va a
 * visitar, si compro o si era ruido. Sin eso la bandeja es una lista que
 * crece, y a la segunda semana nadie sabe a quien le falta contestar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('estado', 20)->default('nuevo')->after('read')->index();
            $table->text('nota')->nullable()->after('estado');
            $table->timestamp('estado_en')->nullable()->after('nota');
            $table->foreignId('atendido_por')->nullable()->after('estado_en')->constrained('users')->nullOnDelete();
            $table->timestamp('avisado_sin_atender_en')->nullable()->after('atendido_por');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('atendido_por');
            $table->dropColumn(['estado', 'nota', 'estado_en', 'avisado_sin_atender_en']);
        });
    }
};
