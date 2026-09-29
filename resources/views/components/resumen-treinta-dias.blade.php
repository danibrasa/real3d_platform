@props(['resumen'])

{{-- Tres numeros, sin graficas: cuanta gente entro al visor, que miraron,
     cuantos escribieron. Ultimos treinta dias. --}}
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
    <div class="flex items-baseline justify-between mb-4">
        <h3 class="font-semibold text-gray-800">{{ __('visor.resumen_titulo') }}</h3>
        <span class="text-xs text-gray-400">{{ __('visor.resumen_periodo', ['dias' => \App\Support\Visor\ResumenDeTreintaDias::DIAS]) }}</span>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <div class="text-3xl font-bold text-gray-900 tabular-nums">{{ $resumen['visitas'] }}</div>
            <div class="text-sm text-gray-500">{{ __('visor.resumen_visitas') }}</div>
        </div>
        <div>
            @forelse ($resumen['mas_vistas'] as $v)
                <div class="text-sm text-gray-900"><span class="font-semibold">{{ $v['identificador'] }}</span> <span class="text-gray-400">· {{ $v['veces'] }}</span></div>
            @empty
                <div class="text-sm text-gray-400">—</div>
            @endforelse
            <div class="text-sm text-gray-500 mt-1">{{ __('visor.resumen_mas_vistas') }}</div>
        </div>
        <div>
            <div class="text-3xl font-bold text-emerald-700 tabular-nums">{{ $resumen['leads'] }}</div>
            <div class="text-sm text-gray-500">{{ __('visor.resumen_leads') }}</div>
        </div>
    </div>
</div>
