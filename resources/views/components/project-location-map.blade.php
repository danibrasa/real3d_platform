@props(['project'])

@php
    if (!$project->latitude || !$project->longitude) return;
    $pois = $project->pointsOfInterest ?? collect();
@endphp

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
@endpush

<section class="scroll-mt-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ __('landing.location_title') }}</h2>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        {{-- Location text header --}}
        @if($project->translated_location)
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="text-gray-700 font-medium">{{ $project->translated_location }}</span>
        </div>
        @endif

        {{-- Points of Interest --}}
        @if($pois->count())
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ __('landing.nearby_places') }}</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                @foreach($pois as $poi)
                <div class="flex items-center gap-2.5 p-2 rounded-lg bg-gray-50">
                    <div class="w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center shrink-0">
                        @include('components._poi-icon-public', ['category' => $poi->category])
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-gray-800 truncate">{{ $poi->translated_name }}</div>
                        <div class="text-xs text-gray-500">{{ $poi->distance }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Map container --}}
        <div
            x-data="landingMap({{ $project->latitude }}, {{ $project->longitude }}, {{ json_encode($project->name) }}, {{ json_encode($pois->filter(fn($p) => $p->latitude && $p->longitude)->map(fn($p) => ['lat' => $p->latitude, 'lng' => $p->longitude, 'name' => $p->translated_name, 'distance' => $p->distance, 'category' => $p->categoryLabel()])->values()) }})"
            x-ref="mapEl"
            class="h-96 z-0"
            x-init="$nextTick(() => init())"
        ></div>
    </div>
</section>

<script>
    function landingMap(lat, lng, label, pois) {
        return {
            init() {
                const map = L.map(this.$refs.mapEl, {
                    scrollWheelZoom: false,
                }).setView([lat, lng], 14);

                const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19,
                });

                const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    attribution: '&copy; Esri',
                    maxZoom: 19,
                });

                osmLayer.addTo(map);

                L.control.layers({
                    '{{ __("landing.map_view") }}': osmLayer,
                    '{{ __("landing.satellite_view") }}': satelliteLayer,
                }).addTo(map);

                // Project marker (blue, default)
                L.marker([lat, lng])
                    .addTo(map)
                    .bindPopup('<strong>' + label + '</strong>')
                    .openPopup();

                // POI markers (grey, smaller)
                const bounds = L.latLngBounds([[lat, lng]]);

                if (pois && pois.length) {
                    const greyIcon = L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-grey.png',
                        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                        iconSize: [20, 33],
                        iconAnchor: [10, 33],
                        popupAnchor: [1, -28],
                        shadowSize: [33, 33],
                    });

                    pois.forEach(function(poi) {
                        const m = L.marker([poi.lat, poi.lng], { icon: greyIcon }).addTo(map);
                        m.bindPopup('<strong>' + poi.name + '</strong><br><small>' + poi.category + ' &middot; ' + poi.distance + '</small>');
                        bounds.extend([poi.lat, poi.lng]);
                    });

                    map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
                }

                setTimeout(() => map.invalidateSize(), 200);
            }
        };
    }
</script>
