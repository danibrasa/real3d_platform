<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Una vivienda puede no tener precio, y eso no es un precio de cero.
 *
 * La columna era NOT NULL, asi que "aun no tiene precio" y "vale 0" eran lo
 * mismo en la base de datos. En la web salia publicado un "0", que es peor que
 * no poner nada: un hueco se entiende, un cero parece una oferta.
 *
 * Salio al importar folletos en PDF, donde una unidad que pone "Consultar" es lo
 * mas normal del mundo, pero el Excel tenia el mismo problema con una celda
 * vacia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->nullable()->change();
        });

        // Los ceros que ya hay se dejan como estan: aqui no hay forma de saber
        // cuales son "sin precio" y cuales son un cero puesto a proposito, y
        // convertirlos a null seria decidir por alguien que no esta.
    }

    public function down(): void
    {
        DB::table('units')->whereNull('price')->update(['price' => 0]);

        Schema::table('units', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->nullable(false)->change();
        });
    }
};
