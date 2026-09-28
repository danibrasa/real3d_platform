<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A quien ya era cliente de pago se le da la prueba por empezada.
 *
 * La regla nueva es "se ancla la primera vez que un visor se da por montado, si
 * no se habia anclado antes". Para una empresa que lleva meses pagando,
 * prueba_desde tambien esta a null, asi que el primer visor que se le montara
 * le regalaria catorce dias gratis sin que nadie lo hubiera decidido.
 *
 * Importa mucho a QUIEN se le marca. Tener una fila en subscriptions no es ser
 * cliente: ahi estan tambien la suscripcion que se cancelo a los dos dias y la
 * que se quedo en incomplete porque la tarjeta no paso. A esa gente quitarle la
 * prueba seria justo lo contrario de lo que se busca, porque no llego a probar
 * nada. Asi que solo cuentan los estados en los que la suscripcion de verdad
 * estuvo viva.
 *
 * Hoy no hay ninguna fila que tocar -- no hay clientes de pago todavia -- y
 * precisamente por eso conviene dejarlo escrito ahora: cuando los haya, esta
 * migracion ya habra pasado y el caso no se va a recordar.
 */
return new class extends Migration
{
    /**
     * Estados en los que la empresa llego a tener el producto en marcha.
     *
     * Fuera quedan canceled, incomplete, incomplete_expired y unpaid: ninguno
     * de esos llego a darle un producto que probar.
     */
    private const VIVA = ['active', 'trialing', 'past_due'];

    public function up(): void
    {
        if (! Schema::hasTable('subscriptions') || ! Schema::hasColumn('company_profiles', 'prueba_desde')) {
            return;
        }

        DB::table('company_profiles')
            ->whereNull('prueba_desde')
            ->whereIn('user_id', DB::table('subscriptions')
                ->whereIn('stripe_status', self::VIVA)
                ->select('user_id'))
            ->update(['prueba_desde' => now()]);
    }

    public function down(): void
    {
        // No se deshace: no se puede distinguir a quien marco esta migracion de
        // quien empezo su prueba de verdad despues, y equivocarse aqui es
        // regalar catorce dias o quitarlos.
    }
};
