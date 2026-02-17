<x-app-layout>
    <x-slot name="title">Nueva Tipologia</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nueva Tipologia: {{ $project->name }}</h2>
            <a href="{{ route('admin.projects.typologies.index', $project) }}" class="text-sm text-gray-600 hover:underline">&larr; Volver</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.projects.typologies.store', $project) }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="Ej: 2 Dormitorios Tipo A" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dormitorios *</label>
                            <input type="number" name="bedrooms" value="{{ old('bedrooms', 1) }}" min="0" max="10" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            @error('bedrooms') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Banos *</label>
                            <input type="number" name="bathrooms" value="{{ old('bathrooms', 1) }}" min="0" max="10" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            @error('bathrooms') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Area m2 *</label>
                            <input type="number" name="area_m2" value="{{ old('area_m2') }}" min="1" max="9999" step="0.01" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            @error('area_m2') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripcion</label>
                        <textarea name="description" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Plano (imagen, max 5MB)</label>
                        <input type="file" name="floor_plan" accept="image/*" class="w-full text-sm">
                        @error('floor_plan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition">Crear Tipologia</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
