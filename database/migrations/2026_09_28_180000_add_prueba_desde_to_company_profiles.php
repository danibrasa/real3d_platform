<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuando empezo a contar la prueba de esta empresa.
 *
 * Nulo mientras no haya empezado. Sirve para que se ancle una sola vez: sin
 * esta marca, cada proyecto que se diera por montado estiraria la prueba otros
 * catorce dias y no se llegaria a cobrar nunca.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->timestamp('prueba_desde')->nullable()->after('plan_tier');
        });
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn('prueba_desde');
        });
    }
};
