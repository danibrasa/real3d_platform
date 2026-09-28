<?php

namespace App\Support\Facturacion;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Cuando empiezan a contar los dias de prueba.
 *
 * Empezaban al suscribirse, y eso hacia que la promotora gastara la prueba
 * esperando. El visor lo montamos nosotros: entre que lo pide y lo tiene
 * pueden pasar dias, y durante esos dias no hay nada que probar -- su panel
 * esta vacio y su pagina no se puede publicar-. Con catorce dias contados
 * desde el pago, una espera de cinco deja nueve para probar el producto y
 * catorce para pagarlo.
 *
 * Asi que la prueba se ancla cuando el visor se da por montado, que es el
 * primer momento en que existe algo que probar. A partir de ahi, catorce dias
 * completos.
 *
 * Se ancla una sola vez por empresa. Si no fuera asi, cada proyecto nuevo que
 * se diera por montado estiraria la prueba otros catorce dias y no se llegaria
 * a cobrar nunca.
 */
class PruebaGratuita
{
    /**
     * Si a esta empresa le toca empezar la prueba ahora.
     *
     * La decision va aparte de la llamada a Stripe a proposito: asi las reglas
     * -que son lo que puede equivocarse- se comprueban con tests de verdad y
     * no dependen de simular una pasarela de pago.
     */
    public static function debeAnclarse(CompanyProfile $perfil, bool $enPrueba): bool
    {
        // Ya empezo: un segundo proyecto no regala catorce dias mas.
        if ($perfil->prueba_desde !== null) {
            return false;
        }

        // Sin suscripcion en prueba no hay reloj que mover. El plan gratuito no
        // tiene prueba porque no tiene nada que probar despues.
        return $enPrueba;
    }

    /**
     * Ancla la prueba de quien pidio el visor, si le toca.
     *
     * Devuelve la fecha en que terminara la prueba, o null si no habia nada
     * que anclar.
     */
    public static function anclarAlMontarVisor(?User $usuario): ?Carbon
    {
        $perfil = $usuario?->companyProfile;

        if (! $perfil) {
            return null;
        }

        $suscripcion = $usuario->subscription('default');

        if (! self::debeAnclarse($perfil, (bool) $suscripcion?->onTrial())) {
            return null;
        }

        $fin = now()->addDays((int) config('stripe.trial_days'));

        // Primero Stripe, y solo si Stripe acepta se apunta aqui: al reves,
        // una empresa quedaria marcada como "prueba empezada" mientras la
        // pasarela sigue cobrando el dia siguiente.
        $suscripcion->extendTrial($fin);

        $perfil->update(['prueba_desde' => now()]);

        return $fin;
    }
}
