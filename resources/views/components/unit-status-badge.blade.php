@props(['status'])

@php
    $colors = [
        'available' => 'bg-green-100 text-green-800',
        'reserved' => 'bg-yellow-100 text-yellow-800',
        'sold' => 'bg-red-100 text-red-800',
    ];
    $labels = [
        'available' => 'Disponible',
        'reserved' => 'Reservado',
        'sold' => 'Vendido',
    ];
@endphp

<span class="px-2 py-1 text-xs rounded-full font-medium {{ $colors[$status] ?? 'bg-gray-100 text-gray-800' }}">
    {{ $labels[$status] ?? $status }}
</span>
