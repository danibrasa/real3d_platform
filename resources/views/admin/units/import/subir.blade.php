<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Importar viviendas: {{ $project->name }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-2">Sube tu listado de viviendas</h3>
                <p class="text-sm text-gray-600 mb-6">
                    Vale el Excel, el CSV o el PDF que ya tengas: no hace falta darle ningún formato
                    concreto. El sistema intentará reconocer las columnas y te enseñará lo que
                    ha entendido antes de crear nada.
                </p>

                <form method="POST"
                      action="{{ route('admin.projects.units.import.analizar', $project) }}"
                      enctype="multipart/form-data">
                    @csrf

                    <label for="fichero" class="block text-sm font-medium text-gray-700 mb-1">
                        Fichero
                    </label>
                    <input type="file" name="fichero" id="fichero" required
                           accept=".xlsx,.csv,.ods,.pdf,text/csv,application/pdf"
                           class="w-full text-sm border border-gray-300 rounded-md p-2
                                  file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0
                                  file:text-sm file:bg-blue-50 file:text-blue-700">
                    <p class="text-xs text-gray-500 mt-1">
                        Excel (.xlsx), CSV, ODS o PDF. Hasta {{ config('importacion.max_mb', 15) }} MB y {{ number_format(\App\Support\Import\LectorDeViviendas::MAX_FILAS, 0, ',', '.') }} viviendas.
                        <br>
                        Del PDF -un folleto, un listado de precios- se sacan las viviendas
                        automáticamente. Revísalas antes de confirmar: lo que no aparezca
                        claro en el documento se deja en blanco en vez de adivinarlo.
                    </p>

                    <div class="mt-6 rounded-md bg-gray-50 border border-gray-200 p-4">
                        <p class="text-xs font-semibold text-gray-700 mb-2">Qué suele reconocer</p>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            Identificador, planta, dormitorios, baños, superficie, precio, estado,
                            tipología y notas. Da igual cómo se llamen las columnas: entiende
                            <em>Unidad</em>, <em>Código</em>, <em>Referencia</em>,
                            <em>Precio de venta&nbsp;(€)</em>, <em>Nº&nbsp;Planta</em> y sus
                            equivalentes en inglés. Lo que no reconozca, lo emparejas tú en el
                            paso siguiente.
                        </p>
                    </div>

                    <div class="mt-6 flex items-center gap-3">
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition">
                            Continuar
                        </button>
                        <a href="{{ route('admin.projects.units.index', $project) }}"
                           class="text-sm text-gray-600 hover:underline">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
