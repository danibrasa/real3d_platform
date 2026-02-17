<x-app-layout>
    <x-slot name="title">Unidades: {{ $project->name }}</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Unidades: {{ $project->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.projects.edit', $project) }}" class="text-sm text-gray-600 hover:underline">&larr; Volver al proyecto</a>
                @can('manage-bbox')
                @if($project->getFileByType('model_3d'))
                <a href="{{ route('admin.projects.unit-mapping', $project) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold hover:bg-indigo-700 transition">Mapear en 3D</a>
                @endif
                @endcan
                @can('create-unit')
                <a href="{{ route('admin.projects.units.create', $project) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition">+ Nueva Unidad</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            <!-- Filters -->
            <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-4">
                <form method="GET" class="flex gap-4 items-end flex-wrap">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Piso</label>
                        <select name="floor" class="rounded-md border-gray-300 text-sm">
                            <option value="">Todos</option>
                            @foreach($floors as $f)
                                <option value="{{ $f }}" {{ request('floor') == (string)$f ? 'selected' : '' }}>Piso {{ $f }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Estado</label>
                        <select name="status" class="rounded-md border-gray-300 text-sm">
                            <option value="">Todos</option>
                            <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>Disponible</option>
                            <option value="reserved" {{ request('status') === 'reserved' ? 'selected' : '' }}>Reservado</option>
                            <option value="sold" {{ request('status') === 'sold' ? 'selected' : '' }}>Vendido</option>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200">Filtrar</button>
                    @if(request()->hasAny(['floor', 'status']))
                        <a href="{{ route('admin.projects.units.index', $project) }}" class="text-sm text-gray-500 hover:underline">Limpiar</a>
                    @endif
                </form>
            </div>

            @if($units->count())
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Piso</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipologia</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dorm.</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Banos</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Area</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Precio</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">3D</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($units as $unit)
                        <tr>
                            <td class="px-4 py-3 font-medium text-sm">{{ $unit->identifier }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $unit->floor }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $unit->typology?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $unit->bedrooms }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $unit->bathrooms }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $unit->area_m2 }} m2</td>
                            <td class="px-4 py-3 text-sm font-medium">{{ $unit->formatted_price }}</td>
                            <td class="px-4 py-3">
                                <x-unit-status-badge :status="$unit->status" />
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($unit->has_bbox)
                                    <span class="inline-block w-3 h-3 rounded-full bg-green-500" title="Bbox configurado"></span>
                                @else
                                    <span class="inline-block w-3 h-3 rounded-full bg-gray-300" title="Sin bbox"></span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex gap-2 justify-end items-center">
                                    {{-- Status change buttons based on role --}}
                                    @if(auth()->user()->isAgente())
                                        {{-- Agente: only can reserve --}}
                                        @if($unit->status !== 'reserved')
                                            <form method="POST" action="{{ route('admin.projects.units.updateStatus', [$project, $unit]) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="reserved">
                                                <button type="submit" class="text-xs text-yellow-600 hover:underline">Reservar</button>
                                            </form>
                                        @endif
                                    @else
                                        @if($unit->status !== 'available')
                                            <form method="POST" action="{{ route('admin.projects.units.updateStatus', [$project, $unit]) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="available">
                                                <button type="submit" class="text-xs text-green-600 hover:underline">Disponible</button>
                                            </form>
                                        @endif
                                        @if($unit->status !== 'reserved')
                                            <form method="POST" action="{{ route('admin.projects.units.updateStatus', [$project, $unit]) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="reserved">
                                                <button type="submit" class="text-xs text-yellow-600 hover:underline">Reservar</button>
                                            </form>
                                        @endif
                                        @if($unit->status !== 'sold')
                                            <form method="POST" action="{{ route('admin.projects.units.updateStatus', [$project, $unit]) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="sold">
                                                <button type="submit" class="text-xs text-red-600 hover:underline">Vendido</button>
                                            </form>
                                        @endif
                                    @endif

                                    @can('edit-unit-full')
                                    <a href="{{ route('admin.projects.units.edit', [$project, $unit]) }}" class="text-xs text-blue-600 hover:underline">Editar</a>
                                    @elsecan('edit-unit-commercial', $project)
                                    <a href="{{ route('admin.projects.units.edit', [$project, $unit]) }}" class="text-xs text-blue-600 hover:underline">Editar</a>
                                    @endcan

                                    @can('create-unit')
                                    <form method="POST" action="{{ route('admin.projects.units.destroy', [$project, $unit]) }}" onsubmit="return confirm('Eliminar unidad {{ $unit->identifier }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:underline">Eliminar</button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="bg-white shadow-sm sm:rounded-lg p-12 text-center">
                <p class="text-gray-500 mb-4">No hay unidades creadas.</p>
                @can('create-unit')
                <a href="{{ route('admin.projects.units.create', $project) }}" class="text-blue-600 hover:underline">Crear primera unidad</a>
                @endcan
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
