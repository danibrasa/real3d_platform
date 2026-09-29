<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('visor.pendientes_titulo') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if ($proyectos->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">
                    {{ __('visor.pendientes_vacio') }}
                </div>
            @else
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="text-left px-4 py-3 font-medium">Proyecto</th>
                                <th class="text-left px-4 py-3 font-medium">Promotora</th>
                                <th class="text-right px-4 py-3 font-medium">Viviendas</th>
                                <th class="text-left px-4 py-3 font-medium">{{ __('material.en_cola') }}</th>
                                <th class="text-left px-4 py-3 font-medium">{{ __('visor.esperando_desde') }}</th>
                                <th class="text-left px-4 py-3 font-medium">Falta</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($proyectos as $p)
                                @php
                                    $lista = $listas[$p->id];
                                    $dias = $p->viewer_requested_at->diffInDays(now());
                                @endphp
                                <tr class="border-t border-gray-100 hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.projects.edit', $p) }}"
                                           class="font-medium text-blue-600 hover:underline">{{ $p->name }}</a>
                                        @if ($p->location)
                                            <div class="text-xs text-gray-400">{{ $p->location }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ optional($p->assignedAgencies->first()?->companyProfile)->company_name ?? '—' }}
                                        @if ($p->solicitanteDelVisor)
                                            <div class="text-xs text-gray-400">{{ $p->solicitanteDelVisor->email }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ $p->units_count }}</td>
                                    <td class="px-4 py-3">
                                        {{-- Lo que hay para montar, y donde cogerlo. Sin esto el
                                             equipo lo pedia por correo. --}}
                                        <a href="{{ route('admin.projects.material.index', $p) }}" class="text-blue-600 hover:underline">
                                            {{ $p->material_count }} {{ $p->material_count === 1 ? 'pieza' : 'piezas' }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3">
                                        {{-- Que se vea de un golpe a quien se esta haciendo esperar. --}}
                                        <span class="{{ $dias >= 7 ? 'text-amber-700 font-medium' : 'text-gray-600' }}">
                                            {{ $p->viewer_requested_at->translatedFormat('j M') }}
                                            @if ($dias > 0)
                                                <span class="text-xs">({{ $dias }} d)</span>
                                            @endif
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @forelse ($lista->bloqueos() as $b)
                                            <div class="text-xs text-amber-700">{{ __('publicacion.'.$b['clave']) }}</div>
                                        @empty
                                            {{-- Solo se puede cerrar el circulo cuando hay algo que
                                                 enseñar: dar por montado un visor que no existe manda
                                                 a la promotora a publicar una pagina vacia. --}}
                                            <form method="POST" action="{{ route('admin.projects.visor.montado', $p) }}">
                                                @csrf
                                                <button type="submit"
                                                        class="px-2.5 py-1 bg-emerald-600 text-white rounded text-xs font-semibold hover:bg-emerald-700 transition">
                                                    {{ __('visor.montado_boton') }}
                                                </button>
                                            </form>
                                        @endforelse
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $proyectos->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
