<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Con una suscripcion de pago activa no se borra: borrar al usuario
        // de nuestra base no borra nada en Stripe, y el cobro seguiria cada
        // mes a una cuenta que ya no existe. Primero se cancela desde
        // facturacion, y entonces si.
        // Cualquier suscripcion que Stripe no haya cerrado, no solo la que
        // Cashier llama activa: una en reintento de cobro (past_due) sigue
        // cobrando, y subscribed() la da por inactiva.
        $viva = $user->subscriptions()
            ->whereNotIn('stripe_status', ['canceled', 'incomplete_expired'])
            ->exists();

        if ($viva) {
            return back()->withErrors([
                'suscripcion' => __('legal.baja_con_suscripcion'),
            ], 'userDeletion');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
