<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Token: {{ $apiToken->name }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.api-tokens.update', $apiToken) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                            <input type="text" name="name" value="{{ old('name', $apiToken->name) }}" required
                                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripcion</label>
                            <textarea name="description" rows="2"
                                      class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">{{ old('description', $apiToken->description) }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rate Limit (requests/minuto) *</label>
                            <select name="rate_limit"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                                @foreach([60, 120, 300, 600] as $limit)
                                    <option value="{{ $limit }}" {{ old('rate_limit', $apiToken->rate_limit) == $limit ? 'selected' : '' }}>
                                        {{ $limit }}/min {{ $limit == 60 ? '(estandar)' : ($limit == 600 ? '(alto)' : '') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center gap-2">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1"
                                       class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                       {{ old('is_active', $apiToken->is_active) ? 'checked' : '' }}>
                                <span class="text-sm font-medium text-gray-700">Token activo</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1">Desactivar un token bloquea todas las peticiones que lo usen.</p>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Proyectos accesibles</label>
                            <p class="text-xs text-gray-500 mb-2">Si no seleccionas ninguno, el token tendra acceso a todos los proyectos publicos.</p>
                            <div class="max-h-60 overflow-y-auto border rounded-lg p-3 space-y-2">
                                @foreach($projects as $project)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="project_ids[]" value="{{ $project->id }}"
                                               class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                               {{ in_array($project->id, old('project_ids', $apiToken->project_ids ?? [])) ? 'checked' : '' }}>
                                        <span>{{ $project->name }}</span>
                                        <span class="text-gray-400 text-xs">({{ $project->slug }})</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-6 p-3 bg-gray-50 rounded-lg text-xs text-gray-500 space-y-1">
                            <p>Creado: {{ $apiToken->created_at->format('d/m/Y H:i') }}</p>
                            <p>Ultimo uso: {{ $apiToken->last_used_at ? $apiToken->last_used_at->format('d/m/Y H:i') : 'Nunca' }}</p>
                            @if($apiToken->last_used_ip)
                                <p>Ultima IP: {{ $apiToken->last_used_ip }}</p>
                            @endif
                        </div>

                        <div class="flex justify-end gap-3">
                            <a href="{{ route('admin.api-tokens.index') }}"
                               class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancelar</a>
                            <button type="submit"
                                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700">
                                Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
