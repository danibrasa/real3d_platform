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
</div>
