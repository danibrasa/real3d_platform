<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Agentes\Invitacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        // Un agente invitado que aun no acepto no tiene contraseña que
        // restablecer: por aqui entraria sin aceptar las condiciones. Se le
        // reenvia la invitacion y se contesta lo mismo de siempre, que este
        // formulario no debe decir quien tiene cuenta.
        $pendiente = User::where('email', $request->email)->first();
        if ($pendiente && Invitacion::pendiente($pendiente)) {
            Invitacion::reenviar($pendiente);

            return back()->with('status', __(Password::RESET_LINK_SENT));
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
