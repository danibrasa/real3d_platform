<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// La primera vez que se atendio el lead. estado_en se mueve con cada cambio
// de estado; esto no. Lo ya atendido hereda su estado_en, que es lo mas
// cerca que hay.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->timestamp('contestado_en')->nullable()->after('estado_en');
        });
        DB::table('inquiries')->whereNotNull('estado_en')->where('estado', '!=', 'nuevo')->update(['contestado_en' => DB::raw('estado_en')]);
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn('contestado_en');
        });
    }
};
