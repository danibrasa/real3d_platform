<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointOfInterest;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LocationController extends Controller
{
    public function index(Project $project)
    {
        $user = auth()->user();
        if (! $user->canAccessProject($project)) {
            abort(403);
        }

        $project->load(['pointsOfInterest']);

        return view('admin.projects.location', compact('project'));
    }

    public function updateCoords(Request $request, Project $project)
    {
        Gate::authorize('edit-project-commercial');

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $project->update($validated);

        return redirect()->route('admin.projects.location.index', $project)
            ->with('success', 'Ubicacion actualizada.');
    }

    public function storePoi(Request $request, Project $project)
    {
        Gate::authorize('edit-project-commercial');

        $validated = $request->validate([
            'category' => 'required|string|in:'.implode(',', array_keys(PointOfInterest::CATEGORIES)),
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'distance' => 'required|string|max:100',
        ]);

        $validated['project_id'] = $project->id;
        $validated['sort_order'] = $project->pointsOfInterest()->count();

        PointOfInterest::create($validated);

        return redirect()->route('admin.projects.location.index', $project)
            ->with('success', 'Punto de interes agregado.');
    }

    public function updatePoi(Request $request, Project $project, PointOfInterest $poi)
    {
        Gate::authorize('edit-project-commercial');

        $validated = $request->validate([
            'category' => 'required|string|in:'.implode(',', array_keys(PointOfInterest::CATEGORIES)),
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'distance' => 'required|string|max:100',
        ]);

        $poi->update($validated);

        return redirect()->route('admin.projects.location.index', $project)
            ->with('success', 'Punto de interes actualizado.');
    }

    public function destroyPoi(Project $project, PointOfInterest $poi)
    {
        Gate::authorize('edit-project-commercial');

        $poi->delete();

        return redirect()->route('admin.projects.location.index', $project)
            ->with('success', 'Punto de interes eliminado.');
    }
}
