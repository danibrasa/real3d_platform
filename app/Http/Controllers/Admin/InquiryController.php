<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

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
        if ($request->filled('estado') && in_array($request->estado, Inquiry::ESTADOS, true)) {
            $query->where('estado', $request->estado);
        }

        $inquiries = $query->paginate(20);

        return view('admin.inquiries.index', compact('inquiries'));
    }

    public function show(Inquiry $inquiry)
    {
        Gate::authorize('view-inquiries');
        $this->authorizeInquiryAccess($inquiry);

        if (! $inquiry->read) {
            $inquiry->update(['read' => true]);
        }

        $inquiry->load('project', 'unit');

        return view('admin.inquiries.show', compact('inquiry'));
    }

    /**
     * El estado del lead, con la nota de quien lo atiende y cuando.
     *
     * Marcar leido sigue existiendo, pero es lo de menos: lo que le importa a
     * una promotora a la segunda semana es a quien le falta contestar.
     */
    public function estado(Request $request, Inquiry $inquiry)
    {
        Gate::authorize('view-inquiries');
        $this->authorizeInquiryAccess($inquiry);

        $validated = $request->validate([
            'estado' => ['required', Rule::in(Inquiry::ESTADOS)],
            'nota' => ['nullable', 'string', 'max:2000'],
        ]);

        $inquiry->forceFill([
            'estado' => $validated['estado'],
            'nota' => $validated['nota'] ?? $inquiry->nota,
            'estado_en' => now(),
            'atendido_por' => $request->user()->id,
            'read' => true,
        ])->save();

        return back()->with('success', __('inquiry.estado_guardado', ['estado' => __('inquiry.estado_'.$validated['estado'])]));
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
        if (! $user->canAccessProject($inquiry->project)) {
            abort(403);
        }
    }
}
