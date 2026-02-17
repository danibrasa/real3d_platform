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
        .project-card { background: #f8f9fa; border-radius: 8px; padding: 16px; margin: 16px 0; text-align: center; }
        .project-card h3 { margin: 0 0 4px; color: #1e40af; }
        .project-card p { margin: 0; font-size: 13px; color: #666; }
        .footer { padding: 16px 24px; background: #f8f9fa; font-size: 12px; color: #888; text-align: center; }
        .btn { display: inline-block; padding: 10px 24px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Recibimos tu consulta</h1>
        </div>
        <div class="body">
            <p style="margin-top:0;">Hola <strong>{{ $inquiry->name }}</strong>,</p>

            <p>Gracias por tu interes en nuestro proyecto. Hemos recibido tu consulta y nos pondremos en contacto contigo a la brevedad.</p>

            <div class="project-card">
                <h3>{{ $project->name }}</h3>
                @if($project->location)
                    <p>{{ $project->location }}</p>
                @endif
            </div>

            @if($unit)
            <p>Unidad consultada: <strong>{{ $unit->identifier }}</strong> ({{ $unit->formatted_price }})</p>
            @endif

            <p style="margin-top: 24px;">
                <a href="{{ route('viewer.landing', $project->slug) }}" class="btn">Ver proyecto</a>
            </p>

            @if($project->whatsapp_number)
            <p style="margin-top: 16px; font-size: 13px; color: #666;">
                Tambien puedes contactarnos por
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $project->whatsapp_number) }}?text={{ urlencode('Hola, me interesa el proyecto ' . $project->name) }}">WhatsApp</a>.
            </p>
            @endif
        </div>
        <div class="footer">
            {{ config('app.name') }} &mdash; {{ $project->name }}
        </div>
    </div>
</body>
</html>
