<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // USD, DOP, EUR, CAD
            $table->string('symbol', 5); // $, RD$, €, CA$
            $table->string('name', 50);
            $table->decimal('exchange_rate', 12, 4)->default(1.0000); // rate vs USD
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('decimal_places')->default(0);
            $table->timestamps();
        });

        // Seed default currencies
        DB::table('currencies')->insert([
            [
                'code' => 'USD', 'symbol' => 'USD', 'name' => 'US Dollar',
                'exchange_rate' => 1.0000, 'is_default' => true, 'is_active' => true,
                'decimal_places' => 0, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'code' => 'DOP', 'symbol' => 'RD$', 'name' => 'Peso Dominicano',
                'exchange_rate' => 58.5000, 'is_default' => false, 'is_active' => true,
                'decimal_places' => 0, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'code' => 'EUR', 'symbol' => '€', 'name' => 'Euro',
                'exchange_rate' => 0.9200, 'is_default' => false, 'is_active' => true,
                'decimal_places' => 0, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'code' => 'CAD', 'symbol' => 'CA$', 'name' => 'Canadian Dollar',
                'exchange_rate' => 1.3600, 'is_default' => false, 'is_active' => true,
                'decimal_places' => 0, 'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
