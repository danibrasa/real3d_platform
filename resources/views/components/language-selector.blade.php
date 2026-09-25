@php
    $currentLocale = app()->getLocale();
    $locales = [
        'es' => ['label' => 'ES', 'flag' => '🇪🇸', 'name' => 'Español'],
        'en' => ['label' => 'EN', 'flag' => '🇺🇸', 'name' => 'English'],
    ];
@endphp

<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" @click.outside="open = false"
            class="inline-flex items-center gap-1 px-2 py-1 text-sm text-gray-600 hover:text-gray-800 transition rounded">
        <span>{{ $locales[$currentLocale]['flag'] }}</span>
        <span class="font-medium">{{ $locales[$currentLocale]['label'] }}</span>
        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-transition class="absolute right-0 mt-1 w-32 bg-white rounded-md shadow-lg border border-gray-200 z-50 overflow-hidden" style="display: none;">
        @foreach($locales as $code => $info)
        <a href="{{ \App\Support\QueryUrl::with(['lang' => $code]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 transition {{ $code === $currentLocale ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700' }}">
            <span>{{ $info['flag'] }}</span>
            <span>{{ $info['name'] }}</span>
        </a>
        @endforeach
    </div>
</div>
