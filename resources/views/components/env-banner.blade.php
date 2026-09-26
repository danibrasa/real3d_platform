{{-- Franja que identifica el entorno. Solo aparece fuera de produccion, para que
     no se confunda con la web real: son identicas a simple vista.
     Un color por entorno, para distinguirlos de un vistazo. --}}
@if (!app()->environment('production'))
    @php
        $v = \App\Support\Version::all();
        $esDev = app()->environment('local', 'development');
        $fondo = $esDev ? '#7c3aed' : '#facc15';
        $texto = $esDev ? '#f5f3ff' : '#422006';
        $borde = $esDev ? '#5b21b6' : '#ca8a04';
        $rotulo = $esDev
            ? 'DESARROLLO — entorno de trabajo del agente'
            : strtoupper(app()->environment()).' — no es la web real';
    @endphp
    <div role="status"
         style="position:sticky;top:0;z-index:9999;display:flex;flex-wrap:wrap;
                align-items:center;justify-content:center;gap:4px 14px;
                padding:6px 16px;background:{{ $fondo }};color:{{ $texto }};
                font:600 12px/1.4 ui-sans-serif,system-ui,-apple-system,'Segoe UI',sans-serif;
                letter-spacing:.02em;border-bottom:1px solid {{ $borde }};">
        <span>{{ $rotulo }}</span>
        <span style="font-weight:500;opacity:.8;font-family:ui-monospace,Consolas,monospace;">
            {{ \App\Support\Version::label() }}@if($v['deployed_at']) · {{ $v['deployed_at'] }}@endif
        </span>
    </div>
@endif
