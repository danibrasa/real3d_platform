@push('head')
{{-- SEO Meta --}}
@php
    $seoTitle = __('unit_detail.unit_prefix') . ' ' . $unit->identifier . ' - ' . $project->name;
    $seoDesc = __('unit_detail.seo_description', [
        'unit' => $unit->identifier,
        'project' => $project->name,
        'beds' => $unit->bedrooms,
        'area' => $unit->area_m2,
        'price' => $unit->formatted_price,
    ]);
    $seoImage = $project->thumbnail_path
        ? url('/storage/' . $project->thumbnail_path)
        : ($project->galleryImages->first() ? url('/api/projects/' . $project->id . '/gallery/' . $project->galleryImages->first()->id) : null);
@endphp

<!-- OG Meta Tags -->
<meta property="og:type" content="product">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDesc }}">
<meta property="og:url" content="{{ route('viewer.unit.detail', [$project->slug, $unit->id]) }}">
@if($seoImage)
<meta property="og:image" content="{{ $seoImage }}">
@endif
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDesc }}">
@if($seoImage)
<meta name="twitter:image" content="{{ $seoImage }}">
@endif

{{-- JSON-LD Product + BreadcrumbList --}}
@php
    $productLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => __('unit_detail.unit_prefix') . ' ' . $unit->identifier,
        'description' => $seoDesc,
        'url' => route('viewer.unit.detail', [$project->slug, $unit->id]),
        'brand' => ['@type' => 'Organization', 'name' => $project->name],
    ];
    if ($seoImage) $productLd['image'] = $seoImage;
    if ($unit->price) {
        $productLd['offers'] = [
            '@type' => 'Offer',
            'priceCurrency' => 'USD',
            'price' => $unit->price,
            'availability' => $unit->status === 'available' ? 'https://schema.org/InStock' : ($unit->status === 'reserved' ? 'https://schema.org/LimitedAvailability' : 'https://schema.org/SoldOut'),
        ];
    }

    $breadcrumbLd = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => __('general.projects'), 'item' => route('viewer.index')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $project->name, 'item' => route('viewer.landing', $project->slug)],
            ['@type' => 'ListItem', 'position' => 4, 'name' => __('unit_detail.unit_prefix') . ' ' . $unit->identifier],
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($productLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($breadcrumbLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

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
    <x-slot name="title">{{ $seoTitle }}</x-slot>
    <x-slot name="metaDescription">{{ $seoDesc }}</x-slot>

    {{-- Breadcrumb header --}}
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <nav class="text-sm text-gray-500">
                <a href="{{ route('viewer.index') }}" class="hover:underline">{{ __('general.projects') }}</a>
                <span class="mx-1">/</span>
                <a href="{{ route('viewer.landing', $project->slug) }}" class="hover:underline">{{ $project->name }}</a>
                <span class="mx-1">/</span>
                <span class="text-gray-800 font-medium">{{ __('unit_detail.unit_prefix') }} {{ $unit->identifier }}</span>
            </nav>
            <a href="{{ route('viewer.landing', $project->slug) }}" class="text-sm text-gray-600 hover:underline">&larr; {{ __('unit_detail.back_to_project') }}</a>
        </div>
    </x-slot>

    <div>
        {{-- 1. Hero --}}
        <div class="bg-gradient-to-br from-blue-600 to-indigo-800 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <h1 class="text-3xl md:text-4xl font-bold">{{ __('unit_detail.unit_prefix') }} {{ $unit->identifier }}</h1>
                            <x-unit-status-badge :status="$unit->status" />
                        </div>
                        @if($unit->typology)
                            <p class="text-lg text-blue-200 mb-1">{{ $unit->typology->name }}</p>
                        @endif
                        <p class="text-blue-200">
                            {{ $project->name }}
                            @if($project->translated_location) &mdash; {{ $project->translated_location }} @endif
                        </p>
                    </div>
                    <div class="text-left md:text-right">
                        <div class="text-3xl md:text-4xl font-bold" data-currency-amount="{{ $unit->price }}">
                            {{ $currencyService->format($unit->price, $currentCurrency) }}
                        </div>
                        @if($unit->area_m2 > 0)
                            <div class="text-blue-200 text-sm mt-1">
                                {{ __('unit_detail.price_per_m2') }}:
                                <span data-currency-amount="{{ $unit->price / $unit->area_m2 }}">
                                    {{ $currencyService->format($unit->price / $unit->area_m2, $currentCurrency) }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Specs grid --}}
        <div class="bg-white border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-6 text-center">
                    <div>
                        <div class="text-3xl font-bold text-gray-800">{{ $unit->bedrooms }}</div>
                        <div class="text-sm text-gray-500 mt-1">{{ __('landing.beds') }}</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-gray-800">{{ $unit->bathrooms }}</div>
                        <div class="text-sm text-gray-500 mt-1">{{ __('landing.baths') }}</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-gray-800">{{ $unit->area_m2 }} <span class="text-lg">m&sup2;</span></div>
                        <div class="text-sm text-gray-500 mt-1">{{ __('landing.area') }}</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-gray-800">{{ $unit->floor == 0 ? __('landing.ground_floor') : $unit->floor }}</div>
                        <div class="text-sm text-gray-500 mt-1">{{ __('landing.floor') }}</div>
                    </div>
                    @if($unit->typology)
                    <div>
                        <div class="text-2xl font-bold text-gray-800">{{ $unit->typology->name }}</div>
                        <div class="text-sm text-gray-500 mt-1">{{ __('landing.typology') }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-16">

            {{-- 3. Floor plan --}}
            @if($unit->floor_plan)
            <section>
                <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ __('unit_detail.floor_plan') }}</h2>
                <div class="bg-white rounded-lg shadow-sm p-4 max-w-2xl cursor-pointer" onclick="document.getElementById('lightbox-img').src=this.querySelector('img').src; document.getElementById('lightbox').classList.remove('hidden');">
                    <img src="/api/units/{{ $unit->id }}/floor-plan" alt="{{ __('unit_detail.floor_plan') }} - {{ $unit->identifier }}" class="w-full rounded" loading="lazy">
                </div>
            </section>
            @endif

            {{-- 4. Typology description --}}
            @if($unit->typology?->description)
            <section>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">{{ __('unit_detail.about_this_type') }}</h2>
                <div class="prose max-w-none text-gray-600">
                    {!! nl2br(e($unit->typology->description)) !!}
                </div>
            </section>
            @endif

            {{-- 5. Notes --}}
            @if($unit->notes)
            <section>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">{{ __('unit_detail.notes') }}</h2>
                <div class="prose max-w-none text-gray-600">
                    {!! nl2br(e($unit->notes)) !!}
                </div>
            </section>
            @endif

            {{-- 6. Action buttons --}}
            <section>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('viewer.show', $project->slug) }}?unit={{ $unit->id }}" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition shadow">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"/></svg>
                        {{ __('unit_detail.view_in_3d') }}
                    </a>
                    <a href="{{ route('viewer.unit.pdf', [$project->slug, $unit->id]) }}" class="inline-flex items-center px-6 py-3 bg-red-50 text-red-700 rounded-lg text-sm font-semibold hover:bg-red-100 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        {{ __('unit_detail.download_pdf') }}
                    </a>
                    @if($project->paymentPlans->count())
                    <a href="{{ route('viewer.payment-schedule.pdf', [$project->slug, $unit->id]) }}" class="inline-flex items-center px-6 py-3 bg-purple-50 text-purple-700 rounded-lg text-sm font-semibold hover:bg-purple-100 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        {{ __('unit_detail.payment_schedule_pdf') }}
                    </a>
                    @endif
                    @if($project->whatsapp_number)
                    @php
                        $waNumber = preg_replace('/[^0-9]/', '', $project->whatsapp_number);
                        $waMessage = __('unit_detail.whatsapp_message', ['unit' => $unit->identifier, 'project' => $project->name]);
                    @endphp
                    <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($waMessage) }}" target="_blank" rel="noopener" class="inline-flex items-center px-6 py-3 bg-green-500 text-white rounded-lg text-sm font-semibold hover:bg-green-600 transition">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        WhatsApp
                    </a>
                    @endif
                </div>
            </section>

            {{-- 7. Payment Plans (pre-filled with unit price) --}}
            <x-payment-plans :project="$project" :priceMin="$unit->price" />

            {{-- 8. Investment Calculator (pre-filled with unit price) --}}
            <x-investment-calculator :project="$project" :priceMin="$unit->price" />

            {{-- 9. Gallery --}}
            @if($project->galleryImages->count())
            <section>
                <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ __('landing.gallery') }}</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($project->galleryImages->take(8) as $img)
                    <div class="cursor-pointer rounded-lg overflow-hidden shadow-sm hover:shadow-md transition gallery-thumb" data-src="/api/projects/{{ $project->id }}/gallery/{{ $img->id }}">
                        <img src="/api/projects/{{ $project->id }}/gallery/{{ $img->id }}" alt="{{ $img->caption ?? __('landing.gallery') }}" class="w-full h-48 object-cover" loading="lazy">
                        @if($img->caption)
                            <div class="p-2 bg-white text-xs text-gray-600">{{ $img->caption }}</div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </section>
            @endif

            {{-- 10. Similar units --}}
            @if($similarUnits->count())
            <section>
                <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ __('unit_detail.similar_units') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($similarUnits as $similar)
                    <a href="{{ route('viewer.unit.detail', [$project->slug, $similar->id]) }}" class="block bg-white rounded-lg shadow-sm hover:shadow-md transition overflow-hidden group">
                        <div class="p-5">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-bold text-gray-800 group-hover:text-blue-600 transition">{{ $similar->identifier }}</h3>
                                <x-unit-status-badge :status="$similar->status" />
                            </div>
                            <div class="text-xl font-bold text-gray-800 mb-3" data-currency-amount="{{ $similar->price }}">
                                {{ $currencyService->format($similar->price, $currentCurrency) }}
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-500">
                                <span>{{ $similar->bedrooms }} {{ __('landing.beds') }}</span>
                                <span>&bull;</span>
                                <span>{{ $similar->bathrooms }} {{ __('landing.baths') }}</span>
                                <span>&bull;</span>
                                <span>{{ $similar->area_m2 }} m&sup2;</span>
                            </div>
                            @if($similar->typology)
                                <div class="mt-2 text-xs text-gray-400">{{ $similar->typology->name }}</div>
                            @endif
                        </div>
                    </a>
                    @endforeach
                </div>
            </section>
            @endif

            {{-- 11. Contact form --}}
            <section id="contacto">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ __('unit_detail.contact_about_unit') }}</h2>

                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
                @endif

                <div class="bg-white rounded-lg shadow-sm p-6 max-w-2xl">
                    <form method="POST" action="{{ route('viewer.inquiry', $project->slug) }}">
                        @csrf
                        <input type="hidden" name="unit_id" value="{{ $unit->id }}">
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
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('landing.phone') }}</label>
                            <input type="tel" name="phone" value="{{ old('phone') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
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
        </div>

        {{-- 12. Share buttons --}}
        <x-share-buttons
            :url="route('viewer.unit.detail', [$project->slug, $unit->id])"
            :title="__('unit_detail.unit_prefix') . ' ' . $unit->identifier . ' - ' . $project->name"
            :text="$unit->formatted_price . ' | ' . $unit->bedrooms . ' ' . __('landing.beds') . ' | ' . $unit->area_m2 . ' m2'"
        />

        {{-- 13. WhatsApp floating button --}}
        @if($project->whatsapp_number)
        @php
            $waNumber = preg_replace('/[^0-9]/', '', $project->whatsapp_number);
            $waMessage = __('unit_detail.whatsapp_message', ['unit' => $unit->identifier, 'project' => $project->name]);
        @endphp
        <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($waMessage) }}" target="_blank" rel="noopener" class="fixed bottom-6 right-6 z-50 w-14 h-14 bg-green-500 rounded-full flex items-center justify-center shadow-lg hover:bg-green-600 transition hover:scale-110" title="{{ __('landing.contact_via_whatsapp') }}">
            <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        </a>
        @endif
    </div>

    {{-- 14. Lightbox --}}
    <div id="lightbox" class="fixed inset-0 z-50 hidden bg-black/90 flex items-center justify-center cursor-pointer" onclick="this.classList.add('hidden')">
        <img id="lightbox-img" src="" alt="" class="max-w-[90vw] max-h-[90vh] object-contain">
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gallery lightbox
            document.querySelectorAll('.gallery-thumb').forEach(el => {
                el.addEventListener('click', function() {
                    const src = this.dataset.src;
                    document.getElementById('lightbox-img').src = src;
                    document.getElementById('lightbox').classList.remove('hidden');
                });
            });

            // Dispatch unit price to payment plans and calculator
            window.dispatchEvent(new CustomEvent('unit-selected-price', {
                detail: { price: {{ $unit->price }} }
            }));
        });
    </script>
    <script src="/js/viewer-analytics.js" defer></script>
</x-app-layout>
