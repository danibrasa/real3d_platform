<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->enum('file_type', ['video_360', 'model_3d', 'ground_texture', 'thumbnail']);
            $table->string('original_name');
            $table->string('storage_path', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->boolean('upload_complete')->default(false);
            $table->timestamps();

            $table->index(['project_id', 'file_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_files');
    }
};
