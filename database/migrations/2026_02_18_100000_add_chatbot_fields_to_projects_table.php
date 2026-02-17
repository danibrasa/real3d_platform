<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('chatbot_enabled')->default(true)->after('analytics_id');
            $table->text('chatbot_welcome_es')->nullable()->after('chatbot_enabled');
            $table->text('chatbot_welcome_en')->nullable()->after('chatbot_welcome_es');
            $table->text('chatbot_instructions')->nullable()->after('chatbot_welcome_en');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['chatbot_enabled', 'chatbot_welcome_es', 'chatbot_welcome_en', 'chatbot_instructions']);
        });
    }
};
