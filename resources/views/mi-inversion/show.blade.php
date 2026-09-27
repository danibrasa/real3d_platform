<x-portal-layout :title="__('buyer.my_investment').' — '.$unit->identifier">

    <div class="max-w-4xl mx-auto px-4 py-8">

        {{-- Cabecera: que vivienda es --}}
        <div class="mb-8">
            <p class="text-sm text-gray-500">{{ $project->name }}</p>
            <h1 class="text-3xl font-bold text-gray-900 mt-1">
                {{ __('buyer.unit') }} {{ $unit->identifier }}
            </h1>
            <p class="text-sm text-gray-600 mt-2">
                @if ($unit->typology)
                    {{ $unit->typology->name }} ·
                @endif
                {{ $unit->bedrooms }} {{ __('buyer.bedrooms') }} ·
                {{ $unit->bathrooms }} {{ __('buyer.bathrooms') }} ·
                {{ number_format($unit->area_m2, 0, ',', '.') }} m²
                @if ($unit->floor)
                    · {{ __('buyer.floor') }} {{ $unit->floor }}
                @endif
            </p>
        </div>

        {{-- Las dos preguntas de quien espera: como va la obra y que llevo pagado --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">

            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">
                    {{ __('buyer.construction') }}
                </p>
                @if ($avanceObra !== null)
                    <p class="text-3xl font-bold text-gray-900 tabular-nums">
                        {{ number_format($avanceObra, 0) }}%
                    </p>
                    <div class="h-2 w-full rounded-full bg-gray-100 overflow-hidden mt-3">
                        <div class="h-full rounded-full bg-blue-500" style="width: {{ $avanceObra }}%"></div>
                    </div>
                    @if ($actualizaciones->first())
                        <p class="text-xs text-gray-500 mt-2">
                            {{ __('buyer.last_update') }}
                            {{ $actualizaciones->first()->date->format('d/m/Y') }}
                        </p>
                    @endif
                @else
                    <p class="text-sm text-gray-500">{{ __('buyer.no_progress_yet') }}</p>
                @endif
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">
                    {{ __('buyer.payments') }}
                </p>
                <p class="text-3xl font-bold text-gray-900 tabular-nums">
                    {{ number_format($porcentajePagado, 0) }}%
                </p>
                <div class="h-2 w-full rounded-full bg-gray-100 overflow-hidden mt-3">
                    <div class="h-full rounded-full bg-emerald-500" style="width: {{ $porcentajePagado }}%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-2 tabular-nums">
                    USD {{ number_format($pagado, 0, ',', '.') }}
                    {{ __('buyer.of') }}
                    {{ number_format($unit->price, 0, ',', '.') }}
                </p>
            </div>
        </div>

        {{-- Lo que ha pagado, con fecha y referencia: es su justificante --}}
        <section class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">{{ __('buyer.payment_history') }}</h2>

            @if ($pagos->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-center">
                    <p class="text-sm text-gray-500">{{ __('buyer.no_payments_yet') }}</p>
                </div>
            @else
                <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="text-left font-medium px-4 py-2.5">{{ __('buyer.date') }}</th>
                                <th class="text-left font-medium px-4 py-2.5">{{ __('buyer.concept') }}</th>
                                <th class="text-left font-medium px-4 py-2.5">{{ __('buyer.reference') }}</th>
                                <th class="text-right font-medium px-4 py-2.5">{{ __('buyer.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($pagos as $pago)
                                <tr>
                                    <td class="px-4 py-2.5 whitespace-nowrap">{{ $pago->paid_on->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2.5">{{ $pago->concept }}</td>
                                    <td class="px-4 py-2.5 text-gray-500 font-mono text-xs">{{ $pago->reference ?: '—' }}</td>
                                    <td class="px-4 py-2.5 text-right tabular-nums font-medium">
                                        {{ number_format($pago->amount, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 font-semibold">
                            <tr>
                                <td colspan="3" class="px-4 py-2.5">{{ __('buyer.total_paid') }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums">
                                    {{ number_format($pagado, 2, ',', '.') }}
                                </td>
                            </tr>
                            @if ($pendiente > 0)
                                <tr class="text-gray-600 font-normal">
                                    <td colspan="3" class="px-4 py-2.5">{{ __('buyer.pending') }}</td>
                                    <td class="px-4 py-2.5 text-right tabular-nums">
                                        {{ number_format($pendiente, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            @endif
        </section>

        {{-- El avance de obra con fotos: la prueba de que existe --}}
        <section class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">{{ __('buyer.progress_updates') }}</h2>

            @if ($actualizaciones->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-center">
                    <p class="text-sm text-gray-500">{{ __('buyer.no_updates_yet') }}</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($actualizaciones as $act)
                        <article class="rounded-xl border border-gray-200 bg-white p-5">
                            <div class="flex flex-wrap items-baseline justify-between gap-2 mb-2">
                                <h3 class="font-semibold text-gray-900">
                                    {{ $act->title }}
                                </h3>
                                <time class="text-xs text-gray-500">{{ $act->date->format('d/m/Y') }}</time>
                            </div>

                            @if ($act->progress_percentage !== null)
                                <p class="text-sm text-gray-600 mb-2 tabular-nums">
                                    {{ number_format($act->progress_percentage, 0) }}% {{ __('buyer.completed') }}
                                </p>
                            @endif

                            @if ($act->description)
                                <p class="text-sm text-gray-700 leading-relaxed">{{ $act->description }}</p>
                            @endif

                            @if ($act->images->isNotEmpty())
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-4">
                                    @foreach ($act->images as $img)
                                        <img src="{{ route('api.construction.image', [$project->id, $img->id]) }}"
                                             alt="{{ __('buyer.progress_photo') }} {{ $act->date->format('d/m/Y') }}"
                                             loading="lazy"
                                             class="w-full aspect-[4/3] object-cover rounded-lg border border-gray-200">
                                    @endforeach
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <p class="text-xs text-gray-400 border-t border-gray-200 pt-4">
            {{ __('buyer.footer_note') }}
            @if ($project->contact_email)
                <a href="mailto:{{ $project->contact_email }}" class="underline">{{ $project->contact_email }}</a>
            @endif
        </p>
    </div>

</x-portal-layout>
