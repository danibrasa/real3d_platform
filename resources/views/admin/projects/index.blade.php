<x-app-layout>
    <x-slot name="title">Proyectos</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Proyectos</h2>
            @can('create-project')
            <a href="{{ route('admin.projects.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition">
                + Nuevo Proyecto
            </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            @if($projects->count())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($projects as $project)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="h-40 bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                        <span class="text-white text-4xl">&#127970;</span>
                    </div>
                    <div class="p-5">
                        <h3 class="font-semibold text-lg mb-1">{{ $project->name }}</h3>
                        <p class="text-sm text-gray-500 mb-2">{{ $project->location ?? 'Sin ubicacion' }}</p>
                        <div class="flex items-center justify-between">
                            @php
                                $badgeColors = ['public' => 'bg-green-100 text-green-800', 'draft' => 'bg-yellow-100 text-yellow-800', 'private' => 'bg-blue-100 text-blue-800', 'unlisted' => 'bg-gray-100 text-gray-800'];
                                $badgeLabels = ['public' => 'Publico', 'draft' => 'Borrador', 'private' => 'Privado', 'unlisted' => 'Oculto'];
                            @endphp
                            <span class="px-2 py-1 text-xs rounded-full {{ $badgeColors[$project->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $badgeLabels[$project->status] ?? $project->status }}
                            </span>
                            <div class="flex gap-2">
                                <a href="{{ route('admin.projects.edit', $project) }}" class="text-blue-600 hover:underline text-sm">Editar</a>
                                @can('delete-project')
                                <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" onsubmit="return confirm('Eliminar este proyecto?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline text-sm">Eliminar</button>
                                </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-6">{{ $projects->links() }}</div>
            @else
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                <p class="text-gray-500 mb-4">No hay proyectos todavia.</p>
                @can('create-project')
                <a href="{{ route('admin.projects.create') }}" class="text-blue-600 hover:underline">Crear el primer proyecto</a>
                @endcan
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
