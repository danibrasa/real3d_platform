<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('description_en')->nullable()->after('description');
            $table->string('tagline_en', 255)->nullable()->after('tagline');
            $table->string('location_en', 255)->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['description_en', 'tagline_en', 'location_en']);
        });
    }
};
