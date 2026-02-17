<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Ficha {{ $unit->identifier }} - {{ $project->name }}</title>
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

        /* Unit title bar */
        .unit-title-bar {
            background-color: #f0f9ff;
            border-bottom: 2px solid #1e40af;
            padding: 14px 30px;
        }
        .unit-title-bar h2 {
            font-size: 16pt;
            font-weight: bold;
            color: #1e3a5f;
            display: inline;
        }
        .unit-status {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-left: 10px;
            vertical-align: middle;
        }
        .status-available { background-color: #dcfce7; color: #166534; }
        .status-reserved { background-color: #fef9c3; color: #854d0e; }
        .status-sold { background-color: #fee2e2; color: #991b1b; }

        /* Content area */
        .content {
            padding: 20px 30px;
        }

        /* Data grid */
        .data-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-grid td {
            padding: 8px 12px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .data-label {
            font-size: 8pt;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: bold;
            width: 30%;
            background-color: #f9fafb;
        }
        .data-value {
            font-size: 10pt;
            color: #111827;
            font-weight: 500;
        }

        /* Price highlight */
        .price-row .data-value {
            font-size: 14pt;
            font-weight: bold;
            color: #1e40af;
        }

        /* Section titles */
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #1e3a5f;
            margin: 20px 0 10px;
            padding-bottom: 4px;
            border-bottom: 1px solid #dbeafe;
        }

        /* Floor plan */
        .floor-plan-container {
            text-align: center;
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background-color: #fafafa;
        }
        .floor-plan-container img {
            max-width: 100%;
            max-height: 280px;
        }

        /* Gallery grid */
        .gallery-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .gallery-grid td {
            width: 25%;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
        }
        .gallery-grid img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
        }

        /* QR + Contact footer */
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

        /* Notes box */
        .notes-box {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 4px;
            padding: 10px 14px;
            margin-top: 12px;
        }
        .notes-box p {
            font-size: 9pt;
            color: #92400e;
            margin: 0;
        }
        .notes-box .notes-label {
            font-weight: bold;
            color: #78350f;
            font-size: 8pt;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        /* Thumbnail */
        .thumbnail-container {
            text-align: center;
            margin-bottom: 16px;
        }
        .thumbnail-container img {
            max-width: 100%;
            max-height: 180px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
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
        <div class="header-tag">FICHA DE UNIDAD</div>
    </div>

    {{-- Unit title bar --}}
    <div class="unit-title-bar">
        <h2>Unidad {{ $unit->identifier }}</h2>
        @php
            $statusLabels = ['available' => 'Disponible', 'reserved' => 'Reservado', 'sold' => 'Vendido'];
            $statusClass = 'status-' . $unit->status;
        @endphp
        <span class="unit-status {{ $statusClass }}">{{ $statusLabels[$unit->status] ?? $unit->status }}</span>
        @if($unit->typology)
            <span style="font-size: 10pt; color: #6b7280; margin-left: 10px;">Tipologia: {{ $unit->typology->name }}</span>
        @endif
    </div>

    {{-- Content --}}
    <div class="content">
        {{-- Thumbnail --}}
        @if($thumbnailBase64)
        <div class="thumbnail-container">
            <img src="{{ $thumbnailBase64 }}" alt="{{ $project->name }}">
        </div>
        @endif

        {{-- Data grid --}}
        <table class="data-grid">
            <tr>
                <td class="data-label">Identificador</td>
                <td class="data-value">{{ $unit->identifier }}</td>
                <td class="data-label">Piso</td>
                <td class="data-value">{{ $unit->floor == 0 ? 'Planta Baja' : 'Piso ' . $unit->floor }}</td>
            </tr>
            @if($unit->typology)
            <tr>
                <td class="data-label">Tipologia</td>
                <td class="data-value" colspan="3">{{ $unit->typology->name }}@if($unit->typology->description) — {{ $unit->typology->description }}@endif</td>
            </tr>
            @endif
            <tr>
                <td class="data-label">Dormitorios</td>
                <td class="data-value">{{ $unit->bedrooms }}</td>
                <td class="data-label">Banos</td>
                <td class="data-value">{{ $unit->bathrooms }}</td>
            </tr>
            <tr>
                <td class="data-label">Area</td>
                <td class="data-value">{{ $unit->area_m2 }} m&sup2;</td>
                <td class="data-label">Estado</td>
                <td class="data-value">{{ $statusLabels[$unit->status] ?? $unit->status }}</td>
            </tr>
            @if($unit->price)
            <tr class="price-row">
                <td class="data-label">Precio</td>
                <td class="data-value" colspan="3">{{ $unit->formatted_price }}</td>
            </tr>
            @endif
            @if($unit->price && $unit->area_m2)
            <tr>
                <td class="data-label">Precio / m&sup2;</td>
                <td class="data-value" colspan="3">USD {{ number_format($unit->price / $unit->area_m2, 0, '.', ',') }}/m&sup2;</td>
            </tr>
            @endif
            @if($project->estimated_delivery)
            <tr>
                <td class="data-label">Entrega estimada</td>
                <td class="data-value" colspan="3">{{ $project->estimated_delivery->translatedFormat('F Y') }}</td>
            </tr>
            @endif
        </table>

        {{-- Payment plan --}}
        @php
            $defaultPlan = $project->paymentPlans->firstWhere('is_default', true) ?? $project->paymentPlans->first();
        @endphp
        @if($defaultPlan && $defaultPlan->milestones->count())
        <div class="section-title">Plan de Pago — {{ $defaultPlan->name }}</div>
        <table class="data-grid" style="margin-bottom: 12px;">
            @foreach($defaultPlan->milestones as $ms)
            <tr>
                <td class="data-label" style="width: 40%;">{{ $ms->name }}@if($ms->due_description) <span style="font-weight: normal; font-size: 7pt;"><br>{{ $ms->due_description }}</span>@endif</td>
                <td class="data-value" style="text-align: center; width: 15%; font-weight: bold; color: #1e40af;">{{ number_format($ms->percentage, 0) }}%</td>
                <td class="data-value" style="width: 45%;">
                    @if($unit->price)
                        USD {{ number_format($unit->price * $ms->percentage / 100, 0, '.', ',') }}
                    @endif
                    @if($ms->description)
                        <span style="font-size: 8pt; color: #6b7280;">— {{ $ms->description }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
            @if($unit->price)
            <tr>
                <td class="data-label" style="font-weight: bold;">TOTAL</td>
                <td class="data-value" style="text-align: center; font-weight: bold;">{{ number_format($defaultPlan->milestones->sum('percentage'), 0) }}%</td>
                <td class="data-value" style="font-weight: bold; color: #1e40af;">{{ $unit->formatted_price }}</td>
            </tr>
            @endif
        </table>
        @endif

        {{-- Floor plan --}}
        @if($floorPlanBase64)
        <div class="section-title">Plano de Planta</div>
        <div class="floor-plan-container">
            <img src="{{ $floorPlanBase64 }}" alt="Plano de {{ $unit->identifier }}">
        </div>
        @endif

        {{-- Project description --}}
        @if($project->description)
        <div class="section-title">Sobre el Proyecto</div>
        <p style="font-size: 9pt; color: #4b5563; text-align: justify;">{{ Str::limit($project->description, 400) }}</p>
        @endif

        {{-- Gallery --}}
        @if(count($galleryBase64))
        <div class="section-title">Galeria del Proyecto</div>
        <table class="gallery-grid">
            <tr>
                @foreach($galleryBase64 as $img64)
                <td><img src="{{ $img64 }}" alt="Galeria"></td>
                @endforeach
                @for($i = count($galleryBase64); $i < 4; $i++)
                <td></td>
                @endfor
            </tr>
        </table>
        @endif

        {{-- Notes --}}
        @if($unit->notes)
        <div class="notes-box">
            <p class="notes-label">Notas</p>
            <p>{{ $unit->notes }}</p>
        </div>
        @endif
    </div>

    {{-- Footer with QR + contact --}}
    <div class="footer-bar">
        <table class="footer-table">
            <tr>
                <td class="footer-qr">
                    {!! $qrSvg !!}
                    <div class="footer-qr-label">Escanea para ver<br>en el visor 3D</div>
                </td>
                <td class="footer-contact">
                    @if($project->contact_email)
                        <p><strong>Email:</strong> {{ $project->contact_email }}</p>
                    @endif
                    @if($project->whatsapp_number)
                        <p><strong>WhatsApp:</strong> {{ $project->whatsapp_number }}</p>
                    @endif
                    <p style="font-size: 8pt; color: #6b7280; margin-top: 4px;">{{ $unitUrl }}</p>
                </td>
                <td class="footer-brand">
                    <div class="footer-brand-name">RealEstate 3D</div>
                    <div class="footer-brand-url">{{ url('/') }}</div>
                    <div class="footer-brand-date">Generado: {{ now()->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
