<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El comprador entra en el sistema.
 *
 * Hasta ahora una vivienda pasaba a "sold" y ahi terminaba todo: no habia a
 * quien avisar ni nada que enseñarle. Pero en obra nueva la venta es el
 * principio de dos o tres años de espera con dinero puesto, y ese tiempo lo
 * pasa el comprador escribiendo por WhatsApp para saber como va.
 */
return new class extends Migration
{
    public function up(): void
    {
        // El comprador de cada vivienda. Uno por vivienda: si compran dos
        // personas, una figura como titular y la otra se añade cuando haga
        // falta (no complicamos lo que todavia no se ha pedido).
        Schema::table('units', function (Blueprint $table) {
            $table->foreignId('buyer_id')->nullable()->after('typology_id')
                ->constrained('users')->nullOnDelete();
            $table->date('sold_at')->nullable()->after('status');
        });

        // Los pagos que el comprador ha hecho de verdad, frente al plan teorico
        // del proyecto. Sin esto solo se puede enseñar "deberias haber pagado",
        // que no es lo que quiere ver quien ya pago.
        Schema::create('buyer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_milestone_id')->nullable()
                ->constrained('payment_milestones')->nullOnDelete();
            $table->string('concept');              // por si no corresponde a un hito del plan
            $table->decimal('amount', 12, 2);
            $table->date('paid_on');
            $table->string('reference', 80)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('registered_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['unit_id', 'paid_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_payments');

        Schema::table('units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('buyer_id');
            $table->dropColumn('sold_at');
        });
    }
};
