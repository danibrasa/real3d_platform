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
                    <a href="{{ route('admin.projects.units.import.create', $project) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md text-sm font-semibold hover:bg-gray-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Importar
                    </a>
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

            @if (session('import_resultado'))
                @php
                    $resumen = session('import_resultado');
                    $partes = [$resumen['creadas'] . ' ' . ($resumen['creadas'] === 1 ? 'vivienda creada' : 'viviendas creadas')];
                    if ($resumen['actualizadas'] > 0) {
                        $partes[] = $resumen['actualizadas'] . ' ' . ($resumen['actualizadas'] === 1 ? 'actualizada' : 'actualizadas');
                    }
                    if ($resumen['saltadas'] > 0) {
                        $partes[] = $resumen['saltadas'] . ' sin tocar por existir ya';
                    }
                    $fallos = count($resumen['errores']);
                    if ($fallos > 0) {
                        $partes[] = $fallos . ' ' . ($fallos === 1 ? 'fila no se pudo importar' : 'filas no se pudieron importar');
                    }
                @endphp
                <div class="mb-4 p-4 rounded-lg border text-sm {{ $fallos > 0 ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-green-50 border-green-200 text-green-900' }}">
                    <p class="font-semibold">Importación terminada</p>
                    <p class="mt-1">{{ implode(', ', $partes) }}.</p>
                    @if ($fallos > 0)
                        <ul class="mt-2 text-xs list-disc list-inside space-y-0.5">
                            @foreach (array_slice($resumen['errores'], 0, 10) as $error)
                                <li>Fila {{ $error['linea'] }}: {{ $error['motivo'] }}</li>
                            @endforeach
                            @if ($fallos > 10)
                                <li>y {{ $fallos - 10 }} más.</li>
                            @endif
                        </ul>
                    @endif
                </div>
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
