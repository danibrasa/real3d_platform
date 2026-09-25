<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Analisis Estrategico - Real3D.io</title>
    <style>
        /* Reset */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            line-height: 1.6;
            color: #1f2937;
            padding: 0;
        }

        /* Cover page */
        .cover {
            text-align: center;
            padding-top: 200px;
            page-break-after: always;
        }
        .cover-logo {
            font-size: 28pt;
            font-weight: bold;
            color: #0891b2;
            margin-bottom: 8px;
        }
        .cover-subtitle {
            font-size: 10pt;
            color: #6b7280;
            margin-bottom: 60px;
        }
        .cover-title {
            font-size: 24pt;
            font-weight: bold;
            color: #111827;
            margin-bottom: 16px;
        }
        .cover-desc {
            font-size: 12pt;
            color: #4b5563;
            margin-bottom: 40px;
        }
        .cover-meta {
            font-size: 9pt;
            color: #9ca3af;
        }
        .cover-line {
            width: 80px;
            height: 3px;
            background: #0891b2;
            margin: 30px auto;
        }

        /* Content */
        .content {
            padding: 0 10px;
        }

        h1 {
            font-size: 20pt;
            font-weight: bold;
            color: #111827;
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e5e7eb;
            display: none; /* Hidden since we have cover page */
        }

        h2 {
            font-size: 15pt;
            font-weight: bold;
            color: #0891b2;
            margin-top: 30px;
            margin-bottom: 14px;
            padding-bottom: 6px;
            border-bottom: 1px solid #cffafe;
            page-break-after: avoid;
        }

        h3 {
            font-size: 12pt;
            font-weight: bold;
            color: #1f2937;
            margin-top: 20px;
            margin-bottom: 8px;
            page-break-after: avoid;
        }

        h4 {
            font-size: 11pt;
            font-weight: bold;
            color: #374151;
            margin-top: 14px;
            margin-bottom: 6px;
            page-break-after: avoid;
        }

        p {
            margin-bottom: 8px;
            color: #374151;
            text-align: justify;
        }

        strong {
            color: #111827;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 16px;
            font-size: 8.5pt;
            page-break-inside: avoid;
        }

        thead tr {
            background-color: #f3f4f6;
        }

        th {
            font-weight: bold;
            color: #374151;
            padding: 6px 8px;
            text-align: left;
            border: 1px solid #d1d5db;
            font-size: 8pt;
        }

        td {
            padding: 5px 8px;
            border: 1px solid #e5e7eb;
            color: #4b5563;
            vertical-align: top;
        }

        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        /* Lists */
        ul, ol {
            margin: 8px 0 12px 20px;
            color: #374151;
        }

        li {
            margin-bottom: 4px;
        }

        /* Blockquote */
        blockquote {
            border-left: 3px solid #0891b2;
            background-color: #ecfeff;
            padding: 10px 14px;
            margin: 12px 0;
            border-radius: 0 4px 4px 0;
        }

        blockquote p {
            color: #374151;
            margin: 0;
            font-style: normal;
        }

        /* Horizontal rule */
        hr {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 20px 0;
        }

        /* Footer */
        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7pt;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
        }

        /* Page break helpers */
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    {{-- Cover Page --}}
    <div class="cover">
        <div class="cover-logo">Real3D.io</div>
        <div class="cover-subtitle">Plataforma de Visualizacion Inmobiliaria 3D</div>
        <div class="cover-line"></div>
        <div class="cover-title">Analisis Estrategico</div>
        <div class="cover-desc">Marketing, Tecnologia y Roadmap de Producto</div>
        <div class="cover-line"></div>
        <div class="cover-meta">
            Fecha: {{ now()->format('d/m/Y') }}<br>
            Version 1.0 | Documento Confidencial
        </div>
    </div>

    {{-- Content --}}
    <div class="content">
        {!! $html !!}
    </div>

    <div class="page-footer">
        Real3D.io - Analisis Estrategico | Confidencial | {{ now()->format('d/m/Y') }}
    </div>
</body>
</html>
