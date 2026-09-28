<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectSetting;
use App\Services\WebhookService;
use App\Support\Publicacion\ListaParaPublicar;
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

        // Decirselo aqui y no despues de rellenarlo todo.
        $user = auth()->user();
        if ($user->isInmobiliaria() && $user->companyProfile
            && ! $user->companyProfile->canCreateProject()) {
            return redirect()->route('admin.projects.index')
                ->with('error', __('billing.project_limit_reached'));
        }

        return view('admin.projects.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create-project');

        // Project quota check for inmobiliaria
        $user = $request->user();
        if ($user->isInmobiliaria() && $user->companyProfile) {
            if (! $user->companyProfile->canCreateProject()) {
                return back()->with('error', __('billing.project_limit_reached'));
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['status'] = 'draft';

        $project = Project::create($validated);

        // Sin esto, una promotora crea el proyecto y lo ve desaparecer:
        // accessibleProjects() y canAccessProject() miran la tabla de
        // asignaciones, no `created_by`.
        if ($request->user()->isInmobiliaria()) {
            $request->user()->assignedProjects()->attach($project->id);
        }

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

        if (! $user->canAccessProject($project)) {
            abort(403);
        }

        $project->load('settings', 'files', 'galleryImages');
        $project->loadCount(['typologies', 'units', 'galleryImages', 'paymentPlans']);

        return view('admin.projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $user = $request->user();

        if (! $user->canAccessProject($project)) {
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
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
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
                'chatbot_enabled' => 'sometimes|boolean',
                'chatbot_welcome_es' => 'nullable|string|max:1000',
                'chatbot_welcome_en' => 'nullable|string|max:1000',
                'chatbot_instructions' => 'nullable|string|max:2000',
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
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'estimated_delivery' => 'nullable|date',
                'whatsapp_number' => 'nullable|string|max:20',
                'whatsapp_message' => 'nullable|string|max:500',
                'whatsapp_message_en' => 'nullable|string|max:500',
                'contact_email' => 'nullable|email|max:255',
                'analytics_id' => 'nullable|string|max:50',
                'chatbot_enabled' => 'sometimes|boolean',
                'chatbot_welcome_es' => 'nullable|string|max:1000',
                'chatbot_welcome_en' => 'nullable|string|max:1000',
                'chatbot_instructions' => 'nullable|string|max:2000',
                // El estado se le permite, pero no a ciegas: mas abajo se
                // comprueba que haya algo que enseñar. Antes no estaba en esta
                // lista, asi que lo enviaba, se ignoraba en silencio y la web le
                // respondia "Proyecto actualizado" con el proyecto en borrador.
                'status' => 'required|in:draft,public,private,unlisted',
            ]);
        } else {
            abort(403);
        }

        // Publicar algo sin visor deja una pagina vacia con el nombre de la
        // promotora encima. Se le dice que falta y de quien es cada cosa.
        if (isset($validated['status'])
            && ListaParaPublicar::esSalirALaWeb($validated['status'], $project->status)) {
            $lista = ListaParaPublicar::de($project);

            if (! $lista->puedePublicarse()) {
                $motivos = array_map(
                    fn ($p) => __('publicacion.'.$p['clave']),
                    $lista->bloqueos()
                );

                return back()
                    ->withInput()
                    ->with('error', __('publicacion.rechazado').' '.implode(' ', $motivos));
            }
        }

        $oldStatus = $project->status;
        $project->update($validated);

        // Dispatch webhook if project was just published
        if (isset($validated['status']) && $validated['status'] === 'public' && $oldStatus !== 'public') {
            WebhookService::dispatch('project_published', [
                'project_slug' => $project->slug,
                'project_name' => $project->name,
            ], $project->id);
        }

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
