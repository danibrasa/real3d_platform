<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MaterialDelProyecto;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * La entrega del material para montar el visor.
 *
 * Es la costura del reparto por el lado de la promotora: ella entrega, el
 * equipo monta. Hasta ahora el material llegaba por fuera y no habia forma
 * de saber que habia llegado ni de que faltaba; el equipo lo pedia por
 * correo y la promotora contestaba por WhatsApp. Ahora queda aqui, con
 * fecha, y la cola del equipo lo ve.
 */
class MaterialController extends Controller
{
    /** Lo que cabe por el formulario. Por encima, un enlace. */
    private const MAXIMO_KB = 20000;

    public function index(Request $request, Project $project)
    {
        abort_unless($request->user()->canAccessProject($project), 403);

        $material = $project->material()->with('autor')->orderBy('tipo')->latest()->get();

        return view('admin.projects.material', [
            'project' => $project,
            'material' => $material,
            'tipos' => MaterialDelProyecto::TIPOS,
            'entregados' => $material->pluck('tipo')->unique()->all(),
            'imprescindibles' => MaterialDelProyecto::IMPRESCINDIBLES,
            'maximoMb' => (int) (self::MAXIMO_KB / 1000),
        ]);
    }

    public function store(Request $request, Project $project)
    {
        abort_unless($request->user()->canAccessProject($project), 403);

        $validated = $request->validate([
            'tipo' => ['required', Rule::in(MaterialDelProyecto::TIPOS)],
            'fichero' => ['required_without:enlace', 'nullable', 'file', 'max:'.self::MAXIMO_KB],
            'enlace' => ['required_without:fichero', 'nullable', 'url', 'max:500'],
        ], [
            'fichero.required_without' => __('material.hace_falta_fichero_o_enlace'),
            'enlace.required_without' => __('material.hace_falta_fichero_o_enlace'),
            'fichero.max' => __('material.demasiado_grande', ['mb' => (int) (self::MAXIMO_KB / 1000)]),
        ]);

        $fichero = $request->file('fichero');

        // La cuota, tambien aqui: es sitio de la promotora igual que el visor.
        $perfil = $project->assignedAgencies()->first()?->companyProfile;
        if ($fichero && $perfil && ! $perfil->hasStorageAvailable($fichero->getSize())) {
            return back()->with('error', __('billing.storage_quota_exceeded'));
        }

        $material = MaterialDelProyecto::create([
            'project_id' => $project->id,
            'tipo' => $validated['tipo'],
            'original_name' => $fichero ? $fichero->getClientOriginalName() : ($validated['enlace'] ?? ''),
            'storage_path' => $fichero
                ? $fichero->storeAs("projects/{$project->id}/material", uniqid().'-'.$fichero->getClientOriginalName())
                : null,
            'enlace' => $fichero ? null : $validated['enlace'],
            'file_size' => $fichero ? $fichero->getSize() : 0,
            'mime_type' => $fichero?->getMimeType(),
            'subido_por' => $request->user()->id,
        ]);

        $perfil?->recalculateStorage();

        AuditLog::record('material_entregado', $project, null, [
            'tipo' => $material->tipo,
            'nombre' => $material->original_name,
        ]);

        return redirect()->route('admin.projects.material.index', $project)
            ->with('success', __('material.recibido'));
    }

    public function destroy(Request $request, Project $project, MaterialDelProyecto $material)
    {
        abort_unless($request->user()->canAccessProject($project), 403);
        abort_unless($material->project_id === $project->id, 404);

        $material->delete();
        $project->assignedAgencies()->first()?->companyProfile?->recalculateStorage();

        return back()->with('success', __('material.quitado'));
    }

    public function descargar(Request $request, Project $project, MaterialDelProyecto $material)
    {
        abort_unless($request->user()->canAccessProject($project), 403);
        abort_unless($material->project_id === $project->id, 404);

        if ($material->esEnlace()) {
            return redirect()->away($material->enlace);
        }

        abort_unless($material->storage_path && Storage::exists($material->storage_path), 404);

        return Storage::download($material->storage_path, $material->original_name);
    }
}
