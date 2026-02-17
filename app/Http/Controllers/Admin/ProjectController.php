<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = auth()->user()->accessibleProjects()
            ->with('creator', 'files')
            ->latest()
            ->paginate(12);

        return view('admin.projects.index', compact('projects'));
    }

    public function create()
    {
        Gate::authorize('create-project');

        return view('admin.projects.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create-project');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['status'] = 'draft';

        $project = Project::create($validated);

        // Crear settings por defecto
        ProjectSetting::create(['project_id' => $project->id]);

        // Crear directorio de almacenamiento
        Storage::makeDirectory("projects/{$project->id}/video");
        Storage::makeDirectory("projects/{$project->id}/model");
        Storage::makeDirectory("projects/{$project->id}/texture");
        Storage::makeDirectory("projects/{$project->id}/thumbnail");

        return redirect()->route('admin.projects.edit', $project)
            ->with('success', 'Proyecto creado. Ahora podes subir archivos y configurar el visor.');
    }

    public function edit(Project $project)
    {
        $user = auth()->user();

        if (!$user->canAccessProject($project)) {
            abort(403);
        }

        $project->load('settings', 'files', 'galleryImages');
        $project->loadCount(['typologies', 'units', 'galleryImages', 'paymentPlans']);

        return view('admin.projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $user = $request->user();

        if (!$user->canAccessProject($project)) {
            abort(403);
        }

        // Fields depend on role
        if ($user->hasRole('superadmin', 'gestor')) {
            // Full access to all fields
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'description_en' => 'nullable|string',
                'location' => 'nullable|string|max:255',
                'location_en' => 'nullable|string|max:255',
                'status' => 'required|in:draft,public,private,unlisted',
                'tagline' => 'nullable|string|max:255',
                'tagline_en' => 'nullable|string|max:255',
                'total_floors' => 'nullable|integer|min:1|max:200',
                'estimated_delivery' => 'nullable|date',
                'whatsapp_number' => 'nullable|string|max:20',
                'whatsapp_message' => 'nullable|string|max:500',
                'whatsapp_message_en' => 'nullable|string|max:500',
                'contact_email' => 'nullable|email|max:255',
                'analytics_id' => 'nullable|string|max:50',
                'avg_nightly_rate' => 'nullable|numeric|min:0',
                'average_occupancy' => 'nullable|numeric|min:0|max:100',
                'appreciation_rate_annual' => 'nullable|numeric|min:0',
                'management_fee' => 'nullable|numeric|min:0|max:100',
                'property_tax_rate' => 'nullable|numeric|min:0',
            ]);
        } elseif ($user->isInmobiliaria()) {
            Gate::authorize('edit-project-commercial', $project);
            // Only commercial fields
            $validated = $request->validate([
                'description' => 'nullable|string',
                'description_en' => 'nullable|string',
                'tagline' => 'nullable|string|max:255',
                'tagline_en' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:255',
                'location_en' => 'nullable|string|max:255',
                'estimated_delivery' => 'nullable|date',
                'whatsapp_number' => 'nullable|string|max:20',
                'whatsapp_message' => 'nullable|string|max:500',
                'whatsapp_message_en' => 'nullable|string|max:500',
                'contact_email' => 'nullable|email|max:255',
                'analytics_id' => 'nullable|string|max:50',
            ]);
        } else {
            abort(403);
        }

        $project->update($validated);

        return redirect()->route('admin.projects.edit', $project)
            ->with('success', 'Proyecto actualizado.');
    }

    public function destroy(Project $project)
    {
        Gate::authorize('delete-project');

        Storage::deleteDirectory("projects/{$project->id}");
        $project->delete();

        return redirect()->route('admin.projects.index')
            ->with('success', 'Proyecto eliminado.');
    }
}
