<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Plan de Pagos - {{ $unit->identifier }} - {{ $project->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            line-height: 1.5;
            color: #1f2937;
        }

        /* Header */
        .header {
            background-color: #1e40af;
            color: #ffffff;
            padding: 24px 30px;
            position: relative;
        }
        .header-brand {
            font-size: 10pt;
            font-weight: bold;
            letter-spacing: 1px;
            color: #93c5fd;
            margin-bottom: 4px;
        }
        .header-project {
            font-size: 20pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .header-location {
            font-size: 10pt;
            color: #bfdbfe;
        }
        .header-tag {
            position: absolute;
            top: 24px;
            right: 30px;
            background-color: #3b82f6;
            color: #fff;
            padding: 4px 14px;
            border-radius: 4px;
            font-size: 9pt;
            font-weight: bold;
        }

        /* Unit info bar */
        .unit-info-bar {
            background-color: #f0f9ff;
            border-bottom: 2px solid #1e40af;
            padding: 12px 30px;
        }
        .unit-info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .unit-info-table td {
            padding: 2px 8px;
            font-size: 9pt;
            vertical-align: middle;
        }
        .info-label {
            color: #6b7280;
            font-size: 8pt;
            text-transform: uppercase;
            font-weight: bold;
        }
        .info-value {
            color: #111827;
            font-weight: 500;
        }
        .info-price {
            font-size: 14pt;
            font-weight: bold;
            color: #1e40af;
        }
        .unit-status {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-available { background-color: #dcfce7; color: #166534; }
        .status-reserved { background-color: #fef9c3; color: #854d0e; }
        .status-sold { background-color: #fee2e2; color: #991b1b; }

        /* Content */
        .content {
            padding: 20px 30px;
        }

        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #1e3a5f;
            margin: 16px 0 10px;
            padding-bottom: 4px;
            border-bottom: 1px solid #dbeafe;
        }

        .plan-name {
            font-size: 11pt;
            font-weight: bold;
            color: #374151;
            margin-bottom: 12px;
        }

        /* Progress bar */
        .progress-bar-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .progress-bar-table td {
            height: 16px;
            text-align: center;
            font-size: 7pt;
            font-weight: bold;
            color: #ffffff;
        }
        .progress-bar-first { border-radius: 8px 0 0 8px; }
        .progress-bar-last { border-radius: 0 8px 8px 0; }
        .progress-bar-only { border-radius: 8px; }

        /* Milestones table */
        .milestones-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .milestones-table th {
            background-color: #1e40af;
            color: #ffffff;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
            font-weight: bold;
        }
        .milestones-table th:nth-child(n+3) { text-align: right; }
        .milestones-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9pt;
            vertical-align: top;
        }
        .milestones-table td:nth-child(n+3) { text-align: right; }
        .milestone-name {
            font-weight: 600;
            color: #111827;
        }
        .milestone-when {
            font-size: 8pt;
            color: #6b7280;
        }
        .milestone-pct {
            font-weight: bold;
            color: #1e40af;
        }
        .total-row td {
            background-color: #dbeafe;
            font-weight: bold;
            color: #1e3a5f;
            border-bottom: 2px solid #1e40af;
            padding: 10px;
        }

        /* Phase color indicators */
        .phase-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
            vertical-align: middle;
        }

        /* Disclaimer */
        .disclaimer {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 4px;
            padding: 10px 14px;
            margin-top: 16px;
        }
        .disclaimer-label {
            font-weight: bold;
            color: #78350f;
            font-size: 8pt;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .disclaimer p {
            font-size: 8pt;
            color: #92400e;
            margin: 0;
        }

        /* Footer */
        .footer-bar {
            background-color: #f0f9ff;
            border-top: 2px solid #1e40af;
            padding: 14px 30px;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-table td {
            vertical-align: middle;
            padding: 0;
        }
        .footer-qr {
            width: 100px;
            text-align: center;
        }
        .footer-qr svg {
            width: 80px;
            height: 80px;
        }
        .footer-qr-label {
            font-size: 7pt;
            color: #6b7280;
            margin-top: 2px;
        }
        .footer-contact {
            padding-left: 16px;
        }
        .footer-contact p {
            font-size: 9pt;
            color: #374151;
            margin-bottom: 2px;
        }
        .footer-contact strong {
            color: #111827;
        }
        .footer-brand {
            text-align: right;
        }
        .footer-brand-name {
            font-size: 9pt;
            font-weight: bold;
            color: #1e40af;
        }
        .footer-brand-url {
            font-size: 7pt;
            color: #6b7280;
        }
        .footer-brand-date {
            font-size: 7pt;
            color: #9ca3af;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <div class="header-brand">REALESTATE 3D</div>
        <div class="header-project">{{ $project->name }}</div>
        @if($project->location)
            <div class="header-location">{{ $project->location }}</div>
        @endif
        <div class="header-tag">PLAN DE PAGOS</div>
    </div>

    {{-- Unit info bar --}}
    <div class="unit-info-bar">
        <table class="unit-info-table">
            <tr>
                <td>
                    <span class="info-label">Unidad:</span>
                    <span class="info-value" style="font-weight: bold; font-size: 11pt;">{{ $unit->identifier }}</span>
                </td>
                <td>
                    <span class="info-label">Tipologia:</span>
                    <span class="info-value">{{ $unit->typology?->name ?? '-' }}</span>
                </td>
                <td>
                    <span class="info-label">Precio:</span>
                    @if($hasDiscount)
                        <span style="text-decoration: line-through; color: #9ca3af; font-size: 9pt;">{{ $formattedPrice }}</span>
                        <span class="info-price" style="color: #059669;">{{ $formattedEffectivePrice }}</span>
                    @else
                        <span class="info-price">{{ $formattedPrice }}</span>
                    @endif
                </td>
                <td style="text-align: right;">
                    @php
                        $statusLabels = ['available' => 'Disponible', 'reserved' => 'Reservado', 'sold' => 'Vendido'];
                    @endphp
                    <span class="unit-status status-{{ $unit->status }}">{{ $statusLabels[$unit->status] ?? $unit->status }}</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- Content --}}
    <div class="content">
        <div class="plan-name">{{ $plan->name }}</div>

        {{-- Progress bar --}}
        @php
            $colorBarMap = [
                'blue'  => '#2563eb',
                'amber' => '#d97706',
                'green' => '#059669',
                'gray'  => '#6b7280',
            ];
        @endphp
        <table class="progress-bar-table">
            <tr>
                @foreach($milestonesData as $i => $ms)
                @php
                    $barColor = $colorBarMap[$ms['color']] ?? '#6b7280';
                    $radiusClass = '';
                    if (count($milestonesData) === 1) $radiusClass = 'progress-bar-only';
                    elseif ($i === 0) $radiusClass = 'progress-bar-first';
                    elseif ($i === count($milestonesData) - 1) $radiusClass = 'progress-bar-last';
                @endphp
                <td class="{{ $radiusClass }}"
                    style="width: {{ $ms['pct'] }}%; background-color: {{ $barColor }};">
                    @if($ms['pct'] > 8){{ number_format($ms['pct'], 0) }}%@endif
                </td>
                @endforeach
            </tr>
        </table>

        {{-- Milestones table --}}
        <table class="milestones-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 30%;">Hito</th>
                    <th style="width: 15%;">Cuando</th>
                    <th style="width: 10%;">%</th>
                    <th style="width: 20%;">Monto</th>
                    <th style="width: 10%;">Acum %</th>
                    <th style="width: 20%;">Acumulado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($milestonesData as $i => $ms)
                @php $dotColor = $colorBarMap[$ms['color']] ?? '#6b7280'; @endphp
                <tr>
                    <td>
                        <span class="phase-dot" style="background-color: {{ $dotColor }};"></span>
                        {{ $i + 1 }}
                    </td>
                    <td>
                        <div class="milestone-name">{{ $ms['name'] }}</div>
                        @if($ms['computedDate'])
                        <div class="milestone-when">~{{ $ms['computedDate'] }}</div>
                        @endif
                    </td>
                    <td class="milestone-when">{{ $ms['due_description'] ?? '' }}</td>
                    <td class="milestone-pct">{{ number_format($ms['pct'], 0) }}%</td>
                    <td>{{ $ms['formattedAmount'] }}</td>
                    <td>{{ number_format($ms['cumPct'], 0) }}%</td>
                    <td>{{ $ms['formattedCumAmount'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                @if($hasDiscount)
                <tr>
                    <td colspan="3" style="color: #6b7280;">Precio original</td>
                    <td></td>
                    <td style="text-decoration: line-through; color: #9ca3af;">{{ $formattedPrice }}</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="3" style="color: #b45309;">{{ $discountLabel }}</td>
                    <td></td>
                    <td style="color: #dc2626; font-weight: bold;">-{{ $discountAmount }}</td>
                    <td></td>
                    <td></td>
                </tr>
                @endif
                <tr class="total-row">
                    <td colspan="3" style="font-weight: bold;">TOTAL</td>
                    <td style="font-weight: bold;">{{ number_format(array_sum(array_column($milestonesData, 'pct')), 0) }}%</td>
                    <td style="font-weight: bold;">{{ $formattedEffectivePrice }}</td>
                    <td></td>
                    <td style="font-weight: bold;">{{ $formattedEffectivePrice }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- Estimated delivery --}}
        @if($project->estimated_delivery)
        <p style="font-size: 9pt; color: #6b7280; margin-top: 8px;">
            <strong>Entrega estimada:</strong> {{ $project->estimated_delivery->translatedFormat('F Y') }}
        </p>
        @endif

        {{-- Disclaimer --}}
        <div class="disclaimer">
            <div class="disclaimer-label">Aviso importante</div>
            <p>Los montos y fechas indicados son estimativos y sujetos a modificacion. Este documento no constituye un compromiso contractual.</p>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer-bar">
        <table class="footer-table">
            <tr>
                <td class="footer-qr">
                    {!! $qrSvg !!}
                    <div class="footer-qr-label">Ver plan<br>de pagos online</div>
                </td>
                <td class="footer-contact">
                    @if($project->contact_email)
                        <p><strong>Email:</strong> {{ $project->contact_email }}</p>
                    @endif
                    @if($project->whatsapp_number)
                        <p><strong>WhatsApp:</strong> {{ $project->whatsapp_number }}</p>
                    @endif
                    <p style="font-size: 8pt; color: #6b7280; margin-top: 4px;">{{ $landingUrl }}</p>
                </td>
                <td class="footer-brand">
                    <div class="footer-brand-name">Real3D.io</div>
                    <div class="footer-brand-url">{{ url('/') }}</div>
                    <div class="footer-brand-date">Generado: {{ now()->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
