<x-app-layout>
    <x-slot name="title">Dashboard Admin</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard Admin</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-{{ isset($stats['total_users']) ? '7' : (isset($stats['unread_inquiries']) ? '6' : '5') }} gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-3xl font-bold text-blue-600">{{ $stats['total_projects'] }}</div>
                    <div class="text-sm text-gray-500 mt-1">Proyectos</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-3xl font-bold text-green-600">{{ $stats['published'] }}</div>
                    <div class="text-sm text-gray-500 mt-1">Publicos</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-3xl font-bold text-yellow-600">{{ $stats['drafts'] }}</div>
                    <div class="text-sm text-gray-500 mt-1">Borradores</div>
                </div>
                @if(isset($stats['total_users']))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-3xl font-bold text-purple-600">{{ $stats['total_users'] }}</div>
                    <div class="text-sm text-gray-500 mt-1">Usuarios</div>
                </div>
                @endif
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-3xl font-bold text-indigo-600">{{ $stats['total_units'] }}</div>
                    <div class="text-sm text-gray-500 mt-1">Unidades</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-3xl font-bold text-emerald-600">{{ $stats['available_units'] }}</div>
                    <div class="text-sm text-gray-500 mt-1">Disponibles</div>
                </div>
                @if(isset($stats['unread_inquiries']))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-3xl font-bold text-red-600">{{ $stats['unread_inquiries'] }}</div>
                    <div class="text-sm text-gray-500 mt-1">Consultas</div>
                </div>
                @endif
            </div>

            <!-- Actions -->
            @can('create-project')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <a href="{{ route('admin.projects.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition">
                    + Nuevo Proyecto
                </a>
            </div>
            @endcan

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent projects -->
                @if($recent_projects->count())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Proyectos recientes</h3>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    <th class="px-4 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($recent_projects as $project)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-sm">{{ $project->name }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $statusColors = ['public' => 'bg-green-100 text-green-800', 'draft' => 'bg-yellow-100 text-yellow-800', 'private' => 'bg-blue-100 text-blue-800', 'unlisted' => 'bg-gray-100 text-gray-800'];
                                            $statusLabels = ['public' => 'Publico', 'draft' => 'Borrador', 'private' => 'Privado', 'unlisted' => 'Oculto'];
                                        @endphp
                                        <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$project->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $statusLabels[$project->status] ?? $project->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $project->created_at->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.projects.edit', $project) }}" class="text-blue-600 hover:underline text-sm">Editar</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Recent inquiries -->
                @can('view-inquiries')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">Consultas sin leer</h3>
                            <a href="{{ route('admin.inquiries.index') }}" class="text-sm text-blue-600 hover:underline">Ver todas</a>
                        </div>
                        @if($recent_inquiries->count())
                        <div class="space-y-3">
                            @foreach($recent_inquiries as $inquiry)
                            <a href="{{ route('admin.inquiries.show', $inquiry) }}" class="block p-3 rounded-lg bg-blue-50 hover:bg-blue-100 transition">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-medium text-sm">{{ $inquiry->name }}</p>
                                        <p class="text-xs text-gray-600">{{ $inquiry->project->name }}{{ $inquiry->unit ? ' - ' . $inquiry->unit->identifier : '' }}</p>
                                    </div>
                                    <span class="text-xs text-gray-500">{{ $inquiry->created_at->diffForHumans() }}</span>
                                </div>
                                @if($inquiry->message)
                                    <p class="text-xs text-gray-500 mt-1 line-clamp-1">{{ $inquiry->message }}</p>
                                @endif
                            </a>
                            @endforeach
                        </div>
                        @else
                        <p class="text-gray-500 text-sm">No hay consultas sin leer.</p>
                        @endif
                    </div>
                </div>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
