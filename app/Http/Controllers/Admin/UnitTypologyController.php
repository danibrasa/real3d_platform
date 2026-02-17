<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\UnitTypology;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class UnitTypologyController extends Controller
{
    public function index(Project $project)
    {
        if (!auth()->user()->canAccessProject($project)) {
            abort(403);
        }

        $typologies = $project->typologies()->withCount('units')->get();

        return view('admin.typologies.index', compact('project', 'typologies'));
    }

    public function create(Project $project)
    {
        Gate::authorize('manage-typologies');

        return view('admin.typologies.create', compact('project'));
    }

    public function store(Request $request, Project $project)
    {
        Gate::authorize('manage-typologies');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bedrooms' => 'required|integer|min:0|max:10',
            'bathrooms' => 'required|integer|min:0|max:10',
            'area_m2' => 'required|numeric|min:1|max:9999',
            'description' => 'nullable|string',
            'floor_plan' => 'nullable|image|max:5120',
        ]);

        unset($validated['floor_plan']);

        if ($request->hasFile('floor_plan')) {
            $path = $request->file('floor_plan')->store("projects/{$project->id}/typologies");
            $validated['floor_plan_path'] = $path;
        }

        $validated['project_id'] = $project->id;
        UnitTypology::create($validated);

        return redirect()->route('admin.projects.typologies.index', $project)
            ->with('success', 'Tipologia creada.');
    }

    public function edit(Project $project, UnitTypology $typology)
    {
        Gate::authorize('manage-typologies');

        return view('admin.typologies.edit', compact('project', 'typology'));
    }

    public function update(Request $request, Project $project, UnitTypology $typology)
    {
        Gate::authorize('manage-typologies');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bedrooms' => 'required|integer|min:0|max:10',
            'bathrooms' => 'required|integer|min:0|max:10',
            'area_m2' => 'required|numeric|min:1|max:9999',
            'description' => 'nullable|string',
            'floor_plan' => 'nullable|image|max:5120',
        ]);

        unset($validated['floor_plan']);

        if ($request->hasFile('floor_plan')) {
            if ($typology->floor_plan_path) {
                Storage::delete($typology->floor_plan_path);
            }
            $path = $request->file('floor_plan')->store("projects/{$project->id}/typologies");
            $validated['floor_plan_path'] = $path;
        }

        $typology->update($validated);

        return redirect()->route('admin.projects.typologies.index', $project)
            ->with('success', 'Tipologia actualizada.');
    }

    public function destroy(Project $project, UnitTypology $typology)
    {
        Gate::authorize('manage-typologies');

        if ($typology->floor_plan_path) {
            Storage::delete($typology->floor_plan_path);
        }

        $typology->delete();

        return redirect()->route('admin.projects.typologies.index', $project)
            ->with('success', 'Tipologia eliminada.');
    }
}
