<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function count(): JsonResponse
    {
        $user = auth()->user();
        $query = Inquiry::where('read', false);

        // Scope by accessible projects for non-superadmin
        if (! $user->isSuperadmin()) {
            $projectIds = $user->accessibleProjects()->pluck('id');
            $query->whereIn('project_id', $projectIds);
        }

        $unread = $query->count();

        $latest = (clone $query)
            ->with('project:id,name')
            ->latest()
            ->first();

        return response()->json([
            'unread_inquiries' => $unread,
            'latest_inquiry' => $latest ? [
                'id' => $latest->id,
                'name' => $latest->name,
                'project_name' => $latest->project?->name,
                'time_ago' => $latest->created_at->diffForHumans(),
            ] : null,
        ]);
    }

    public function recent(): JsonResponse
    {
        $user = auth()->user();
        $query = Inquiry::where('read', false);

        if (! $user->isSuperadmin()) {
            $projectIds = $user->accessibleProjects()->pluck('id');
            $query->whereIn('project_id', $projectIds);
        }

        $inquiries = $query->with('project:id,name')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'name' => $i->name,
                'email' => $i->email,
                'project_name' => $i->project?->name,
                'time_ago' => $i->created_at->diffForHumans(),
            ]);

        return response()->json(['inquiries' => $inquiries]);
    }
}
