<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ViewerEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $projects = $user->accessibleProjects()->orderBy('name')->get(['id', 'name', 'slug']);
        $projectId = $request->get('project_id', $projects->first()?->id);

        if (!$projectId) {
            return view('admin.analytics', [
                'projects' => $projects,
                'projectId' => null,
                'stats' => null,
            ]);
        }

        // Date range
        $days = (int) $request->get('days', 30);
        $from = now()->subDays($days)->startOfDay();
        $to = now()->endOfDay();

        $baseQuery = ViewerEvent::where('project_id', $projectId)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to);

        // Key metrics
        $totalSessions = (clone $baseQuery)->where('event_type', 'session_start')->count();
        $uniqueIps = (clone $baseQuery)->where('event_type', 'session_start')->distinct('ip')->count('ip');

        // Average session duration (from session_end events data)
        $avgDuration = (clone $baseQuery)->where('event_type', 'session_end')
            ->whereNotNull('event_data')
            ->avg(DB::raw("CAST(JSON_EXTRACT(event_data, '$.duration') AS UNSIGNED)")) ?? 0;

        // Device breakdown
        $devices = (clone $baseQuery)->where('event_type', 'session_start')
            ->select('device_type', DB::raw('COUNT(*) as total'))
            ->groupBy('device_type')
            ->pluck('total', 'device_type')
            ->toArray();

        // Top events
        $eventCounts = (clone $baseQuery)
            ->select('event_type', DB::raw('COUNT(*) as total'))
            ->groupBy('event_type')
            ->orderByDesc('total')
            ->pluck('total', 'event_type')
            ->toArray();

        // Most viewed units
        $topUnits = (clone $baseQuery)->where('event_type', 'unit_selected')
            ->whereNotNull('unit_id')
            ->select('unit_id', DB::raw('COUNT(*) as views'))
            ->groupBy('unit_id')
            ->orderByDesc('views')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $unit = \App\Models\Unit::find($row->unit_id);
                return [
                    'id' => $row->unit_id,
                    'identifier' => $unit?->identifier ?? 'N/A',
                    'views' => $row->views,
                ];
            });

        // Sessions per day (for chart)
        $sessionsPerDay = (clone $baseQuery)->where('event_type', 'session_start')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Conversion rate: sessions → inquiries
        $inquiryCount = (clone $baseQuery)->where('event_type', 'inquiry_sent')->count();
        $conversionRate = $totalSessions > 0 ? round($inquiryCount / $totalSessions * 100, 1) : 0;

        // WhatsApp clicks
        $whatsappClicks = (clone $baseQuery)->where('event_type', 'whatsapp_clicked')->count();

        // PDF downloads
        $pdfDownloads = (clone $baseQuery)->where('event_type', 'pdf_downloaded')->count();

        $stats = [
            'total_sessions' => $totalSessions,
            'unique_visitors' => $uniqueIps,
            'avg_duration' => round($avgDuration),
            'devices' => $devices,
            'event_counts' => $eventCounts,
            'top_units' => $topUnits,
            'sessions_per_day' => $sessionsPerDay,
            'inquiry_count' => $inquiryCount,
            'conversion_rate' => $conversionRate,
            'whatsapp_clicks' => $whatsappClicks,
            'pdf_downloads' => $pdfDownloads,
            'days' => $days,
        ];

        return view('admin.analytics', compact('projects', 'projectId', 'stats'));
    }

    public function data(Request $request)
    {
        // JSON endpoint for AJAX chart updates
        $projectId = $request->get('project_id');
        $days = (int) $request->get('days', 30);
        $from = now()->subDays($days)->startOfDay();

        $sessionsPerDay = ViewerEvent::where('project_id', $projectId)
            ->where('event_type', 'session_start')
            ->where('created_at', '>=', $from)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        return response()->json(['sessions_per_day' => $sessionsPerDay]);
    }
}
