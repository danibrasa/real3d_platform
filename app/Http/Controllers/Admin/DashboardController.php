<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Unit;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $projectsQuery = $user->accessibleProjects();

        $projectIds = (clone $projectsQuery)->pluck('id');

        $stats = [
            'total_projects' => (clone $projectsQuery)->count(),
            'published' => (clone $projectsQuery)->where('status', 'public')->count(),
            'drafts' => (clone $projectsQuery)->where('status', 'draft')->count(),
            'total_units' => Unit::whereIn('project_id', $projectIds)->count(),
            'available_units' => Unit::whereIn('project_id', $projectIds)->where('status', 'available')->count(),
        ];

        // User stats only for superadmin
        if ($user->isSuperadmin()) {
            $stats['total_users'] = User::where('role', 'user')->count();
        }

        // Inquiry stats for superadmin, inmobiliaria, agente
        if ($user->can('view-inquiries')) {
            $stats['unread_inquiries'] = Inquiry::where('read', false)
                ->whereIn('project_id', $projectIds)->count();
        }

        $recent_projects = (clone $projectsQuery)->with('creator')->latest()->take(5)->get();

        $recent_inquiries = collect();
        if ($user->can('view-inquiries')) {
            $recent_inquiries = Inquiry::with('project', 'unit')
                ->where('read', false)
                ->whereIn('project_id', $projectIds)
                ->latest()->take(5)->get();
        }

        return view('admin.dashboard', compact('stats', 'recent_projects', 'recent_inquiries'));
    }
}
