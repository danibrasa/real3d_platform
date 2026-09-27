<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('investment.report_title') }} — {{ $unit->identifier }}</title>
    <style>
        @page { margin: 22mm 18mm; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 10.5px; line-height: 1.5; margin: 0; }
        h1 { font-size: 20px; margin: 0 0 4px; color: #ffffff; }
        h2 { font-size: 12.5px; margin: 0 0 8px; color: #1e40af; text-transform: uppercase; letter-spacing: .4px; }

        .cabecera { background-color: #1e40af; color: #ffffff; padding: 18px 20px; margin-bottom: 18px; }
        .cabecera .proyecto { color: #bfdbfe; font-size: 11px; }
        .cabecera .ubicacion { color: #93c5fd; font-size: 10px; margin-top: 2px; }

        .destacado { background-color: #eff6ff; border-left: 3px solid #1e40af; padding: 12px 16px; margin-bottom: 16px; }
        .destacado table { width: 100%; }
        .destacado .cifra { font-size: 22px; font-weight: bold; color: #1e40af; }
        .destacado .etiqueta { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: .3px; }

        table { width: 100%; border-collapse: collapse; }
        .cuentas td { padding: 5px 0; border-bottom: 1px solid #f3f4f6; }
        .cuentas td.cifra { text-align: right; font-weight: bold; }
        .cuentas tr.gasto td { color: #b91c1c; }
        .cuentas tr.total td { border-top: 2px solid #1e40af; border-bottom: none; padding-top: 8px; font-weight: bold; font-size: 11.5px; }
        .cuentas tr.total td.cifra { color: #047857; }

        .supuestos { background-color: #f9fafb; border: 1px solid #e5e7eb; padding: 12px 16px; margin-bottom: 16px; }
        .supuestos td { padding: 3px 0; font-size: 9.5px; color: #4b5563; }
        .supuestos td.valor { text-align: right; font-weight: bold; color: #1f2937; }

        .bloque { margin-bottom: 16px; }
        .aviso { font-size: 8.5px; color: #9ca3af; line-height: 1.45; border-top: 1px solid #e5e7eb; padding-top: 10px; margin-top: 4px; }
        .pie { font-size: 8.5px; color: #9ca3af; margin-top: 14px; }
        .qr { float: right; margin-left: 14px; text-align: center; }
        .qr span { display: block; font-size: 8px; color: #9ca3af; margin-top: 3px; }
    </style>
</head>
<body>

<div class="cabecera">
    <h1>{{ __('investment.unit') }} {{ $unit->identifier }}</h1>
    <div class="proyecto">{{ $project->name }}</div>
    @if ($project->location)
        <div class="ubicacion">{{ $project->location }}</div>
    @endif
</div>

@if ($qrSvg)
    <div class="qr">
        {!! $qrSvg !!}
        <span>{{ __('investment.see_online') }}</span>
    </div>
@endif

{{-- Lo primero que busca quien abre esto: cuanto renta y cuando lo recupera --}}
<div class="destacado">
    <table>
        <tr>
            <td width="34%">
                <div class="etiqueta">{{ __('investment.price') }}</div>
                <div class="cifra">{{ $moneda }} {{ number_format($c['precio'], 0, ',', '.') }}</div>
            </td>
            <td width="33%">
                <div class="etiqueta">{{ __('investment.annual_return') }}</div>
                <div class="cifra">{{ number_format($c['rentabilidad'], 1, ',', '.') }}%</div>
            </td>
            <td width="33%">
                <div class="etiqueta">{{ __('investment.payback') }}</div>
                <div class="cifra">
                    @if ($c['anos_retorno'] !== null)
                        {{ number_format($c['anos_retorno'], 1, ',', '.') }} {{ __('investment.years') }}
                    @else
                        —
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="bloque">
    <h2>{{ __('investment.annual_breakdown') }}</h2>
    <table class="cuentas">
        <tr>
            <td>{{ __('investment.gross_income') }}</td>
            <td class="cifra">{{ $moneda }} {{ number_format($c['ingreso_bruto'], 0, ',', '.') }}</td>
        </tr>
        <tr class="gasto">
            <td>{{ __('investment.management') }} ({{ number_format($c['supuestos']['comision'], 0) }}%)</td>
            <td class="cifra">− {{ $moneda }} {{ number_format($c['coste_gestion'], 0, ',', '.') }}</td>
        </tr>
        <tr class="gasto">
            <td>{{ __('investment.taxes') }} ({{ number_format($c['supuestos']['impuestos'], 1, ',', '.') }}%)</td>
            <td class="cifra">− {{ $moneda }} {{ number_format($c['coste_impuestos'], 0, ',', '.') }}</td>
        </tr>
        <tr class="total">
            <td>{{ __('investment.net_income') }}</td>
            <td class="cifra">{{ $moneda }} {{ number_format($c['ingreso_neto'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td style="padding-top:6px; color:#6b7280;">{{ __('investment.monthly_net') }}</td>
            <td class="cifra" style="padding-top:6px; color:#6b7280;">{{ $moneda }} {{ number_format($c['ingreso_mensual'], 0, ',', '.') }}</td>
        </tr>
    </table>
</div>

<div class="bloque">
    <h2>{{ __('investment.projection', ['years' => $c['anos']]) }}</h2>
    <table class="cuentas">
        <tr>
            <td>{{ __('investment.accumulated_rent', ['years' => $c['anos']]) }}</td>
            <td class="cifra">{{ $moneda }} {{ number_format($c['ingreso_neto'] * $c['anos'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>{{ __('investment.future_value') }} ({{ number_format($c['supuestos']['revalorizacion'], 1, ',', '.') }}% {{ __('investment.annual') }})</td>
            <td class="cifra">{{ $moneda }} {{ number_format($c['valor_futuro'], 0, ',', '.') }}</td>
        </tr>
        <tr class="total">
            <td>{{ __('investment.total_gain') }}</td>
            <td class="cifra">{{ $moneda }} {{ number_format($c['ganancia_total'], 0, ',', '.') }}</td>
        </tr>
    </table>
</div>

{{-- De donde sale cada numero. Sin esto, la proyeccion es una afirmacion
     sin respaldo, y quien compra a distancia desconfia con razon. --}}
<div class="supuestos">
    <h2 style="margin-bottom:6px;">{{ __('investment.assumptions') }}</h2>
    <table>
        <tr>
            <td>{{ __('investment.nightly_rate') }}</td>
            <td class="valor">{{ $moneda }} {{ number_format($c['supuestos']['precio_noche'], 0, ',', '.') }}</td>
            <td width="20"></td>
            <td>{{ __('investment.occupancy') }}</td>
            <td class="valor">{{ number_format($c['supuestos']['ocupacion'], 0) }}%</td>
        </tr>
        <tr>
            <td>{{ __('investment.management_fee') }}</td>
            <td class="valor">{{ number_format($c['supuestos']['comision'], 0) }}%</td>
            <td></td>
            <td>{{ __('investment.property_tax') }}</td>
            <td class="valor">{{ number_format($c['supuestos']['impuestos'], 1, ',', '.') }}%</td>
        </tr>
        <tr>
            <td>{{ __('investment.appreciation') }}</td>
            <td class="valor">{{ number_format($c['supuestos']['revalorizacion'], 1, ',', '.') }}%</td>
            <td></td>
            <td>{{ __('investment.unit_data') }}</td>
            <td class="valor">
                {{ $unit->bedrooms }}{{ __('investment.bed_short') }} ·
                {{ $unit->bathrooms }}{{ __('investment.bath_short') }} ·
                {{ number_format($unit->area_m2, 0, ',', '.') }} m²
            </td>
        </tr>
    </table>
</div>

<p class="aviso">{{ __('investment.disclaimer') }}</p>

<p class="pie">
    {{ $project->name }} · {{ __('investment.generated_on') }} {{ now()->format('d/m/Y') }}
    @if ($project->contact_email) · {{ $project->contact_email }} @endif
</p>

</body>
</html>
