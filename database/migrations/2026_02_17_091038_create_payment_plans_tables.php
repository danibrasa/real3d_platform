<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('payment_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_plan_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('percentage', 5, 2);
            $table->string('description')->nullable();
            $table->string('due_description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_milestones');
        Schema::dropIfExists('payment_plans');
    }
};
