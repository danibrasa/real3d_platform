<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upload_chunks', function (Blueprint $table) {
            $table->id();
            $table->uuid('upload_id')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->enum('file_type', ['video_360', 'model_3d', 'ground_texture', 'thumbnail']);
            $table->string('original_name');
            $table->unsignedInteger('total_chunks');
            $table->unsignedInteger('received_chunks')->default(0);
            $table->unsignedBigInteger('total_size');
            $table->string('temp_directory', 500);
            $table->boolean('completed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_chunks');
    }
};
