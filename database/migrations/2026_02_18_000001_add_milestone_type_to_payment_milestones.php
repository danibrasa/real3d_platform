<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_milestones', function (Blueprint $table) {
            $table->string('milestone_type', 20)->default('other')->after('due_description');
        });
    }

    public function down(): void
    {
        Schema::table('payment_milestones', function (Blueprint $table) {
            $table->dropColumn('milestone_type');
        });
    }
};
