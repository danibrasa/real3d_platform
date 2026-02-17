<x-app-layout>
    <x-slot name="title">Tipologias: {{ $project->name }}</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Tipologias: {{ $project->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.projects.edit', $project) }}" class="text-sm text-gray-600 hover:underline">&larr; Volver al proyecto</a>
                @can('manage-typologies')
                <a href="{{ route('admin.projects.typologies.create', $project) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition">+ Nueva Tipologia</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            @if($typologies->count())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($typologies as $typology)
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    @if($typology->floor_plan_path)
                        <div class="h-40 bg-gray-100 flex items-center justify-center overflow-hidden">
                            <img src="{{ route('admin.projects.typologies.edit', [$project, $typology]) }}" alt="Plano" class="h-full w-full object-contain p-2" onerror="this.parentElement.innerHTML='<span class=\'text-gray-400 text-4xl\'>&#128208;</span>'">
                        </div>
                    @else
                        <div class="h-40 bg-gray-100 flex items-center justify-center">
                            <span class="text-gray-400 text-4xl">&#128208;</span>
                        </div>
                    @endif
                    <div class="p-4">
                        <h3 class="font-semibold text-lg mb-2">{{ $typology->name }}</h3>
                        <div class="grid grid-cols-3 gap-2 text-sm text-gray-600 mb-3">
                            <div>
                                <span class="font-medium">{{ $typology->bedrooms }}</span> Dorm.
                            </div>
                            <div>
                                <span class="font-medium">{{ $typology->bathrooms }}</span> Banos
                            </div>
                            <div>
                                <span class="font-medium">{{ $typology->area_m2 }}</span> m2
                            </div>
                        </div>
                        @if($typology->description)
                            <p class="text-sm text-gray-500 mb-3 line-clamp-2">{{ $typology->description }}</p>
                        @endif
                        <div class="text-xs text-gray-400 mb-3">{{ $typology->units_count }} unidades asociadas</div>
                        @can('manage-typologies')
                        <div class="flex gap-2">
                            <a href="{{ route('admin.projects.typologies.edit', [$project, $typology]) }}" class="text-sm text-blue-600 hover:underline">Editar</a>
                            <form method="POST" action="{{ route('admin.projects.typologies.destroy', [$project, $typology]) }}" onsubmit="return confirm('Eliminar esta tipologia?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:underline">Eliminar</button>
                            </form>
                        </div>
                        @endcan
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="bg-white shadow-sm sm:rounded-lg p-12 text-center">
                <p class="text-gray-500 mb-4">No hay tipologias creadas.</p>
                @can('manage-typologies')
                <a href="{{ route('admin.projects.typologies.create', $project) }}" class="text-blue-600 hover:underline">Crear primera tipologia</a>
                @endcan
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
