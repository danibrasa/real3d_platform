<x-app-layout>
    <x-slot name="title">Editar: {{ $project->name }}</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar: {{ $project->name }}</h2>
            <div class="flex gap-2">
                @if($project->status !== 'draft')
                    <a href="{{ route('viewer.landing', $project->slug) }}" target="_blank" class="text-sm text-blue-600 hover:underline">Ver landing</a>
                    <a href="{{ route('viewer.show', $project->slug) }}" target="_blank" class="text-sm text-blue-600 hover:underline">Ver visor 3D</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            <!-- Quick Links -->
            <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('admin.projects.typologies.index', $project) }}" class="inline-flex items-center px-4 py-2 bg-indigo-50 text-indigo-700 rounded-md text-sm font-medium hover:bg-indigo-100 transition">
                        Tipologias ({{ $project->typologies_count ?? 0 }})
                    </a>
                    <a href="{{ route('admin.projects.units.index', $project) }}" class="inline-flex items-center px-4 py-2 bg-emerald-50 text-emerald-700 rounded-md text-sm font-medium hover:bg-emerald-100 transition">
                        Unidades ({{ $project->units_count ?? 0 }})
                    </a>
                    <a href="{{ route('admin.projects.payment-plans.index', $project) }}" class="inline-flex items-center px-4 py-2 bg-purple-50 text-purple-700 rounded-md text-sm font-medium hover:bg-purple-100 transition">
                        Planes de pago ({{ $project->payment_plans_count ?? 0 }})
                    </a>
                    <a href="{{ route('admin.projects.construction.index', $project) }}" class="inline-flex items-center px-4 py-2 bg-orange-50 text-orange-700 rounded-md text-sm font-medium hover:bg-orange-100 transition">
                        Progreso de obra
                    </a>
                    <span class="inline-flex items-center px-4 py-2 bg-amber-50 text-amber-700 rounded-md text-sm font-medium">
                        Galeria ({{ $project->gallery_images_count ?? 0 }})
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Col 1: Metadata + Uploads -->
                <div class="space-y-6">
                    <!-- Project Info -->
                    @if(auth()->user()->can('edit-project-technical', $project) || auth()->user()->can('edit-project-commercial', $project))
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="font-semibold mb-4">Datos del proyecto</h3>
                        <form method="POST" action="{{ route('admin.projects.update', $project) }}">
                            @csrf @method('PUT')

                            @can('edit-project-technical', $project)
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                                <input type="text" name="name" value="{{ $project->name }}" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                            @else
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                                <p class="text-sm text-gray-600 py-2">{{ $project->name }}</p>
                            </div>
                            @endcan

                            @can('edit-project-commercial', $project)
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tagline (ES)</label>
                                <input type="text" name="tagline" value="{{ $project->tagline }}" placeholder="Frase comercial corta" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tagline (EN)</label>
                                <input type="text" name="tagline_en" value="{{ $project->tagline_en }}" placeholder="Short commercial phrase (English)" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ubicacion (ES)</label>
                                <input type="text" name="location" value="{{ $project->location }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Location (EN)</label>
                                <input type="text" name="location_en" value="{{ $project->location_en }}" placeholder="Location in English" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Descripcion (ES)</label>
                                <textarea name="description" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">{{ $project->description }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description (EN)</label>
                                <textarea name="description_en" rows="3" placeholder="Project description in English" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">{{ $project->description_en }}</textarea>
                            </div>
                            @endcan

                            @can('edit-project-technical', $project)
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Total pisos</label>
                                    <input type="number" name="total_floors" value="{{ $project->total_floors }}" min="1" max="200" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                @can('edit-project-commercial', $project)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Entrega estimada</label>
                                    <input type="date" name="estimated_delivery" value="{{ $project->estimated_delivery?->format('Y-m-d') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                @endcan
                            </div>
                            @else
                                @can('edit-project-commercial', $project)
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Entrega estimada</label>
                                    <input type="date" name="estimated_delivery" value="{{ $project->estimated_delivery?->format('Y-m-d') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                @endcan
                            @endcan

                            @can('edit-project-commercial', $project)
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp (con codigo pais)</label>
                                <input type="text" name="whatsapp_number" value="{{ $project->whatsapp_number }}" placeholder="+5491112345678" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mensaje WhatsApp (ES)</label>
                                <input type="text" name="whatsapp_message" value="{{ $project->whatsapp_message }}" placeholder="Hola, me interesa el proyecto..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <p class="text-xs text-gray-400 mt-1">Mensaje pre-cargado al hacer clic en WhatsApp. Dejar vacio para usar el predeterminado.</p>
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mensaje WhatsApp (EN)</label>
                                <input type="text" name="whatsapp_message_en" value="{{ $project->whatsapp_message_en }}" placeholder="Hi, I'm interested in..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email de contacto</label>
                                <input type="email" name="contact_email" value="{{ $project->contact_email }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Google Analytics ID</label>
                                <input type="text" name="analytics_id" value="{{ $project->analytics_id }}" placeholder="G-XXXXXXXXXX o GTM-XXXXXXX" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <p class="text-xs text-gray-400 mt-1">ID de GA4 o GTM para este proyecto. Deje vacio para usar el global.</p>
                            </div>

                            {{-- Investment Calculator Settings --}}
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3">Calculadora de Inversion</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Tarifa noche (USD)</label>
                                        <input type="number" name="avg_nightly_rate" value="{{ $project->avg_nightly_rate }}" step="1" placeholder="120" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Ocupacion media (%)</label>
                                        <input type="number" name="average_occupancy" value="{{ $project->average_occupancy }}" step="1" min="0" max="100" placeholder="70" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Apreciacion anual (%)</label>
                                        <input type="number" name="appreciation_rate_annual" value="{{ $project->appreciation_rate_annual }}" step="0.5" placeholder="6" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Fee administracion (%)</label>
                                        <input type="number" name="management_fee" value="{{ $project->management_fee }}" step="1" placeholder="20" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Impuesto propiedad (%)</label>
                                        <input type="number" name="property_tax_rate" value="{{ $project->property_tax_rate }}" step="0.1" placeholder="1" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                </div>
                                <p class="text-xs text-gray-400 mt-2">Parametros para la calculadora de inversion en la pagina publica. Deje vacio para usar valores por defecto.</p>
                            </div>
                            @endcan

                            @can('edit-project-technical', $project)
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Visibilidad</label>
                                <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <option value="draft" {{ $project->status === 'draft' ? 'selected' : '' }}>Borrador</option>
                                    <option value="public" {{ $project->status === 'public' ? 'selected' : '' }}>Publico</option>
                                    <option value="private" {{ $project->status === 'private' ? 'selected' : '' }}>Privado</option>
                                    <option value="unlisted" {{ $project->status === 'unlisted' ? 'selected' : '' }}>Oculto</option>
                                </select>
                                <p class="text-xs text-gray-400 mt-1">Publico: todos | Privado: solo logueados | Oculto: solo con enlace</p>
                            </div>
                            @endcan

                            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition">Guardar datos</button>
                        </form>
                    </div>
                    @else
                    <!-- Read-only view for agente -->
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="font-semibold mb-4">Datos del proyecto</h3>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-gray-500">Nombre</dt><dd class="font-medium">{{ $project->name }}</dd></div>
                            @if($project->tagline)<div><dt class="text-gray-500">Tagline</dt><dd>{{ $project->tagline }}</dd></div>@endif
                            @if($project->location)<div><dt class="text-gray-500">Ubicacion</dt><dd>{{ $project->location }}</dd></div>@endif
                            @if($project->description)<div><dt class="text-gray-500">Descripcion</dt><dd>{{ $project->description }}</dd></div>@endif
                        </dl>
                    </div>
                    @endif

                    <!-- File Uploads -->
                    @can('upload-files')
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="font-semibold mb-4">Archivos</h3>

                        <!-- Video 360 -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Video 360 (.mp4)</label>
                            @php $videoFile = $project->getFileByType('video_360'); @endphp
                            @if($videoFile)
                                <div class="text-xs text-green-600 mb-1">{{ $videoFile->original_name }} ({{ number_format($videoFile->file_size / 1048576, 1) }} MB)</div>
                            @endif
                            <input type="file" accept="video/mp4,video/*" class="upload-input w-full text-sm" data-file-type="video_360" data-project-id="{{ $project->id }}">
                            <div class="upload-progress hidden mt-1"><div class="h-2 bg-blue-200 rounded overflow-hidden"><div class="h-full bg-blue-600 rounded transition-all" style="width: 0%"></div></div></div>
                        </div>

                        <!-- Modelo 3D -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Modelo 3D (.glb / .gltf)</label>
                            @php $modelFile = $project->getFileByType('model_3d'); @endphp
                            @if($modelFile)
                                <div class="text-xs text-green-600 mb-1">{{ $modelFile->original_name }} ({{ number_format($modelFile->file_size / 1048576, 1) }} MB)</div>
                            @endif
                            <input type="file" accept=".glb,.gltf" class="upload-input w-full text-sm" data-file-type="model_3d" data-project-id="{{ $project->id }}">
                            <div class="upload-progress hidden mt-1"><div class="h-2 bg-blue-200 rounded overflow-hidden"><div class="h-full bg-blue-600 rounded transition-all" style="width: 0%"></div></div></div>
                        </div>

                        <!-- Textura suelo -->
                        <div class="mb-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Textura de suelo (imagen)</label>
                            @php $texFile = $project->getFileByType('ground_texture'); @endphp
                            @if($texFile)
                                <div class="text-xs text-green-600 mb-1">{{ $texFile->original_name }}</div>
                            @endif
                            <input type="file" accept="image/*" class="upload-input w-full text-sm" data-file-type="ground_texture" data-project-id="{{ $project->id }}">
                            <div class="upload-progress hidden mt-1"><div class="h-2 bg-blue-200 rounded overflow-hidden"><div class="h-full bg-blue-600 rounded transition-all" style="width: 0%"></div></div></div>
                        </div>
                    </div>
                    @endcan

                    <!-- Gallery -->
                    @can('manage-gallery', $project)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="font-semibold mb-4">Galeria de imagenes</h3>
                        <form method="POST" action="{{ route('admin.projects.gallery.store', $project) }}" enctype="multipart/form-data">
                            @csrf
                            <input type="file" name="images[]" multiple accept="image/*" class="w-full text-sm mb-2">
                            <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold hover:bg-indigo-700 transition">Subir imagenes</button>
                        </form>
                        @if($project->galleryImages->count())
                        <div class="grid grid-cols-3 gap-2 mt-4">
                            @foreach($project->galleryImages as $img)
                            <div class="relative group">
                                <div class="h-20 bg-gray-100 rounded overflow-hidden">
                                    <img src="{{ route('admin.projects.gallery.destroy', [$project, $img]) }}" class="w-full h-full object-cover" alt="" onerror="this.style.display='none'">
                                </div>
                                <form method="POST" action="{{ route('admin.projects.gallery.destroy', [$project, $img]) }}" class="absolute top-1 right-1 hidden group-hover:block">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-5 h-5 bg-red-600 text-white rounded-full text-xs leading-none flex items-center justify-center hover:bg-red-700">&times;</button>
                                </form>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endcan
                </div>

                <!-- Col 2-3: Viewer Preview + Settings -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- 3D Viewer Preview -->
                    @can('edit-viewer-settings')
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div id="viewer-container" class="w-full" style="height: 500px; background: #1a1a2e;">
                            <p id="viewer-placeholder" class="text-gray-400 text-sm text-center pt-48">El visor 3D se cargara cuando subas un modelo o video.</p>
                        </div>
                    </div>

                    <!-- Viewer Settings -->
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-semibold">Configuracion del visor</h3>
                            <button id="btn-save-settings" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-semibold hover:bg-green-700 transition">
                                Guardar settings
                            </button>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Rotacion modelo</label>
                                <input type="range" id="s-model-rotation" min="0" max="360" value="{{ $project->settings->model_rotation ?? 0 }}" class="w-full accent-blue-600">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Escala modelo</label>
                                <input type="range" id="s-model-scale" min="1" max="200" value="{{ $project->settings->model_scale ?? 100 }}" class="w-full accent-blue-600">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Elevacion modelo</label>
                                <input type="range" id="s-model-elevation" min="-50" max="50" value="{{ $project->settings->model_elevation ?? 0 }}" class="w-full accent-blue-600">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Altura suelo</label>
                                <input type="range" id="s-ground-height" min="-100" max="100" value="{{ $project->settings->ground_height ?? 0 }}" class="w-full accent-blue-600">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Opacidad suelo</label>
                                <input type="range" id="s-ground-opacity" min="0" max="100" value="{{ $project->settings->ground_opacity ?? 100 }}" class="w-full accent-blue-600">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Opacidad video</label>
                                <input type="range" id="s-video-opacity" min="0" max="100" value="{{ $project->settings->video_opacity ?? 100 }}" class="w-full accent-blue-600">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Textura suelo</label>
                                <select id="s-ground-texture" class="w-full rounded-md border-gray-300 text-sm">
                                    <option value="grass" {{ ($project->settings->ground_texture_type ?? 'grass') === 'grass' ? 'selected' : '' }}>Pasto</option>
                                    <option value="concrete" {{ ($project->settings->ground_texture_type ?? '') === 'concrete' ? 'selected' : '' }}>Concreto</option>
                                    <option value="dirt" {{ ($project->settings->ground_texture_type ?? '') === 'dirt' ? 'selected' : '' }}>Tierra</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Iluminacion</label>
                                <select id="s-lighting" class="w-full rounded-md border-gray-300 text-sm">
                                    <option value="morning" {{ ($project->settings->lighting_preset ?? '') === 'morning' ? 'selected' : '' }}>Manana</option>
                                    <option value="noon" {{ ($project->settings->lighting_preset ?? 'noon') === 'noon' ? 'selected' : '' }}>Mediodia</option>
                                    <option value="evening" {{ ($project->settings->lighting_preset ?? '') === 'evening' ? 'selected' : '' }}>Atardecer</option>
                                </select>
                            </div>
                            <div class="flex items-end gap-2">
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" id="s-ground-visible" {{ ($project->settings->ground_visible ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600">
                                    Mostrar suelo
                                </label>
                            </div>
                        </div>
                    </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    @can('edit-viewer-settings')
    <!-- Project data for JS -->
    <script type="application/json" id="project-data">
        {!! json_encode([
            'id' => $project->id,
            'slug' => $project->slug,
            'settings' => $project->settings,
            'files' => [
                'video_360' => $project->getFileByType('video_360') ? '/api/projects/' . $project->id . '/files/video_360' : null,
                'model_3d' => $project->getFileByType('model_3d') ? '/api/projects/' . $project->id . '/files/model_3d' : null,
            ],
            'routes' => [
                'settings_update' => route('admin.projects.settings.update', $project),
                'upload_init' => route('admin.projects.upload.init', $project),
                'upload_chunk' => route('admin.projects.upload.chunk', $project),
                'upload_complete' => route('admin.projects.upload.complete', $project),
            ],
            'csrf' => csrf_token(),
        ]) !!}
    </script>
    <script src="/js/admin-upload.js"></script>
    <script type="module" src="/js/viewer-admin.js"></script>

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
    @endcan
</x-app-layout>
