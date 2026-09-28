<?php

namespace App\Support\Facturacion;

use App\Models\CompanyProfile;
use App\Models\User;
use Laravel\Cashier\Cashier;

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

    /**
     * Aplica el plan de una sesion de pago, solo si Stripe la confirma.
     *
     * Vive aqui y no en un controlador porque hacen falta las mismas
     * comprobaciones en dos vueltas distintas: la del alta y la del cambio de
     * plan desde el panel. Estaba escrita solo en la segunda, asi que la
     * primera concedia el plan por lo que dijera `?plan=` en la direccion: con
     * la sesion abierta, visitar /onboarding/complete?plan=enterprise daba el
     * plan mas caro sin pagar nada. Un camino, no dos.
     *
     * Devuelve el plan aplicado, o null si no habia nada que aplicar. Quien
     * concede de verdad sigue siendo el webhook, que llega firmado por Stripe;
     * esto solo adelanta el resultado para que el panel no se vea con los
     * limites viejos mientras llega.
     */
    public static function aplicarSesionDePago(User $usuario, ?string $sesionId): ?string
    {
        // Sin cliente en Stripe no hay nada que comprobar, y ademas evita salir
        // a la red para preguntar por una sesion que no puede ser suya.
        if (! $sesionId || ! $usuario->stripe_id || ! $usuario->companyProfile) {
            return null;
        }

        $sesion = Cashier::stripe()->checkout->sessions->retrieve(
            $sesionId, ['expand' => ['line_items']]
        );

        // Que la sesion sea de quien dice serlo: sin esta comprobacion, quien
        // consiguiera el identificador de un pago ajeno se aplicaria ese plan.
        if ($sesion->customer !== $usuario->stripe_id || $sesion->status !== 'complete') {
            return null;
        }

        $plan = self::desdePrecio($sesion->line_items->data[0]->price->id ?? null);

        if (! $plan) {
            return null;
        }

        self::aplicar($usuario->companyProfile, $plan);

        return $plan;
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
