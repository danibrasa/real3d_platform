<x-mail::message>
@if ($aprobado)
# Visor aprobado

**{{ $quienRevisa->name }}** ha visto el visor de **{{ $project->name }}** y lo da por bueno.
Ya se puede dar por montado desde la cola.
@else
# Piden cambios en el visor

**{{ $quienRevisa->name }}** ha visto el visor de **{{ $project->name }}** y pide cambios.
El proyecto vuelve a "en preparación" en la cola.
@endif

@if ($comentario)
<x-mail::panel>
{{ $comentario }}
</x-mail::panel>
@endif

<x-mail::button :url="route('admin.visores.pendientes')">
Abrir la cola
</x-mail::button>

Se le puede contestar directamente: este correo responde a {{ $quienRevisa->email }}.
</x-mail::message>
