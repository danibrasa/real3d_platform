<x-portal-layout>
    <x-slot name="title">{{ __('portal.search_results') }}{{ request('location') ? ' — ' . request('location') : '' }}</x-slot>
    <x-slot name="metaDescription">{{ __('portal.projects_found', ['count' => $projects->total()]) }}{{ request('location') ? ' ' . request('location') : '' }}</x-slot>

    @push('head')
    @if(request()->hasAny(['price_min', 'price_max', 'bedrooms']))
        <meta name="robots" content="noindex, follow">
    @endif
    <meta property="og:title" content="{{ __('portal.search_results') }}{{ request('location') ? ' — ' . request('location') : '' }} | Real3D">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <style>
        .marker-cluster-small, .marker-cluster-medium, .marker-cluster-large {
            background-color: rgba(6, 182, 212, 0.3) !important;
        }
        .marker-cluster-small div, .marker-cluster-medium div, .marker-cluster-large div {
            background-color: rgba(6, 182, 212, 0.7) !important;
            color: white !important;
            font-weight: 600;
        }
    </style>
    @endpush

    <div x-data="portalSearchPage()" class="flex flex-col" style="height: calc(100vh - 64px);">

        {{-- Filter Bar --}}
        <div class="bg-white border-b border-gray-200 px-4 py-3 flex-shrink-0">
            {{-- Mobile: toggle button --}}
            <div class="md:hidden flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">{{ __('portal.projects_found', ['count' => $projects->total()]) }}</span>
                <button @click="filtersOpen = !filtersOpen" class="text-sm text-cyan-600 font-medium flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/></svg>
                    {{ __('portal.filters') }}
                </button>
            </div>

            {{-- Filter form --}}
            <form method="GET" action="{{ route('portal.search') }}"
                  class="flex-wrap gap-2 items-end"
                  :class="filtersOpen ? 'flex' : 'hidden md:flex'">
                {{-- Search --}}
                <div class="w-full md:w-auto md:flex-1 md:min-w-[180px]">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('portal.search_placeholder') }}"
                           class="w-full rounded-lg border-gray-300 text-sm py-2 px-3 focus:ring-cyan-500 focus:border-cyan-500">
                </div>
                {{-- Location --}}
                <div class="w-full md:w-auto">
                    <select name="location" class="w-full rounded-lg border-gray-300 text-sm py-2 px-3 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">{{ __('portal.all_locations') }}</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc }}" {{ request('location') === $loc ? 'selected' : '' }}>{{ $loc }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Bedrooms --}}
                <div class="w-1/2 md:w-auto">
                    <select name="bedrooms" class="w-full rounded-lg border-gray-300 text-sm py-2 px-3 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">{{ __('portal.all_bedrooms') }}</option>
                        @foreach($bedroomOptions as $bed)
                            <option value="{{ $bed }}" {{ request('bedrooms') == $bed ? 'selected' : '' }}>{{ $bed }}+</option>
                        @endforeach
                    </select>
                </div>
                {{-- Price min --}}
                <div class="w-1/4 md:w-auto">
                    <input type="number" name="price_min" value="{{ request('price_min') }}" placeholder="{{ __('portal.price_min') }}"
                           class="w-full rounded-lg border-gray-300 text-sm py-2 px-3 focus:ring-cyan-500 focus:border-cyan-500 md:w-28">
                </div>
                {{-- Price max --}}
                <div class="w-1/4 md:w-auto">
                    <input type="number" name="price_max" value="{{ request('price_max') }}" placeholder="{{ __('portal.price_max') }}"
                           class="w-full rounded-lg border-gray-300 text-sm py-2 px-3 focus:ring-cyan-500 focus:border-cyan-500 md:w-28">
                </div>
                {{-- Sort --}}
                <div class="w-1/2 md:w-auto">
                    <select name="sort" class="w-full rounded-lg border-gray-300 text-sm py-2 px-3 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>{{ __('portal.sort_newest') }}</option>
                        <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>{{ __('portal.sort_name') }}</option>
                        <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>{{ __('portal.sort_price_asc') }}</option>
                        <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>{{ __('portal.sort_price_desc') }}</option>
                        <option value="availability" {{ request('sort') === 'availability' ? 'selected' : '' }}>{{ __('portal.sort_availability') }}</option>
                    </select>
                </div>
                {{-- Submit --}}
                <button type="submit" class="bg-cyan-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-cyan-700 transition-colors">
                    {{ __('portal.search_btn') }}
                </button>
                @if(request()->hasAny(['q', 'location', 'bedrooms', 'price_min', 'price_max']))
                    <a href="{{ route('portal.search') }}" class="text-xs text-gray-500 hover:text-red-500 px-2 py-2">{{ __('portal.clear_filters') }}</a>
                @endif
            </form>
        </div>

        {{-- Split View --}}
        <div class="flex-1 flex flex-col md:flex-row overflow-hidden">

            {{-- Map Panel --}}
            <div class="w-full md:w-1/2 lg:w-[55%] h-64 md:h-full relative flex-shrink-0">
                <div x-ref="map" class="w-full h-full"></div>
                {{-- Map layer toggle --}}
                <div class="absolute top-3 right-3 z-[1000]">
                    <button @click="toggleMapLayer()" class="bg-white shadow-md rounded-lg px-3 py-1.5 text-xs font-medium text-gray-600 hover:text-gray-900 transition-colors">
                        <span x-text="mapLayer === 'streets' ? 'Satellite' : 'Map'"></span>
                    </button>
                </div>
            </div>

            {{-- Results Panel --}}
            <div class="flex-1 overflow-y-auto bg-gray-50 p-4 md:p-6">
                <div class="hidden md:flex items-center justify-between mb-4">
                    <p class="text-sm text-gray-600">{{ __('portal.projects_found', ['count' => $projects->total()]) }}</p>
                </div>

                @if($projects->count())
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        @foreach($projects as $project)
                            <div @mouseenter="highlightMarker({{ $project->id }})" @mouseleave="unhighlightMarker({{ $project->id }})">
                                <x-portal-project-card :project="$project" />
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $projects->links() }}
                    </div>
                @else
                    {{-- Empty state --}}
                    <div class="text-center py-16">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <h3 class="text-lg font-medium text-gray-600 mb-2">{{ __('portal.no_results') }}</h3>
                        <p class="text-sm text-gray-400 mb-4">{{ __('portal.no_results_hint') }}</p>
                        <a href="{{ route('portal.search') }}" class="text-sm text-cyan-600 hover:text-cyan-700 font-medium">{{ __('portal.clear_filters') }}</a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function portalSearchPage() {
        return {
            map: null,
            markers: null,
            markerMap: {},
            mapLayer: 'streets',
            osmLayer: null,
            satLayer: null,
            filtersOpen: false,

            init() {
                this.$nextTick(() => this.initMap());
            },

            async initMap() {
                this.map = L.map(this.$refs.map, { scrollWheelZoom: true });

                this.osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OSM', maxZoom: 19,
                });
                this.satLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    attribution: '&copy; Esri', maxZoom: 19,
                });
                this.osmLayer.addTo(this.map);

                this.markers = L.markerClusterGroup({ maxClusterRadius: 50 });
                this.map.addLayer(this.markers);

                await this.loadMarkers();

                const bounds = @json($mapBounds);
                if (bounds) {
                    this.map.fitBounds([
                        [bounds.south, bounds.west],
                        [bounds.north, bounds.east]
                    ], { padding: [40, 40], maxZoom: 14 });
                } else {
                    this.map.setView([18.5, -69.9], 8);
                }
            },

            async loadMarkers() {
                const params = new URLSearchParams(window.location.search);
                const res = await fetch('{{ route("portal.api.map-projects") }}?' + params.toString());
                const data = await res.json();

                this.markers.clearLayers();
                this.markerMap = {};

                data.forEach(p => {
                    const marker = L.marker([p.lat, p.lng]);
                    marker.bindPopup(
                        '<div style="min-width:150px">' +
                        (p.thumbnail ? '<img src="' + p.thumbnail + '" style="width:100%;height:80px;object-fit:cover;border-radius:6px;margin-bottom:6px">' : '') +
                        '<strong style="font-size:13px">' + p.name + '</strong><br>' +
                        '<small style="color:#6b7280">' + (p.location || '') + '</small><br>' +
                        '<small style="color:#059669;font-weight:600">' + p.available + ' {{ __("portal.available") }}</small><br>' +
                        '<a href="' + p.url + '" style="color:#0891b2;font-size:12px;font-weight:600">{{ __("portal.view_project") }} &rarr;</a>' +
                        '</div>'
                    );
                    this.markers.addLayer(marker);
                    this.markerMap[p.id] = marker;
                });
            },

            toggleMapLayer() {
                if (this.mapLayer === 'streets') {
                    this.map.removeLayer(this.osmLayer);
                    this.satLayer.addTo(this.map);
                    this.mapLayer = 'satellite';
                } else {
                    this.map.removeLayer(this.satLayer);
                    this.osmLayer.addTo(this.map);
                    this.mapLayer = 'streets';
                }
            },

            highlightMarker(projectId) {
                const marker = this.markerMap[projectId];
                if (marker) {
                    marker.openPopup();
                    this.map.panTo(marker.getLatLng(), { animate: true, duration: 0.3 });
                }
            },

            unhighlightMarker(projectId) {
                const marker = this.markerMap[projectId];
                if (marker) marker.closePopup();
            }
        };
    }
    </script>
    @endpush
</x-portal-layout>
