<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuyerPayment;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * El lado de la promotora: a quien se le vendio y que ha pagado.
 *
 * Lo que se registra aqui es lo que el comprador ve en su portal, asi que el
 * trabajo de subir una foto de obra o anotar un pago sirve para dos cosas:
 * llevar la cuenta y dejar de contestar el mismo WhatsApp ochenta veces.
 */
class CompradorController extends Controller
{
    /** Ficha de la vivienda vendida: comprador y pagos. */
    public function show(Project $project, Unit $unit)
    {
        Gate::authorize('create-unit');
        $this->autorizar($project, $unit);

        $unit->load(['buyer', 'payments.milestone', 'typology']);
        $project->load('paymentPlans.milestones');

        return view('admin.units.comprador', [
            'project' => $project,
            'unit' => $unit,
            'pagado' => $unit->totalPaid(),
            'pendiente' => $unit->pendingAmount(),
            'hitos' => $project->paymentPlans->flatMap->milestones,
        ]);
    }

    /**
     * Asigna el comprador a la vivienda.
     *
     * Si el correo no existe se crea la cuenta, porque obligar a la promotora a
     * dar de alta al comprador aparte seria un paso que nadie daria. La
     * contraseña se deja sin usar: el comprador entra por "he olvidado mi
     * contraseña", que ademas verifica que el correo es suyo.
     */
    public function asignar(Request $request, Project $project, Unit $unit)
    {
        Gate::authorize('create-unit');
        $this->autorizar($project, $unit);

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'sold_at' => ['nullable', 'date'],
        ]);

        $comprador = User::firstOrCreate(
            ['email' => mb_strtolower($datos['email'])],
            [
                'name' => $datos['name'],
                'password' => Hash::make(Str::random(40)),
                'role' => User::ROLE_USER,
            ]
        );

        $unit->update([
            'buyer_id' => $comprador->id,
            'sold_at' => $datos['sold_at'] ?? now()->toDateString(),
            'status' => 'sold',
        ]);

        return back()->with('success', __('buyer_admin.assigned', ['name' => $comprador->name]));
    }

    /** Quita el comprador sin borrar su cuenta ni sus pagos. */
    public function desasignar(Project $project, Unit $unit)
    {
        Gate::authorize('create-unit');
        $this->autorizar($project, $unit);

        $unit->update(['buyer_id' => null]);

        return back()->with('success', __('buyer_admin.unassigned'));
    }

    /** Anota un pago recibido. */
    public function registrarPago(Request $request, Project $project, Unit $unit)
    {
        Gate::authorize('create-unit');
        $this->autorizar($project, $unit);

        $datos = $request->validate([
            'concept' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:80'],
            'payment_milestone_id' => [
                'nullable',
                Rule::exists('payment_milestones', 'id'),
            ],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        BuyerPayment::create($datos + [
            'unit_id' => $unit->id,
            'registered_by' => $request->user()->id,
        ]);

        return back()->with('success', __('buyer_admin.payment_recorded'));
    }

    public function borrarPago(Project $project, Unit $unit, BuyerPayment $payment)
    {
        Gate::authorize('create-unit');
        $this->autorizar($project, $unit);
        abort_unless($payment->unit_id === $unit->id, 404);

        $payment->delete();

        return back()->with('success', __('buyer_admin.payment_deleted'));
    }

    private function autorizar(Project $project, Unit $unit): void
    {
        abort_unless(auth()->user()->canAccessProject($project), 403);
        abort_unless($unit->project_id === $project->id, 404);
    }
}
