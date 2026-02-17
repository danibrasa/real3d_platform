<x-app-layout>
    <x-slot name="title">{{ auth()->user()->isSuperadmin() ? 'Usuarios' : 'Mis Agentes' }}</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ auth()->user()->isSuperadmin() ? 'Usuarios' : 'Mis Agentes' }}
            </h2>
            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition">
                + {{ auth()->user()->isSuperadmin() ? 'Nuevo Usuario' : 'Nuevo Agente' }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">{{ session('error') }}</div>
            @endif

            @if($users->count())
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rol</th>
                            @if(auth()->user()->isSuperadmin())
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inmobiliaria</th>
                            @endif
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($users as $u)
                        <tr>
                            <td class="px-4 py-3 font-medium text-sm">{{ $u->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $u->email }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $roleColors = [
                                        'superadmin' => 'bg-red-100 text-red-800',
                                        'gestor' => 'bg-blue-100 text-blue-800',
                                        'inmobiliaria' => 'bg-purple-100 text-purple-800',
                                        'agente' => 'bg-yellow-100 text-yellow-800',
                                    ];
                                    $roleLabels = [
                                        'superadmin' => 'Superadmin',
                                        'gestor' => 'Gestor',
                                        'inmobiliaria' => 'Inmobiliaria',
                                        'agente' => 'Agente',
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs rounded-full {{ $roleColors[$u->role] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $roleLabels[$u->role] ?? $u->role }}
                                </span>
                            </td>
                            @if(auth()->user()->isSuperadmin())
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $u->agency?->name ?? '-' }}</td>
                            @endif
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $u->created_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex gap-2 justify-end">
                                    <a href="{{ route('admin.users.edit', $u) }}" class="text-sm text-blue-600 hover:underline">Editar</a>
                                    @if($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}" onsubmit="return confirm('Eliminar usuario {{ $u->name }}?')">
                                        @csrf @method('DELETE')
                                        <button class="text-sm text-red-600 hover:underline">Eliminar</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6">{{ $users->links() }}</div>
            @else
            <div class="bg-white shadow-sm sm:rounded-lg p-12 text-center">
                <p class="text-gray-500">No hay usuarios registrados.</p>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
