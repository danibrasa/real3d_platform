<x-app-layout>
    <x-slot name="title">Unidades: {{ $project->name }}</x-slot>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Unidades: {{ $project->name }}
            </h2>
            <div class="flex flex-wrap gap-2">
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

    @php
        // Quien puede tocar precio y estado desde la tabla. El agente solo
        // reserva: ve los precios sin poder editarlos.
        $agente = auth()->user()->isAgente();
        $editaComercial = $agente || auth()->user()->can('edit-unit-commercial', $project);
        $nombresDeEstado = ['available' => 'Disponible', 'reserved' => 'Reservado', 'sold' => 'Vendido'];
        $coloresDeEstado = ['available' => 'bg-green-100 text-green-800', 'reserved' => 'bg-yellow-100 text-yellow-800', 'sold' => 'bg-red-100 text-red-800'];
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @if (session('edicion_rapida'))
                @php
                    $r = session('edicion_rapida');
                    $fallos = count($r['errores']);
                    $partes = [];
                    if ($r['guardadas'] > 0) {
                        $partes[] = $r['guardadas'] . ' ' . ($r['guardadas'] === 1 ? 'vivienda guardada' : 'viviendas guardadas');
                    }
                    if ($r['sin_cambios'] > 0) {
                        $partes[] = $r['sin_cambios'] . ' sin cambios';
                    }
                    if ($fallos > 0) {
                        $partes[] = $fallos . ' ' . ($fallos === 1 ? 'no se pudo guardar' : 'no se pudieron guardar');
                    }
                @endphp
                <div class="mb-4 p-4 rounded-lg border text-sm {{ $fallos > 0 ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-green-50 border-green-200 text-green-900' }}">
                    <p class="font-semibold">{{ $partes ? implode(', ', $partes) . '.' : 'No había nada que guardar.' }}</p>
                    @if ($fallos > 0)
                        <ul class="mt-2 text-xs list-disc list-inside space-y-0.5">
                            @foreach (array_slice($r['errores'], 0, 10) as $error)
                                <li>{{ $error['vivienda'] }}: {{ $error['motivo'] }}</li>
                            @endforeach
                            @if ($fallos > 10)
                                <li>y {{ $fallos - 10 }} más.</li>
                            @endif
                        </ul>
                    @endif
                </div>
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

            <!-- Filters -->
            <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-4">
                <form method="GET" class="flex gap-4 items-end flex-wrap">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Piso</label>
                        <select name="floor" class="rounded-md border-gray-300 text-sm">
                            <option value="">Todos</option>
                            @foreach($floors as $f)
                                <option value="{{ $f }}" {{ request('floor') == $f ? 'selected' : '' }}>{{ $f }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Estado</label>
                        <select name="status" class="rounded-md border-gray-300 text-sm">
                            <option value="">Todos</option>
                            @foreach ($nombresDeEstado as $valor => $nombre)
                                <option value="{{ $valor }}" {{ request('status') === $valor ? 'selected' : '' }}>{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200">Filtrar</button>
                    @if(request()->hasAny(['floor', 'status']))
                        <a href="{{ route('admin.projects.units.index', $project) }}" class="text-sm text-gray-500 hover:underline">Limpiar</a>
                    @endif
                </form>
            </div>

            @if($units->count())
            {{-- Precio y estado se editan en la propia tabla y se guardan de una
                 vez: sesenta viviendas por su formulario son una tarde. Lo que
                 no se toca no se guarda. Los botones de borrar van fuera de
                 este formulario (atributo form=), que un formulario dentro de
                 otro no es HTML. --}}
            <form method="POST" action="{{ route('admin.projects.units.lote', $project) }}" id="edicion-rapida"
                  x-data="{ cambios: 0, marcadas: 0, contar() { this.marcadas = $el.querySelectorAll('input[name=\'sel[]\']:checked').length } }"
                  @input="cambios++">
                @csrf @method('PATCH')
            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @if ($editaComercial)
                            <th class="px-3 py-3"><input type="checkbox" class="rounded border-gray-300" aria-label="Marcar todas"
                                @change="$el.closest('table').querySelectorAll('input[name=\'sel[]\']').forEach(c => c.checked = $el.checked); contar()"></th>
                            @endif
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Piso</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipología</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dorm.</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Baños</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Área</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Precio</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">3D</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($units as $unit)
                        <tr>
                            @if ($editaComercial)
                            <td class="px-3 py-3"><input type="checkbox" name="sel[]" value="{{ $unit->id }}" class="rounded border-gray-300" aria-label="Marcar {{ $unit->identifier }}" @change="contar()" {{ in_array($unit->id, old('sel', [])) ? 'checked' : '' }}></td>
                            @endif
                            <td class="px-4 py-3 font-medium text-sm">{{ $unit->identifier }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $unit->floor }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $unit->typology?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $unit->bedrooms }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $unit->bathrooms }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $unit->area_m2 }} m2</td>
                            <td class="px-4 py-3 text-sm font-medium">
                                @if ($editaComercial && ! $agente)
                                    <input type="number" name="v[{{ $unit->id }}][price]" value="{{ old('v.'.$unit->id.'.price', $unit->price) }}" step="0.01" min="0" placeholder="consultar"
                                           class="w-32 rounded-md border-gray-300 text-sm py-1" aria-label="Precio de {{ $unit->identifier }}">
                                @else
                                    {{ $unit->formatted_price }}
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($editaComercial)
                                    <select name="v[{{ $unit->id }}][status]" class="text-xs rounded-full border-0 py-1 pl-2 pr-7 {{ $coloresDeEstado[$unit->status] ?? '' }}" aria-label="Estado de {{ $unit->identifier }}">
                                        @foreach ($nombresDeEstado as $valor => $nombre)
                                            {{-- El agente solo reserva: su lista lleva lo que hay y "Reservado". --}}
                                            @if (! $agente || $valor === 'reserved' || $valor === $unit->status)
                                                <option value="{{ $valor }}" {{ old('v.'.$unit->id.'.status', $unit->status) === $valor ? 'selected' : '' }}>{{ $nombre }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                @else
                                    <x-unit-status-badge :status="$unit->status" />
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($unit->has_bbox)
                                    <span class="inline-block w-3 h-3 rounded-full bg-green-500" title="Situada en el 3D"></span>
                                @else
                                    <span class="inline-block w-3 h-3 rounded-full bg-gray-300" title="Sin situar en el 3D"></span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex gap-2 justify-end items-center">
                                    @can('edit-unit-full')
                                    @pmv('pagos')
<a href="{{ route('admin.projects.units.comprador', [$project, $unit]) }}" class="text-emerald-600 hover:underline text-xs">{{ __('buyer_admin.buyer') }}</a>
@endpmv
                                    <a href="{{ route('admin.projects.units.edit', [$project, $unit]) }}" class="text-xs text-blue-600 hover:underline">Editar</a>
                                    @elsecan('edit-unit-commercial', $project)
                                    <a href="{{ route('admin.projects.units.edit', [$project, $unit]) }}" class="text-xs text-blue-600 hover:underline">Editar</a>
                                    @endcan

                                    @can('create-unit')
                                    <button type="submit" form="borrar-{{ $unit->id }}" class="text-xs text-red-600 hover:underline">Eliminar</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($editaComercial)
            <div class="mt-4 bg-white shadow-sm sm:rounded-lg p-4 flex flex-wrap items-center gap-3 text-sm">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition"
                        :class="{ 'ring-2 ring-blue-300': cambios > 0 }">Guardar cambios</button>
                <span class="text-xs text-gray-500" x-show="cambios > 0" x-cloak>Hay cambios sin guardar.</span>

                @if (! $agente)
                <span class="hidden sm:inline-block w-px h-6 bg-gray-200"></span>
                {{-- El lote: lo mismo para todas las marcadas, que "todas las de la
                     planta 3 a vendido" o "un 5% mas" no es cosa de ir fila a fila. --}}
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-gray-600">Con las <b x-text="marcadas"></b> marcadas:</span>
                    <select name="lote_accion" class="rounded-md border-gray-300 text-sm py-1" x-data="{ accion: '{{ old('lote_accion') }}' }" x-model="accion" x-ref="accion" aria-label="Cambio en lote">
                        <option value="">elige un cambio</option>
                        <option value="estado">poner estado</option>
                        <option value="porcentaje">cambiar el precio un %</option>
                    </select>
                    <select name="lote_estado" class="rounded-md border-gray-300 text-sm py-1" aria-label="Estado para el lote">
                        @foreach ($nombresDeEstado as $valor => $nombre)
                            <option value="{{ $valor }}" {{ old('lote_estado') === $valor ? 'selected' : '' }}>{{ $nombre }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="lote_porcentaje" step="0.1" min="-90" max="300" placeholder="+5 o -10" value="{{ old('lote_porcentaje') }}"
                           class="w-28 rounded-md border-gray-300 text-sm py-1" aria-label="Porcentaje para el lote">
                    <span class="text-gray-500">%</span>
                    <button type="submit" class="px-3 py-1.5 bg-gray-800 text-white rounded-md text-xs font-semibold hover:bg-gray-900">Aplicar y guardar</button>
                </div>
                @endif
            </div>
            @endif
            </form>

            @can('create-unit')
                @foreach($units as $unit)
                    <form method="POST" action="{{ route('admin.projects.units.destroy', [$project, $unit]) }}" id="borrar-{{ $unit->id }}" onsubmit="return confirm('Eliminar unidad {{ $unit->identifier }}?')">
                        @csrf @method('DELETE')
                    </form>
                @endforeach
            @endcan
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
