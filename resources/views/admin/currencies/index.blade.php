<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Monedas y Tasas de Cambio</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="text-sm text-gray-500 mb-6">
                        Los precios se almacenan en USD. Las tasas de cambio se usan para mostrar precios convertidos en la vista publica.
                        Actualiza las tasas manualmente segun el mercado.
                    </p>

                    <form method="POST" action="{{ route('admin.currencies.update') }}">
                        @csrf
                        @method('PUT')

                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-3 px-2">Moneda</th>
                                    <th class="text-left py-3 px-2">Simbolo</th>
                                    <th class="text-left py-3 px-2">Tasa (1 USD = ?)</th>
                                    <th class="text-center py-3 px-2">Activa</th>
                                    <th class="text-left py-3 px-2">Ejemplo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($currencies as $i => $currency)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-2">
                                        <input type="hidden" name="currencies[{{ $i }}][id]" value="{{ $currency->id }}">
                                        <span class="font-medium">{{ $currency->code }}</span>
                                        <span class="text-gray-500 ml-1">{{ $currency->name }}</span>
                                    </td>
                                    <td class="py-3 px-2 font-mono">{{ $currency->symbol }}</td>
                                    <td class="py-3 px-2">
                                        @if($currency->is_default)
                                            <span class="text-gray-400">1.0000 (base)</span>
                                            <input type="hidden" name="currencies[{{ $i }}][exchange_rate]" value="1">
                                        @else
                                            <input type="number" name="currencies[{{ $i }}][exchange_rate]"
                                                   value="{{ $currency->exchange_rate }}"
                                                   step="0.0001" min="0.0001"
                                                   class="w-32 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                   x-data x-on:input="$el.closest('tr').querySelector('.example-price').textContent = '{{ $currency->symbol }} ' + (150000 * $el.value).toLocaleString('en-US', {maximumFractionDigits: 0})">
                                        @endif
                                    </td>
                                    <td class="py-3 px-2 text-center">
                                        @if($currency->is_default)
                                            <span class="text-green-600 text-xs font-medium">Default</span>
                                            <input type="hidden" name="currencies[{{ $i }}][is_active]" value="1">
                                        @else
                                            <input type="checkbox" name="currencies[{{ $i }}][is_active]" value="1"
                                                   {{ $currency->is_active ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        @endif
                                    </td>
                                    <td class="py-3 px-2 text-gray-500">
                                        <span class="example-price">{{ $currency->symbol }} {{ number_format(150000 * $currency->exchange_rate, 0, '.', ',') }}</span>
                                        <span class="text-xs text-gray-400 ml-1">(USD 150,000)</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-6 flex items-center gap-4">
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 transition">
                                Guardar tasas
                            </button>
                            <span class="text-xs text-gray-400">
                                Ultima actualizacion: {{ $currencies->max('updated_at')?->diffForHumans() ?? 'nunca' }}
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
