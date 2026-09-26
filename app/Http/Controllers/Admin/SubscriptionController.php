<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Inmobiliaria: view own subscription.
     */
    public function index()
    {
        $user = auth()->user();
        $profile = $user->companyProfile;
        $plans = config('stripe.plans');
        $currentPlan = $profile?->plan_tier ?? 'starter';
        $subscription = $user->subscription('default');

        $invoices = [];
        try {
            if ($user->hasStripeId()) {
                $invoices = $user->invoices();
            }
        } catch (\Exception $e) {
            // Stripe not configured or no customer
        }

        return view('admin.subscription.index', compact('user', 'profile', 'plans', 'currentPlan', 'subscription', 'invoices'));
    }

    /**
     * Superadmin: view all companies with their subscription/plan info.
     */
    public function companies()
    {
        $user = auth()->user();
        if (! $user->isSuperadmin()) {
            abort(403);
        }

        $companies = CompanyProfile::with('user')
            ->orderByDesc('created_at')
            ->paginate(20);

        $planLimits = CompanyProfile::PLAN_LIMITS;

        return view('admin.companies.subscriptions', compact('companies', 'planLimits'));
    }

    /**
     * Superadmin: update a company's plan tier directly.
     */
    public function updatePlan(Request $request, CompanyProfile $company)
    {
        $user = $request->user();
        if (! $user->isSuperadmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'plan_tier' => 'required|in:starter,professional,enterprise',
        ]);

        $this->syncPlanLimits($company, $validated['plan_tier']);

        return back()->with('success', __('billing.plan_changed'));
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:starter,professional,enterprise',
            'interval' => 'required|in:monthly,yearly',
        ]);

        $user = $request->user();
        $plan = $request->plan;
        $interval = $request->interval;
        $plans = config('stripe.plans');

        if (! isset($plans[$plan])) {
            return back()->with('error', __('billing.invalid_plan'));
        }

        $priceId = $interval === 'yearly'
            ? $plans[$plan]['price_yearly_id']
            : $plans[$plan]['price_monthly_id'];

        if (! $priceId) {
            return back()->with('error', __('billing.stripe_not_configured'));
        }

        $checkout = $user->newSubscription('default', $priceId);

        if (config('stripe.trial_days') > 0 && ! $user->subscription('default')) {
            $checkout->trialDays(config('stripe.trial_days'));
        }

        return $checkout->checkout([
            'success_url' => route('admin.subscription.success').'?plan='.$plan,
            'cancel_url' => route('admin.subscription.index'),
        ]);
    }

    public function success(Request $request)
    {
        $plan = $request->query('plan', 'starter');
        $user = $request->user();
        $profile = $user->companyProfile;
        if ($profile) {
            $this->syncPlanLimits($profile, $plan);
        }

        return redirect()->route('admin.dashboard')
            ->with('success', __('billing.subscription_activated'));
    }

    public function portal(Request $request)
    {
        $user = $request->user();

        if (! $user->hasStripeId()) {
            return back()->with('error', __('billing.no_stripe_customer'));
        }

        return $user->redirectToBillingPortal(route('admin.subscription.index'));
    }

    public function changePlan(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:starter,professional,enterprise',
            'interval' => 'required|in:monthly,yearly',
        ]);

        $user = $request->user();
        $plan = $request->plan;
        $interval = $request->interval;
        $plans = config('stripe.plans');

        $priceId = $interval === 'yearly'
            ? $plans[$plan]['price_yearly_id']
            : $plans[$plan]['price_monthly_id'];

        if (! $priceId) {
            return back()->with('error', __('billing.stripe_not_configured'));
        }

        $subscription = $user->subscription('default');
        if ($subscription) {
            $subscription->swap($priceId);
            $profile = $user->companyProfile;
            if ($profile) {
                $this->syncPlanLimits($profile, $plan);
            }

            return back()->with('success', __('billing.plan_changed'));
        }

        return back()->with('error', __('billing.no_active_subscription'));
    }

    private function syncPlanLimits(CompanyProfile $profile, string $tier): void
    {
        $limits = CompanyProfile::PLAN_LIMITS[$tier] ?? CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_STARTER];

        $profile->update([
            'plan_tier' => $tier,
            'max_projects' => $limits['max_projects'],
            'max_storage_bytes' => $limits['max_storage_bytes'],
        ]);
    }
}
