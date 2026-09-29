<x-mail::message>
# {{ __('agentes.titulo', ['empresa' => $empresa]) }}

{{ __('agentes.cuerpo', ['quien' => $promotora->name, 'empresa' => $empresa, 'nombre' => $agente->name]) }}

<x-mail::button :url="$enlace">
{{ __('agentes.boton') }}
</x-mail::button>

{{ __('agentes.caduca', ['dias' => \App\Support\Agentes\Invitacion::DIAS_DE_VIDA]) }}

{{ __('agentes.responder', ['correo' => $promotora->email]) }}
</x-mail::message>
