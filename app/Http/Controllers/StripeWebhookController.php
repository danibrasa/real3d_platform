<?php

namespace App\Http\Controllers;

use App\Mail\SubscriptionConfirmation;
use App\Models\AuditLog;
use App\Models\CompanyProfile;
use App\Models\User;
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
        $tier = $this->resolveTierFromPrice($priceId);

        if ($tier) {
            $limits = CompanyProfile::PLAN_LIMITS[$tier] ?? CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_STARTER];
            $user->companyProfile->update([
                'plan_tier' => $tier,
                'max_projects' => $limits['max_projects'],
                'max_storage_bytes' => $limits['max_storage_bytes'],
            ]);
        }

        Mail::to($user->email)->queue(new SubscriptionConfirmation($user, $tier ?? 'starter'));

        AuditLog::record('subscription_created', $user->companyProfile, null, [
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
        $starterLimits = CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_STARTER];

        $user->companyProfile->update([
            'plan_tier' => CompanyProfile::PLAN_STARTER,
            'max_projects' => $starterLimits['max_projects'],
            'max_storage_bytes' => $starterLimits['max_storage_bytes'],
        ]);

        AuditLog::record('subscription_deleted', $user->companyProfile, ['plan_tier' => $oldTier], ['plan_tier' => 'starter']);
    }

    private function resolveTierFromPrice(?string $priceId): ?string
    {
        if (! $priceId) {
            return null;
        }

        $plans = config('stripe.plans');
        foreach ($plans as $tier => $plan) {
            if ($plan['price_monthly_id'] === $priceId || $plan['price_yearly_id'] === $priceId) {
                return $tier;
            }
        }

        return null;
    }
}
