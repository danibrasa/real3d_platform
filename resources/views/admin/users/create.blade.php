<x-app-layout>
    <x-slot name="title">{{ auth()->user()->isSuperadmin() ? 'Nuevo Usuario' : 'Nuevo Agente' }}</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ auth()->user()->isSuperadmin() ? 'Nuevo Usuario' : 'Nuevo Agente' }}
            </h2>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:underline">&larr; Volver</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @error('name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @error('email') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contrasena</label>
                        <input type="password" name="password" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @error('password') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contrasena</label>
                        <input type="password" name="password_confirmation" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>

                    @if(auth()->user()->isSuperadmin())
                    <div class="mb-4" x-data="{ role: '{{ old('role', '') }}' }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                        <select name="role" x-model="role" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Seleccionar rol</option>
                            <option value="superadmin">Superadmin</option>
                            <option value="gestor">Gestor (tecnico 3D)</option>
                            <option value="inmobiliaria">Inmobiliaria</option>
                            <option value="agente">Agente</option>
                        </select>
                        @error('role') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror

                        <div x-show="role === 'agente'" x-cloak class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Inmobiliaria del agente</label>
                            <select name="agency_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">Sin inmobiliaria</option>
                                @foreach($agencies as $agency)
                                    <option value="{{ $agency->id }}" {{ old('agency_id') == $agency->id ? 'selected' : '' }}>{{ $agency->name }}</option>
                                @endforeach
                            </select>
                            @error('agency_id') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    @else
                    {{-- Inmobiliaria creating agent: role is forced --}}
                    <input type="hidden" name="role" value="agente">
                    <p class="mb-4 text-sm text-gray-500">Rol: <span class="font-medium text-gray-700">Agente</span> (asociado a tu inmobiliaria)</p>
                    @endif

                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition">
                        Crear {{ auth()->user()->isSuperadmin() ? 'usuario' : 'agente' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
