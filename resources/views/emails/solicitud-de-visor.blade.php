<x-mail::message>
# Un proyecto espera visor

**{{ $quienLoPide->name }}** ha terminado de cargar **{{ $project->name }}** y
pide que le montéis el modelo 3D y el fondo 360.

- **Viviendas cargadas:** {{ $project->units()->count() }}
- **Promotora:** {{ optional($project->assignedAgencies->first()?->companyProfile)->company_name ?? $quienLoPide->name }}
- **Ubicación:** {{ $project->location ?: 'sin indicar' }}

<x-mail::button :url="route('admin.projects.edit', $project)">
Abrir el proyecto
</x-mail::button>

Podéis responder a este correo para escribirle directamente y pedirle los planos
o los renders.

<x-mail::subcopy>
La lista completa de proyectos esperando está en
[visores pendientes]({{ route('admin.visores.pendientes') }}).
</x-mail::subcopy>
</x-mail::message>
