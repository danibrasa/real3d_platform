<x-app-layout>
    <x-slot name="title">Proyectos</x-slot>
    <x-slot name="metaDescription">Explora proyectos inmobiliarios en 3D. Busca por ubicacion, habitaciones y precio. Visualiza modelos 3D interactivos y consulta disponibilidad en tiempo real.</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Proyectos Inmobiliarios</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Search & Filters --}}
            <form method="GET" action="{{ route('viewer.index') }}" class="bg-white shadow-sm sm:rounded-lg p-4 sm:p-6 mb-6">
                {{-- Search bar --}}
                <div class="relative mb-4">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre, ubicacion o descripcion..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-cyan-500 focus:border-cyan-500">
                </div>

                {{-- Filter row --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 items-end">
                    {{-- Location --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Ubicacion</label>
                        <select name="location" class="w-full border-gray-300 rounded-lg text-sm focus:ring-cyan-500 focus:border-cyan-500">
                            <option value="">Todas</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc }}" {{ request('location') === $loc ? 'selected' : '' }}>{{ $loc }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Bedrooms --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Habitaciones</label>
                        <select name="bedrooms" class="w-full border-gray-300 rounded-lg text-sm focus:ring-cyan-500 focus:border-cyan-500">
                            <option value="">Todas</option>
                            @foreach($bedroomOptions as $bed)
                                <option value="{{ $bed }}" {{ request('bedrooms') == $bed ? 'selected' : '' }}>{{ $bed }} hab.</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Price min --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Precio min (USD)</label>
                        <input type="number" name="price_min" value="{{ request('price_min') }}" placeholder="0" min="0" step="1000" class="w-full border-gray-300 rounded-lg text-sm focus:ring-cyan-500 focus:border-cyan-500">
                    </div>

                    {{-- Price max --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Precio max (USD)</label>
                        <input type="number" name="price_max" value="{{ request('price_max') }}" placeholder="Sin limite" min="0" step="1000" class="w-full border-gray-300 rounded-lg text-sm focus:ring-cyan-500 focus:border-cyan-500">
                    </div>

                    {{-- Sort --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Ordenar por</label>
                        <select name="sort" class="w-full border-gray-300 rounded-lg text-sm focus:ring-cyan-500 focus:border-cyan-500">
                            <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Mas recientes</option>
                            <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Nombre A-Z</option>
                            <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Precio: menor a mayor</option>
                            <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Precio: mayor a menor</option>
                        </select>
                    </div>

                    {{-- Buttons --}}
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium py-2.5 px-4 rounded-lg transition-colors">
                            Filtrar
                        </button>
                        @if(request()->hasAny(['q', 'location', 'bedrooms', 'price_min', 'price_max', 'sort']))
                            <a href="{{ route('viewer.index') }}" class="px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-500 hover:bg-gray-50 transition-colors" title="Limpiar filtros">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Active filters summary --}}
                @if(request()->hasAny(['q', 'location', 'bedrooms', 'price_min', 'price_max']))
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                        <span class="text-gray-400">Filtros activos:</span>
                        @if(request('q'))
                            <span class="inline-flex items-center gap-1 bg-cyan-50 text-cyan-700 px-2 py-1 rounded-full">
                                "{{ request('q') }}"
                            </span>
                        @endif
                        @if(request('location'))
                            <span class="inline-flex items-center gap-1 bg-cyan-50 text-cyan-700 px-2 py-1 rounded-full">
                                {{ request('location') }}
                            </span>
                        @endif
                        @if(request('bedrooms'))
                            <span class="inline-flex items-center gap-1 bg-cyan-50 text-cyan-700 px-2 py-1 rounded-full">
                                {{ request('bedrooms') }} hab.
                            </span>
                        @endif
                        @if(request('price_min') || request('price_max'))
                            <span class="inline-flex items-center gap-1 bg-cyan-50 text-cyan-700 px-2 py-1 rounded-full">
                                USD {{ request('price_min', '0') }} - {{ request('price_max', '∞') }}
                            </span>
                        @endif
                        <span class="text-gray-400 ml-1">{{ $projects->total() }} resultado{{ $projects->total() !== 1 ? 's' : '' }}</span>
                    </div>
                @endif
            </form>

            {{-- Results --}}
            @if($projects->count())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($projects as $project)
                        <a href="{{ route('viewer.landing', $project->slug) }}" class="group block bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5">
                            {{-- Thumbnail --}}
                            <div class="h-48 relative overflow-hidden">
                                @php
                                    $thumbnail = $project->files->where('file_type', 'thumbnail')->first();
                                @endphp
                                @if($thumbnail)
                                    <img src="{{ url('api/projects/' . $project->id . '/files/thumbnail') }}" alt="{{ $project->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center">
                                        <svg class="w-16 h-16 text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    </div>
                                @endif

                                {{-- Overlay badges --}}
                                <div class="absolute top-3 left-3 flex gap-2">
                                    @if($project->units_count > 0 && $project->available_units_count > 0)
                                        <span class="bg-green-500/90 text-white text-xs font-medium px-2 py-1 rounded-md backdrop-blur-sm">
                                            {{ $project->available_units_count }} disponible{{ $project->available_units_count !== 1 ? 's' : '' }}
                                        </span>
                                    @elseif($project->units_count > 0)
                                        <span class="bg-amber-500/90 text-white text-xs font-medium px-2 py-1 rounded-md backdrop-blur-sm">
                                            Agotado
                                        </span>
                                    @endif
                                </div>

                                @php
                                    $has3d = $project->files->where('file_type', 'model_3d')->first();
                                    $has360 = $project->files->where('file_type', 'video_360')->first();
                                @endphp
                                @if($has3d || $has360)
                                    <div class="absolute top-3 right-3 flex gap-1">
                                        @if($has3d)
                                            <span class="bg-black/50 text-white text-xs px-1.5 py-0.5 rounded backdrop-blur-sm" title="Modelo 3D">3D</span>
                                        @endif
                                        @if($has360)
                                            <span class="bg-black/50 text-white text-xs px-1.5 py-0.5 rounded backdrop-blur-sm" title="Video 360">360°</span>
                                        @endif
                                    </div>
                                @endif

                                <div class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-black/30 to-transparent"></div>
                            </div>

                            {{-- Info --}}
                            <div class="p-5">
                                <h3 class="font-semibold text-lg text-gray-900 mb-1 group-hover:text-cyan-600 transition-colors">{{ $project->name }}</h3>

                                @if($project->location)
                                    <p class="text-sm text-gray-500 flex items-center gap-1 mb-2">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                        {{ $project->location }}
                                    </p>
                                @endif

                                @if($project->description)
                                    <p class="text-sm text-gray-400 line-clamp-2 mb-3">{{ $project->description }}</p>
                                @endif

                                {{-- Price range --}}
                                @if($project->units_count > 0)
                                    @php
                                        $minPrice = $project->units->where('status', 'available')->min('price');
                                        $maxPrice = $project->units->where('status', 'available')->max('price');
                                    @endphp
                                    @if($minPrice)
                                        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                            <div>
                                                <span class="text-xs text-gray-400">Desde</span>
                                                <span class="block text-sm font-semibold text-gray-800">{{ app(\App\Services\CurrencyService::class)->format($minPrice, \App\Services\CurrencyService::getCurrentCode()) }}</span>
                                            </div>
                                            <span class="text-cyan-600 text-sm font-medium group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                                                Ver proyecto
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 5l7 7-7 7"/></svg>
                                            </span>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-8">{{ $projects->links() }}</div>

            @else
                {{-- No results --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                    @if(request()->hasAny(['q', 'location', 'bedrooms', 'price_min', 'price_max']))
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <p class="text-gray-500 mb-1">No se encontraron proyectos con los filtros aplicados.</p>
                        <a href="{{ route('viewer.index') }}" class="text-sm text-cyan-600 hover:text-cyan-700 font-medium">Limpiar filtros</a>
                    @else
                        <p class="text-gray-500">No hay proyectos publicados todavia.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
