@if($project->getFileByType('model_3d'))
@push('importmap')
<script type="importmap">
{
    "imports": {
        "three": "https://cdn.jsdelivr.net/npm/three@0.162.0/build/three.module.js",
        "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.162.0/examples/jsm/"
    }
}
</script>
@endpush
@endif

@push('head')
{{-- SEO Meta --}}
@php
    $seoDesc = $project->translated_tagline ?? Str::limit($project->translated_description, 160) ?? __('landing.seo_default');
    $seoImage = $project->thumbnail_path ? url('/storage/' . $project->thumbnail_path) : ($project->galleryImages->first() ? url('/api/projects/' . $project->id . '/gallery/' . $project->galleryImages->first()->id) : null);
    $availableUnits = $project->units->where('status', 'available');
    $priceMin = $availableUnits->min('price');
    $priceMax = $availableUnits->max('price');
@endphp

<!-- OG Meta Tags -->
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $project->name }}">
<meta property="og:description" content="{{ $seoDesc }}">
<meta property="og:url" content="{{ route('viewer.landing', $project->slug) }}">
@if($seoImage)
<meta property="og:image" content="{{ $seoImage }}">
@endif
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $project->name }}">
<meta name="twitter:description" content="{{ $seoDesc }}">
@if($seoImage)
<meta name="twitter:image" content="{{ $seoImage }}">
@endif

{{-- JSON-LD RealEstateListing --}}
@php
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'RealEstateListing',
        'name' => $project->name,
        'description' => Str::limit($project->description, 300),
        'url' => route('viewer.landing', $project->slug),
        'numberOfRooms' => $availableUnits->pluck('bedrooms')->unique()->sort()->implode(', '),
        'provider' => ['@type' => 'Organization', 'name' => 'RealEstate 3D', 'url' => url('/')],
    ];
    if ($seoImage) $jsonLd['image'] = $seoImage;
    if ($project->location) $jsonLd['address'] = ['@type' => 'PostalAddress', 'addressLocality' => $project->location];
    if ($priceMin) $jsonLd['offers'] = ['@type' => 'AggregateOffer', 'priceCurrency' => 'USD', 'lowPrice' => $priceMin, 'highPrice' => $priceMax ?? 0, 'offerCount' => $availableUnits->count()];

    $breadcrumb = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Proyectos', 'item' => route('viewer.index')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $project->name, 'item' => route('viewer.landing', $project->slug)],
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

<!-- Google Analytics -->
<x-analytics :project="$project" />
<meta name="project-id" content="{{ $project->id }}">

{{-- Currency config --}}
@php
    $currencyService = app(\App\Services\CurrencyService::class);
    $currentCurrency = \App\Services\CurrencyService::getCurrentCode();
    $currencies = $currencyService->getAvailable();
@endphp
<script>
    window.__currency = {
        current: @json($currentCurrency),
        rate: {{ $currencyService->getRate($currentCurrency) }},
        symbol: @json($currencies[$currentCurrency]['symbol'] ?? 'USD'),
        currencies: @json(collect($currencies)->filter(fn($c) => $c['is_active'])->map(fn($c) => ['symbol' => $c['symbol'], 'rate' => $c['exchange_rate']])),
    };
    window.formatPrice = function(usdAmount, currencyCode) {
        const c = currencyCode ? window.__currency.currencies[currencyCode] : window.__currency;
        if (!c) return 'USD ' + Number(usdAmount).toLocaleString('en-US', {maximumFractionDigits: 0});
        const rate = c.rate || 1;
        const symbol = c.symbol || 'USD';
        return symbol + ' ' + Math.round(usdAmount * rate).toLocaleString('en-US');
    };
</script>
@endpush

<x-app-layout>
    <x-slot name="title">{{ $project->name }}</x-slot>
    <x-slot name="metaDescription">{{ $project->translated_tagline ?? Str::limit($project->translated_description, 160) ?? __('landing.seo_default') }}</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $project->name }}</h2>
                @if($project->translated_location)
                    <p class="text-sm text-gray-500">{{ $project->translated_location }}</p>
                @endif
            </div>
            <a href="{{ route('viewer.index') }}" class="text-sm text-gray-600 hover:underline">&larr; {{ __('general.all_projects') }}</a>
        </div>
    </x-slot>

    <div>
        <!-- Hero -->
        <div class="bg-gradient-to-br from-blue-600 to-indigo-800 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ $project->name }}</h1>
                @if($project->translated_tagline)
                    <p class="text-xl md:text-2xl text-blue-100 mb-6">{{ $project->translated_tagline }}</p>
                @endif
                @if($project->translated_location)
                    <p class="text-lg text-blue-200 mb-8">{{ $project->translated_location }}</p>
                @endif
                <a href="{{ route('viewer.show', $project->slug) }}" class="inline-flex items-center px-8 py-4 bg-white text-blue-700 rounded-lg text-lg font-semibold hover:bg-blue-50 transition shadow-lg">
                    {{ __('landing.view_3d') }} &rarr;
                </a>
            </div>
        </div>

        <!-- Stats -->
        <div class="bg-white border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-6 text-center">
                    <div>
                        <div class="text-3xl font-bold text-gray-800">{{ $stats['total_units'] }}</div>
                        <div class="text-sm text-gray-500 mt-1">{{ __('landing.total_units') }}</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-green-600">{{ $stats['available'] }}</div>
                        <div class="text-sm text-gray-500 mt-1">{{ __('landing.available') }}</div>
                    </div>
                    @if($priceMin)
                    <div>
                        <div class="text-2xl font-bold text-gray-800" data-currency-amount="{{ $priceMin }}">
                            <span class="currency-formatted">{{ app(\App\Services\CurrencyService::class)->format($priceMin, \App\Services\CurrencyService::getCurrentCode()) }}</span>
                        </div>
                        <div class="text-sm text-gray-500 mt-1">{{ __('general.from') }}</div>
                    </div>
                    @endif
                    @if($project->total_floors)
                    <div>
                        <div class="text-3xl font-bold text-gray-800">{{ $project->total_floors }}</div>
                        <div class="text-sm text-gray-500 mt-1">{{ __('landing.floors') }}</div>
                    </div>
                    @endif
                    @if($project->estimated_delivery)
                    <div>
                        <div class="text-2xl font-bold text-gray-800">{{ $project->estimated_delivery->format('M Y') }}</div>
                        <div class="text-sm text-gray-500 mt-1">{{ __('landing.estimated_delivery') }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-16">
            <!-- Description -->
            @if($project->translated_description)
            <section>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">{{ __('landing.about_project') }}</h2>
                <div class="prose max-w-none text-gray-600">
                    {!! nl2br(e($project->translated_description)) !!}
                </div>
            </section>
            @endif

            <!-- Investment Calculator -->
            <x-investment-calculator :project="$project" :priceMin="$priceMin" />

            <!-- Construction Progress -->
            <x-construction-progress :project="$project" />

            <!-- Payment Plans -->
            <x-payment-plans :project="$project" :priceMin="$priceMin" />

            <!-- Gallery -->
            @if($project->galleryImages->count())
            <section>
                <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ __('landing.gallery') }}</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($project->galleryImages as $img)
                    <div class="cursor-pointer rounded-lg overflow-hidden shadow-sm hover:shadow-md transition gallery-thumb" data-src="/api/projects/{{ $project->id }}/gallery/{{ $img->id }}">
                        <img src="/api/projects/{{ $project->id }}/gallery/{{ $img->id }}" alt="{{ $img->caption ?? 'Imagen del proyecto' }}" class="w-full h-48 object-cover" loading="lazy">
                        @if($img->caption)
                            <div class="p-2 bg-white text-xs text-gray-600">{{ $img->caption }}</div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </section>
            @endif

            <!-- Embedded 3D Viewer -->
            @if($project->getFileByType('model_3d'))
            <section id="viewer-3d-section" class="relative rounded-xl overflow-hidden shadow-lg" style="height: 50vh; background: #000;">
                {{-- Mobile lazy load placeholder --}}
                <div id="viewer-lazy-placeholder" class="md:hidden absolute inset-0 z-[110] flex flex-col items-center justify-center bg-gray-900 cursor-pointer" onclick="startViewer3D()">
                    @if($project->thumbnail_path)
                    <img src="/storage/{{ $project->thumbnail_path }}" alt="{{ $project->name }}" class="absolute inset-0 w-full h-full object-cover opacity-60">
                    @elseif($project->galleryImages->first())
                    <img src="/api/projects/{{ $project->id }}/gallery/{{ $project->galleryImages->first()->id }}" alt="{{ $project->name }}" class="absolute inset-0 w-full h-full object-cover opacity-60">
                    @endif
                    <div class="relative z-10 text-center">
                        <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-blue-600/80 flex items-center justify-center shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </div>
                        <p class="text-white font-semibold text-sm">{{ __('landing.tap_to_load_3d') }}</p>
                        <p class="text-gray-400 text-xs mt-1">{{ __('landing.interactive_model') }}</p>
                    </div>
                </div>
                <div id="canvas-container" style="width: 100%; height: 100%;"></div>
                <div id="loading-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 100;">
                    <div style="width: 40px; height: 40px; border: 3px solid rgba(79,195,247,0.2); border-top-color: #4fc3f7; border-radius: 50%; animation: viewer-spin 0.8s linear infinite;"></div>
                    <p id="loading-text" style="color: #aaa; margin-top: 16px; font-size: 14px;">{{ __('landing.loading_3d') }}</p>
                </div>
                {{-- Quality downgrade notification --}}
                <div id="quality-note" style="display:none; position: absolute; top: 12px; right: 12px; z-index: 20; background: rgba(0,0,0,0.7); color: #fbbf24; padding: 6px 12px; border-radius: 6px; font-size: 11px;">
                    {{ __('landing.quality_reduced') }}
                </div>
                <div style="position: absolute; bottom: 12px; left: 50%; transform: translateX(-50%); z-index: 10; background: rgba(0,0,0,0.5); backdrop-filter: blur(10px); padding: 8px 20px; border-radius: 8px; font-size: 12px; color: #888; border: 1px solid rgba(255,255,255,0.05); white-space: nowrap;">
                    {{ __('landing.viewer_controls') }}
                </div>
                <style>
                    @keyframes viewer-spin { to { transform: rotate(360deg); } }
                </style>
            </section>
            @endif

            <!-- Units (QW5: Alpine.js filters) -->
            @if($project->units->count())
            <section x-data="unitFilters()" x-init="init()">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ __('landing.available_units') }}</h2>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('landing.bedrooms') }}</label>
                            <select x-model="filters.bedrooms" @change="applyFilters()" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">{{ __('general.all') }}</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3+</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('landing.max_price') }}</label>
                            <select x-model="filters.priceMax" @change="applyFilters()" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">{{ __('landing.no_limit') }}</option>
                                <template x-for="opt in priceOptions" :key="opt.value">
                                    <option :value="opt.value" x-text="opt.label"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('landing.min_area') }}</label>
                            <select x-model="filters.areaMin" @change="applyFilters()" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">{{ __('landing.no_minimum') }}</option>
                                <option value="30">30+ m2</option>
                                <option value="50">50+ m2</option>
                                <option value="70">70+ m2</option>
                                <option value="100">100+ m2</option>
                                <option value="150">150+ m2</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('landing.status') }}</label>
                            <select x-model="filters.status" @change="applyFilters()" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">{{ __('general.all') }}</option>
                                <option value="available">{{ __('landing.status_available') }}</option>
                                <option value="reserved">{{ __('landing.status_reserved') }}</option>
                                <option value="sold">{{ __('landing.status_sold') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <p class="text-xs text-gray-500">
                            {{ __('landing.showing') }} <span x-text="visibleCount"></span> {{ __('landing.of') }} {{ $project->units->count() }} {{ __('landing.units_label') }}
                        </p>
                        <button @click="clearFilters()" x-show="hasActiveFilters()" class="text-xs text-blue-600 hover:underline">{{ __('general.clear_filters') }}</button>
                    </div>
                </div>

                @php
                    $unitsByFloor = $project->units->groupBy('floor');
                @endphp

                @foreach($unitsByFloor as $floor => $floorUnits)
                <div class="mb-8" x-show="floorHasVisible({{ $floor }})" x-transition>
                    <h3 class="text-lg font-semibold text-gray-700 mb-3">
                        {{ $floor == 0 ? __('landing.ground_floor') : __('landing.floor_number', ['n' => $floor]) }}
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white rounded-lg overflow-hidden shadow-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-2 py-3 w-8"></th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('landing.unit') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('landing.type') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('landing.beds') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('landing.baths') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('landing.area') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('landing.price') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('landing.status') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($floorUnits as $unit)
                                <tr class="hover:bg-gray-50 cursor-pointer unit-row"
                                    x-show="isVisible({{ $unit->id }})" x-transition
                                    data-unit-id="{{ $unit->id }}" data-unit-name="{{ $unit->identifier }}" data-unit-price="{{ $unit->formatted_price }}" data-unit-bedrooms="{{ $unit->bedrooms }}" data-unit-bathrooms="{{ $unit->bathrooms }}" data-unit-area="{{ $unit->area_m2 }}" data-unit-typology="{{ $unit->typology?->name ?? '' }}" data-unit-status="{{ $unit->status }}" data-unit-floor="{{ $unit->floor }}" data-unit-floor-plan="{{ $unit->floor_plan ? '/api/units/' . $unit->id . '/floor-plan' : '' }}" data-unit-raw-price="{{ $unit->price }}" data-unit-pdf="{{ route('viewer.unit.pdf', [$project->slug, $unit->id]) }}" @if($unit->has_bbox) data-unit-bbox="{{ json_encode(['cx'=>$unit->bbox_center_x,'cy'=>$unit->bbox_center_y,'cz'=>$unit->bbox_center_z,'sx'=>$unit->bbox_size_x,'sy'=>$unit->bbox_size_y,'sz'=>$unit->bbox_size_z]) }}" @endif>
                                    <td class="px-2 py-3">
                                        <input type="checkbox" @click.stop="toggleCompare({{ $unit->id }})"
                                               :checked="compareList.includes({{ $unit->id }})"
                                               :disabled="!compareList.includes({{ $unit->id }}) && compareList.length >= 3"
                                               class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 disabled:opacity-30 cursor-pointer">
                                    </td>
                                    <td class="px-4 py-3 font-medium text-sm">{{ $unit->identifier }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $unit->typology?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $unit->bedrooms }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $unit->bathrooms }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $unit->area_m2 }} m2</td>
                                    <td class="px-4 py-3 text-sm font-semibold" x-text="formatPrice({{ $unit->price }})">{{ app(\App\Services\CurrencyService::class)->format($unit->price, \App\Services\CurrencyService::getCurrentCode()) }}</td>
                                    <td class="px-4 py-3">
                                        <x-unit-status-badge :status="$unit->status" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <!-- PDF download -->
                                            <a href="{{ route('viewer.unit.pdf', [$project->slug, $unit->id]) }}" @click.stop title="Descargar ficha PDF" class="text-gray-400 hover:text-red-600 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            </a>
                                            <!-- Share button -->
                                            <button @click.stop="shareUnit({{ $unit->id }}, '{{ $unit->identifier }}')" class="text-gray-400 hover:text-blue-600 transition" title="Copiar link">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach

                <!-- Share toast -->
                <div x-show="showToast" x-transition.opacity class="fixed bottom-20 left-1/2 -translate-x-1/2 z-50 bg-gray-800 text-white px-4 py-2 rounded-lg text-sm shadow-lg" style="display:none;">
                    {{ __('general.link_copied') }}
                </div>

                {{-- Comparison floating bar --}}
                <div x-show="compareList.length > 0" x-transition
                     class="fixed bottom-0 md:bottom-4 left-1/2 -translate-x-1/2 z-40 bg-white border border-gray-200 rounded-t-lg md:rounded-lg shadow-xl px-4 py-3 w-full md:w-auto md:max-w-lg"
                     style="display: none;">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="text-sm text-gray-600" x-text="compareList.length + ' de 3 seleccionadas'"></span>
                        <div class="flex gap-1">
                            <template x-for="uid in compareList" :key="uid">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                                    <span x-text="getUnitName(uid)"></span>
                                    <button @click="toggleCompare(uid)" class="text-blue-500 hover:text-blue-700">&times;</button>
                                </span>
                            </template>
                        </div>
                        <button @click="openCompare()" :disabled="compareList.length < 2"
                                class="px-4 py-1.5 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 transition disabled:opacity-40 disabled:cursor-not-allowed">
                            Comparar
                        </button>
                    </div>
                </div>

                {{-- Comparison modal --}}
                <div x-show="compareOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/50" @click="compareOpen = false" style="display: none;"></div>
                <div x-show="compareOpen" x-transition
                     class="fixed inset-2 md:inset-8 z-50 bg-white rounded-xl shadow-2xl overflow-y-auto"
                     style="display: none;">
                    <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
                        <h3 class="text-lg font-bold text-gray-800">{{ __('landing.compare_units') }}</h3>
                        <div class="flex items-center gap-3">
                            <button @click="shareCompare()" class="text-sm text-blue-600 hover:underline">{{ __('landing.share_comparison') }}</button>
                            <button @click="compareOpen = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm" x-ref="compareTable">
                                <thead>
                                    <tr>
                                        <th class="text-left py-2 px-3 text-gray-500 font-medium text-xs uppercase w-32"></th>
                                        <template x-for="uid in compareList" :key="uid">
                                            <th class="text-center py-2 px-3">
                                                <div class="font-bold text-gray-800" x-text="'Unidad ' + getUnitName(uid)"></div>
                                                <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                                      :class="{
                                                          'bg-green-100 text-green-700': getUnitData(uid).status === 'available',
                                                          'bg-yellow-100 text-yellow-700': getUnitData(uid).status === 'reserved',
                                                          'bg-red-100 text-red-700': getUnitData(uid).status === 'sold',
                                                      }"
                                                      x-text="{'available':'Disponible','reserved':'Reservado','sold':'Vendido'}[getUnitData(uid).status]"></span>
                                            </th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="field in compareFields" :key="field.key">
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-2.5 px-3 text-gray-500 font-medium text-xs uppercase" x-text="field.label"></td>
                                            <template x-for="uid in compareList" :key="uid">
                                                <td class="py-2.5 px-3 text-center"
                                                    :class="isBestValue(field.key, uid) ? 'text-green-700 font-bold bg-green-50' : 'text-gray-700'"
                                                    x-text="getCompareValue(field.key, uid)">
                                                </td>
                                            </template>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        {{-- Action buttons per unit --}}
                        <div class="mt-6 grid gap-4" :style="'grid-template-columns: 100px repeat(' + compareList.length + ', 1fr)'">
                            <div></div>
                            <template x-for="uid in compareList" :key="uid">
                                <div class="text-center space-y-2">
                                    <a :href="getUnitData(uid).pdf" class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-50 text-red-700 rounded text-xs font-medium hover:bg-red-100 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        PDF
                                    </a>
                                    <a href="#contacto" @click="compareOpen = false; selectInquiryUnit(uid)" class="inline-block px-3 py-1.5 bg-blue-600 text-white rounded text-xs font-medium hover:bg-blue-700 transition">{{ __('landing.contact') }}</a>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </section>
            @endif

            {{-- Currency disclaimer --}}
            @if(\App\Services\CurrencyService::getCurrentCode() !== 'USD')
            @php $cs = app(\App\Services\CurrencyService::class); $cc = \App\Services\CurrencyService::getCurrentCode(); @endphp
            <div class="text-xs text-gray-400 text-center -mt-8 mb-4">
                {{ __('landing.currency_disclaimer', ['currency' => $cc, 'rate' => number_format($cs->getRate($cc), 2), 'code' => $cc]) }}
            </div>
            @endif

            <!-- Contact Form -->
            <section id="contacto">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ __('landing.contact') }}</h2>

                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
                @endif

                <div class="bg-white rounded-lg shadow-sm p-6 max-w-2xl">
                    <form method="POST" action="{{ route('viewer.inquiry', $project->slug) }}">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('landing.your_name') }} *</label>
                                <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('landing.email') }} *</label>
                                <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('landing.phone') }}</label>
                                <input type="tel" name="phone" value="{{ old('phone') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('landing.unit_of_interest') }}</label>
                                <select name="unit_id" id="inquiry-unit-select" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <option value="">{{ __('landing.general') }}</option>
                                    @foreach($project->units->where('status', 'available') as $unit)
                                        <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->identifier }} - {{ app(\App\Services\CurrencyService::class)->format($unit->price, \App\Services\CurrencyService::getCurrentCode()) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('landing.message') }}</label>
                            <textarea name="message" rows="4" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="{{ __('landing.message_placeholder') }}">{{ old('message') }}</textarea>
                            @error('message') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="px-8 py-3 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition">{{ __('landing.send_inquiry') }}</button>
                    </form>
                </div>
            </section>

            <!-- CTA -->
            <section class="text-center py-8">
                <a href="{{ route('viewer.show', $project->slug) }}" class="inline-flex items-center px-10 py-4 bg-blue-600 text-white rounded-lg text-lg font-semibold hover:bg-blue-700 transition shadow-lg">
                    {{ __('landing.view_in_3d') }} &rarr;
                </a>
            </section>
        </div>

        {{-- Share buttons (floating sidebar/bottom bar) --}}
        <x-share-buttons
            :url="route('viewer.landing', $project->slug)"
            :title="$project->name . ($project->translated_location ? ' ' . __('general.in') . ' ' . $project->translated_location : '')"
            :text="($project->translated_tagline ?? $project->name) . ($priceMin ? ' | ' . __('general.from') . ' USD ' . number_format($priceMin, 0, '.', ',') : '')"
        />

        <!-- WhatsApp floating button -->
        @if($project->whatsapp_number)
        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $project->whatsapp_number) }}?text={{ urlencode($project->translated_whatsapp_message) }}" target="_blank" rel="noopener" class="fixed bottom-6 right-6 z-50 w-14 h-14 bg-green-500 rounded-full flex items-center justify-center shadow-lg hover:bg-green-600 transition hover:scale-110" title="{{ __('landing.contact_via_whatsapp') }}">
            <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        </a>
        @endif
    </div>

    <!-- Unit detail modal -->
    <div id="unit-modal" class="fixed inset-0 z-50 hidden" style="background: rgba(0,0,0,0.5)">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-xl font-bold" id="modal-title"></h3>
                        <button onclick="document.getElementById('unit-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
                    </div>
                    <div id="modal-body"></div>
                    <div id="modal-floor-plan" class="mt-4 hidden">
                        <img id="modal-plan-img" src="" alt="Plano" class="w-full rounded-lg">
                    </div>
                    <div class="mt-6 flex flex-wrap gap-3">
                        @if($project->whatsapp_number)
                        <a id="modal-whatsapp" href="#" target="_blank" class="px-4 py-2 bg-green-500 text-white rounded-md text-sm font-semibold hover:bg-green-600 transition">WhatsApp</a>
                        @endif
                        <a href="#contacto" onclick="document.getElementById('unit-modal').classList.add('hidden')" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition" id="modal-inquiry-btn">Consultar</a>
                        <!-- PDF download -->
                        <a id="modal-pdf-btn" href="#" class="px-4 py-2 bg-red-50 text-red-700 rounded-md text-sm font-semibold hover:bg-red-100 transition inline-flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            PDF
                        </a>
                        <!-- Share button -->
                        <button id="modal-share-btn" onclick="shareFromModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-semibold hover:bg-gray-200 transition">Compartir</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lightbox -->
    <div id="lightbox" class="fixed inset-0 z-50 hidden bg-black/90 flex items-center justify-center cursor-pointer" onclick="this.classList.add('hidden')">
        <img id="lightbox-img" src="" alt="" class="max-w-[90vw] max-h-[90vh] object-contain">
    </div>

    @if($project->getFileByType('model_3d'))
    <script type="application/json" id="project-data">
        {!! json_encode([
            'slug' => $project->slug,
            'name' => $project->name,
            'settings' => $project->settings,
            'files' => [
                'video_360' => $project->getFileByType('video_360') ? '/api/projects/' . $project->id . '/files/video_360' : null,
                'model_3d' => $project->getFileByType('model_3d') ? '/api/projects/' . $project->id . '/files/model_3d' : null,
                'ground_texture' => $project->getFileByType('ground_texture') ? '/api/projects/' . $project->id . '/files/ground_texture' : null,
            ],
        ]) !!}
    </script>
    <script>
        // Lazy loading: on mobile, wait for user tap; on desktop, load immediately
        function startViewer3D() {
            var placeholder = document.getElementById('viewer-lazy-placeholder');
            if (placeholder) placeholder.style.display = 'none';
            if (!document.querySelector('script[src="/js/viewer-public.js"]')) {
                var s = document.createElement('script');
                s.type = 'module';
                s.src = '/js/viewer-public.js';
                document.body.appendChild(s);
            }
        }
        // Auto-load on desktop (no lazy placeholder visible)
        if (window.innerWidth >= 768) {
            startViewer3D();
        }
    </script>
    @endif

    <script>
        // QW5: Alpine.js unit filter logic
        function unitFilters() {
            return {
                filters: { bedrooms: '', priceMax: '', areaMin: '', status: '' },
                visibleIds: new Set(),
                visibleCount: {{ $project->units->count() }},
                showToast: false,
                priceOptions: [],
                // Comparison
                compareList: [],
                compareOpen: false,
                compareFields: [
                    { key: 'typology', label: 'Tipologia' },
                    { key: 'floor', label: 'Piso' },
                    { key: 'bedrooms', label: 'Dormitorios' },
                    { key: 'bathrooms', label: 'Banos' },
                    { key: 'area', label: 'Area (m2)' },
                    { key: 'price', label: 'Precio' },
                    { key: 'priceM2', label: 'Precio/m2' },
                ],

                init() {
                    // Build price options from actual data
                    const prices = [];
                    document.querySelectorAll('.unit-row').forEach(row => {
                        const p = parseFloat(row.dataset.unitRawPrice);
                        if (p > 0) prices.push(p);
                    });
                    if (prices.length) {
                        const max = Math.max(...prices);
                        const steps = [50000, 75000, 100000, 150000, 200000, 300000, 500000, 750000, 1000000, 2000000, 5000000];
                        this.priceOptions = steps
                            .filter(s => s <= max * 1.2)
                            .map(s => ({ value: s, label: 'USD ' + s.toLocaleString('en-US') }));
                    }

                    // Initialize all as visible
                    document.querySelectorAll('.unit-row').forEach(row => {
                        this.visibleIds.add(parseInt(row.dataset.unitId));
                    });

                    // Load compare state from URL
                    this.loadCompareFromUrl();
                },

                applyFilters() {
                    this.visibleIds = new Set();
                    document.querySelectorAll('.unit-row').forEach(row => {
                        const d = row.dataset;
                        const bedrooms = parseInt(d.unitBedrooms);
                        const price = parseFloat(d.unitRawPrice);
                        const area = parseFloat(d.unitArea);
                        const status = d.unitStatus;

                        let show = true;
                        if (this.filters.bedrooms) {
                            const fb = parseInt(this.filters.bedrooms);
                            if (fb >= 3) { if (bedrooms < 3) show = false; }
                            else { if (bedrooms !== fb) show = false; }
                        }
                        if (this.filters.priceMax && price > parseFloat(this.filters.priceMax)) show = false;
                        if (this.filters.areaMin && area < parseFloat(this.filters.areaMin)) show = false;
                        if (this.filters.status && status !== this.filters.status) show = false;

                        if (show) this.visibleIds.add(parseInt(d.unitId));
                    });
                    this.visibleCount = this.visibleIds.size;
                },

                isVisible(unitId) {
                    return this.visibleIds.has(unitId);
                },

                floorHasVisible(floor) {
                    let found = false;
                    document.querySelectorAll('.unit-row[data-unit-floor="' + floor + '"]').forEach(row => {
                        if (this.visibleIds.has(parseInt(row.dataset.unitId))) found = true;
                    });
                    return found;
                },

                hasActiveFilters() {
                    return this.filters.bedrooms || this.filters.priceMax || this.filters.areaMin || this.filters.status;
                },

                clearFilters() {
                    this.filters = { bedrooms: '', priceMax: '', areaMin: '', status: '' };
                    this.applyFilters();
                },

                // QW2: Share unit link
                shareUnit(unitId, unitName) {
                    const url = new URL(window.location.href.split('#')[0].split('?')[0]);
                    url.searchParams.set('unit', unitId);
                    navigator.clipboard.writeText(url.toString()).then(() => {
                        this.showToast = true;
                        setTimeout(() => this.showToast = false, 2000);
                    });
                },

                // Comparison methods
                toggleCompare(unitId) {
                    const idx = this.compareList.indexOf(unitId);
                    if (idx >= 0) {
                        this.compareList.splice(idx, 1);
                    } else if (this.compareList.length < 3) {
                        this.compareList.push(unitId);
                    }
                },

                getUnitName(unitId) {
                    const row = document.querySelector('.unit-row[data-unit-id="' + unitId + '"]');
                    return row ? row.dataset.unitName : '';
                },

                getUnitData(unitId) {
                    const row = document.querySelector('.unit-row[data-unit-id="' + unitId + '"]');
                    if (!row) return {};
                    return {
                        name: row.dataset.unitName,
                        typology: row.dataset.unitTypology || '-',
                        floor: row.dataset.unitFloor,
                        bedrooms: row.dataset.unitBedrooms,
                        bathrooms: row.dataset.unitBathrooms,
                        area: parseFloat(row.dataset.unitArea),
                        price: parseFloat(row.dataset.unitRawPrice),
                        priceFormatted: window.formatPrice(parseFloat(row.dataset.unitRawPrice)),
                        status: row.dataset.unitStatus,
                        pdf: row.dataset.unitPdf,
                        floorPlan: row.dataset.unitFloorPlan,
                    };
                },

                getCompareValue(key, unitId) {
                    const d = this.getUnitData(unitId);
                    switch (key) {
                        case 'typology': return d.typology;
                        case 'floor': return d.floor == 0 ? 'PB' : d.floor;
                        case 'bedrooms': return d.bedrooms;
                        case 'bathrooms': return d.bathrooms;
                        case 'area': return d.area + ' m2';
                        case 'price': return d.priceFormatted;
                        case 'priceM2': return d.area > 0 ? window.formatPrice(d.price / d.area) : '-';
                        default: return '-';
                    }
                },

                isBestValue(key, unitId) {
                    if (this.compareList.length < 2) return false;
                    const values = this.compareList.map(uid => {
                        const d = this.getUnitData(uid);
                        switch (key) {
                            case 'area': return { uid, val: d.area };
                            case 'price': return { uid, val: d.price };
                            case 'priceM2': return { uid, val: d.area > 0 ? d.price / d.area : Infinity };
                            case 'bedrooms': return { uid, val: parseInt(d.bedrooms) };
                            case 'bathrooms': return { uid, val: parseInt(d.bathrooms) };
                            default: return { uid, val: 0 };
                        }
                    });
                    if (['price', 'priceM2'].includes(key)) {
                        const min = Math.min(...values.map(v => v.val));
                        return values.find(v => v.uid === unitId)?.val === min && values.filter(v => v.val === min).length < values.length;
                    }
                    if (['area', 'bedrooms', 'bathrooms'].includes(key)) {
                        const max = Math.max(...values.map(v => v.val));
                        return values.find(v => v.uid === unitId)?.val === max && values.filter(v => v.val === max).length < values.length;
                    }
                    return false;
                },

                openCompare() {
                    if (this.compareList.length >= 2) this.compareOpen = true;
                },

                shareCompare() {
                    const names = this.compareList.map(uid => this.getUnitName(uid)).join(',');
                    const url = new URL(window.location.href.split('#')[0].split('?')[0]);
                    url.searchParams.set('compare', this.compareList.join(','));
                    navigator.clipboard.writeText(url.toString()).then(() => {
                        this.showToast = true;
                        setTimeout(() => this.showToast = false, 2000);
                    });
                },

                selectInquiryUnit(unitId) {
                    const sel = document.getElementById('inquiry-unit-select');
                    if (sel) {
                        for (let opt of sel.options) {
                            if (opt.value == unitId) { sel.value = unitId; break; }
                        }
                    }
                },

                loadCompareFromUrl() {
                    const params = new URLSearchParams(window.location.search);
                    const compare = params.get('compare');
                    if (compare) {
                        const ids = compare.split(',').map(Number).filter(n => n > 0);
                        if (ids.length >= 2 && ids.length <= 3) {
                            this.compareList = ids;
                            setTimeout(() => this.openCompare(), 600);
                        }
                    }
                }
            };
        }

        // QW2: Share from modal
        let currentModalUnitId = null;
        function shareFromModal() {
            if (!currentModalUnitId) return;
            const url = new URL(window.location.href.split('#')[0].split('?')[0]);
            url.searchParams.set('unit', currentModalUnitId);
            navigator.clipboard.writeText(url.toString());
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Gallery lightbox
            document.querySelectorAll('.gallery-thumb').forEach(el => {
                el.addEventListener('click', function() {
                    const src = this.dataset.src;
                    document.getElementById('lightbox-img').src = src;
                    document.getElementById('lightbox').classList.remove('hidden');
                });
            });

            // Register units with bbox data for 3D viewer
            const totalFloors = {{ $project->total_floors ?? ($project->units->count() ? $project->units->max('floor') + 1 : 1) }};
            const landingUnits = [];
            document.querySelectorAll('.unit-row').forEach(row => {
                const d = row.dataset;
                const unit = {
                    id: parseInt(d.unitId),
                    floor: parseInt(d.unitFloor),
                    status: d.unitStatus,
                    bbox: d.unitBbox ? JSON.parse(d.unitBbox) : null,
                };
                landingUnits.push(unit);
            });
            if (window.viewerAPI?.registerUnits && landingUnits.length) {
                window.viewerAPI.registerUnits(landingUnits);
            }

            // Unit row click -> modal + 3D viewer focus
            let selectedRow = null;

            document.querySelectorAll('.unit-row').forEach(row => {
                row.addEventListener('click', function() {
                    const d = this.dataset;

                    // Highlight selected row
                    if (selectedRow) selectedRow.classList.remove('bg-blue-50', 'ring-2', 'ring-blue-300');
                    this.classList.add('bg-blue-50', 'ring-2', 'ring-blue-300');
                    selectedRow = this;

                    currentModalUnitId = d.unitId;

                    // Focus 3D viewer on this unit's bbox, fallback to floor
                    const unitId = parseInt(d.unitId);
                    const floor = parseInt(d.unitFloor);
                    let focused = false;
                    if (d.unitBbox && window.viewerAPI?.focusOnUnit) {
                        const viewerSection = document.getElementById('viewer-3d-section');
                        if (viewerSection) {
                            viewerSection.scrollIntoView({ behavior: 'smooth' });
                            setTimeout(() => { focused = window.viewerAPI.focusOnUnit(unitId); }, 400);
                        }
                        focused = true;
                    }
                    if (!focused && window.viewerAPI?.focusOnFloor && !isNaN(floor)) {
                        const viewerSection = document.getElementById('viewer-3d-section');
                        if (viewerSection) {
                            viewerSection.scrollIntoView({ behavior: 'smooth' });
                            setTimeout(() => window.viewerAPI.focusOnFloor(floor, totalFloors), 400);
                        }
                    }

                    // Show modal
                    document.getElementById('modal-title').textContent = 'Unidad ' + d.unitName;

                    let statusLabel = {available: 'Disponible', reserved: 'Reservado', sold: 'Vendido'}[d.unitStatus] || d.unitStatus;
                    let statusColor = {available: 'text-green-600', reserved: 'text-yellow-600', sold: 'text-red-600'}[d.unitStatus] || '';

                    let html = '<div class="space-y-2 text-sm">';
                    if (d.unitTypology) html += '<p><span class="text-gray-500">Tipo:</span> ' + d.unitTypology + '</p>';
                    html += '<p><span class="text-gray-500">Dormitorios:</span> ' + d.unitBedrooms + '</p>';
                    html += '<p><span class="text-gray-500">Banos:</span> ' + d.unitBathrooms + '</p>';
                    html += '<p><span class="text-gray-500">Area:</span> ' + d.unitArea + ' m2</p>';
                    html += '<p><span class="text-gray-500">Precio:</span> <span class="font-bold text-lg">' + window.formatPrice(parseFloat(d.unitRawPrice)) + '</span></p>';
                    html += '<p><span class="text-gray-500">Estado:</span> <span class="font-medium ' + statusColor + '">' + statusLabel + '</span></p>';
                    html += '</div>';
                    document.getElementById('modal-body').innerHTML = html;

                    const fpDiv = document.getElementById('modal-floor-plan');
                    if (d.unitFloorPlan) {
                        document.getElementById('modal-plan-img').src = d.unitFloorPlan;
                        fpDiv.classList.remove('hidden');
                    } else {
                        fpDiv.classList.add('hidden');
                    }

                    // Update inquiry select
                    const sel = document.getElementById('inquiry-unit-select');
                    if (sel) {
                        for (let opt of sel.options) {
                            if (opt.value == d.unitId) { sel.value = d.unitId; break; }
                        }
                    }

                    // WhatsApp link
                    const waBtn = document.getElementById('modal-whatsapp');
                    if (waBtn) {
                        const waNumber = '{{ preg_replace("/[^0-9]/", "", $project->whatsapp_number ?? "") }}';
                        waBtn.href = 'https://wa.me/' + waNumber + '?text=' + encodeURIComponent('Hola, me interesa la unidad ' + d.unitName + ' del proyecto {{ $project->name }}');
                    }

                    // PDF link
                    const pdfBtn = document.getElementById('modal-pdf-btn');
                    if (pdfBtn && d.unitPdf) {
                        pdfBtn.href = d.unitPdf;
                    }

                    document.getElementById('unit-modal').classList.remove('hidden');
                });
            });

            // QW2: Auto-open unit from ?unit= query param
            const urlParams = new URLSearchParams(window.location.search);
            const unitParam = urlParams.get('unit');
            if (unitParam) {
                const targetRow = document.querySelector('.unit-row[data-unit-id="' + unitParam + '"]');
                if (targetRow) {
                    setTimeout(() => {
                        targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        setTimeout(() => targetRow.click(), 300);
                    }, 500);
                }
            }
        });
    </script>
    <script src="/js/viewer-analytics.js" defer></script>
</x-app-layout>
