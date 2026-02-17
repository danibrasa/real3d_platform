<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f5f5f5; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: #2563eb; color: #fff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; }
        .body { padding: 24px; }
        .label { font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .value { font-size: 15px; margin-bottom: 16px; }
        .message-box { background: #f8f9fa; border-left: 3px solid #2563eb; padding: 12px 16px; margin: 16px 0; border-radius: 0 6px 6px 0; }
        .unit-badge { display: inline-block; background: #e0f2fe; color: #0369a1; padding: 4px 12px; border-radius: 12px; font-size: 13px; font-weight: 600; }
        .footer { padding: 16px 24px; background: #f8f9fa; font-size: 12px; color: #888; text-align: center; }
        .btn { display: inline-block; padding: 10px 24px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Nueva consulta recibida</h1>
        </div>
        <div class="body">
            <p style="margin-top:0;">Se ha recibido una nueva consulta para el proyecto <strong>{{ $project->name }}</strong>.</p>

            <div class="label">Nombre</div>
            <div class="value">{{ $inquiry->name }}</div>

            <div class="label">Email</div>
            <div class="value"><a href="mailto:{{ $inquiry->email }}">{{ $inquiry->email }}</a></div>

            @if($inquiry->phone)
            <div class="label">Telefono</div>
            <div class="value">{{ $inquiry->phone }}</div>
            @endif

            @if($unit)
            <div class="label">Unidad de interes</div>
            <div class="value"><span class="unit-badge">{{ $unit->identifier }} - {{ $unit->formatted_price }}</span></div>
            @endif

            @if($inquiry->message)
            <div class="label">Mensaje</div>
            <div class="message-box">{{ $inquiry->message }}</div>
            @endif

            <p style="margin-top: 24px;">
                <a href="{{ url('/admin/inquiries/' . $inquiry->id) }}" class="btn">Ver en panel admin</a>
            </p>
        </div>
        <div class="footer">
            {{ config('app.name') }} &mdash; {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>
