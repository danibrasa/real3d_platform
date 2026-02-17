<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('rental_yield_annual', 5, 2)->nullable()->after('analytics_id');
            $table->decimal('average_occupancy', 5, 2)->nullable()->after('rental_yield_annual');
            $table->decimal('appreciation_rate_annual', 5, 2)->nullable()->after('average_occupancy');
            $table->decimal('management_fee', 5, 2)->nullable()->after('appreciation_rate_annual');
            $table->decimal('property_tax_rate', 5, 2)->nullable()->after('management_fee');
            $table->decimal('avg_nightly_rate', 8, 2)->nullable()->after('property_tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'rental_yield_annual',
                'average_occupancy',
                'appreciation_rate_annual',
                'management_fee',
                'property_tax_rate',
                'avg_nightly_rate',
            ]);
        });
    }
};
