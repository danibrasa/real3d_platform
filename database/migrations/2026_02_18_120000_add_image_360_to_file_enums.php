<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE project_files MODIFY COLUMN file_type ENUM('video_360', 'model_3d', 'ground_texture', 'thumbnail', 'image_360') NOT NULL");
        DB::statement("ALTER TABLE upload_chunks MODIFY COLUMN file_type ENUM('video_360', 'model_3d', 'ground_texture', 'thumbnail', 'image_360') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE project_files MODIFY COLUMN file_type ENUM('video_360', 'model_3d', 'ground_texture', 'thumbnail') NOT NULL");
        DB::statement("ALTER TABLE upload_chunks MODIFY COLUMN file_type ENUM('video_360', 'model_3d', 'ground_texture', 'thumbnail') NOT NULL");
    }
};
