<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Expand ENUM to include all roles (keeping 'admin' temporarily)
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','superadmin','gestor','inmobiliaria','agente','user') NOT NULL DEFAULT 'user'");

        // 2. Convert existing 'admin' users to 'superadmin'
        DB::table('users')->where('role', 'admin')->update(['role' => 'superadmin']);

        // 3. Remove 'admin' from ENUM
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin','gestor','inmobiliaria','agente','user') NOT NULL DEFAULT 'user'");

        // 4. Add agency_id column (if not already present from partial run)
        if (!Schema::hasColumn('users', 'agency_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('agency_id')->nullable()->after('role')
                    ->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'agency_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['agency_id']);
                $table->dropColumn('agency_id');
            });
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','superadmin','gestor','inmobiliaria','agente','user') NOT NULL DEFAULT 'user'");
        DB::table('users')->where('role', 'superadmin')->update(['role' => 'admin']);
        DB::table('users')->whereIn('role', ['gestor', 'inmobiliaria', 'agente'])->update(['role' => 'admin']);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','user') NOT NULL DEFAULT 'user'");
    }
};
