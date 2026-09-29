<?php

namespace App\Http\Controllers;

use App\Support\Agentes\Invitacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;

/**
 * El enlace de la invitacion de un agente: elige contraseña, acepta las
 * condiciones y entra. Sin cuenta previa ni contraseña que le pasen.
 */
class InvitacionController extends Controller
{
    public function mostrar(string $token)
    {
        $agente = Invitacion::porToken($token);
        if (! $agente) {
            return response()->view('auth.invitacion-caducada', [], 410);
        }

        return view('auth.invitacion', [
            'agente' => $agente,
            'token' => $token,
            'empresa' => $agente->agency?->companyProfile?->company_name ?? $agente->agency?->name ?? config('legal.marca'),
        ]);
    }

    public function aceptar(Request $request, string $token)
    {
        $agente = Invitacion::porToken($token);
        if (! $agente) {
            return response()->view('auth.invitacion-caducada', [], 410);
        }

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // Como en el alta: sin aceptar no hay cuenta, y queda constancia.
            'acepto' => ['accepted'],
        ], [
            'acepto.accepted' => __('legal.acepto_obligatorio'),
        ]);

        Invitacion::aceptar($agente, $request->password);
        Auth::login($agente);

        return redirect('/admin')->with('success', __('agentes.bienvenida'));
    }
}
