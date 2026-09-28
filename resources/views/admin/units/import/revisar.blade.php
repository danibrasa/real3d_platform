<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Importar viviendas: {{ $project->name }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-900">
                <b>{{ $original }}</b> · {{ number_format($total, 0, ',', '.') }}
                {{ $total === 1 ? 'fila' : 'filas' }} con datos.
                Todavía no se ha creado nada: revisa lo de abajo y confirma.
            </div>

            {{-- Un folleto que pone "consultar" en alguna unidad es lo normal, y la
                 columna de precio no admite nulos: sin este aviso esas viviendas se
                 crean con un 0 que luego aparece publicado. --}}
            @if (($sinPrecio ?? 0) > 0)
                <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-900">
                    {{ $sinPrecio === 1
                        ? 'Hay 1 vivienda sin precio en el documento'
                        : 'Hay '.number_format($sinPrecio, 0, ',', '.').' viviendas sin precio en el documento' }}
                    (por ejemplo, las que ponen «consultar»). Se crearán con precio 0 y
                    aparecerán así en la web hasta que lo rellenes.
                </div>
            @endif

            <form method="POST" action="{{ route('admin.projects.units.import.confirmar', $project) }}">
                @csrf

                {{-- Paso 1: emparejar columnas --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-4">
                    <h3 class="font-semibold mb-1">Qué es cada columna</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Esto es lo que ha reconocido. Cambia lo que no cuadre; el identificador
                        es obligatorio y el resto puede quedarse vacío.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($campos as $campo => $etiqueta)
                            <div>
                                <label for="mapeo-{{ $campo }}"
                                       class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ $etiqueta }}
                                    @if ($campo === 'identifier')
                                        <span class="text-red-600">*</span>
                                    @endif
                                </label>
                                <select name="mapeo[{{ $campo }}]" id="mapeo-{{ $campo }}"
                                        class="w-full rounded-md border-gray-300 shadow-sm text-sm
                                               focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">— ninguna —</option>
                                    @foreach ($cabeceras as $i => $cabecera)
                                        <option value="{{ $i }}" @selected($mapeo[$campo] === $i)>
                                            {{ $cabecera !== '' ? $cabecera : 'Columna '.($i + 1) }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($mapeo[$campo] === null)
                                    <p class="text-xs text-amber-700 mt-1">Sin reconocer</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Paso 2: vista previa --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-4">
                    <h3 class="font-semibold mb-1">Así quedarían las primeras viviendas</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Con los precios y superficies ya interpretados. Si algo sale raro, corrige
                        el emparejamiento de arriba.
                    </p>

                    <div class="overflow-x-auto border border-gray-200 rounded-md">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-gray-600">
                                <tr>
                                    <th class="text-left font-medium px-3 py-2">Identificador</th>
                                    <th class="text-left font-medium px-3 py-2">Planta</th>
                                    <th class="text-left font-medium px-3 py-2">Dorm.</th>
                                    <th class="text-left font-medium px-3 py-2">Baños</th>
                                    <th class="text-right font-medium px-3 py-2">Superficie</th>
                                    <th class="text-right font-medium px-3 py-2">Precio</th>
                                    <th class="text-left font-medium px-3 py-2">Estado</th>
                                    <th class="text-left font-medium px-3 py-2">Tipología</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($vistaPrevia as $v)
                                    @php $repetida = in_array($v['identifier'], $existentes, true); @endphp
                                    <tr class="{{ $repetida ? 'bg-amber-50' : '' }}">
                                        <td class="px-3 py-2 font-medium">
                                            {{ $v['identifier'] !== '' ? $v['identifier'] : '—' }}
                                            @if ($repetida)
                                                <span class="text-xs text-amber-700">(ya existe)</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">{{ $v['floor'] ?? '—' }}</td>
                                        <td class="px-3 py-2">{{ $v['bedrooms'] ?? '—' }}</td>
                                        <td class="px-3 py-2">{{ $v['bathrooms'] ?? '—' }}</td>
                                        <td class="px-3 py-2 text-right tabular-nums">
                                            {{ $v['area_m2'] !== null ? number_format($v['area_m2'], 2, ',', '.').' m²' : '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-right tabular-nums">
                                            {{ $v['price'] !== null ? number_format($v['price'], 0, ',', '.') : '—' }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <x-unit-status-badge :status="$v['status']" />
                                        </td>
                                        <td class="px-3 py-2 text-gray-600">{{ $v['typology'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($total > count($vistaPrevia))
                        <p class="text-xs text-gray-500 mt-2">
                            Se muestran {{ count($vistaPrevia) }} de {{ number_format($total, 0, ',', '.') }} filas.
                        </p>
                    @endif
                </div>

                {{-- Paso 3: duplicados y confirmar --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-3">Si una vivienda ya existe</h3>
                    <div class="space-y-2 mb-6">
                        <label class="flex items-start gap-2 text-sm">
                            <input type="radio" name="duplicados" value="saltar" checked
                                   class="mt-1 text-blue-600 focus:ring-blue-500">
                            <span>
                                <b>Dejarla como está</b>
                                <span class="block text-gray-600 text-xs">
                                    Solo se crean las que no existan todavía.
                                </span>
                            </span>
                        </label>
                        <label class="flex items-start gap-2 text-sm">
                            <input type="radio" name="duplicados" value="actualizar"
                                   class="mt-1 text-blue-600 focus:ring-blue-500">
                            <span>
                                <b>Actualizarla con los datos del fichero</b>
                                <span class="block text-gray-600 text-xs">
                                    Útil para cambios de precio o de estado. No se toca el mapeo 3D.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition">
                            Importar {{ number_format($total, 0, ',', '.') }}
                            {{ $total === 1 ? 'vivienda' : 'viviendas' }}
                        </button>
                        <a href="{{ route('admin.projects.units.import.create', $project) }}"
                           class="text-sm text-gray-600 hover:underline">Subir otro fichero</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
