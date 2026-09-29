<x-app-layout>
    <x-slot name="title">Consulta de {{ $inquiry->name }}</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Consulta de {{ $inquiry->name }}</h2>
            <a href="{{ route('admin.inquiries.index') }}" class="text-sm text-gray-600 hover:underline">&larr; Volver</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Fecha</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inquiry->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Nombre</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inquiry->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="mailto:{{ $inquiry->email }}" class="text-blue-600 hover:underline">{{ $inquiry->email }}</a>
                        </dd>
                    </div>
                    @if($inquiry->phone)
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Teléfono</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="tel:{{ $inquiry->phone }}" class="text-blue-600 hover:underline">{{ $inquiry->phone }}</a>
                            {{-- Aqui es donde la promotora decide si llama o escribe, y
                                 en Republica Dominicana escribe. --}}
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $inquiry->phone) }}?text={{ urlencode(__('inquiry.saludo_whatsapp', ['nombre' => $inquiry->name, 'proyecto' => $inquiry->project->name])) }}"
                               target="_blank" rel="noopener"
                               class="ml-2 inline-flex items-center px-4 py-2.5 rounded-md bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition">
                                WhatsApp
                            </a>
                        </dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Proyecto</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('admin.projects.edit', $inquiry->project) }}" class="text-blue-600 hover:underline">{{ $inquiry->project->name }}</a>
                        </dd>
                    </div>
                    @if($inquiry->unit)
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Unidad</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inquiry->unit->identifier }} - {{ $inquiry->unit->formatted_price }}</dd>
                    </div>
                    @endif
                    @if($inquiry->message)
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Mensaje</dt>
                        <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ $inquiry->message }}</dd>
                    </div>
                    @endif
                </dl>

                {{-- El estado y la nota: lo que le importa a la promotora a la
                     segunda semana es a quien le falta contestar y que le dijo. --}}
                <form method="POST" action="{{ route('admin.inquiries.estado', $inquiry) }}" class="mt-6 rounded-md border border-gray-200 p-4 space-y-3">
                    @csrf @method('PATCH')
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <label for="estado" class="block text-xs font-medium text-gray-500 uppercase">{{ __('inquiry.estado') }}</label>
                            <select id="estado" name="estado" class="mt-1 rounded-md border-gray-300 text-sm">
                                @foreach (\App\Models\Inquiry::ESTADOS as $e)
                                    <option value="{{ $e }}" {{ $inquiry->estado === $e ? 'selected' : '' }}>{{ __('inquiry.estado_'.$e) }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($inquiry->atendidoPor)
                            <p class="text-xs text-gray-500 pb-2">{{ __('inquiry.atendido_por', ['quien' => $inquiry->atendidoPor->name, 'cuando' => $inquiry->estado_en?->format('d/m/Y H:i')]) }}</p>
                        @endif
                    </div>
                    <div>
                        <label for="nota" class="block text-xs font-medium text-gray-500 uppercase">{{ __('inquiry.nota') }}</label>
                        <textarea id="nota" name="nota" rows="3" class="mt-1 w-full rounded-md border-gray-300 text-sm" placeholder="{{ __('inquiry.nota_ayuda') }}">{{ old('nota', $inquiry->nota) }}</textarea>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm font-semibold hover:bg-gray-900">{{ __('inquiry.guardar') }}</button>
                </form>

                <div class="mt-6 flex gap-3">
                    <a href="mailto:{{ $inquiry->email }}" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition">Responder por email</a>
                    @can('delete-inquiry')
                    <form method="POST" action="{{ route('admin.inquiries.destroy', $inquiry) }}" onsubmit="return confirm('Eliminar esta consulta?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-semibold hover:bg-red-700 transition">Eliminar</button>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
