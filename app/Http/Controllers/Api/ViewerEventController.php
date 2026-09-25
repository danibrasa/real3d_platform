<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ViewerEvent;
use Illuminate\Http\Request;

class ViewerEventController extends Controller
{
    private const ALLOWED_EVENTS = [
        'session_start',
        'session_end',
        'model_loaded',
        'unit_selected',
        'unit_focused',
        'comparison_opened',
        'pdf_downloaded',
        'inquiry_sent',
        'whatsapp_clicked',
        'share_clicked',
        'calculator_used',
        'payment_plan_viewed',
        'gallery_viewed',
        'viewer_3d_opened',
    ];

    /**
     * Rastreadores y clientes automaticos cuyas visitas no son analitica.
     * El rastreador de Meta genero 2,9 millones de registros antes de este filtro.
     */
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|meta-external|'
        . 'bytespider|headless|scrapy|python-requests|curl\/|wget|go-http-client|'
        . 'java\/|okhttp|axios|libwww|lighthouse|pagespeed|preview|monitoring|uptime/i';

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'session_id' => 'required|string|max:64',
            'events' => 'required|array|max:50',
            'events.*.type' => 'required|string|max:50',
            'events.*.data' => 'nullable|array',
            'events.*.unit_id' => 'nullable|integer',
            'events.*.timestamp' => 'nullable|numeric',
        ]);

        $ip = $request->ip();
        $ua = substr($request->userAgent() ?? '', 0, 500);

        // Las visitas de rastreadores no se registran: se responde ok para no
        // delatar el filtro ni provocar reintentos.
        if ($ua === '' || preg_match(self::BOT_PATTERN, $ua)) {
            return response()->json(['ok' => true]);
        }

        $deviceType = $this->detectDevice($ua);
        $referrer = substr($request->header('referer', ''), 0, 500) ?: null;

        $rows = [];
        foreach ($validated['events'] as $event) {
            if (!in_array($event['type'], self::ALLOWED_EVENTS)) {
                continue;
            }

            $rows[] = [
                'project_id' => $validated['project_id'],
                'session_id' => $validated['session_id'],
                'event_type' => $event['type'],
                'event_data' => isset($event['data']) ? json_encode($event['data']) : null,
                'unit_id' => $event['unit_id'] ?? null,
                'ip' => $ip,
                'user_agent' => $ua,
                'device_type' => $deviceType,
                'referrer' => $referrer,
                'created_at' => isset($event['timestamp'])
                    ? date('Y-m-d H:i:s', (int) ($event['timestamp'] / 1000))
                    : now(),
            ];
        }

        if (!empty($rows)) {
            ViewerEvent::insert($rows);
        }

        return response()->json(['ok' => true]);
    }

    private function detectDevice(string $ua): string
    {
        $ua = strtolower($ua);
        if (preg_match('/tablet|ipad|playbook|silk/', $ua)) return 'tablet';
        if (preg_match('/mobile|android|iphone|ipod|opera mini|webos/', $ua)) return 'mobile';
        return 'desktop';
    }
}
