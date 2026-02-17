<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InquiryController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('view-inquiries');

        $user = auth()->user();
        $projectIds = $user->accessibleProjects()->pluck('id');

        $query = Inquiry::with('project', 'unit')
            ->whereIn('project_id', $projectIds)
            ->latest();

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('read')) {
            $query->where('read', $request->read === '1');
        }

        $inquiries = $query->paginate(20);

        return view('admin.inquiries.index', compact('inquiries'));
    }

    public function show(Inquiry $inquiry)
    {
        Gate::authorize('view-inquiries');
        $this->authorizeInquiryAccess($inquiry);

        if (!$inquiry->read) {
            $inquiry->update(['read' => true]);
        }

        $inquiry->load('project', 'unit');

        return view('admin.inquiries.show', compact('inquiry'));
    }

    public function markRead(Inquiry $inquiry)
    {
        Gate::authorize('view-inquiries');
        $this->authorizeInquiryAccess($inquiry);

        $inquiry->update(['read' => true]);

        return back()->with('success', 'Consulta marcada como leida.');
    }

    public function destroy(Inquiry $inquiry)
    {
        Gate::authorize('delete-inquiry');

        $inquiry->delete();

        return redirect()->route('admin.inquiries.index')
            ->with('success', 'Consulta eliminada.');
    }

    private function authorizeInquiryAccess(Inquiry $inquiry): void
    {
        $user = auth()->user();
        if (!$user->canAccessProject($inquiry->project)) {
            abort(403);
        }
    }
}
