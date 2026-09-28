<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeEmail;
use App\Models\CompanyProfile;
use App\Models\User;
use App\Support\Facturacion\PlanDeStripe;
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

        // El plan de entrada es gratuito de verdad: se activa sin pasar por
        // Stripe porque no hay nada que cobrar.
        if ($plan === CompanyProfile::PLAN_STARTER) {
            PlanDeStripe::aplicar($user->companyProfile, CompanyProfile::PLAN_STARTER);

            return redirect()->route('admin.dashboard')
                ->with('success', __('billing.subscription_activated'));
        }

        // Los de pago, por la caja de Stripe.
        $plans = config('stripe.plans');
        $priceId = $interval === 'yearly'
            ? $plans[$plan]['price_yearly_id']
            : $plans[$plan]['price_monthly_id'];

        // Sin precio configurado no se activa: antes se concedia el plan
        // "directamente", asi que un despliegue al que le faltaran las
        // variables de Stripe regalaba Enterprise a quien lo pidiera, y no lo
        // decia en ningun sitio. Mejor que no funcione a que funcione gratis.
        if (! $priceId) {
            report(new \RuntimeException(
                "Falta el precio de Stripe para el plan {$plan} ({$interval})"
            ));

            return back()->with('error', __('billing.plan_unavailable'));
        }

        $checkout = $user->newSubscription('default', $priceId);

        if (config('stripe.trial_days') > 0) {
            $checkout->trialDays(config('stripe.trial_days'));
        }

        return $checkout->checkout([
            // El identificador de la sesion, no el plan: el plan se lo
            // preguntamos a Stripe al volver.
            'success_url' => route('onboarding.complete').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('onboarding.plan'),
        ]);
    }

    /**
     * Vuelta desde el pago, al terminar el alta.
     *
     * No concede nada por lo que diga la direccion. Leia el plan de `?plan=` y
     * aplicaba sus limites sin preguntar: con la sesion abierta, visitar
     * /onboarding/complete?plan=enterprise daba el plan mas caro sin pagar. Es
     * el mismo agujero que ya se habia cerrado en el cambio de plan desde el
     * panel, pero esta puerta se quedo abierta.
     */
    public function complete(Request $request)
    {
        try {
            PlanDeStripe::aplicarSesionDePago($request->user(), $request->query('session_id'));
        } catch (\Throwable $e) {
            // Si Stripe no contesta no se deja al usuario colgado: el webhook
            // pondra los limites cuando llegue.
            report($e);
        }

        return redirect()->route('admin.dashboard')
            ->with('success', __('billing.subscription_activated'));
    }
}
