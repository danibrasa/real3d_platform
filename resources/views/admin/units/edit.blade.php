<x-app-layout>
    <x-slot name="title">Editar Unidad: {{ $unit->identifier }}</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Unidad: {{ $unit->identifier }}</h2>
            <a href="{{ route('admin.projects.units.index', $project) }}" class="text-sm text-gray-600 hover:underline">&larr; Volver</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.projects.units.update', [$project, $unit]) }}" enctype="multipart/form-data">
                    @csrf @method('PUT')

                    @can('edit-unit-full')
                    {{-- Full form for superadmin/gestor --}}
                    @if($typologies->count())
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipologia (opcional)</label>
                        <select name="typology_id" id="typology-select" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Sin tipologia</option>
                            @foreach($typologies as $t)
                                <option value="{{ $t->id }}" data-bedrooms="{{ $t->bedrooms }}" data-bathrooms="{{ $t->bathrooms }}" data-area="{{ $t->area_m2 }}" {{ old('typology_id', $unit->typology_id) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Identificador *</label>
                            <input type="text" name="identifier" value="{{ old('identifier', $unit->identifier) }}" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            @error('identifier') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Piso *</label>
                            <input type="number" name="floor" value="{{ old('floor', $unit->floor) }}" min="0" max="200" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dormitorios *</label>
                            <input type="number" name="bedrooms" id="field-bedrooms" value="{{ old('bedrooms', $unit->bedrooms) }}" min="0" max="10" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Banos *</label>
                            <input type="number" name="bathrooms" id="field-bathrooms" value="{{ old('bathrooms', $unit->bathrooms) }}" min="0" max="10" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Area m2 *</label>
                            <input type="number" name="area_m2" id="field-area" value="{{ old('area_m2', $unit->area_m2) }}" min="1" max="9999" step="0.01" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                    </div>
                    @else
                    {{-- Read-only info for inmobiliaria --}}
                    <div class="mb-4 p-3 bg-gray-50 rounded-md">
                        <dl class="grid grid-cols-2 gap-2 text-sm">
                            <div><dt class="text-gray-500">Identificador</dt><dd class="font-medium">{{ $unit->identifier }}</dd></div>
                            <div><dt class="text-gray-500">Piso</dt><dd class="font-medium">{{ $unit->floor }}</dd></div>
                            <div><dt class="text-gray-500">Dormitorios</dt><dd class="font-medium">{{ $unit->bedrooms }}</dd></div>
                            <div><dt class="text-gray-500">Banos</dt><dd class="font-medium">{{ $unit->bathrooms }}</dd></div>
                            <div><dt class="text-gray-500">Area</dt><dd class="font-medium">{{ $unit->area_m2 }} m2</dd></div>
                            <div><dt class="text-gray-500">Tipologia</dt><dd class="font-medium">{{ $unit->typology?->name ?? '-' }}</dd></div>
                        </dl>
                    </div>
                    @endcan

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Precio USD *</label>
                            <input type="number" name="price" value="{{ old('price', $unit->price) }}" min="0" max="99999999.99" step="0.01" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                            <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="available" {{ old('status', $unit->status) === 'available' ? 'selected' : '' }}>Disponible</option>
                                <option value="reserved" {{ old('status', $unit->status) === 'reserved' ? 'selected' : '' }}>Reservado</option>
                                <option value="sold" {{ old('status', $unit->status) === 'sold' ? 'selected' : '' }}>Vendido</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas internas</label>
                        <textarea name="notes" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">{{ old('notes', $unit->notes) }}</textarea>
                    </div>

                    @can('edit-unit-full')
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                            <input type="number" name="sort_order" value="{{ old('sort_order', $unit->sort_order) }}" min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Plano (imagen)</label>
                            @if($unit->floor_plan_path)
                                <p class="text-xs text-green-600 mb-1">Plano actual cargado.</p>
                            @endif
                            <input type="file" name="floor_plan" accept="image/*" class="w-full text-sm">
                        </div>
                    </div>

                    <!-- Bounding Box 3D -->
                    <div class="mb-4 border border-gray-200 rounded-lg">
                        <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden')" class="w-full px-4 py-3 text-left text-sm font-medium text-gray-700 hover:bg-gray-50 flex justify-between items-center">
                            <span>Bounding Box 3D</span>
                            <span class="text-xs text-gray-400">{{ $unit->has_bbox ? 'Configurado' : 'No configurado' }}</span>
                        </button>
                        <div class="hidden px-4 pb-4 space-y-3">
                            <p class="text-xs text-gray-500">Coordenadas como fracciones (0-1) del bounding box del modelo. Usar la herramienta de mapeo visual es mas facil.</p>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Centro X</label>
                                    <input type="number" name="bbox_center_x" value="{{ old('bbox_center_x', $unit->bbox_center_x) }}" min="0" max="1" step="0.001" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Centro Y</label>
                                    <input type="number" name="bbox_center_y" value="{{ old('bbox_center_y', $unit->bbox_center_y) }}" min="0" max="1" step="0.001" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Centro Z</label>
                                    <input type="number" name="bbox_center_z" value="{{ old('bbox_center_z', $unit->bbox_center_z) }}" min="0" max="1" step="0.001" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Tamano X</label>
                                    <input type="number" name="bbox_size_x" value="{{ old('bbox_size_x', $unit->bbox_size_x) }}" min="0" max="1" step="0.001" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Tamano Y</label>
                                    <input type="number" name="bbox_size_y" value="{{ old('bbox_size_y', $unit->bbox_size_y) }}" min="0" max="1" step="0.001" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Tamano Z</label>
                                    <input type="number" name="bbox_size_z" value="{{ old('bbox_size_z', $unit->bbox_size_z) }}" min="0" max="1" step="0.001" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                    @endcan

                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>

    @can('edit-unit-full')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sel = document.getElementById('typology-select');
            if (!sel) return;
            sel.addEventListener('change', function() {
                const opt = sel.options[sel.selectedIndex];
                if (opt.value) {
                    document.getElementById('field-bedrooms').value = opt.dataset.bedrooms;
                    document.getElementById('field-bathrooms').value = opt.dataset.bathrooms;
                    document.getElementById('field-area').value = opt.dataset.area;
                }
            });
        });
    </script>
    @endcan
</x-app-layout>
