<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A quien ya tenia suscripcion se le da la prueba por empezada.
 *
 * La regla nueva es "se ancla la primera vez que un visor se da por montado, si
 * no se habia anclado antes". Para una empresa que lleva meses pagando,
 * prueba_desde tambien esta a null, asi que el primer visor que montara le
 * regalaria catorce dias gratis sin que nadie lo hubiera decidido.
 *
 * Hoy no hay ninguna fila que tocar -- no hay clientes de pago todavia -- y
 * precisamente por eso conviene dejarlo escrito ahora: cuando haya, esta
 * migracion ya habra pasado y el caso no se va a recordar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscriptions') || ! Schema::hasColumn('company_profiles', 'prueba_desde')) {
            return;
        }

        DB::table('company_profiles')
            ->whereNull('prueba_desde')
            ->whereIn('user_id', DB::table('subscriptions')->select('user_id'))
            ->update(['prueba_desde' => now()]);
    }

    public function down(): void
    {
        // No se deshace: no se puede distinguir a quien marco esta migracion de
        // quien empezo su prueba de verdad despues, y equivocarse aqui es
        // regalar catorce dias o quitarlos.
    }
};
