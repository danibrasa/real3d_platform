<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->enum('discount_type', ['percentage', 'fixed'])
                ->nullable()
                ->after('sort_order');
            $table->decimal('discount_value', 10, 2)
                ->nullable()
                ->after('discount_type');
            $table->string('discount_label')
                ->nullable()
                ->after('discount_value');
        });
    }

    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'discount_label']);
        });
    }
};
