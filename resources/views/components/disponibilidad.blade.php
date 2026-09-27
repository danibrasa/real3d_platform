@props([
    'project',
    'variante' => 'completa',   // completa | linea
])

@php
    // Se cuenta sobre lo que ya hay cargado si es posible, para no repetir consultas.
    $unidades = $project->relationLoaded('units') ? $project->units : null;

    $total = $unidades?->count() ?? $project->units()->count();
    $disponibles = $unidades
        ? $unidades->where('status', 'available')->count()
        : $project->units()->where('status', 'available')->count();
    $reservadas = $unidades
        ? $unidades->where('status', 'reserved')->count()
        : $project->units()->where('status', 'reserved')->count();

    $colocadas = $total - $disponibles;
    $porcentaje = $total > 0 ? round($colocadas / $total * 100) : 0;
@endphp

@if ($total > 0)
    @if ($variante === 'linea')
        {{-- Una linea, para tarjetas y listados --}}
        <span {{ $attributes->merge(['class' => 'text-sm text-gray-600']) }}>
            <span class="font-semibold text-gray-900">{{ $disponibles }}</span>
            {{ __('portal.of') }} {{ $total }} {{ __('portal.available_lower') }}
        </span>
    @else
        {{-- Bloque con barra, para la ficha del proyecto --}}
        <div {{ $attributes->merge(['class' => 'rounded-lg border border-gray-200 bg-white p-4']) }}>
            <div class="flex items-baseline justify-between gap-3 mb-2">
                <p class="text-sm font-semibold text-gray-900">
                    {{ $disponibles }} {{ __('portal.of') }} {{ $total }} {{ __('portal.units_available') }}
                </p>
                @if ($porcentaje > 0)
                    <p class="text-xs text-gray-500 tabular-nums">
                        {{ $porcentaje }}% {{ __('portal.placed') }}
                    </p>
                @endif
            </div>

            {{-- La barra enseña lo ya colocado: es el dato que da confianza --}}
            <div class="h-2 w-full rounded-full bg-gray-100 overflow-hidden" role="presentation">
                <div class="h-full rounded-full bg-emerald-500 transition-all"
                     style="width: {{ $porcentaje }}%"></div>
            </div>

            @if ($reservadas > 0)
                <p class="text-xs text-gray-500 mt-2">
                    {{ trans_choice('portal.reserved_now', $reservadas, ['count' => $reservadas]) }}
                </p>
            @endif
        </div>
    @endif
@endif
