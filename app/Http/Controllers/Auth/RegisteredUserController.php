<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // Sin aceptar no hay cuenta, y queda constancia de cuando y de
            // que version: sin eso no hay forma de demostrar que se acepto.
            'acepto' => ['accepted'],
        ], [
            'acepto.accepted' => __('legal.acepto_obligatorio'),
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->forceFill([
            'legal_aceptado_en' => now(),
            'legal_version' => config('legal.version'),
        ])->save();

        event(new Registered($user));

        Mail::to($user->email)->queue(new WelcomeEmail($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
