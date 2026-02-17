<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Progreso de Obra</h2>
                <p class="text-sm text-gray-500">{{ $project->name }}</p>
            </div>
            <a href="{{ route('admin.projects.edit', $project) }}" class="text-sm text-gray-600 hover:underline">&larr; Volver al proyecto</a>
        </div>
    </x-slot>

    <div class="py-6" x-data="{ showPhaseForm: false, showUpdateForm: false, editingPhase: null }">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            {{-- Overall progress --}}
            @php
                $latestUpdate = $project->constructionUpdates->first();
                $overallProgress = $latestUpdate?->progress_percentage ?? 0;
            @endphp
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold text-gray-800">Progreso general</h3>
                    <span class="text-2xl font-bold text-blue-600">{{ $overallProgress }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-4">
                    <div class="bg-blue-600 h-4 rounded-full transition-all" style="width: {{ $overallProgress }}%"></div>
                </div>
                @if($latestUpdate)
                <p class="text-xs text-gray-400 mt-2">Ultima actualizacion: {{ $latestUpdate->date->format('d/m/Y') }} - {{ $latestUpdate->title }}</p>
                @endif
            </div>

            {{-- Phases section --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Fases de construccion</h3>
                    @can('edit-project-technical')
                    <button @click="showPhaseForm = !showPhaseForm" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 transition">
                        + Nueva fase
                    </button>
                    @endcan
                </div>

                {{-- New phase form --}}
                <div x-show="showPhaseForm" x-transition class="mb-6 p-4 bg-gray-50 rounded-lg border" style="display: none;">
                    <form method="POST" action="{{ route('admin.projects.construction.phases.store', $project) }}">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                                <input type="text" name="name" required placeholder="Ej: Cimentacion" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">% del total</label>
                                <input type="number" name="target_percentage" value="0" min="0" max="100" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Descripcion</label>
                                <input type="text" name="description" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Crear fase</button>
                            <button type="button" @click="showPhaseForm = false" class="px-4 py-2 text-gray-600 text-sm hover:underline">Cancelar</button>
                        </div>
                    </form>
                </div>

                {{-- Phases list --}}
                @if($project->constructionPhases->isEmpty())
                    <p class="text-gray-400 text-sm">No hay fases definidas. Crea la primera fase para empezar a documentar el progreso.</p>
                @else
                <div class="space-y-3">
                    @foreach($project->constructionPhases as $phase)
                    <div class="border rounded-lg p-4 hover:bg-gray-50" x-data="{ editing: false }">
                        <div x-show="!editing">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                                        {{ $phase->status === 'completed' ? 'bg-green-100 text-green-700' : ($phase->status === 'in_progress' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500') }}">
                                        @if($phase->status === 'completed') &#10003; @else {{ $loop->iteration }} @endif
                                    </span>
                                    <div>
                                        <span class="font-medium text-gray-800">{{ $phase->name }}</span>
                                        <span class="text-xs text-gray-400 ml-2">{{ $phase->target_percentage }}%</span>
                                        @if($phase->description)
                                        <p class="text-xs text-gray-500">{{ $phase->description }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                        {{ $phase->status === 'completed' ? 'bg-green-100 text-green-700' : ($phase->status === 'in_progress' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500') }}">
                                        {{ $phase->status === 'completed' ? 'Completada' : ($phase->status === 'in_progress' ? 'En curso' : 'Pendiente') }}
                                    </span>
                                    @can('edit-project-technical')
                                    <button @click="editing = true" class="text-gray-400 hover:text-blue-600 text-sm">Editar</button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                        <div x-show="editing" style="display: none;">
                            <form method="POST" action="{{ route('admin.projects.construction.phases.update', [$project, $phase]) }}">
                                @csrf
                                @method('PUT')
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                    <input type="text" name="name" value="{{ $phase->name }}" required class="rounded-md border-gray-300 text-sm">
                                    <input type="number" name="target_percentage" value="{{ $phase->target_percentage }}" min="0" max="100" class="rounded-md border-gray-300 text-sm">
                                    <select name="status" class="rounded-md border-gray-300 text-sm">
                                        <option value="pending" {{ $phase->status === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                        <option value="in_progress" {{ $phase->status === 'in_progress' ? 'selected' : '' }}>En curso</option>
                                        <option value="completed" {{ $phase->status === 'completed' ? 'selected' : '' }}>Completada</option>
                                    </select>
                                    <input type="text" name="description" value="{{ $phase->description }}" placeholder="Descripcion" class="rounded-md border-gray-300 text-sm">
                                </div>
                                <div class="mt-2 flex gap-2">
                                    <button type="submit" class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">Guardar</button>
                                    <button type="button" @click="editing = false" class="px-3 py-1 text-gray-600 text-xs hover:underline">Cancelar</button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('admin.projects.construction.phases.destroy', [$project, $phase]) }}" class="mt-2" onsubmit="return confirm('Eliminar esta fase y sus actualizaciones?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:underline">Eliminar fase</button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Updates section --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Actualizaciones</h3>
                    @can('edit-project-technical')
                    @if($project->constructionPhases->isNotEmpty())
                    <button @click="showUpdateForm = !showUpdateForm" class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-md hover:bg-green-700 transition">
                        + Nueva actualizacion
                    </button>
                    @endif
                    @endcan
                </div>

                {{-- New update form --}}
                <div x-show="showUpdateForm" x-transition class="mb-6 p-4 bg-gray-50 rounded-lg border" style="display: none;">
                    <form method="POST" action="{{ route('admin.projects.construction.updates.store', $project) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fase *</label>
                                <select name="construction_phase_id" required class="w-full rounded-md border-gray-300 text-sm">
                                    @foreach($project->constructionPhases as $phase)
                                    <option value="{{ $phase->id }}">{{ $phase->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Titulo *</label>
                                <input type="text" name="title" required placeholder="Ej: Avance en cimentacion" class="w-full rounded-md border-gray-300 text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                                <input type="date" name="date" value="{{ now()->format('Y-m-d') }}" required class="w-full rounded-md border-gray-300 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Progreso general del proyecto (%) *</label>
                                <input type="number" name="progress_percentage" value="{{ $overallProgress }}" min="0" max="100" required class="w-full rounded-md border-gray-300 text-sm">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripcion</label>
                            <textarea name="description" rows="3" class="w-full rounded-md border-gray-300 text-sm" placeholder="Detalle del avance..."></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fotos (max 5MB c/u)</label>
                            <input type="file" name="images[]" multiple accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">Publicar actualizacion</button>
                            <button type="button" @click="showUpdateForm = false" class="px-4 py-2 text-gray-600 text-sm hover:underline">Cancelar</button>
                        </div>
                    </form>
                </div>

                {{-- Updates timeline --}}
                @if($project->constructionUpdates->isEmpty())
                    <p class="text-gray-400 text-sm">No hay actualizaciones. Crea fases primero y luego publica actualizaciones con fotos.</p>
                @else
                <div class="space-y-6">
                    @foreach($project->constructionUpdates as $update)
                    <div class="border-l-4 {{ $update->phase->status === 'completed' ? 'border-green-400' : 'border-blue-400' }} pl-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 class="font-semibold text-gray-800">{{ $update->title }}</h4>
                                <div class="flex items-center gap-3 text-xs text-gray-500 mt-1">
                                    <span>{{ $update->date->format('d/m/Y') }}</span>
                                    <span class="px-2 py-0.5 bg-gray-100 rounded">{{ $update->phase->name }}</span>
                                    <span class="font-medium text-blue-600">{{ $update->progress_percentage }}% general</span>
                                </div>
                            </div>
                            @can('edit-project-technical')
                            <form method="POST" action="{{ route('admin.projects.construction.updates.destroy', [$project, $update]) }}" onsubmit="return confirm('Eliminar esta actualizacion?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-400 hover:text-red-600">Eliminar</button>
                            </form>
                            @endcan
                        </div>
                        @if($update->description)
                        <p class="text-sm text-gray-600 mt-2">{{ $update->description }}</p>
                        @endif
                        @if($update->images->count())
                        <div class="flex gap-2 mt-3 overflow-x-auto">
                            @foreach($update->images as $img)
                            <img src="{{ route('admin.projects.construction.image', [$project, $img]) }}"
                                 alt="{{ $img->caption ?? 'Foto de obra' }}"
                                 class="w-32 h-24 object-cover rounded-lg shadow-sm flex-shrink-0">
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
