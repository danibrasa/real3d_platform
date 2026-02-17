<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('typology_id')->nullable()->constrained('unit_typologies')->nullOnDelete();
            $table->string('identifier', 50);
            $table->integer('floor')->default(0);
            $table->integer('bedrooms');
            $table->integer('bathrooms');
            $table->decimal('area_m2', 8, 2);
            $table->decimal('price', 12, 2);
            $table->enum('status', ['available', 'reserved', 'sold'])->default('available');
            $table->string('floor_plan_path', 500)->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
