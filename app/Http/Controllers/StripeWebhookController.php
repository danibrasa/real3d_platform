<?php

namespace App\Http\Controllers;

use App\Mail\SubscriptionConfirmation;
use App\Models\AuditLog;
use App\Models\CompanyProfile;
use App\Models\User;
use App\Support\Facturacion\PlanDeStripe;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Http\Controllers\WebhookController;

class StripeWebhookController extends WebhookController
{
    public function handleCustomerSubscriptionCreated($payload): void
    {
        parent::handleCustomerSubscriptionCreated($payload);

        $stripeId = $payload['data']['object']['customer'] ?? null;
        if (! $stripeId) {
            return;
        }

        $user = User::where('stripe_id', $stripeId)->first();
        if (! $user || ! $user->companyProfile) {
            return;
        }

        // Determine tier from price
        $priceId = $payload['data']['object']['items']['data'][0]['price']['id'] ?? null;
        $tier = PlanDeStripe::desdePrecio($priceId);

        if ($tier) {
            PlanDeStripe::aplicar($user->companyProfile, $tier);
        }

        Mail::to($user->email)->queue(new SubscriptionConfirmation($user, $tier ?? 'starter'));

        AuditLog::record('subscription_created', $user->companyProfile, null, [
            'plan_tier' => $tier,
            'stripe_subscription_id' => $payload['data']['object']['id'] ?? null,
        ]);
    }

    /**
     * El tercer aviso, que no se atendia.
     *
     * Subir o bajar de plan desde el portal de Stripe -- o desde su panel,
     * a mano -- llega como "updated", no como "created". Sin esto, una
     * promotora que subiera pagaba el plan caro y seguia con los limites
     * del barato, y una que bajara al reves.
     *
     * Un precio que no es nuestro no cambia nada: es senal de que algo no
     * cuadra, no un plan basico.
     */
    public function handleCustomerSubscriptionUpdated($payload): void
    {
        parent::handleCustomerSubscriptionUpdated($payload);

        $stripeId = $payload['data']['object']['customer'] ?? null;
        if (! $stripeId) {
            return;
        }

        $user = User::where('stripe_id', $stripeId)->first();
        if (! $user || ! $user->companyProfile) {
            return;
        }

        $priceId = $payload['data']['object']['items']['data'][0]['price']['id'] ?? null;
        $tier = PlanDeStripe::desdePrecio($priceId);
        $actual = $user->companyProfile->plan_tier;

        if (! $tier || $tier === $actual) {
            return;
        }

        PlanDeStripe::aplicar($user->companyProfile, $tier);

        AuditLog::record('subscription_updated', $user->companyProfile, ['plan_tier' => $actual], [
            'plan_tier' => $tier,
            'stripe_subscription_id' => $payload['data']['object']['id'] ?? null,
        ]);
    }

    public function handleCustomerSubscriptionDeleted($payload): void
    {
        parent::handleCustomerSubscriptionDeleted($payload);

        $stripeId = $payload['data']['object']['customer'] ?? null;
        if (! $stripeId) {
            return;
        }

        $user = User::where('stripe_id', $stripeId)->first();
        if (! $user || ! $user->companyProfile) {
            return;
        }

        $oldTier = $user->companyProfile->plan_tier;
        PlanDeStripe::aplicar($user->companyProfile, CompanyProfile::PLAN_STARTER);

        AuditLog::record('subscription_deleted', $user->companyProfile, ['plan_tier' => $oldTier], ['plan_tier' => 'starter']);
    }
}
