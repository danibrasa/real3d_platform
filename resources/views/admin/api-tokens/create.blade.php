<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Crear Token API</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.api-tokens.store') }}">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500"
                                   placeholder="Ej: Portal Inmobiliario, CRM Integration...">
                            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripcion</label>
                            <textarea name="description" rows="2"
                                      class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500"
                                      placeholder="Para que se usara este token...">{{ old('description') }}</textarea>
                            @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rate Limit (requests/minuto) *</label>
                            <select name="rate_limit"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="60" {{ old('rate_limit', 60) == 60 ? 'selected' : '' }}>60/min (estandar)</option>
                                <option value="120" {{ old('rate_limit') == 120 ? 'selected' : '' }}>120/min</option>
                                <option value="300" {{ old('rate_limit') == 300 ? 'selected' : '' }}>300/min</option>
                                <option value="600" {{ old('rate_limit') == 600 ? 'selected' : '' }}>600/min (alto)</option>
                            </select>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Proyectos accesibles</label>
                            <p class="text-xs text-gray-500 mb-2">Si no seleccionas ninguno, el token tendra acceso a todos los proyectos publicos.</p>
                            <div class="max-h-60 overflow-y-auto border rounded-lg p-3 space-y-2">
                                @foreach($projects as $project)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="project_ids[]" value="{{ $project->id }}"
                                               class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                               {{ in_array($project->id, old('project_ids', [])) ? 'checked' : '' }}>
                                        <span>{{ $project->name }}</span>
                                        <span class="text-gray-400 text-xs">({{ $project->slug }})</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('project_ids') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end gap-3">
                            <a href="{{ route('admin.api-tokens.index') }}"
                               class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancelar</a>
                            <button type="submit"
                                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700">
                                Crear Token
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
