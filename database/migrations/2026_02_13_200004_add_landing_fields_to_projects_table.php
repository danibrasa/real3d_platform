<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('tagline')->nullable()->after('description');
            $table->integer('total_floors')->nullable()->after('tagline');
            $table->date('estimated_delivery')->nullable()->after('total_floors');
            $table->string('whatsapp_number', 20)->nullable()->after('estimated_delivery');
            $table->string('contact_email')->nullable()->after('whatsapp_number');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['tagline', 'total_floors', 'estimated_delivery', 'whatsapp_number', 'contact_email']);
        });
    }
};
