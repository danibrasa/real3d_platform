<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('slug')->unique();
            $table->string('legal_name')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('country', 2)->default('DO');
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->unsignedBigInteger('storage_used_bytes')->default(0);
            $table->string('plan_tier')->default('starter');
            $table->unsignedInteger('max_projects')->default(1);
            $table->unsignedBigInteger('max_storage_bytes')->default(1073741824); // 1GB
            $table->boolean('is_verified')->default(false);
            $table->boolean('show_in_directory')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_profiles');
    }
};
