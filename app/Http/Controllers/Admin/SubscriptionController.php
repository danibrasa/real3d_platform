<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Support\Facturacion\PlanDeStripe;
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
            // Se manda el identificador de la sesion, no el plan: el plan hay
            // que preguntarselo a Stripe, porque esta direccion la controla
            // quien navega. Stripe sustituye el hueco por el identificador real.
            'success_url' => route('admin.subscription.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('admin.subscription.index'),
        ]);
    }

    /**
     * Vuelta desde el pago de Stripe.
     *
     * Aqui NO se concede nada por lo que diga la direccion. Antes se leia el
     * plan de `?plan=` y se aplicaban sus limites sin preguntar nada, asi que
     * bastaba con visitar /admin/subscription/success?plan=enterprise, estando
     * registrado, para darse el plan mas caro sin pagar un euro.
     *
     * Ahora se le pregunta a Stripe por la sesion de pago: que exista, que sea
     * de este cliente y que este cerrada. El plan sale del precio que hay
     * dentro de la sesion.
     *
     * Quien concede de verdad sigue siendo el webhook, que llega firmado por
     * Stripe. Esto solo adelanta el resultado para que el panel no se vea con
     * los limites viejos durante los segundos que tarde en llegar.
     */
    public function success(Request $request)
    {
        $user = $request->user();

        try {
            // Las comprobaciones viven en PlanDeStripe porque la vuelta del
            // alta necesita exactamente las mismas, y estaban escritas solo
            // aqui: alli se concedia el plan por lo que dijera la direccion.
            PlanDeStripe::aplicarSesionDePago($user, $request->query('session_id'));
        } catch (\Throwable $e) {
            // Si Stripe no contesta no se deja al usuario colgado: el webhook
            // pondra los limites cuando llegue.
            report($e);
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
            // Aqui si vale el plan pedido: el swap lo cambia de verdad en
            // Stripe (con su cobro o su prorrateo) antes de tocar los limites.
            $subscription->swap($priceId);
            if ($user->companyProfile) {
                PlanDeStripe::aplicar($user->companyProfile, $plan);
            }

            return back()->with('success', __('billing.plan_changed'));
        }

        return back()->with('error', __('billing.no_active_subscription'));
    }
}
