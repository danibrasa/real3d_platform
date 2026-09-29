<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuando y que version de las condiciones acepto cada cuenta.
 *
 * Aceptar es una casilla en el registro; sin fecha y version no hay forma
 * de demostrar que se acepto, ni de saber a quien pedirselo de nuevo cuando
 * los textos cambien.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('legal_aceptado_en')->nullable()->after('remember_token');
            $table->string('legal_version', 16)->nullable()->after('legal_aceptado_en');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['legal_aceptado_en', 'legal_version']);
        });
    }
};
