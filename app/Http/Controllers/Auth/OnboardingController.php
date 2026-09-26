<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeEmail;
use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;

class OnboardingController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register-saas');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => User::ROLE_INMOBILIARIA,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('onboarding.company');
    }

    public function showCompanyForm()
    {
        $user = auth()->user();

        // If already has company profile, go to plan selection
        if ($user->companyProfile) {
            return redirect()->route('onboarding.plan');
        }

        return view('auth.onboarding-company');
    }

    public function storeCompany(Request $request)
    {
        $user = $request->user();

        if ($user->companyProfile) {
            return redirect()->route('onboarding.plan');
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'country' => 'required|string|size:2',
            'city' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
        ]);

        CompanyProfile::create([
            'user_id' => $user->id,
            'company_name' => $validated['company_name'],
            'phone' => $validated['phone'],
            'country' => $validated['country'],
            'city' => $validated['city'],
            'website' => $validated['website'] ?? null,
            'plan_tier' => CompanyProfile::PLAN_STARTER,
            'max_projects' => 1,
            'max_storage_bytes' => CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_STARTER]['max_storage_bytes'],
        ]);

        Mail::to($user->email)->queue(new WelcomeEmail($user));

        return redirect()->route('onboarding.plan');
    }

    public function showPlanSelection()
    {
        $user = auth()->user();
        if (! $user->companyProfile) {
            return redirect()->route('onboarding.company');
        }

        $plans = config('stripe.plans');

        return view('auth.onboarding-plan', compact('plans'));
    }

    public function selectPlan(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:starter,professional,enterprise',
            'interval' => 'required|in:monthly,yearly',
        ]);

        $user = $request->user();
        $plan = $request->plan;
        $interval = $request->interval;

        // Starter plan activates directly (free tier or minimal)
        if ($plan === 'starter') {
            $limits = CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_STARTER];
            $user->companyProfile->update([
                'plan_tier' => CompanyProfile::PLAN_STARTER,
                'max_projects' => $limits['max_projects'],
                'max_storage_bytes' => $limits['max_storage_bytes'],
            ]);

            return redirect()->route('admin.dashboard')
                ->with('success', __('billing.subscription_activated'));
        }

        // Pro/Enterprise → Stripe Checkout
        $plans = config('stripe.plans');
        $priceId = $interval === 'yearly'
            ? $plans[$plan]['price_yearly_id']
            : $plans[$plan]['price_monthly_id'];

        if (! $priceId) {
            // If Stripe not configured, activate directly
            $limits = CompanyProfile::PLAN_LIMITS[$plan];
            $user->companyProfile->update([
                'plan_tier' => $plan,
                'max_projects' => $limits['max_projects'],
                'max_storage_bytes' => $limits['max_storage_bytes'],
            ]);

            return redirect()->route('admin.dashboard')
                ->with('success', __('billing.subscription_activated'));
        }

        $checkout = $user->newSubscription('default', $priceId);

        if (config('stripe.trial_days') > 0) {
            $checkout->trialDays(config('stripe.trial_days'));
        }

        return $checkout->checkout([
            'success_url' => route('onboarding.complete').'?plan='.$plan,
            'cancel_url' => route('onboarding.plan'),
        ]);
    }

    public function complete(Request $request)
    {
        $plan = $request->query('plan', 'starter');
        $user = $request->user();

        if ($user->companyProfile) {
            $limits = CompanyProfile::PLAN_LIMITS[$plan] ?? CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_STARTER];
            $user->companyProfile->update([
                'plan_tier' => $plan,
                'max_projects' => $limits['max_projects'],
                'max_storage_bytes' => $limits['max_storage_bytes'],
            ]);
        }

        return redirect()->route('admin.dashboard')
            ->with('success', __('billing.subscription_activated'));
    }
}
