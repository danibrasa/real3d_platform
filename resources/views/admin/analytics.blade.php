<x-app-layout>
    <x-slot name="title">Analytics del Visor</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Analytics del Visor 3D</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                <form method="GET" class="flex flex-wrap items-end gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Proyecto</label>
                        <select name="project_id" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            @foreach($projects as $p)
                                <option value="{{ $p->id }}" {{ $projectId == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Periodo</label>
                        <select name="days" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="7" {{ ($stats['days'] ?? 30) == 7 ? 'selected' : '' }}>7 dias</option>
                            <option value="30" {{ ($stats['days'] ?? 30) == 30 ? 'selected' : '' }}>30 dias</option>
                            <option value="90" {{ ($stats['days'] ?? 30) == 90 ? 'selected' : '' }}>90 dias</option>
                        </select>
                    </div>
                </form>
            </div>

            @if($stats)
            <!-- Key Metrics -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="text-3xl font-bold text-gray-800">{{ number_format($stats['total_sessions']) }}</div>
                    <div class="text-xs text-gray-500 mt-1">Sesiones</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="text-3xl font-bold text-gray-800">{{ number_format($stats['unique_visitors']) }}</div>
                    <div class="text-xs text-gray-500 mt-1">Visitantes unicos</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="text-3xl font-bold text-gray-800">
                        @if($stats['avg_duration'] >= 60)
                            {{ floor($stats['avg_duration'] / 60) }}m {{ $stats['avg_duration'] % 60 }}s
                        @else
                            {{ $stats['avg_duration'] }}s
                        @endif
                    </div>
                    <div class="text-xs text-gray-500 mt-1">Duracion media</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="text-3xl font-bold text-blue-600">{{ $stats['inquiry_count'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">Consultas</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="text-3xl font-bold {{ $stats['conversion_rate'] > 5 ? 'text-green-600' : 'text-gray-800' }}">{{ $stats['conversion_rate'] }}%</div>
                    <div class="text-xs text-gray-500 mt-1">Conversion</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="text-3xl font-bold text-green-600">{{ $stats['whatsapp_clicks'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">WhatsApp clicks</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="text-3xl font-bold text-red-600">{{ $stats['pdf_downloads'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">PDF downloads</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Sessions chart -->
                <div class="lg:col-span-2 bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-4">Sesiones por dia</h3>
                    <canvas id="sessions-chart" height="200"></canvas>
                </div>

                <!-- Device breakdown -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-4">Dispositivos</h3>
                    <canvas id="device-chart" height="200"></canvas>
                    <div class="mt-4 space-y-2">
                        @foreach($stats['devices'] as $device => $count)
                        <div class="flex justify-between text-sm">
                            <span class="capitalize text-gray-600">{{ $device ?: 'Desconocido' }}</span>
                            <span class="font-medium">{{ $count }} <span class="text-gray-400">({{ $stats['total_sessions'] > 0 ? round($count / $stats['total_sessions'] * 100) : 0 }}%)</span></span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Top units -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-4">Unidades mas vistas</h3>
                    @if($stats['top_units']->count())
                    <div class="space-y-2">
                        @php $maxViews = $stats['top_units']->max('views'); @endphp
                        @foreach($stats['top_units'] as $u)
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium w-16 text-gray-700">{{ $u['identifier'] }}</span>
                            <div class="flex-1 bg-gray-100 rounded-full h-5 overflow-hidden">
                                <div class="bg-blue-500 h-full rounded-full transition-all flex items-center justify-end pr-2" style="width: {{ $maxViews > 0 ? round($u['views'] / $maxViews * 100) : 0 }}%;">
                                    <span class="text-[10px] font-bold text-white">{{ $u['views'] }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-sm text-gray-500">Sin datos aun</p>
                    @endif
                </div>

                <!-- Event breakdown -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-4">Eventos registrados</h3>
                    @if(!empty($stats['event_counts']))
                    <div class="space-y-2">
                        @php
                            $eventLabels = [
                                'session_start' => 'Inicios de sesion',
                                'session_end' => 'Fines de sesion',
                                'model_loaded' => 'Modelo 3D cargado',
                                'unit_selected' => 'Unidad seleccionada',
                                'unit_focused' => 'Unidad enfocada en 3D',
                                'comparison_opened' => 'Comparador abierto',
                                'pdf_downloaded' => 'PDF descargado',
                                'inquiry_sent' => 'Consulta enviada',
                                'whatsapp_clicked' => 'Click en WhatsApp',
                                'share_clicked' => 'Compartido',
                                'calculator_used' => 'Calculadora usada',
                                'payment_plan_viewed' => 'Plan de pago visto',
                                'gallery_viewed' => 'Galeria vista',
                                'viewer_3d_opened' => 'Visor 3D abierto',
                            ];
                        @endphp
                        @foreach($stats['event_counts'] as $type => $count)
                        <div class="flex justify-between text-sm border-b border-gray-50 pb-1">
                            <span class="text-gray-600">{{ $eventLabels[$type] ?? $type }}</span>
                            <span class="font-medium text-gray-800">{{ number_format($count) }}</span>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-sm text-gray-500">Sin datos aun</p>
                    @endif
                </div>
            </div>
            @else
            <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">
                <p>No hay proyectos disponibles para mostrar analytics.</p>
            </div>
            @endif
        </div>
    </div>

    @if($stats)
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sessions per day chart
            const sessionsData = @json($stats['sessions_per_day']);
            const labels = Object.keys(sessionsData);
            const values = Object.values(sessionsData);

            // Fill missing days
            const days = {{ $stats['days'] }};
            const allDates = [];
            const allValues = [];
            for (let i = days - 1; i >= 0; i--) {
                const d = new Date();
                d.setDate(d.getDate() - i);
                const key = d.toISOString().split('T')[0];
                allDates.push(key.slice(5)); // MM-DD
                allValues.push(sessionsData[key] || 0);
            }

            new Chart(document.getElementById('sessions-chart'), {
                type: 'line',
                data: {
                    labels: allDates,
                    datasets: [{
                        label: 'Sesiones',
                        data: allValues,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: allDates.length > 30 ? 0 : 3,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } },
                        x: { ticks: { maxTicksLimit: 10 } }
                    }
                }
            });

            // Device chart
            const devices = @json($stats['devices']);
            const deviceLabels = Object.keys(devices).map(d => d.charAt(0).toUpperCase() + d.slice(1));
            const deviceValues = Object.values(devices);
            const deviceColors = ['#2563eb', '#7c3aed', '#059669', '#d97706'];

            new Chart(document.getElementById('device-chart'), {
                type: 'doughnut',
                data: {
                    labels: deviceLabels,
                    datasets: [{
                        data: deviceValues,
                        backgroundColor: deviceColors.slice(0, deviceLabels.length),
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
                }
            });
        });
    </script>
    @endif
</x-app-layout>
