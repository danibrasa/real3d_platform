<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('view-audit-logs');

        $query = AuditLog::with('user')->latest();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('type')) {
            $query->where('auditable_type', $request->type);
        }

        $logs = $query->paginate(50)->withQueryString();

        $actions = AuditLog::distinct()->pluck('action')->sort()->values();
        $types = AuditLog::whereNotNull('auditable_type')->distinct()->pluck('auditable_type')->sort()->values();

        return view('admin.audit-logs.index', compact('logs', 'actions', 'types'));
    }
}
