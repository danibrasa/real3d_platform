<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->decimal('bbox_center_x', 8, 6)->nullable()->after('sort_order');
            $table->decimal('bbox_center_y', 8, 6)->nullable()->after('bbox_center_x');
            $table->decimal('bbox_center_z', 8, 6)->nullable()->after('bbox_center_y');
            $table->decimal('bbox_size_x', 8, 6)->nullable()->after('bbox_center_z');
            $table->decimal('bbox_size_y', 8, 6)->nullable()->after('bbox_size_x');
            $table->decimal('bbox_size_z', 8, 6)->nullable()->after('bbox_size_y');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn([
                'bbox_center_x', 'bbox_center_y', 'bbox_center_z',
                'bbox_size_x', 'bbox_size_y', 'bbox_size_z',
            ]);
        });
    }
};
