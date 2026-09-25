<x-app-layout>
    <x-slot name="title">Ubicacion: {{ $project->name }}</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ubicacion y POIs: {{ $project->name }}</h2>
            <a href="{{ route('admin.projects.edit', $project) }}" class="text-sm text-gray-600 hover:underline">&larr; Volver al proyecto</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8" x-data="locationEditor({
            lat: {{ $project->latitude ?? 'null' }},
            lng: {{ $project->longitude ?? 'null' }},
            pois: {{ $project->pointsOfInterest->map(fn($p) => ['id' => $p->id, 'lat' => $p->latitude, 'lng' => $p->longitude, 'name' => $p->name, 'category' => $p->category])->toJson() }}
        })">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            <!-- Section 1: Project Location -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="font-semibold text-lg mb-4">Ubicacion del proyecto</h3>

                {{-- Map --}}
                <div x-ref="mapContainer" class="h-96 rounded-lg border border-gray-300 z-0 mb-4" x-init="$nextTick(() => initMap())"></div>

                <form method="POST" action="{{ route('admin.projects.location.updateCoords', $project) }}">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Latitud</label>
                            <input type="number" name="latitude" x-model="lat" step="0.0000001" min="-90" max="90" placeholder="18.6834" @change="updateMarkerFromInputs()" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Longitud</label>
                            <input type="number" name="longitude" x-model="lng" step="0.0000001" min="-180" max="180" placeholder="-68.4475" @change="updateMarkerFromInputs()" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mb-3">Haga clic en el mapa o arrastre el marcador azul para definir la ubicacion del proyecto.</p>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition">Guardar ubicacion</button>
                </form>
            </div>

            <!-- Section 2: Points of Interest -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="font-semibold text-lg mb-4">Puntos de interes</h3>

                {{-- Add POI form --}}
                <form method="POST" action="{{ route('admin.projects.location.storePoi', $project) }}" class="border border-gray-200 rounded-lg p-4 mb-6 bg-gray-50">
                    @csrf
                    <h4 class="text-sm font-semibold text-gray-600 mb-3">Agregar punto de interes</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Categoria</label>
                            <select name="category" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">Seleccionar...</option>
                                @foreach(\App\Models\PointOfInterest::CATEGORIES as $key => $labels)
                                    <option value="{{ $key }}">{{ $labels['es'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Nombre (ES)</label>
                            <input type="text" name="name" required placeholder="Ej: Playa Bavaro" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Name (EN)</label>
                            <input type="text" name="name_en" placeholder="Ej: Bavaro Beach" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Distancia</label>
                            <input type="text" name="distance" required placeholder="5 km, 15 min" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Latitud (opcional)</label>
                            <input type="number" name="latitude" x-model="poiLat" step="0.0000001" min="-90" max="90" placeholder="Opcional" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Longitud (opcional)</label>
                            <input type="number" name="longitude" x-model="poiLng" step="0.0000001" min="-180" max="180" placeholder="Opcional" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-semibold hover:bg-green-700 transition">Agregar</button>
                        <button type="button" @click="startPoiPick()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-300 transition" :class="pickingPoi && 'ring-2 ring-blue-400 bg-blue-50'">
                            <template x-if="!pickingPoi">
                                <span>Marcar en mapa</span>
                            </template>
                            <template x-if="pickingPoi">
                                <span>Haga clic en el mapa...</span>
                            </template>
                        </button>
                    </div>
                </form>

                {{-- Existing POIs --}}
                @forelse($project->pointsOfInterest as $poi)
                <div class="border border-gray-200 rounded-lg p-4 mb-3 flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3 flex-1 min-w-0">
                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                            @include('admin.projects._poi-icon', ['category' => $poi->category])
                        </div>
                        <div class="min-w-0">
                            <div class="font-medium text-gray-800">{{ $poi->name }}</div>
                            @if($poi->name_en)
                                <div class="text-xs text-gray-400">{{ $poi->name_en }}</div>
                            @endif
                            <div class="text-sm text-gray-500">
                                <span class="inline-flex items-center gap-1">
                                    <span class="px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded text-xs">{{ $poi->categoryLabel() }}</span>
                                    <span>&middot;</span>
                                    <span>{{ $poi->distance }}</span>
                                </span>
                            </div>
                            @if($poi->latitude && $poi->longitude)
                                <div class="text-xs text-gray-400 mt-1">{{ $poi->latitude }}, {{ $poi->longitude }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        {{-- Edit button opens inline form --}}
                        <button type="button"
                            x-data="{ open: false }"
                            @click="open = !open; $nextTick(() => { if(open) $refs['editForm{{ $poi->id }}'].classList.remove('hidden'); else $refs['editForm{{ $poi->id }}'].classList.add('hidden'); })"
                            class="text-sm text-blue-600 hover:text-blue-800">
                            Editar
                        </button>
                        <form method="POST" action="{{ route('admin.projects.location.destroyPoi', [$project, $poi]) }}" onsubmit="return confirm('Eliminar este punto de interes?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">Eliminar</button>
                        </form>
                    </div>
                </div>

                {{-- Edit form (hidden by default) --}}
                <div id="editForm{{ $poi->id }}" x-ref="editForm{{ $poi->id }}" class="hidden border border-blue-200 rounded-lg p-4 mb-3 bg-blue-50">
                    <form method="POST" action="{{ route('admin.projects.location.updatePoi', [$project, $poi]) }}">
                        @csrf @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Categoria</label>
                                <select name="category" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    @foreach(\App\Models\PointOfInterest::CATEGORIES as $key => $labels)
                                        <option value="{{ $key }}" {{ $poi->category === $key ? 'selected' : '' }}>{{ $labels['es'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Nombre (ES)</label>
                                <input type="text" name="name" value="{{ $poi->name }}" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Name (EN)</label>
                                <input type="text" name="name_en" value="{{ $poi->name_en }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Distancia</label>
                                <input type="text" name="distance" value="{{ $poi->distance }}" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Latitud</label>
                                <input type="number" name="latitude" value="{{ $poi->latitude }}" step="0.0000001" min="-90" max="90" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Longitud</label>
                                <input type="number" name="longitude" value="{{ $poi->longitude }}" step="0.0000001" min="-180" max="180" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition">Guardar</button>
                            <button type="button" @click="$refs['editForm{{ $poi->id }}'].classList.add('hidden')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-300 transition">Cancelar</button>
                        </div>
                    </form>
                </div>
                @empty
                <p class="text-sm text-gray-400 py-4">No hay puntos de interes. Agrega el primero usando el formulario de arriba.</p>
                @endforelse
            </div>
        </div>
    </div>

    @push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    @endpush
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        function locationEditor(config) {
            return {
                lat: config.lat,
                lng: config.lng,
                pois: config.pois || [],
                map: null,
                marker: null,
                poiMarkers: [],
                pickingPoi: false,
                poiLat: null,
                poiLng: null,

                initMap() {
                    const defaultLat = this.lat || 18.5;
                    const defaultLng = this.lng || -68.4;
                    const zoom = (this.lat && this.lng) ? 14 : 8;

                    this.map = L.map(this.$refs.mapContainer).setView([defaultLat, defaultLng], zoom);

                    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors',
                        maxZoom: 19,
                    });

                    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                        attribution: '&copy; Esri',
                        maxZoom: 19,
                    });

                    osmLayer.addTo(this.map);

                    L.control.layers({
                        'Mapa': osmLayer,
                        'Satelite': satelliteLayer,
                    }).addTo(this.map);

                    // Project marker (blue)
                    if (this.lat && this.lng) {
                        this.marker = L.marker([this.lat, this.lng], { draggable: true }).addTo(this.map);
                        this.marker.bindPopup('<strong>Proyecto</strong>').openPopup();
                        this.marker.on('dragend', () => this.onMarkerDrag());
                    }

                    // POI markers (grey)
                    this.pois.forEach(poi => {
                        if (poi.lat && poi.lng) {
                            const greyIcon = L.icon({
                                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-grey.png',
                                shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                                iconSize: [25, 41],
                                iconAnchor: [12, 41],
                                popupAnchor: [1, -34],
                                shadowSize: [41, 41],
                            });
                            const m = L.marker([poi.lat, poi.lng], { icon: greyIcon }).addTo(this.map);
                            m.bindPopup('<strong>' + poi.name + '</strong><br><small>' + poi.category + '</small>');
                            this.poiMarkers.push(m);
                        }
                    });

                    this.map.on('click', (e) => this.onMapClick(e));

                    setTimeout(() => this.map.invalidateSize(), 200);
                },

                onMapClick(e) {
                    const clickLat = parseFloat(e.latlng.lat.toFixed(7));
                    const clickLng = parseFloat(e.latlng.lng.toFixed(7));

                    if (this.pickingPoi) {
                        this.poiLat = clickLat;
                        this.poiLng = clickLng;
                        this.pickingPoi = false;
                    } else {
                        this.lat = clickLat;
                        this.lng = clickLng;
                        this.placeMarker();
                    }
                },

                onMarkerDrag() {
                    const pos = this.marker.getLatLng();
                    this.lat = parseFloat(pos.lat.toFixed(7));
                    this.lng = parseFloat(pos.lng.toFixed(7));
                },

                updateMarkerFromInputs() {
                    if (this.lat && this.lng) {
                        this.placeMarker();
                        this.map.setView([this.lat, this.lng], Math.max(this.map.getZoom(), 14));
                    }
                },

                placeMarker() {
                    if (this.marker) {
                        this.marker.setLatLng([this.lat, this.lng]);
                    } else {
                        this.marker = L.marker([this.lat, this.lng], { draggable: true }).addTo(this.map);
                        this.marker.bindPopup('<strong>Proyecto</strong>').openPopup();
                        this.marker.on('dragend', () => this.onMarkerDrag());
                    }
                },

                startPoiPick() {
                    this.pickingPoi = !this.pickingPoi;
                },
            };
        }
    </script>
</x-app-layout>
