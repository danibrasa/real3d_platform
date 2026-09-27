<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('buyer_admin.title') }}: {{ $unit->identifier }}
            </h2>
            <a href="{{ route('admin.projects.units.index', $project) }}"
               class="text-sm text-gray-600 hover:underline">{{ __('buyer_admin.back') }}</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Quien ha comprado --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-4">
                <h3 class="font-semibold mb-1">{{ __('buyer_admin.buyer') }}</h3>

                @if ($unit->buyer)
                    <p class="text-sm text-gray-600 mb-4">
                        {{ __('buyer_admin.buyer_can_see') }}
                    </p>
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <div>
                            <p class="font-medium text-gray-900">{{ $unit->buyer->name }}</p>
                            <p class="text-sm text-gray-600">{{ $unit->buyer->email }}</p>
                            @if ($unit->sold_at)
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ __('buyer_admin.sold_on') }} {{ $unit->sold_at->format('d/m/Y') }}
                                </p>
                            @endif
                        </div>
                        <form method="POST"
                              action="{{ route('admin.projects.units.comprador.desasignar', [$project, $unit]) }}">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="text-sm text-red-600 hover:underline">{{ __('buyer_admin.unassign') }}</button>
                        </form>
                    </div>
                @else
                    <p class="text-sm text-gray-600 mb-4">{{ __('buyer_admin.assign_help') }}</p>
                    <form method="POST"
                          action="{{ route('admin.projects.units.comprador.asignar', [$project, $unit]) }}"
                          class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @csrf
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('buyer_admin.name') }}
                            </label>
                            <input type="text" name="name" id="name" required value="{{ old('name') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('buyer_admin.email') }}
                            </label>
                            <input type="email" name="email" id="email" required value="{{ old('email') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="sold_at" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('buyer_admin.sale_date') }}
                            </label>
                            <div class="flex gap-2">
                                <input type="date" name="sold_at" id="sold_at" value="{{ old('sold_at') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                                <button type="submit"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition whitespace-nowrap">
                                    {{ __('buyer_admin.assign') }}
                                </button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>

            {{-- Los pagos --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap items-baseline justify-between gap-2 mb-4">
                    <h3 class="font-semibold">{{ __('buyer_admin.payments') }}</h3>
                    <p class="text-sm text-gray-600 tabular-nums">
                        {{ __('buyer_admin.paid') }}
                        <b class="text-gray-900">{{ number_format($pagado, 2, ',', '.') }}</b>
                        {{ __('buyer_admin.of') }} {{ number_format($unit->price, 2, ',', '.') }}
                        @if ($pendiente > 0)
                            · {{ __('buyer_admin.pending') }} {{ number_format($pendiente, 2, ',', '.') }}
                        @endif
                    </p>
                </div>

                @if ($unit->payments->isNotEmpty())
                    <div class="overflow-x-auto border border-gray-200 rounded-md mb-5">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-gray-600">
                                <tr>
                                    <th class="text-left font-medium px-3 py-2">{{ __('buyer_admin.date') }}</th>
                                    <th class="text-left font-medium px-3 py-2">{{ __('buyer_admin.concept') }}</th>
                                    <th class="text-left font-medium px-3 py-2">{{ __('buyer_admin.reference') }}</th>
                                    <th class="text-right font-medium px-3 py-2">{{ __('buyer_admin.amount') }}</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($unit->payments as $pago)
                                    <tr>
                                        <td class="px-3 py-2 whitespace-nowrap">{{ $pago->paid_on->format('d/m/Y') }}</td>
                                        <td class="px-3 py-2">{{ $pago->concept }}</td>
                                        <td class="px-3 py-2 text-gray-500 font-mono text-xs">{{ $pago->reference ?: '—' }}</td>
                                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format($pago->amount, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <form method="POST"
                                                  action="{{ route('admin.projects.units.comprador.pago.borrar', [$project, $unit, $pago]) }}">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-xs text-red-600 hover:underline">
                                                    {{ __('buyer_admin.delete') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <form method="POST"
                      action="{{ route('admin.projects.units.comprador.pago', [$project, $unit]) }}"
                      class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end border-t border-gray-100 pt-5">
                    @csrf
                    <div class="sm:col-span-2">
                        <label for="concept" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('buyer_admin.concept') }}
                        </label>
                        @if ($hitos->isNotEmpty())
                            <input list="hitos" type="text" name="concept" id="concept" required
                                   value="{{ old('concept') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                            <datalist id="hitos">
                                @foreach ($hitos as $hito)
                                    <option value="{{ $hito->name }}"></option>
                                @endforeach
                            </datalist>
                        @else
                            <input type="text" name="concept" id="concept" required value="{{ old('concept') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                        @endif
                    </div>
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('buyer_admin.amount') }}
                        </label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amount" required
                               value="{{ old('amount') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="paid_on" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('buyer_admin.date') }}
                        </label>
                        <input type="date" name="paid_on" id="paid_on" required
                               value="{{ old('paid_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}"
                               class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="reference" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('buyer_admin.reference') }}
                        </label>
                        <div class="flex gap-2">
                            <input type="text" name="reference" id="reference" value="{{ old('reference') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition">
                                {{ __('buyer_admin.add') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
