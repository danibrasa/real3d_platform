<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->json('project_ids')->nullable()->after('abilities');
            $table->unsignedInteger('rate_limit')->default(60)->after('project_ids');
            $table->boolean('is_active')->default(true)->after('rate_limit');
            $table->string('last_used_ip')->nullable()->after('last_used_at');
            $table->string('description')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropColumn(['project_ids', 'rate_limit', 'is_active', 'last_used_ip', 'description']);
        });
    }
};
