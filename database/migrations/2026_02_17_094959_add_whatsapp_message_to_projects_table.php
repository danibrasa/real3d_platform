<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('whatsapp_message', 500)->nullable()->after('whatsapp_number');
            $table->string('whatsapp_message_en', 500)->nullable()->after('whatsapp_message');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_message', 'whatsapp_message_en']);
        });
    }
};
