<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $project->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #fff; color: #1f2937; font-size: 14px; }
        .widget { max-width: 100%; padding: 16px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .project-name { font-size: 18px; font-weight: 700; color: #111827; }
        .project-location { font-size: 12px; color: #6b7280; margin-top: 2px; }
        .badge { display: inline-block; background: #d1fae5; color: #065f46; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 9999px; }
        .stats { display: flex; gap: 16px; margin-bottom: 16px; padding: 10px 0; border-top: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; }
        .stat { text-align: center; flex: 1; }
        .stat-value { font-size: 20px; font-weight: 700; color: #059669; }
        .stat-label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .units-list { margin-bottom: 12px; }
        .unit-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
        .unit-row:last-child { border-bottom: none; }
        .unit-info { display: flex; gap: 8px; align-items: center; }
        .unit-name { font-weight: 600; font-size: 13px; }
        .unit-detail { font-size: 12px; color: #6b7280; }
        .unit-price { font-weight: 700; color: #059669; }
        .cta { display: block; width: 100%; text-align: center; padding: 10px; background: #059669; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px; transition: background 0.2s; }
        .cta:hover { background: #047857; }
        .powered { text-align: center; margin-top: 8px; font-size: 10px; color: #9ca3af; }
        .powered a { color: #9ca3af; text-decoration: none; }
    </style>
</head>
<body>
    <div class="widget">
        <div class="header">
            <div>
                <div class="project-name">{{ $project->name }}</div>
                @if($project->location)
                    <div class="project-location">{{ $project->location }}</div>
                @endif
            </div>
            <span class="badge">{{ $project->available_units_count }} disponibles</span>
        </div>

        <div class="stats">
            <div class="stat">
                <div class="stat-value">{{ $project->units_count }}</div>
                <div class="stat-label">Unidades</div>
            </div>
            <div class="stat">
                <div class="stat-value">{{ $project->available_units_count }}</div>
                <div class="stat-label">Disponibles</div>
            </div>
            @if($priceMin)
            <div class="stat">
                <div class="stat-value">${{ number_format($priceMin / 1000, 0) }}K</div>
                <div class="stat-label">Desde</div>
            </div>
            @endif
        </div>

        @if($units->count() > 0)
        <div class="units-list">
            @foreach($units->take(5) as $unit)
                <div class="unit-row">
                    <div class="unit-info">
                        <span class="unit-name">{{ $unit->identifier }}</span>
                        <span class="unit-detail">{{ $unit->bedrooms }}hab &middot; {{ $unit->area_m2 }}m&sup2;</span>
                    </div>
                    <span class="unit-price">USD {{ number_format($unit->price, 0, '.', ',') }}</span>
                </div>
            @endforeach
        </div>
        @endif

        <a href="{{ route('viewer.landing', $project) }}" target="_blank" class="cta">
            Ver proyecto completo
        </a>
        <div class="powered">
            Powered by <a href="{{ url('/') }}" target="_blank">Real3D.io</a>
        </div>
    </div>
</body>
</html>
