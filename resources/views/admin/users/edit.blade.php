<x-app-layout>
    <x-slot name="title">Editar: {{ $editUser->name }}</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar: {{ $editUser->name }}</h2>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:underline">&larr; Volver</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="font-semibold mb-4">Datos del usuario</h3>
                <form method="POST" action="{{ route('admin.users.update', $editUser) }}">
                    @csrf @method('PUT')

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input type="text" name="name" value="{{ old('name', $editUser->name) }}" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @error('name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email', $editUser->email) }}" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @error('email') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nueva contrasena <span class="text-gray-400 font-normal">(dejar vacio para no cambiar)</span></label>
                        <input type="password" name="password" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @error('password') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contrasena</label>
                        <input type="password" name="password_confirmation" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>

                    @if(auth()->user()->isSuperadmin())
                    <div class="mb-4" x-data="{ role: '{{ old('role', $editUser->role) }}' }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                        <select name="role" x-model="role" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="superadmin" {{ $editUser->role === 'superadmin' ? 'selected' : '' }}>Superadmin</option>
                            <option value="gestor" {{ $editUser->role === 'gestor' ? 'selected' : '' }}>Gestor (tecnico 3D)</option>
                            <option value="inmobiliaria" {{ $editUser->role === 'inmobiliaria' ? 'selected' : '' }}>Inmobiliaria</option>
                            <option value="agente" {{ $editUser->role === 'agente' ? 'selected' : '' }}>Agente</option>
                        </select>
                        @error('role') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror

                        <div x-show="role === 'agente'" x-cloak class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Inmobiliaria del agente</label>
                            <select name="agency_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">Sin inmobiliaria</option>
                                @foreach($agencies as $agency)
                                    <option value="{{ $agency->id }}" {{ old('agency_id', $editUser->agency_id) == $agency->id ? 'selected' : '' }}>{{ $agency->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @else
                    <input type="hidden" name="role" value="agente">
                    <p class="mb-4 text-sm text-gray-500">Rol: <span class="font-medium text-gray-700">Agente</span></p>
                    @endif

                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition">
                        Guardar cambios
                    </button>
                </form>
            </div>

            {{-- Project assignment (superadmin editing inmobiliaria) --}}
            @if(auth()->user()->isSuperadmin() && $editUser->isInmobiliaria() && $allProjects->count())
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-4">Proyectos asignados</h3>
                <form method="POST" action="{{ route('admin.users.assignProjects', $editUser) }}">
                    @csrf
                    <div class="space-y-2 max-h-64 overflow-y-auto mb-4">
                        @foreach($allProjects as $project)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="project_ids[]" value="{{ $project->id }}"
                                {{ in_array($project->id, $assignedProjectIds) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600">
                            {{ $project->name }}
                            <span class="text-gray-400 text-xs">({{ $project->status }})</span>
                        </label>
                        @endforeach
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-md text-sm font-semibold hover:bg-green-700 transition">
                        Guardar asignaciones
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
