{{-- Franja que identifica el entorno. Solo aparece fuera de produccion, para que
     no se confunda staging con la web real: son identicas a simple vista. --}}
@if (!app()->environment('production'))
    @php $v = \App\Support\Version::all(); @endphp
    <div role="status"
         style="position:sticky;top:0;z-index:9999;display:flex;flex-wrap:wrap;
                align-items:center;justify-content:center;gap:4px 14px;
                padding:6px 16px;background:#facc15;color:#422006;
                font:600 12px/1.4 ui-sans-serif,system-ui,-apple-system,'Segoe UI',sans-serif;
                letter-spacing:.02em;border-bottom:1px solid #ca8a04;">
        <span>{{ strtoupper(app()->environment()) }} — no es la web real</span>
        <span style="font-weight:500;opacity:.8;font-family:ui-monospace,Consolas,monospace;">
            {{ \App\Support\Version::label() }}@if($v['deployed_at']) · {{ $v['deployed_at'] }}@endif
        </span>
    </div>
@endif
