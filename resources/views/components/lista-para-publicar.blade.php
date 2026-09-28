@props(['project'])

@php
    $lista = \App\Support\Publicacion\ListaParaPublicar::de($project);
    $bloqueos = $lista->bloqueos();
    $avisos = $lista->avisos();
    $listo = $bloqueos === [] && $avisos === [];
@endphp

<div class="rounded-lg border {{ $bloqueos ? 'border-amber-300 bg-amber-50' : ($listo ? 'border-emerald-300 bg-emerald-50' : 'border-gray-200 bg-gray-50') }} p-4">
    <h4 class="text-sm font-semibold text-gray-800 mb-1">{{ __('publicacion.titulo') }}</h4>

    <p class="text-xs mb-3 {{ $bloqueos ? 'text-amber-800' : 'text-gray-600' }}">
        @if ($bloqueos)
            {{ __('publicacion.no_se_puede') }}
        @elseif ($avisos)
            {{ __('publicacion.listo_con_avisos') }}
        @else
            {{ __('publicacion.listo') }}
        @endif
    </p>

    @if ($bloqueos || $avisos)
        <ul class="space-y-2">
            {{-- Primero lo que impide publicar, luego lo que solo conviene saber. --}}
            @foreach (array_merge($bloqueos, $avisos) as $punto)
                @php $bloquea = in_array($punto, $bloqueos, true); @endphp
                <li class="flex items-start gap-2 text-xs">
                    <span class="mt-0.5 shrink-0 {{ $bloquea ? 'text-amber-600' : 'text-gray-400' }}">
                        {!! $bloquea ? '&#9888;' : '&#8226;' !!}
                    </span>
                    <span class="text-gray-700">
                        {{ __('publicacion.'.$punto['clave']) }}
                        {{-- Que quede claro a quien le toca: no tiene sentido pedirle
                             a una promotora que monte un modelo 3D. --}}
                        <span class="text-gray-400">
                            ({{ $punto['de'] === \App\Support\Publicacion\ListaParaPublicar::EQUIPO
                                ? __('publicacion.lo_hace_el_equipo')
                                : __('publicacion.lo_haces_tu') }})
                        </span>
                    </span>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- La costura del reparto. Sin este boton la promotora leia "lo hace el
         equipo de Real3D" y no tenia forma de avisar a nadie: se quedaba
         esperando a que alguien adivinara que habia terminado. --}}
    @if (collect($bloqueos)->contains(fn ($b) => $b['clave'] === 'sin_visor'))
        <div class="mt-3 pt-3 border-t border-amber-200">
            @if ($project->viewer_requested_at)
                <p class="text-xs text-emerald-800">
                    {{ __('visor.pedido_el', ['fecha' => $project->viewer_requested_at->translatedFormat('j \d\e F')]) }}
                </p>
                <form method="POST" action="{{ route('admin.projects.visor.retirar', $project) }}" class="mt-1">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-gray-500 hover:text-gray-700 underline">
                        {{ __('visor.retirar') }}
                    </button>
                </form>
            @else
                <p class="text-xs text-gray-600 mb-2">{{ __('visor.pedir_ayuda') }}</p>
                <form method="POST" action="{{ route('admin.projects.visor.pedir', $project) }}">
                    @csrf
                    <button type="submit"
                            class="px-3 py-1.5 bg-amber-600 text-white rounded-md text-xs font-semibold hover:bg-amber-700 transition">
                        {{ __('visor.pedir') }}
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>
