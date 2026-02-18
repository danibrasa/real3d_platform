<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="project-id" content="{{ $project->id }}">
    <title>{{ $project->name }} - Visor 3D | RealEstate 3D</title>
    <meta name="description" content="Visor 3D interactivo de {{ $project->name }}{{ $project->location ? ' en ' . $project->location : '' }}. Explora el modelo 3D, selecciona unidades y consulta disponibilidad en tiempo real.">
    <link rel="canonical" href="{{ route('viewer.show', $project->slug) }}">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    <!-- OG Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $project->name }} - Visor 3D">
    <meta property="og:description" content="{{ $project->tagline ?? Str::limit($project->description, 160) ?? 'Visor 3D interactivo' }}">
    <meta property="og:url" content="{{ route('viewer.show', $project->slug) }}">
    @if($project->thumbnail_path)
    <meta property="og:image" content="{{ url('/storage/' . $project->thumbnail_path) }}">
    @elseif($project->galleryImages->first())
    <meta property="og:image" content="{{ url('/api/projects/' . $project->id . '/gallery/' . $project->galleryImages->first()->id) }}">
    @endif
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $project->name }} - Visor 3D">
    <meta name="twitter:description" content="{{ $project->tagline ?? Str::limit($project->description, 160) ?? 'Visor 3D interactivo' }}">
    @if($project->thumbnail_path)
    <meta name="twitter:image" content="{{ url('/storage/' . $project->thumbnail_path) }}">
    @endif

    <!-- QW3: Google Analytics -->
    @php
        $gaId = $project->analytics_id ?? config('services.google_analytics.id');
    @endphp
    @if($gaId)
        @if(str_starts_with($gaId, 'GTM-'))
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $gaId }}');</script>
        @else
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $gaId }}');
        </script>
        @endif
    @endif

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0a0a1e; color: #fff; }
        #canvas-container { position: relative; width: 100%; height: 65vh; background: #000; }
        #canvas-container canvas { display: block; }
        #viewer-header {
            position: absolute; top: 20px; left: 50%; transform: translateX(-50%); z-index: 10;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(10px); padding: 12px 30px;
            border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); text-align: center;
        }
        #viewer-header h1 { font-size: 18px; font-weight: 600; letter-spacing: 1px; }
        #viewer-header p { font-size: 12px; color: #aaa; margin-top: 4px; }
        #viewer-instructions {
            position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 10;
            background: rgba(0,0,0,0.5); backdrop-filter: blur(10px); padding: 10px 24px;
            border-radius: 8px; font-size: 12px; color: #888; border: 1px solid rgba(255,255,255,0.05);
            white-space: nowrap;
        }
        #viewer-back {
            position: absolute; top: 20px; left: 20px; z-index: 10;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(10px); padding: 10px 20px;
            border-radius: 8px; border: 1px solid rgba(255,255,255,0.1);
            color: #4fc3f7; text-decoration: none; font-size: 14px;
        }
        #viewer-back:hover { background: rgba(79,195,247,0.2); }
        #loading-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.9); display: flex; flex-direction: column;
            align-items: center; justify-content: center; z-index: 100;
        }
        .spinner { width: 40px; height: 40px; border: 3px solid rgba(79,195,247,0.2); border-top-color: #4fc3f7; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        #loading-text { color: #aaa; margin-top: 16px; font-size: 14px; }

        /* Units section */
        #units-section {
            background: #0a0a1e; min-height: 40vh; padding: 24px 20px 60px;
        }
        #units-section-header {
            max-width: 1200px; margin: 0 auto 16px; display: flex; align-items: center;
            justify-content: space-between; flex-wrap: wrap; gap: 12px;
        }
        #units-section-header h2 { font-size: 20px; font-weight: 700; }
        #units-summary { font-size: 13px; color: #888; }
        #units-filters {
            max-width: 1200px; margin: 0 auto 20px; display: flex; flex-wrap: wrap; gap: 8px;
        }
        #units-filters select {
            background: rgba(255,255,255,0.08); color: #fff; border: 1px solid rgba(255,255,255,0.15);
            border-radius: 6px; padding: 8px 12px; font-size: 13px; cursor: pointer;
        }
        #units-filters select option { background: #1a1a2e; color: #fff; }
        #units-filters select:focus { border-color: #4fc3f7; outline: none; }

        #units-grid {
            max-width: 1200px; margin: 0 auto;
            display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px;
        }
        .unit-card {
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px; padding: 16px; cursor: pointer; transition: all 0.25s;
        }
        .unit-card:hover { background: rgba(79,195,247,0.08); border-color: rgba(79,195,247,0.3); transform: translateY(-2px); }
        .unit-card.selected { border-color: #4fc3f7; box-shadow: 0 0 12px rgba(79,195,247,0.35), inset 0 0 8px rgba(79,195,247,0.08); background: rgba(79,195,247,0.1); }
        .unit-card .unit-id { font-weight: 600; font-size: 15px; }
        .unit-card .unit-price { color: #4fc3f7; font-weight: 600; font-size: 16px; margin-top: 4px; }
        .unit-card .unit-meta { font-size: 12px; color: #888; margin-top: 6px; }
        .unit-status { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; text-transform: uppercase; }
        .unit-status.available { background: rgba(34,197,94,0.2); color: #4ade80; }
        .unit-status.reserved { background: rgba(234,179,8,0.2); color: #facc15; }
        .unit-status.sold { background: rgba(239,68,68,0.2); color: #f87171; }

        /* Unit detail expandible */
        #unit-detail-panel {
            max-width: 1200px; margin: 0 auto; display: none;
            background: rgba(255,255,255,0.04); border: 1px solid rgba(79,195,247,0.25);
            border-radius: 12px; padding: 24px; margin-bottom: 20px;
        }
        #unit-detail-panel.visible { display: block; }
        .unit-detail-back { color: #4fc3f7; cursor: pointer; font-size: 13px; margin-bottom: 12px; display: inline-block; }
        .unit-detail-back:hover { text-decoration: underline; }
        .detail-title { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .detail-price { font-size: 24px; font-weight: 700; color: #4fc3f7; margin: 12px 0; }
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 12px; margin: 12px 0; }
        .detail-item label { display: block; font-size: 10px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-item span { font-size: 15px; }
        .plan-img { max-width: 100%; border-radius: 8px; margin-top: 12px; }
        .action-btn {
            display: inline-block; padding: 10px 20px; border-radius: 8px; font-size: 13px;
            font-weight: 600; text-decoration: none; margin-top: 12px; margin-right: 8px; transition: all 0.2s;
        }
        .btn-whatsapp { background: #25d366; color: #fff; }
        .btn-whatsapp:hover { background: #20bd5a; }
        .btn-info { background: rgba(79,195,247,0.2); color: #4fc3f7; border: 1px solid rgba(79,195,247,0.3); }
        .btn-info:hover { background: rgba(79,195,247,0.3); }
        .btn-share { background: rgba(255,255,255,0.1); color: #ccc; border: 1px solid rgba(255,255,255,0.2); cursor: pointer; }
        .btn-share:hover { background: rgba(255,255,255,0.2); }

        #section-landing-link {
            display: block; max-width: 1200px; margin: 20px auto 0; padding: 12px 20px;
            text-align: center; font-size: 13px; color: #4fc3f7; text-decoration: none;
            border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
        }
        #section-landing-link:hover { background: rgba(79,195,247,0.1); }

        /* Share toast */
        #share-toast {
            position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%);
            background: rgba(0,0,0,0.8); color: #4fc3f7; padding: 8px 20px;
            border-radius: 8px; font-size: 13px; z-index: 100; display: none;
            border: 1px solid rgba(79,195,247,0.3);
        }
        #share-toast.show { display: block; }

        /* Share bar */
        #share-bar {
            position: fixed; left: 0; top: 50%; transform: translateY(-50%); z-index: 40;
            display: flex; flex-direction: column; gap: 2px;
            transition: opacity 0.3s, transform 0.3s;
            opacity: 0; transform: translateX(-100%) translateY(-50%);
        }
        #share-bar.visible { opacity: 1; transform: translateX(0) translateY(-50%); }
        .share-btn {
            display: flex; align-items: center; justify-content: center;
            width: 40px; height: 40px; border: none; cursor: pointer;
            border-radius: 0 8px 8px 0; transition: width 0.2s;
        }
        .share-btn:hover { width: 48px; }
        .share-btn svg { width: 20px; height: 20px; fill: white; }
        .share-btn.wa { background: #25d366; }
        .share-btn.fb { background: #1877f2; }
        .share-btn.tw { background: #000; }
        .share-btn.li { background: #0a66c2; }
        .share-btn.cp { background: rgba(255,255,255,0.15); }
        #share-bar-mobile { display: none; }

        @media (max-width: 640px) {
            #share-bar { display: none !important; }
            #share-bar-mobile { display: flex; }
            #share-bar-mobile {
                position: fixed; bottom: 0; left: 0; right: 0; z-index: 40;
                background: rgba(10,10,30,0.95); border-top: 1px solid rgba(255,255,255,0.1);
                display: flex; align-items: center; justify-content: space-around; padding: 8px;
                transition: opacity 0.3s, transform 0.3s;
                opacity: 0; transform: translateY(100%);
            }
            #share-bar-mobile.visible { opacity: 1; transform: translateY(0); }
            .share-btn-m { display: flex; flex-direction: column; align-items: center; gap: 2px; background: none; border: none; color: #aaa; cursor: pointer; padding: 4px 8px; }
            .share-btn-m svg { width: 24px; height: 24px; }
            .share-btn-m span { font-size: 9px; }
            #canvas-container { height: 50vh; }
            #units-grid { grid-template-columns: 1fr; }
            #viewer-header { padding: 8px 16px; }
            #viewer-header h1 { font-size: 15px; }
            #viewer-instructions { font-size: 10px; padding: 6px 12px; }
            #units-section { padding: 16px 12px 60px; }
            .detail-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
    <div id="canvas-container">
        <div id="loading-overlay">
            <div class="spinner"></div>
            <p id="loading-text">Cargando proyecto...</p>
        </div>

        <a href="{{ route('viewer.landing', $project->slug) }}" id="viewer-back">&larr; Volver</a>

        <div id="viewer-header">
            <h1>{{ $project->name }}</h1>
            <p>{{ $project->location }}</p>
        </div>

        <div id="viewer-instructions">
            Click izq + arrastrar = rotar | Scroll = zoom | Click der + arrastrar = mover
        </div>

        <div id="quality-note" style="display:none; position: absolute; top: 60px; right: 20px; z-index: 20; background: rgba(0,0,0,0.7); color: #fbbf24; padding: 6px 12px; border-radius: 6px; font-size: 11px;">
            Calidad reducida para mejor rendimiento
        </div>
    </div>

    <div id="units-section">
        <div id="units-section-header">
            <div>
                <h2>Unidades</h2>
                <div id="units-summary"></div>
            </div>
        </div>
        <div id="units-filters">
            <select id="filter-status" onchange="filterUnits()">
                <option value="">Estado</option>
                <option value="available">Disponible</option>
                <option value="reserved">Reservado</option>
                <option value="sold">Vendido</option>
            </select>
            <select id="filter-floor" onchange="filterUnits()"></select>
            <select id="filter-bedrooms" onchange="filterUnits()">
                <option value="">Dorm.</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3+</option>
            </select>
        </div>
        <div id="unit-detail-panel"></div>
        <div id="units-grid"></div>
        <a href="{{ route('viewer.landing', $project->slug) }}" id="section-landing-link">Ver detalles del proyecto</a>
    </div>

    {{-- Share bar - desktop --}}
    <div id="share-bar">
        <a class="share-btn wa" href="https://wa.me/?text={{ urlencode($project->name . ' - Visor 3D' . "\n" . route('viewer.show', $project->slug)) }}" target="_blank" rel="noopener" title="WhatsApp">
            <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        </a>
        <a class="share-btn fb" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('viewer.show', $project->slug)) }}" target="_blank" rel="noopener" title="Facebook">
            <svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
        </a>
        <a class="share-btn tw" href="https://twitter.com/intent/tweet?text={{ urlencode($project->name . ' - Visor 3D') }}&url={{ urlencode(route('viewer.show', $project->slug)) }}" target="_blank" rel="noopener" title="X">
            <svg viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
        </a>
        <button class="share-btn cp" onclick="copyShareLink()" title="Copiar enlace">
            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
        </button>
    </div>

    {{-- Share bar - mobile --}}
    <div id="share-bar-mobile">
        <a class="share-btn-m" href="https://wa.me/?text={{ urlencode($project->name . "\n" . route('viewer.show', $project->slug)) }}" target="_blank" rel="noopener">
            <svg fill="#25d366" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            <span>WhatsApp</span>
        </a>
        <a class="share-btn-m" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('viewer.show', $project->slug)) }}" target="_blank" rel="noopener">
            <svg fill="#1877f2" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            <span>Facebook</span>
        </a>
        <a class="share-btn-m" href="https://twitter.com/intent/tweet?text={{ urlencode($project->name) }}&url={{ urlencode(route('viewer.show', $project->slug)) }}" target="_blank" rel="noopener">
            <svg fill="#fff" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            <span>X</span>
        </a>
        <button class="share-btn-m" onclick="copyShareLink()">
            <svg fill="none" stroke="#aaa" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
            <span>Copiar</span>
        </button>
    </div>

    <!-- Share toast -->
    <div id="share-toast">Link copiado al portapapeles</div>

    <script>
        // Share bar scroll visibility
        let shareBarVisible = false;
        window.addEventListener('scroll', function() {
            const show = window.scrollY > 100;
            if (show !== shareBarVisible) {
                shareBarVisible = show;
                const bar = document.getElementById('share-bar');
                const barM = document.getElementById('share-bar-mobile');
                if (bar) bar.classList.toggle('visible', show);
                if (barM) barM.classList.toggle('visible', show);
            }
        }, { passive: true });

        function copyShareLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                const toast = document.getElementById('share-toast');
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 2500);
            });
            if (typeof gtag === 'function') {
                gtag('event', 'share', { method: 'copy', content_type: 'viewer', item_id: '{{ $project->slug }}' });
            }
        }
    </script>

    <script type="importmap">
    {
        "imports": {
            "three": "https://cdn.jsdelivr.net/npm/three@0.162.0/build/three.module.js",
            "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.162.0/examples/jsm/"
        }
    }
    </script>

    <script type="application/json" id="project-data">
        {!! json_encode([
            'slug' => $project->slug,
            'name' => $project->name,
            'whatsapp_number' => $project->whatsapp_number,
            'settings' => $project->settings,
            'files' => [
                'video_360' => $project->getFileByType('video_360') ? '/api/projects/' . $project->id . '/files/video_360' : null,
                'image_360' => $project->getFileByType('image_360') ? '/api/projects/' . $project->id . '/files/image_360' : null,
                'model_3d' => $project->getFileByType('model_3d') ? '/api/projects/' . $project->id . '/files/model_3d?f=' . urlencode($project->getFileByType('model_3d')->original_name) : null,
                'ground_texture' => $project->getFileByType('ground_texture') ? '/api/projects/' . $project->id . '/files/ground_texture' : null,
            ],
        ]) !!}
    </script>

    <script type="module" src="/js/viewer-public.js"></script>
    <script src="/js/viewer-units.js"></script>
    <script src="/js/viewer-analytics.js" defer></script>
</body>
</html>
