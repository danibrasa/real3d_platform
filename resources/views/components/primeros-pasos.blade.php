@php $pasos = \App\Support\Publicacion\PrimerosPasos::de(auth()->user()); @endphp

@if ($pasos->hayQueEnseñarlos())
    <div class="mb-6 bg-white shadow-sm sm:rounded-lg border border-blue-100 overflow-hidden">
        <div class="px-6 py-4 bg-blue-50 border-b border-blue-100">
            <h3 class="font-semibold text-gray-800">{{ __('primeros_pasos.titulo') }}</h3>
            <p class="text-sm text-gray-600 mt-0.5">{{ __('primeros_pasos.entradilla') }}</p>
        </div>

        <ol class="divide-y divide-gray-100">
            @foreach ($pasos->pasos() as $i => $paso)
                <li class="flex items-start gap-3 px-6 py-4 {{ $paso['actual'] ? 'bg-blue-50/40' : '' }}">
                    <span class="shrink-0 mt-0.5 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold
                        {{ $paso['hecho'] ? 'bg-emerald-100 text-emerald-700'
                           : ($paso['actual'] ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-400') }}">
                        {{ $paso['hecho'] ? '✓' : $i + 1 }}
                    </span>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium {{ $paso['hecho'] ? 'text-gray-400 line-through' : 'text-gray-800' }}">
                            {{ __('primeros_pasos.'.$paso['clave']) }}
                        </p>
                        @if (! $paso['hecho'])
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ __('primeros_pasos.'.$paso['clave'].'_ayuda') }}
                            </p>
                        @endif
                    </div>

                    {{-- El boton solo en el paso que toca: cuatro botones a la vez
                         es otra vez no decirle por donde empezar. --}}
                    @if ($paso['actual'] && $paso['enlace'])
                        <a href="{{ $paso['enlace'] }}"
                           class="shrink-0 px-3 py-1.5 bg-blue-600 text-white rounded-md text-xs font-semibold hover:bg-blue-700 transition">
                            {{ __('primeros_pasos.'.$paso['clave'].'_boton') }}
                        </a>
                    @elseif ($paso['actual'] && $paso['de'] === \App\Support\Publicacion\ListaParaPublicar::EQUIPO)
                        <span class="shrink-0 text-xs text-gray-500 italic">
                            {{ __('primeros_pasos.esperando_al_equipo') }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
@endif
