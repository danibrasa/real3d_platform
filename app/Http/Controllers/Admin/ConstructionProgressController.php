<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConstructionPhase;
use App\Models\ConstructionUpdate;
use App\Models\ConstructionUpdateImage;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ConstructionProgressController extends Controller
{
    public function index(Project $project)
    {
        $user = auth()->user();
        if (! $user->canAccessProject($project)) {
            abort(403);
        }

        $project->load([
            'constructionPhases' => fn ($q) => $q->orderBy('sort_order'),
            'constructionUpdates' => fn ($q) => $q->with('phase', 'images')->orderByDesc('date'),
        ]);

        return view('admin.projects.construction-progress', compact('project'));
    }

    // --- Phases ---

    public function storePhase(Request $request, Project $project)
    {
        Gate::authorize('edit-project-technical');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_percentage' => 'required|integer|min:0|max:100',
        ]);

        $validated['project_id'] = $project->id;
        $validated['sort_order'] = $project->constructionPhases()->count();

        ConstructionPhase::create($validated);

        return redirect()->route('admin.projects.construction.index', $project)
            ->with('success', 'Fase creada.');
    }

    public function updatePhase(Request $request, Project $project, ConstructionPhase $phase)
    {
        Gate::authorize('edit-project-technical');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_percentage' => 'required|integer|min:0|max:100',
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        $phase->update($validated);

        return redirect()->route('admin.projects.construction.index', $project)
            ->with('success', 'Fase actualizada.');
    }

    public function destroyPhase(Project $project, ConstructionPhase $phase)
    {
        Gate::authorize('edit-project-technical');
        $phase->delete();

        return redirect()->route('admin.projects.construction.index', $project)
            ->with('success', 'Fase eliminada.');
    }

    // --- Updates ---

    public function storeUpdate(Request $request, Project $project)
    {
        Gate::authorize('edit-project-technical');

        $validated = $request->validate([
            'construction_phase_id' => 'required|exists:construction_phases,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'progress_percentage' => 'required|integer|min:0|max:100',
            'images.*' => 'image|max:5120',
        ]);

        $validated['project_id'] = $project->id;
        $validated['created_by'] = auth()->id();

        $update = ConstructionUpdate::create($validated);

        // Store images
        if ($request->hasFile('images')) {
            $dir = "projects/{$project->id}/construction";
            Storage::makeDirectory($dir);

            foreach ($request->file('images') as $i => $image) {
                $path = $image->store($dir);
                ConstructionUpdateImage::create([
                    'construction_update_id' => $update->id,
                    'image_path' => $path,
                    'sort_order' => $i,
                ]);
            }
        }

        return redirect()->route('admin.projects.construction.index', $project)
            ->with('success', 'Actualizacion publicada.');
    }

    public function destroyUpdate(Project $project, ConstructionUpdate $update)
    {
        Gate::authorize('edit-project-technical');

        // Delete images from storage
        foreach ($update->images as $img) {
            Storage::delete($img->image_path);
        }
        $update->delete();

        return redirect()->route('admin.projects.construction.index', $project)
            ->with('success', 'Actualizacion eliminada.');
    }

    // Serve construction images
    public function serveImage(Project $project, ConstructionUpdateImage $image)
    {
        $path = Storage::path($image->image_path);
        if (! file_exists($path)) {
            abort(404);
        }

        return response()->file($path);
    }
}
