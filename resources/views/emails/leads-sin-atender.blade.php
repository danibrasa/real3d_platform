<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f5f5f5; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: #b45309; color: #fff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; }
        .body { padding: 24px; }
        .lead { border-left: 3px solid #b45309; padding: 10px 14px; margin: 12px 0; background: #fffbeb; border-radius: 0 6px 6px 0; }
        .lead b { display: block; }
        .lead small { color: #777; }
        .btn { display: inline-block; padding: 10px 24px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; }
        .footer { padding: 16px 24px; background: #f8f9fa; font-size: 12px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $leads->count() === 1 ? 'Un comprador sigue esperando' : $leads->count().' compradores siguen esperando' }}</h1>
        </div>
        <div class="body">
            <p>{{ __('inquiry.sin_atender_intro', ['horas' => $horas]) }}</p>

            @foreach ($leads as $lead)
                <div class="lead">
                    <b>{{ $lead->name }} · {{ $lead->project->name }}</b>
                    {{ $lead->email }}@if ($lead->phone) · {{ $lead->phone }}@endif
                    @if ($lead->unit) · {{ $lead->unit->identifier }}@endif
                    <br><small>{{ __('inquiry.escribio_hace', ['tiempo' => $lead->created_at->diffForHumans()]) }}</small>
                    @if ($lead->message)<br><small>“{{ \Illuminate\Support\Str::limit($lead->message, 140) }}”</small>@endif
                </div>
            @endforeach

            <p style="margin-top: 20px;">
                <a href="{{ route('admin.inquiries.index', ['estado' => 'nuevo']) }}" class="btn">{{ __('inquiry.ir_a_la_bandeja') }}</a>
            </p>
        </div>
        <div class="footer">{{ __('inquiry.sin_atender_pie') }}</div>
    </div>
</body>
</html>
