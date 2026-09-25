<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            $table->boolean('real_scale_enabled')->default(false)->after('wireframe');
            $table->decimal('real_dimension_meters', 8, 2)->nullable()->after('real_scale_enabled');
            $table->enum('reference_axis', ['height', 'width', 'depth'])->default('height')->after('real_dimension_meters');
        });
    }

    public function down(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            $table->dropColumn(['real_scale_enabled', 'real_dimension_meters', 'reference_axis']);
        });
    }
};
