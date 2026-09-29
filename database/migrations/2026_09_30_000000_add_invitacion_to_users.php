<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Un agente entra por invitacion: el enlace, cuando se mando y cuando se acepto.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('invitacion_token', 64)->nullable()->unique()->after('agency_id');
            $table->timestamp('invitado_en')->nullable()->after('invitacion_token');
            $table->timestamp('invitacion_aceptada_en')->nullable()->after('invitado_en');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['invitacion_token', 'invitado_en', 'invitacion_aceptada_en']);
        });
    }
};
