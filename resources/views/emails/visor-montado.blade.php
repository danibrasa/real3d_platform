<x-mail::message>
# Tu visor 3D ya está listo

El equipo de Real3D ha montado el visor de **{{ $project->name }}**. Ya puedes
revisarlo y publicarlo cuando quieras.

<x-mail::button :url="route('admin.projects.edit', $project)">
Ver el proyecto
</x-mail::button>

En la ficha verás si queda algo pendiente antes de publicar, como las
coordenadas o el correo de contacto.

Puedes responder a este correo si algo no encaja: llega directamente a quien lo
ha montado.
</x-mail::message>
