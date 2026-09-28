<?php

namespace App\Support\Facturacion;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Cuando empiezan a contar los dias de prueba.
 *
 * Empezaban al pagar, y eso hacia que la promotora gastara la prueba
 * esperandonos. El visor lo montamos nosotros: entre que lo pide y lo tiene
 * pueden pasar dias en los que su panel esta vacio y su pagina no se puede
 * publicar, asi que no hay nada que probar. Catorce dias desde el pago, con
 * cinco de espera, dejaban nueve de prueba y catorce de cobro.
 *
 * Ahora la prueba se ancla cuando el visor se da por montado, que es el primer
 * momento en que existe algo que probar.
 *
 * Para que eso funcione hacen falta dos piezas y la primera version solo tenia
 * una. Aqui se mueve el final de la prueba; pero si la suscripcion se crea con
 * una prueba de catorce dias y nosotros tardamos quince en montar el visor, al
 * llegar aqui ya se le esta cobrando y mover el final llega tarde. Por eso la
 * suscripcion nace con una prueba larga -- stripe.trial_espera_dias -- que es
 * solo el tiempo que nos damos para montarlo, y es esta clase la que la recorta
 * a los dias de verdad en cuanto hay visor. La promotora siempre tiene sus
 * catorce dias completos con el producto delante.
 *
 * Se ancla una sola vez por empresa. Sin esa marca, cada proyecto nuevo que se
 * diera por montado estiraria la prueba otros catorce dias y no se llegaria a
 * cobrar nunca.
 */
class PruebaGratuita
{
    /**
     * Si a esta empresa le toca empezar la prueba ahora.
     *
     * La decision va aparte de la llamada a Stripe a proposito: asi las reglas
     * -que son lo que puede equivocarse- se comprueban con tests de verdad y no
     * dependen de simular una pasarela de pago.
     *
     * No se mira si la suscripcion sigue en prueba. Se miraba, y era el fallo:
     * una espera larga dejaba onTrial() en falso justo cuando por fin habia
     * visor, asi que no se anclaba nada y la promotora se quedaba pagando sin
     * haber llegado a probar. Exactamente lo que esto venia a arreglar.
     */
    public static function debeAnclarse(CompanyProfile $perfil, bool $haySuscripcion): bool
    {
        // Ya empezo: un segundo proyecto no regala catorce dias mas.
        if ($perfil->prueba_desde !== null) {
            return false;
        }

        // Sin suscripcion no hay reloj que mover. El plan gratuito no tiene
        // prueba porque no se acaba nunca.
        return $haySuscripcion;
    }

    /**
     * Ancla la prueba de quien pidio el visor, si le toca.
     *
     * Devuelve la fecha en que terminara, o null si no habia nada que anclar.
     */
    public static function anclarAlMontarVisor(?User $usuario): ?Carbon
    {
        $perfil = $usuario?->companyProfile;

        if (! $perfil) {
            return null;
        }

        return self::anclar($perfil, $usuario->subscription('default'));
    }

    /**
     * El anclaje en si, con la suscripcion ya resuelta.
     *
     * Recibe la suscripcion en vez de buscarla para que se pueda comprobar de
     * verdad: que se llama a extendTrial con la fecha correcta, y que la marca
     * en la base de datos no se pone si Stripe falla. Con la suscripcion
     * buscada aqui dentro, eso solo se podia afirmar leyendo el codigo.
     *
     * @param  object|null  $suscripcion  algo con canceled() y extendTrial()
     */
    public static function anclar(CompanyProfile $perfil, $suscripcion): ?Carbon
    {
        $sirve = $suscripcion !== null && ! $suscripcion->canceled();

        if (! self::debeAnclarse($perfil, $sirve)) {
            return null;
        }

        $fin = now()->addDays((int) config('stripe.trial_days'));

        // Primero Stripe, y solo si Stripe acepta se apunta aqui. Al reves, una
        // empresa quedaria marcada como "prueba empezada" mientras la pasarela
        // sigue con la fecha vieja y le cobra al dia siguiente.
        $suscripcion->extendTrial($fin);

        $perfil->update(['prueba_desde' => now()]);

        return $fin;
    }
}
