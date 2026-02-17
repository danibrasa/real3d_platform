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

<x-app-layout>
    <x-slot name="title">Mapeo 3D: {{ $project->name }}</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mapeo 3D: {{ $project->name }}</h2>
            <a href="{{ route('admin.projects.units.index', $project) }}" class="text-sm text-gray-600 hover:underline">&larr; Volver a unidades</a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-full mx-auto px-4">
            @if(!$project->getFileByType('model_3d'))
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                    <p class="text-yellow-800">Este proyecto no tiene modelo 3D cargado. Suba un modelo primero.</p>
                    <a href="{{ route('admin.projects.edit', $project) }}" class="text-blue-600 hover:underline mt-2 inline-block">Ir al proyecto</a>
                </div>
            @else
            <div class="flex gap-4" style="height: calc(100vh - 160px);">
                <!-- Left panel: unit list -->
                <div class="w-80 flex-shrink-0 bg-white rounded-lg shadow-sm overflow-hidden flex flex-col">
                    <div class="p-3 bg-gray-50 border-b">
                        <h3 class="text-sm font-semibold text-gray-700">Unidades ({{ $units->count() }})</h3>
                        <p class="text-xs text-gray-500 mt-1">Click para seleccionar, luego click en modelo para posicionar</p>
                    </div>
                    <div class="overflow-y-auto flex-1" id="unit-list">
                        @foreach($units as $unit)
                        <div class="unit-list-item px-3 py-2 border-b border-gray-100 cursor-pointer hover:bg-blue-50 flex items-center gap-2 text-sm"
                             data-unit-id="{{ $unit->id }}"
                             data-unit-identifier="{{ $unit->identifier }}"
                             data-unit-floor="{{ $unit->floor }}"
                             data-unit-status="{{ $unit->status }}"
                             data-unit-bbox="{{ $unit->has_bbox ? json_encode(['cx'=>$unit->bbox_center_x,'cy'=>$unit->bbox_center_y,'cz'=>$unit->bbox_center_z,'sx'=>$unit->bbox_size_x,'sy'=>$unit->bbox_size_y,'sz'=>$unit->bbox_size_z]) : '' }}">
                            <span class="w-3 h-3 rounded-full flex-shrink-0 {{ $unit->has_bbox ? 'bg-green-500' : 'bg-gray-300' }}" id="dot-{{ $unit->id }}"></span>
                            <span class="font-medium">{{ $unit->identifier }}</span>
                            <span class="text-gray-400 text-xs">P{{ $unit->floor }}</span>
                            <span class="ml-auto text-xs px-1.5 py-0.5 rounded {{ $unit->status === 'available' ? 'bg-green-100 text-green-700' : ($unit->status === 'reserved' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">{{ $unit->status }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Right panel: 3D viewer + controls -->
                <div class="flex-1 flex flex-col gap-3">
                    <!-- 3D Viewer -->
                    <div class="flex-1 bg-black rounded-lg overflow-hidden relative" id="canvas-container">
                        <div id="loading-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 100;">
                            <div style="width: 40px; height: 40px; border: 3px solid rgba(79,195,247,0.2); border-top-color: #4fc3f7; border-radius: 50%; animation: mapper-spin 0.8s linear infinite;"></div>
                            <p id="loading-text" style="color: #aaa; margin-top: 16px; font-size: 14px;">Cargando modelo 3D...</p>
                        </div>
                        <style>@keyframes mapper-spin { to { transform: rotate(360deg); } }</style>
                        <!-- Info overlay -->
                        <div id="selected-info" class="hidden absolute top-3 left-3 z-10 bg-black/60 backdrop-blur-sm text-white text-sm px-3 py-2 rounded-lg">
                            <span id="selected-name">-</span>
                        </div>
                        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 z-10 text-xs text-gray-400 bg-black/50 px-3 py-1.5 rounded-lg">
                            Click izq en modelo = posicionar centro | Scroll = zoom | Click der = mover
                        </div>
                    </div>

                    <!-- Slider controls -->
                    <div class="bg-white rounded-lg shadow-sm p-4" id="bbox-controls">
                        <div class="grid grid-cols-6 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Centro X</label>
                                <input type="range" id="slider-cx" min="0" max="1" step="0.001" value="0.5" class="w-full" disabled>
                                <span class="text-xs text-gray-600" id="val-cx">0.500</span>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Centro Y</label>
                                <input type="range" id="slider-cy" min="0" max="1" step="0.001" value="0.5" class="w-full" disabled>
                                <span class="text-xs text-gray-600" id="val-cy">0.500</span>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Centro Z</label>
                                <input type="range" id="slider-cz" min="0" max="1" step="0.001" value="0.5" class="w-full" disabled>
                                <span class="text-xs text-gray-600" id="val-cz">0.500</span>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Tamano X</label>
                                <input type="range" id="slider-sx" min="0.001" max="0.5" step="0.001" value="0.05" class="w-full" disabled>
                                <span class="text-xs text-gray-600" id="val-sx">0.050</span>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Tamano Y</label>
                                <input type="range" id="slider-sy" min="0.001" max="0.5" step="0.001" value="0.05" class="w-full" disabled>
                                <span class="text-xs text-gray-600" id="val-sy">0.050</span>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Tamano Z</label>
                                <input type="range" id="slider-sz" min="0.001" max="0.5" step="0.001" value="0.05" class="w-full" disabled>
                                <span class="text-xs text-gray-600" id="val-sz">0.050</span>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-3">
                            <button id="btn-save" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition disabled:opacity-50" disabled>Guardar</button>
                            <button id="btn-clear" class="px-4 py-2 bg-red-100 text-red-700 rounded-md text-sm font-semibold hover:bg-red-200 transition disabled:opacity-50" disabled>Limpiar bbox</button>
                            <span id="save-status" class="text-xs text-gray-500 self-center ml-2"></span>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    @if($project->getFileByType('model_3d'))
    <script type="application/json" id="mapper-data">
        {!! json_encode([
            'projectId' => $project->id,
            'settings' => $project->settings,
            'modelUrl' => '/api/projects/' . $project->id . '/files/model_3d',
            'csrfToken' => csrf_token(),
        ]) !!}
    </script>
    <script type="module" src="/js/viewer-bbox-mapper.js"></script>
    @endif
</x-app-layout>
