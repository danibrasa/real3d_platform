<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('material.titulo') }} · {{ $project->name }}</h2>
            <a href="{{ route('admin.projects.edit', $project) }}" class="text-sm text-blue-600 hover:underline">&larr; {{ $project->name }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @foreach (['success' => 'emerald', 'error' => 'red'] as $clave => $color)
                @if (session($clave))
                    <div class="p-3 bg-{{ $color }}-50 text-{{ $color }}-800 rounded-md text-sm">{{ session($clave) }}</div>
                @endif
            @endforeach

            <p class="text-sm text-gray-600 max-w-2xl">{{ __('material.entradilla') }}</p>

            {{-- Lo que hace falta, de un vistazo: que no tenga que adivinar que es "material". --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                @foreach ($tipos as $tipo)
                    @php $hay = in_array($tipo, $entregados, true); $obligatorio = in_array($tipo, $imprescindibles, true); @endphp
                    <div class="rounded-md border px-3 py-2 text-sm {{ $hay ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : ($obligatorio ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-gray-200 text-gray-500') }}">
                        <span class="mr-1">{{ $hay ? '✓' : ($obligatorio ? '!' : '·') }}</span>
                        {{ __('material.tipo_'.$tipo) }}
                        @if ($obligatorio && ! $hay)<span class="block text-xs">{{ __('material.imprescindible') }}</span>@endif
                    </div>
                @endforeach
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <form method="POST" action="{{ route('admin.projects.material.store', $project) }}" enctype="multipart/form-data" class="bg-white shadow-sm rounded-lg p-5 space-y-3">
                    @csrf
                    <h3 class="font-semibold text-gray-800">{{ __('material.subir') }}</h3>
                    <p class="text-xs text-gray-500">{{ __('material.subir_ayuda', ['mb' => $maximoMb]) }}</p>
                    <div>
                        <label for="tipo" class="block text-sm font-medium text-gray-700">{{ __('material.que_es') }}</label>
                        <select id="tipo" name="tipo" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                            @foreach ($tipos as $tipo)
                                <option value="{{ $tipo }}" {{ old('tipo') === $tipo ? 'selected' : '' }}>{{ __('material.tipo_'.$tipo) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="fichero" class="block text-sm font-medium text-gray-700">{{ __('material.fichero') }}</label>
                        <input id="fichero" name="fichero" type="file" class="mt-1 w-full text-sm">
                        <x-input-error :messages="$errors->get('fichero')" class="mt-1" />
                    </div>
                    <div>
                        <label for="enlace" class="block text-sm font-medium text-gray-700">{{ __('material.o_enlace') }}</label>
                        <input id="enlace" name="enlace" type="url" value="{{ old('enlace') }}" placeholder="https://" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                        <x-input-error :messages="$errors->get('enlace')" class="mt-1" />
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700">{{ __('material.entregar') }}</button>
                </form>

                <div class="bg-white shadow-sm rounded-lg p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">{{ __('material.entregado', ['n' => $material->count()]) }}</h3>
                    @forelse ($material as $pieza)
                        <div class="flex items-center justify-between gap-3 py-2 border-t border-gray-100 text-sm">
                            <div class="min-w-0">
                                <a href="{{ route('admin.projects.material.descargar', [$project, $pieza]) }}" class="text-blue-600 hover:underline break-all" @if ($pieza->esEnlace()) target="_blank" rel="noopener" @endif>{{ $pieza->original_name }}</a>
                                <div class="text-xs text-gray-400">
                                    {{ __('material.tipo_'.$pieza->tipo) }}
                                    @if (! $pieza->esEnlace()) · {{ number_format($pieza->file_size / 1048576, 1) }} MB @endif
                                    · {{ $pieza->created_at->format('d/m/Y') }}
                                    @if ($pieza->autor) · {{ $pieza->autor->name }} @endif
                                </div>
                            </div>
                            <form method="POST" action="{{ route('admin.projects.material.destroy', [$project, $pieza]) }}" onsubmit="return confirm('{{ __('material.quitar_confirmar') }}')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-600 hover:underline">{{ __('material.quitar') }}</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">{{ __('material.nada_todavia') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
