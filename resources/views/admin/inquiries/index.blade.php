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
                        <select name="estado" class="rounded-md border-gray-300 text-sm">
                            <option value="">{{ __('inquiry.estado') }}: todos</option>
                            @foreach (\App\Models\Inquiry::ESTADOS as $e)
                                <option value="{{ $e }}" {{ request('estado') === $e ? 'selected' : '' }}>{{ __('inquiry.estado_'.$e) }}</option>
                            @endforeach
                        </select>
                        <select name="read" class="rounded-md border-gray-300 text-sm">
                            <option value="">Todas</option>
                            <option value="0" {{ request('read') === '0' ? 'selected' : '' }}>No leidas</option>
                            <option value="1" {{ request('read') === '1' ? 'selected' : '' }}>Leidas</option>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200">Filtrar</button>
                    @if(request()->hasAny(['read', 'project_id', 'estado']))
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
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contacto</th>
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
                            {{-- El telefono se capturaba y no se enseñaba. En Republica
                                 Dominicana el negocio se hace por WhatsApp: una promotora que
                                 recibe un lead quiere escribirle, no leerlo. --}}
                            <td class="px-4 py-3 text-sm">
                                <a href="mailto:{{ $inquiry->email }}"
                                   class="text-blue-600 hover:underline">{{ $inquiry->email }}</a>
                                @if ($inquiry->phone)
                                    <div class="mt-0.5 flex items-center gap-2">
                                        <a href="tel:{{ $inquiry->phone }}"
                                           class="text-gray-600 hover:underline">{{ $inquiry->phone }}</a>
                                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $inquiry->phone) }}"
                                           target="_blank" rel="noopener"
                                           class="text-xs font-medium text-emerald-700 hover:underline">WhatsApp</a>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $inquiry->project->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $inquiry->unit?->identifier ?? '-' }}</td>
                            <td class="px-4 py-3">
                                {{-- El estado se cambia desde aqui, sin entrar: contestar
                                     un lead son tres clics y cada clic de mas se nota. --}}
                                @php $colores = ['nuevo' => 'bg-blue-100 text-blue-800', 'contactado' => 'bg-amber-100 text-amber-800', 'visita' => 'bg-purple-100 text-purple-800', 'cerrado' => 'bg-emerald-100 text-emerald-800', 'descartado' => 'bg-gray-100 text-gray-500']; @endphp
                                <form method="POST" action="{{ route('admin.inquiries.estado', $inquiry) }}">
                                    @csrf @method('PATCH')
                                    <select name="estado" onchange="this.form.submit()" class="text-xs rounded-full border-0 py-1 pl-2 pr-7 {{ $colores[$inquiry->estado] ?? '' }}" aria-label="{{ __('inquiry.estado') }}">
                                        @foreach (\App\Models\Inquiry::ESTADOS as $e)
                                            <option value="{{ $e }}" {{ $inquiry->estado === $e ? 'selected' : '' }}>{{ __('inquiry.estado_'.$e) }}</option>
                                        @endforeach
                                    </select>
                                </form>
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
