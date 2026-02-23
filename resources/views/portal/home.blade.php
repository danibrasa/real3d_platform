<x-portal-layout>
    <x-slot name="title">{{ __('portal.home_title') }}</x-slot>
    <x-slot name="metaDescription">{{ __('portal.home_meta', ['projects' => $stats['projects'], 'units' => $stats['units']]) }}</x-slot>

    @push('head')
    <meta property="og:type" content="website">
    <meta property="og:title" content="Real3D Properties — {{ __('portal.home_title') }}">
    <meta property="og:url" content="{{ route('portal.home') }}">
    @php
    $jsonLd = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'Real3D Properties',
        'url' => route('portal.home'),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => route('portal.search') . '?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp
    <script type="application/ld+json">{!! $jsonLd !!}</script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    @endpush

    {{-- Hero --}}
    <section class="relative bg-gradient-to-b from-[#0a0a1e] via-[#0f172a] to-gray-50 pt-16 pb-24 lg:pt-24 lg:pb-32 overflow-hidden">
        {{-- Decorative orbs --}}
        <div class="absolute top-20 left-1/4 w-72 h-72 bg-cyan-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 right-1/4 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>

        <div class="max-w-4xl mx-auto px-4 text-center relative z-10">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-4 animate-fade-in-up">
                {{ __('portal.hero_title_1') }}
                <span class="bg-gradient-to-r from-cyan-400 via-blue-400 to-cyan-300 bg-clip-text text-transparent">{{ __('portal.hero_title_2') }}</span>
            </h1>
            <p class="text-lg text-slate-400 mb-4 animate-fade-in-up-delay-1">{{ __('portal.hero_subtitle') }}</p>

            {{-- Stats --}}
            <div class="flex justify-center gap-8 mb-10 animate-fade-in-up-delay-1">
                <div class="text-center">
                    <span class="block text-2xl font-bold bg-gradient-to-r from-cyan-400 to-blue-400 bg-clip-text text-transparent">{{ $stats['projects'] }}</span>
                    <span class="text-xs text-slate-500">{{ __('portal.stat_projects') }}</span>
                </div>
                <div class="text-center">
                    <span class="block text-2xl font-bold bg-gradient-to-r from-cyan-400 to-blue-400 bg-clip-text text-transparent">{{ $stats['units'] }}</span>
                    <span class="text-xs text-slate-500">{{ __('portal.stat_units') }}</span>
                </div>
                <div class="text-center">
                    <span class="block text-2xl font-bold bg-gradient-to-r from-cyan-400 to-blue-400 bg-clip-text text-transparent">{{ $stats['locations'] }}</span>
                    <span class="text-xs text-slate-500">{{ __('portal.stat_locations') }}</span>
                </div>
            </div>

            {{-- Search Bar --}}
            <div x-data="portalSearch()" class="bg-white rounded-2xl shadow-2xl p-3 md:p-4 max-w-3xl mx-auto animate-fade-in-up-delay-2">
                <form :action="'{{ route('portal.search') }}'" method="GET" class="flex flex-col md:flex-row gap-3">
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" name="q" x-model="query" @input.debounce.300ms="fetchLocations()"
                               placeholder="{{ __('portal.search_placeholder') }}"
                               class="w-full pl-10 pr-4 py-3 border-0 rounded-xl bg-gray-50 text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-cyan-500 text-sm">
                        {{-- Autocomplete dropdown --}}
                        <div x-show="suggestions.length > 0" x-cloak @click.outside="suggestions = []"
                             class="absolute top-full left-0 right-0 mt-1 bg-white rounded-xl shadow-lg border border-gray-100 z-20 overflow-hidden">
                            <template x-for="loc in suggestions" :key="loc">
                                <button type="button" @click="selectLocation(loc)" x-text="loc"
                                        class="block w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-cyan-50 hover:text-cyan-700 transition-colors"></button>
                            </template>
                        </div>
                    </div>
                    <select name="bedrooms" class="rounded-xl border-0 bg-gray-50 text-gray-700 text-sm py-3 px-4 focus:ring-2 focus:ring-cyan-500 md:w-36">
                        <option value="">{{ __('portal.all_bedrooms') }}</option>
                        <option value="1">1+</option>
                        <option value="2">2+</option>
                        <option value="3">3+</option>
                        <option value="4">4+</option>
                    </select>
                    <button type="submit" class="btn-glow px-8 py-3 rounded-xl text-white font-semibold text-sm">
                        {{ __('portal.search_btn') }}
                    </button>
                </form>
            </div>
        </div>
    </section>

    {{-- Featured Projects --}}
    @if($featured->count())
    <section class="py-16 lg:py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl lg:text-3xl font-bold text-gray-900">{{ __('portal.featured_projects') }}</h2>
                <a href="{{ route('portal.search') }}" class="text-sm text-cyan-600 hover:text-cyan-700 font-medium">{{ __('portal.view_all') }} &rarr;</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($featured as $project)
                    <x-portal-project-card :project="$project" />
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Map Preview --}}
    @if($mapProjects->count())
    <section class="py-16 lg:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl lg:text-3xl font-bold text-gray-900">{{ __('portal.explore_map') }}</h2>
                <a href="{{ route('portal.search') }}" class="text-sm text-cyan-600 hover:text-cyan-700 font-medium">{{ __('portal.view_all_map') }} &rarr;</a>
            </div>
            <div x-data="portalMapPreview()" class="rounded-2xl overflow-hidden shadow-lg border border-gray-200" style="height: 420px;">
                <div x-ref="map" class="w-full h-full"></div>
            </div>
        </div>
    </section>
    @endif

    {{-- Latest Articles --}}
    @if(isset($latestPosts) && $latestPosts->count())
    <section class="py-16 lg:py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl lg:text-3xl font-bold text-gray-900">{{ __('blog.latest_articles') }}</h2>
                    <p class="text-gray-500 text-sm mt-1">{{ __('blog.latest_articles_subtitle') }}</p>
                </div>
                <a href="{{ route('blog.index') }}" class="text-sm text-cyan-600 hover:text-cyan-700 font-medium">{{ __('blog.view_all_articles') }} &rarr;</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($latestPosts as $post)
                    <x-portal-blog-card :post="$post" />
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- CTA --}}
    <section class="py-16 lg:py-20 bg-gradient-to-r from-[#0a0a1e] to-[#0f172a]">
        <div class="max-w-3xl mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">{{ __('portal.cta_title') }}</h2>
            <p class="text-slate-400 mb-8">{{ __('portal.cta_subtitle') }}</p>
            <a href="{{ route('register.business') }}" class="btn-glow inline-block px-8 py-3 rounded-xl text-white font-semibold">{{ __('portal.list_property') }}</a>
        </div>
    </section>

    @push('scripts')
    <script>
    function portalSearch() {
        return {
            query: '',
            suggestions: [],
            async fetchLocations() {
                if (this.query.length < 2) { this.suggestions = []; return; }
                try {
                    const res = await fetch('{{ route("portal.api.locations") }}?q=' + encodeURIComponent(this.query));
                    this.suggestions = await res.json();
                } catch (e) { this.suggestions = []; }
            },
            selectLocation(loc) {
                this.query = loc;
                this.suggestions = [];
            }
        };
    }

    function portalMapPreview() {
        return {
            init() {
                this.$nextTick(() => {
                    const mapProjects = @json($mapProjects);
                    if (!mapProjects.length) return;

                    const map = L.map(this.$refs.map, { scrollWheelZoom: false });
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OSM', maxZoom: 18,
                    }).addTo(map);

                    const bounds = [];
                    mapProjects.forEach(p => {
                        if (!p.latitude || !p.longitude) return;
                        const marker = L.marker([p.latitude, p.longitude]);
                        marker.bindPopup(
                            '<div class="text-center" style="min-width:140px">' +
                            '<strong>' + p.name + '</strong><br>' +
                            '<small>' + (p.location || '') + '</small><br>' +
                            '<small>' + p.available_units_count + ' {{ __("portal.available") }}</small><br>' +
                            '<a href="{{ url("projects") }}/' + p.slug + '/info" style="color:#0891b2;font-weight:600;font-size:12px">{{ __("portal.view_project") }} &rarr;</a>' +
                            '</div>'
                        );
                        marker.addTo(map);
                        bounds.push([p.latitude, p.longitude]);
                    });

                    if (bounds.length) {
                        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 14 });
                    }
                });
            }
        };
    }
    </script>
    @endpush
</x-portal-layout>
