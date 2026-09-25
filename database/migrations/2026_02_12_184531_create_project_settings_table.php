<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();

            // Model transform
            $table->decimal('model_rotation', 6, 2)->default(0);
            $table->decimal('model_scale', 6, 2)->default(100);
            $table->decimal('model_elevation', 6, 2)->default(0);

            // Ground
            $table->decimal('ground_height', 6, 2)->default(0);
            $table->enum('ground_texture_type', ['grass', 'concrete', 'dirt', 'custom'])->default('grass');
            $table->decimal('ground_opacity', 5, 2)->default(100);
            $table->boolean('ground_visible')->default(true);

            // Video
            $table->decimal('video_opacity', 5, 2)->default(100);
            $table->boolean('video_autoplay')->default(true);

            // Lighting
            $table->enum('lighting_preset', ['morning', 'noon', 'evening'])->default('noon');

            // Camera
            $table->decimal('camera_position_x', 10, 2)->default(30);
            $table->decimal('camera_position_y', 10, 2)->default(20);
            $table->decimal('camera_position_z', 10, 2)->default(30);
            $table->decimal('camera_target_x', 10, 2)->default(0);
            $table->decimal('camera_target_y', 10, 2)->default(5);
            $table->decimal('camera_target_z', 10, 2)->default(0);

            // Display
            $table->boolean('wireframe')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_settings');
    }
};
