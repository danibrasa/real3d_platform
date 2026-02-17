<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Unit;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    private function authorizeProjectAccess(Project $project): void
    {
        if (!auth()->user()->canAccessProject($project)) {
            abort(403);
        }
    }

    public function index(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $query = $project->units()->with('typology');

        if ($request->filled('floor')) {
            $query->where('floor', $request->floor);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $units = $query->orderBy('floor')->orderBy('sort_order')->orderBy('identifier')->get();
        $floors = $project->units()->distinct()->pluck('floor')->sort()->values();
        $typologies = $project->typologies;

        return view('admin.units.index', compact('project', 'units', 'floors', 'typologies'));
    }

    public function create(Project $project)
    {
        Gate::authorize('create-unit');
        $this->authorizeProjectAccess($project);

        $typologies = $project->typologies;

        return view('admin.units.create', compact('project', 'typologies'));
    }

    public function store(Request $request, Project $project)
    {
        Gate::authorize('create-unit');
        $this->authorizeProjectAccess($project);

        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:50', Rule::unique('units')->where('project_id', $project->id)],
            'typology_id' => 'nullable|exists:unit_typologies,id',
            'floor' => 'required|integer|min:0|max:200',
            'bedrooms' => 'required|integer|min:0|max:10',
            'bathrooms' => 'required|integer|min:0|max:10',
            'area_m2' => 'required|numeric|min:1|max:9999',
            'price' => 'required|numeric|min:0|max:99999999.99',
            'status' => 'required|in:available,reserved,sold',
            'notes' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'floor_plan' => 'nullable|image|max:5120',
            'bbox_center_x' => 'nullable|numeric|min:0|max:1',
            'bbox_center_y' => 'nullable|numeric|min:0|max:1',
            'bbox_center_z' => 'nullable|numeric|min:0|max:1',
            'bbox_size_x' => 'nullable|numeric|min:0|max:1',
            'bbox_size_y' => 'nullable|numeric|min:0|max:1',
            'bbox_size_z' => 'nullable|numeric|min:0|max:1',
        ]);

        unset($validated['floor_plan']);
        $validated['project_id'] = $project->id;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        if ($request->hasFile('floor_plan')) {
            $validated['floor_plan_path'] = $request->file('floor_plan')->store("projects/{$project->id}/units");
        }

        Unit::create($validated);

        return redirect()->route('admin.projects.units.index', $project)
            ->with('success', 'Unidad creada.');
    }

    public function edit(Project $project, Unit $unit)
    {
        $this->authorizeProjectAccess($project);

        $typologies = $project->typologies;

        return view('admin.units.edit', compact('project', 'unit', 'typologies'));
    }

    public function update(Request $request, Project $project, Unit $unit)
    {
        $this->authorizeProjectAccess($project);
        $user = $request->user();

        if ($user->hasRole('superadmin', 'gestor')) {
            // Full edit
            $validated = $request->validate([
                'identifier' => ['required', 'string', 'max:50', Rule::unique('units')->where('project_id', $project->id)->ignore($unit->id)],
                'typology_id' => 'nullable|exists:unit_typologies,id',
                'floor' => 'required|integer|min:0|max:200',
                'bedrooms' => 'required|integer|min:0|max:10',
                'bathrooms' => 'required|integer|min:0|max:10',
                'area_m2' => 'required|numeric|min:1|max:9999',
                'price' => 'required|numeric|min:0|max:99999999.99',
                'status' => 'required|in:available,reserved,sold',
                'notes' => 'nullable|string',
                'sort_order' => 'nullable|integer|min:0',
                'floor_plan' => 'nullable|image|max:5120',
                'bbox_center_x' => 'nullable|numeric|min:0|max:1',
                'bbox_center_y' => 'nullable|numeric|min:0|max:1',
                'bbox_center_z' => 'nullable|numeric|min:0|max:1',
                'bbox_size_x' => 'nullable|numeric|min:0|max:1',
                'bbox_size_y' => 'nullable|numeric|min:0|max:1',
                'bbox_size_z' => 'nullable|numeric|min:0|max:1',
            ]);

            unset($validated['floor_plan']);
            $validated['sort_order'] = $validated['sort_order'] ?? 0;

            if ($request->hasFile('floor_plan')) {
                if ($unit->floor_plan_path) {
                    Storage::delete($unit->floor_plan_path);
                }
                $validated['floor_plan_path'] = $request->file('floor_plan')->store("projects/{$project->id}/units");
            }
        } elseif ($user->isInmobiliaria()) {
            // Only price, status, notes
            $validated = $request->validate([
                'price' => 'required|numeric|min:0|max:99999999.99',
                'status' => 'required|in:available,reserved,sold',
                'notes' => 'nullable|string',
            ]);
        } else {
            abort(403);
        }

        $unit->update($validated);

        return redirect()->route('admin.projects.units.index', $project)
            ->with('success', 'Unidad actualizada.');
    }

    public function updateStatus(Request $request, Project $project, Unit $unit)
    {
        $this->authorizeProjectAccess($project);
        $user = $request->user();

        $validated = $request->validate([
            'status' => 'required|in:available,reserved,sold',
        ]);

        // Agente can only set 'reserved'
        if ($user->isAgente() && $validated['status'] !== 'reserved') {
            abort(403, 'Los agentes solo pueden marcar unidades como reservadas.');
        }

        $oldStatus = $unit->status;
        $unit->update($validated);

        if ($oldStatus !== $validated['status']) {
            WebhookService::dispatch('unit_status_changed', [
                'unit_identifier' => $unit->identifier,
                'old_status' => $oldStatus,
                'new_status' => $unit->status,
            ], $project->id);
        }

        return back()->with('success', "Unidad {$unit->identifier} actualizada a {$validated['status']}.");
    }

    public function updateBbox(Request $request, Project $project, Unit $unit)
    {
        Gate::authorize('manage-bbox');

        $validated = $request->validate([
            'bbox_center_x' => 'required|numeric|min:0|max:1',
            'bbox_center_y' => 'required|numeric|min:0|max:1',
            'bbox_center_z' => 'required|numeric|min:0|max:1',
            'bbox_size_x' => 'required|numeric|min:0|max:1',
            'bbox_size_y' => 'required|numeric|min:0|max:1',
            'bbox_size_z' => 'required|numeric|min:0|max:1',
        ]);

        $unit->update($validated);

        return response()->json(['success' => true, 'unit' => $unit->fresh()]);
    }

    public function clearBbox(Project $project, Unit $unit)
    {
        Gate::authorize('manage-bbox');

        $unit->update([
            'bbox_center_x' => null,
            'bbox_center_y' => null,
            'bbox_center_z' => null,
            'bbox_size_x' => null,
            'bbox_size_y' => null,
            'bbox_size_z' => null,
        ]);

        return response()->json(['success' => true]);
    }

    public function mapping(Project $project)
    {
        Gate::authorize('manage-bbox');

        $units = $project->units()
            ->with('typology')
            ->orderBy('floor')
            ->orderBy('sort_order')
            ->orderBy('identifier')
            ->get();

        return view('admin.units.mapping', compact('project', 'units'));
    }

    public function destroy(Project $project, Unit $unit)
    {
        Gate::authorize('create-unit');

        if ($unit->floor_plan_path) {
            Storage::delete($unit->floor_plan_path);
        }

        $unit->delete();

        return redirect()->route('admin.projects.units.index', $project)
            ->with('success', 'Unidad eliminada.');
    }
}
