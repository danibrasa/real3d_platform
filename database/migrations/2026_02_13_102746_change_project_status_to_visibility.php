<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add new values while keeping 'published'
        DB::statement("ALTER TABLE projects MODIFY COLUMN status ENUM('draft','published','public','private','unlisted') NOT NULL DEFAULT 'draft'");
        // Step 2: Convert existing data
        DB::statement("UPDATE projects SET status = 'public' WHERE status = 'published'");
        // Step 3: Remove 'published' from ENUM
        DB::statement("ALTER TABLE projects MODIFY COLUMN status ENUM('draft','public','private','unlisted') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE projects MODIFY COLUMN status ENUM('draft','published','public','private','unlisted') NOT NULL DEFAULT 'draft'");
        DB::statement("UPDATE projects SET status = 'published' WHERE status IN ('public','private','unlisted')");
        DB::statement("ALTER TABLE projects MODIFY COLUMN status ENUM('draft','published') NOT NULL DEFAULT 'draft'");
    }
};
