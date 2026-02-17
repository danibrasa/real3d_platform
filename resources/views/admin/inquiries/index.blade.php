<x-app-layout>
    <x-slot name="title">Consultas</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Consultas</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            <!-- Filters -->
            <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-4">
                <form method="GET" class="flex gap-4 items-end flex-wrap">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Estado</label>
                        <select name="read" class="rounded-md border-gray-300 text-sm">
                            <option value="">Todas</option>
                            <option value="0" {{ request('read') === '0' ? 'selected' : '' }}>No leidas</option>
                            <option value="1" {{ request('read') === '1' ? 'selected' : '' }}>Leidas</option>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200">Filtrar</button>
                    @if(request()->hasAny(['read', 'project_id']))
                        <a href="{{ route('admin.inquiries.index') }}" class="text-sm text-gray-500 hover:underline">Limpiar</a>
                    @endif
                </form>
            </div>

            @if($inquiries->count())
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proyecto</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unidad</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($inquiries as $inquiry)
                        <tr class="{{ !$inquiry->read ? 'bg-blue-50' : '' }}">
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $inquiry->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm font-medium {{ !$inquiry->read ? 'text-gray-900' : 'text-gray-600' }}">{{ $inquiry->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $inquiry->email }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $inquiry->project->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $inquiry->unit?->identifier ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if($inquiry->read)
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">Leida</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Nueva</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex gap-2 justify-end">
                                    <a href="{{ route('admin.inquiries.show', $inquiry) }}" class="text-xs text-blue-600 hover:underline">Ver</a>
                                    @if(!$inquiry->read)
                                        <form method="POST" action="{{ route('admin.inquiries.markRead', $inquiry) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="text-xs text-green-600 hover:underline">Marcar leida</button>
                                        </form>
                                    @endif
                                    @can('delete-inquiry')
                                    <form method="POST" action="{{ route('admin.inquiries.destroy', $inquiry) }}" onsubmit="return confirm('Eliminar esta consulta?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:underline">Eliminar</button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $inquiries->withQueryString()->links() }}</div>
            @else
            <div class="bg-white shadow-sm sm:rounded-lg p-12 text-center">
                <p class="text-gray-500">No hay consultas.</p>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
