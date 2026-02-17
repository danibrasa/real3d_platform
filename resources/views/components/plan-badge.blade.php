@props(['tier'])

@php
$colors = [
    'starter' => 'bg-gray-100 text-gray-700',
    'professional' => 'bg-blue-100 text-blue-700',
    'enterprise' => 'bg-purple-100 text-purple-700',
];
$color = $colors[$tier] ?? $colors['starter'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {$color}"]) }}>
    {{ ucfirst($tier) }}
</span>
