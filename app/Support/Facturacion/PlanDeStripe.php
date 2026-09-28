<?php

namespace App\Support\Facturacion;

use App\Models\CompanyProfile;

/**
 * Que plan corresponde a un precio de Stripe, y que limites trae.
 *
 * Vive aparte porque lo necesitan dos sitios con niveles de confianza muy
 * distintos: el webhook de Stripe, que llega firmado, y la pagina de vuelta del
 * pago, a la que llega el navegador del usuario. Tener la traduccion de precio a
 * plan en un solo lugar evita que uno de los dos se quede atras cuando se
 * añada un plan nuevo.
 */
class PlanDeStripe
{
    /**
     * El plan al que corresponde un precio, o null si no es de los nuestros.
     *
     * No cae a "starter" por defecto a proposito: un precio desconocido es
     * señal de que algo no cuadra (un plan retirado, otra cuenta de Stripe),
     * no un plan basico. Quien llama decide que hacer con esa duda.
     */
    public static function desdePrecio(?string $precio): ?string
    {
        if (! $precio) {
            return null;
        }

        foreach (config('stripe.plans', []) as $plan => $datos) {
            if (($datos['price_monthly_id'] ?? null) === $precio
                || ($datos['price_yearly_id'] ?? null) === $precio) {
                return $plan;
            }
        }

        return null;
    }

    /** Deja la ficha de empresa con los limites del plan. */
    public static function aplicar(CompanyProfile $perfil, string $plan): void
    {
        $limites = CompanyProfile::PLAN_LIMITS[$plan]
            ?? CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_STARTER];

        $perfil->update([
            'plan_tier' => $plan,
            'max_projects' => $limites['max_projects'],
            'max_storage_bytes' => $limites['max_storage_bytes'],
        ]);
    }
}
