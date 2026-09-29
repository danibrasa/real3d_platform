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
    @if ($project->visor_estado === 'para_revisar')
        {{-- El visto bueno. Antes la promotora se enteraba del
             resultado al publicarlo; ahora lo ve en borrador y decide. --}}
        <div class="mt-2 rounded-md border border-emerald-200 bg-emerald-50 p-3">
            @if ($project->visor_aprobado_en)
                <p class="text-xs text-emerald-800">{{ __('visor.aprobado_el', ['fecha' => $project->visor_aprobado_en->translatedFormat('j \d\e F')]) }}</p>
            @else
                <p class="text-xs text-emerald-900 mb-2">{{ __('visor.listo_para_revisar_texto') }}</p>
                <a href="{{ route('viewer.show', $project) }}" target="_blank" rel="noopener" class="inline-block mb-2 px-3 py-1.5 bg-emerald-600 text-white rounded-md text-xs font-semibold hover:bg-emerald-700">{{ __('visor.ver_mi_visor') }}</a>
                <form method="POST" action="{{ route('admin.projects.visor.revisado', $project) }}" class="space-y-2">
                    @csrf
                    <textarea name="comentario" rows="2" class="w-full rounded-md border-gray-300 text-xs" placeholder="{{ __('visor.comentario_ayuda') }}">{{ old('comentario') }}</textarea>
                    <x-input-error :messages="$errors->get('comentario')" class="mt-1" />
                    <div class="flex gap-2">
                        <button type="submit" name="veredicto" value="aprobado" class="px-3 py-1.5 bg-emerald-700 text-white rounded-md text-xs font-semibold hover:bg-emerald-800">{{ __('visor.aprobar') }}</button>
                        <button type="submit" name="veredicto" value="cambios" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 rounded-md text-xs font-semibold hover:bg-gray-50">{{ __('visor.pedir_cambios') }}</button>
                    </div>
                </form>
            @endif
        </div>
    @endif

    @if (collect($bloqueos)->contains(fn ($b) => $b['clave'] === 'sin_visor'))
        <div class="mt-3 pt-3 border-t border-amber-200">
            @if ($project->viewer_requested_at)
                <p class="text-xs text-emerald-800">
                    {{ __('visor.pedido_el', ['fecha' => $project->viewer_requested_at->translatedFormat('j \d\e F')]) }}
                    @if ($project->visor_estado && $project->visor_estado !== 'pedido')
                        <span class="block mt-0.5 font-medium">{{ __('visor.estado_'.$project->visor_estado) }}@if ($project->visor_objetivo) · {{ __('visor.previsto_para', ['fecha' => $project->visor_objetivo->translatedFormat('j \d\e F')]) }}@endif</span>
                    @endif
                </p>
                <form method="POST" action="{{ route('admin.projects.visor.retirar', $project) }}" class="mt-1">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-gray-500 hover:text-gray-700 underline">
                        {{ __('visor.retirar') }}
                    </button>
                </form>
            @elseif(! \App\Support\Facturacion\AccesoAlVisor::puedePedirlo(auth()->user()))
                {{-- Un boton que va a devolver un error no es un boton: se dice
                     lo que hace falta, y se lleva ahi de un clic. --}}
                <p class="text-xs text-gray-600 mb-2">{{ __('visor.pedir_ayuda') }}</p>
                <p class="text-xs text-gray-700 mb-2 font-medium">{{ __('visor.hace_falta_plan') }}</p>
                <a href="{{ route('admin.subscription.index') }}"
                   class="inline-block px-3 py-1.5 bg-amber-600 text-white rounded-md text-xs font-semibold hover:bg-amber-700 transition">
                    {{ __('visor.ver_planes') }}
                </a>
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
