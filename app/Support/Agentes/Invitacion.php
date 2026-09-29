<?php

namespace App\Support\Agentes;

use App\Mail\InvitacionDeAgente;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Un agente entra por invitacion: la promotora pone nombre y correo, el
 * agente recibe un enlace y elige su contraseña.
 *
 * Antes la promotora tecleaba la contraseña del agente y se la pasaba por
 * WhatsApp, y el tope de agentes del plan solo se descubria al chocar. El
 * agente pendiente es un usuario con token y sin aceptar: cuenta para el
 * cupo desde que se le invita, que una invitacion es un sitio reservado.
 */
class Invitacion
{
    public const DIAS_DE_VIDA = 7;

    /** Cuantos agentes permite el plan, cuantos hay (aceptados o pendientes) y cuantos quedan. */
    public static function cupo(User $promotora): array
    {
        $tope = (int) ($promotora->companyProfile?->getPlanLimits()['max_agents'] ?? 0);
        $usados = User::where('agency_id', $promotora->id)->where('role', User::ROLE_AGENTE)->count();

        return [
            'tope' => $tope,
            'usados' => $usados,
            'quedan' => max(0, $tope - $usados),
            'plan' => $promotora->companyProfile?->plan_tier,
        ];
    }

    public static function invitar(User $promotora, string $nombre, string $correo): User
    {
        // Una contraseña que nadie conoce: hasta que acepte, no hay forma de entrar.
        $agente = User::create([
            'name' => $nombre,
            'email' => $correo,
            'password' => Str::random(40),
            'role' => User::ROLE_AGENTE,
            'agency_id' => $promotora->id,
        ]);

        self::enviar($agente, $promotora);

        return $agente;
    }

    /** Un enlace nuevo; el anterior deja de valer. */
    public static function reenviar(User $agente): void
    {
        self::enviar($agente, $agente->agency);
    }

    private static function enviar(User $agente, User $promotora): void
    {
        $agente->forceFill([
            'invitacion_token' => Str::random(48),
            'invitado_en' => now(),
            'invitacion_aceptada_en' => null,
        ])->save();

        Mail::to($agente->email)->queue(new InvitacionDeAgente($agente, $promotora));
    }

    public static function pendiente(User $agente): bool
    {
        return $agente->invitacion_token !== null && $agente->invitacion_aceptada_en === null;
    }

    /** El agente de ese enlace, si el enlace sigue valiendo. */
    public static function porToken(?string $token): ?User
    {
        if (! $token) {
            return null;
        }

        $agente = User::where('invitacion_token', $token)->whereNull('invitacion_aceptada_en')->first();
        if (! $agente || ! $agente->invitado_en || $agente->invitado_en->lt(now()->subDays(self::DIAS_DE_VIDA))) {
            return null;
        }

        return $agente;
    }

    /** Elige contraseña y acepta las condiciones: desde aqui es un usuario como otro. */
    public static function aceptar(User $agente, string $password): void
    {
        $agente->forceFill([
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'legal_aceptado_en' => now(),
            'legal_version' => config('legal.version'),
            'invitacion_aceptada_en' => now(),
            'invitacion_token' => null,
        ])->save();
    }
}
