@php
    $currencyService = app(\App\Services\CurrencyService::class);
    $currencies = $currencyService->getAvailable();
    $currentCurrency = \App\Services\CurrencyService::getCurrentCode();
@endphp

<div x-data="{ open: false }" class="relative" @click.away="open = false">
    <button @click="open = !open" class="flex items-center gap-1 text-sm font-medium text-gray-600 hover:text-gray-800 transition px-2 py-1 rounded-md hover:bg-gray-100">
        <span>{{ $currencies[$currentCurrency]['symbol'] ?? 'USD' }}</span>
        <span class="text-xs">{{ $currentCurrency }}</span>
        <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-transition class="absolute right-0 mt-1 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50 min-w-[140px]" style="display: none;">
        @foreach($currencies as $code => $cur)
            @if($cur['is_active'])
            <a href="{{ \App\Support\QueryUrl::with(['currency' => $code]) }}"
               class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 transition {{ $currentCurrency === $code ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700' }}">
                <span class="w-8 text-right font-mono">{{ $cur['symbol'] }}</span>
                <span>{{ $code }}</span>
            </a>
            @endif
        @endforeach
    </div>
</div>
