<x-app-layout>
    <x-slot name="title">Pilotos</x-slot>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pilotos: el embudo de cada promotora</h2>
            <span class="text-sm text-gray-500">Lo mismo en texto: <code>php artisan pilotos:informe</code></span>
        </div>
    </x-slot>

    @php
        $nombreDeEtapa = [
            'sin_proyecto' => 'Sin proyecto', 'sin_viviendas' => 'Sin viviendas', 'sin_material' => 'Sin material',
            'sin_pedir_visor' => 'Sin pedir el visor', 'esperando_equipo' => 'Esperando al equipo', 'sin_publicar' => 'Sin publicar',
            'sin_leads' => 'Publicado, sin leads', 'en_marcha' => 'En marcha',
        ];
        $colorDeEtapa = fn ($e) => match ($e) {
            'en_marcha' => 'bg-emerald-100 text-emerald-800',
            'esperando_equipo' => 'bg-purple-100 text-purple-800',
            'sin_leads' => 'bg-blue-100 text-blue-800',
            default => 'bg-amber-100 text-amber-800',
        };
        $dias = fn ($n) => $n === null ? '—' : $n.' d';
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Los cinco numeros del plan de pilotos, en cabecera: tiempo de
                 alta a visor pedido, de pedido a publicado, leads por semana,
                 contestados (y en el dia), y donde se queda cada una. --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <div class="text-2xl font-bold text-gray-900 tabular-nums">{{ $medias['alta_a_pedido'] ?? '—' }}<span class="text-sm font-normal text-gray-500"> días</span></div>
                    <div class="text-xs text-gray-500 mt-1">de alta a pedir el visor (media)</div>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <div class="text-2xl font-bold text-gray-900 tabular-nums">{{ $medias['pedido_a_publicado'] ?? '—' }}<span class="text-sm font-normal text-gray-500"> días</span></div>
                    <div class="text-xs text-gray-500 mt-1">de pedirlo a publicar (media)</div>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <div class="text-2xl font-bold text-gray-900 tabular-nums">{{ $medias['leads_semana'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">leads esta semana (todas)</div>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <div class="text-2xl font-bold text-gray-900 tabular-nums">{{ $medias['contestados'] }}<span class="text-sm font-normal text-gray-500"> de {{ $medias['leads'] }}</span></div>
                    <div class="text-xs text-gray-500 mt-1">leads contestados · {{ $medias['en_el_dia'] }} en el día</div>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <div class="text-xs text-gray-500 mb-1">dónde están</div>
                    @foreach ($porEtapa as $etapa => $n)
                        <div class="text-xs"><span class="font-semibold tabular-nums">{{ $n }}</span> {{ $nombreDeEtapa[$etapa] ?? $etapa }}</div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Promotora</th>
                            <th class="px-4 py-3 text-left">Alta</th>
                            <th class="px-4 py-3 text-right" title="Días de alta a pedir el visor">→ pedido</th>
                            <th class="px-4 py-3 text-right" title="Días de pedir el visor a publicar, en el proyecto que más lejos llegó">→ publicado</th>
                            <th class="px-4 py-3 text-right">Leads / sem.</th>
                            <th class="px-4 py-3 text-right" title="Contestados (en menos de 24 h)">Contestados</th>
                            <th class="px-4 py-3 text-right">Visitas 30 d</th>
                            <th class="px-4 py-3 text-left">Etapa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($embudos as $e)
                        <tr data-piloto="{{ $e['promotora']->id }}">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $e['promotora']->companyProfile?->company_name ?? $e['promotora']->name }}</div>
                                <div class="text-xs text-gray-500">{{ $e['promotora']->name }} · {{ $e['plan'] ?? 'sin plan' }} · {{ $e['proyectos'] }} {{ $e['proyectos'] === 1 ? 'proyecto' : 'proyectos' }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $e['alta']->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $dias($e['dias_alta_a_pedido']) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">@if ($e['publicado_sin_fecha'])<span class="text-xs text-gray-500" title="Está publicado, pero de antes de que se anotara la fecha">sin fecha</span>@else{{ $dias($e['dias_pedido_a_publicado']) }}@endif</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $e['leads'] }} / {{ $e['leads_semana'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $e['leads_contestados'] }} <span class="text-xs text-gray-500">({{ $e['leads_en_el_dia'] }} en el día)</span></td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $e['visitas_30d'] }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full {{ $colorDeEtapa($e['etapa']) }}">{{ $nombreDeEtapa[$e['etapa']] }}</span>
                                @if ($e['dias_en_etapa'] !== null)
                                    <span class="text-xs text-gray-500">desde hace {{ $e['dias_en_etapa'] }} d</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Todavía no hay promotoras.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-gray-500">"Contestados" es cualquier estado distinto de "nuevo"; "en el día", en menos de {{ \App\Support\Pilotos\Embudo::HORAS_PARA_CONTESTAR }} horas desde que escribió el comprador. Los tiempos se miden en el proyecto que más lejos llegó. "Publicado" sale del registro de auditoría; lo publicado antes de que se anotara sale como "sin fecha".</p>
        </div>
    </div>
</x-app-layout>
