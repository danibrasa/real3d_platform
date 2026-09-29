<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectGalleryImage;
use App\Models\Unit;
use App\Support\Facturacion\AccesoAlVisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectApiController extends Controller
{
    public function show(Project $project)
    {
        $this->authorizeAccess($project);

        $project->load('settings', 'files');

        return response()->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'location' => $project->location,
                'status' => $project->status,
            ],
            'settings' => $project->settings,
            'files' => [
                'video_360' => $project->urlDeFichero('video_360'),
                'model_3d' => $project->urlDeFichero('model_3d'),
                'ground_texture' => $project->urlDeFichero('ground_texture'),
                'image_360' => $project->urlDeFichero('image_360'),
            ],
        ]);
    }

    /**
     * Los ficheros que SON el visor 3D, y no la ficha de informacion.
     *
     * La distincion importa: al darse de baja se deja de servir el visor, pero
     * la miniatura sigue haciendo falta para el listado y el portal, que son
     * del plan gratuito.
     */
    private const DEL_VISOR = ['model_3d', 'image_360', 'video_360'];

    public function serveFile(Request $request, Project $project, string $fileType)
    {
        $this->authorizeAccess($project);

        // Bloquear solo la pagina del visor habria sido medio muro: el modelo y
        // el fondo salen por aqui, y con la direccion se descargan igual.
        if (in_array($fileType, self::DEL_VISOR, true)
            && ! AccesoAlVisor::servidoEnPublico($project)) {
            abort(404);
        }

        $file = $project->files()
            ->where('file_type', $fileType)
            ->where('upload_complete', true)
            ->firstOrFail();

        // El tamaño que se pide (?tam=2k): la version ligera si la hay, y si
        // no, el original, que es lo que habia. Asi el visor puede pedir la
        // ligera siempre sin saber si existe.
        $tam = $request->query('tam');
        $ruta = $file->rutaPara($tam);
        $path = Storage::path($ruta);

        if (! file_exists($path)) {
            abort(404, 'File not found on disk.');
        }

        // La cache. Con la version en la direccion (?v=), un ano e inmutable:
        // una direccion nueva es un fichero nuevo. Sin version, una hora, y
        // ETag para que la segunda visita pregunte y no vuelva a bajar 35 MB.
        // Antes: una hora y sin ETag, asi que a la hora se bajaba entero.
        $etag = '"'.$file->version().($file->tieneVariante($tam) ? '-'.$tam : '').'"';
        $inmutable = $request->query('v') === $file->version();
        $cabeceras = [
            'Content-Type' => $file->mime_type,
            'Accept-Ranges' => 'bytes',
            'ETag' => $etag,
            'Last-Modified' => gmdate('D, d M Y H:i:s', (int) filemtime($path)).' GMT',
            'Cache-Control' => $inmutable ? 'public, max-age=31536000, immutable' : 'public, max-age=3600',
        ];

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304, $cabeceras);
        }

        // Quien lee el fichero. Por nginx, PHP solo ha decidido -- permiso,
        // plan, cache -- y nginx lo sirve desde un location interno
        // (deploy/nginx-ficheros.conf) con sendfile y rangos; por PHP, un
        // proceso php-fpm lee 35 MB y los escribe, que es lo que habia.
        if (config('ficheros.por_nginx')) {
            // nginx pone Accept-Ranges y Content-Length por su cuenta.
            unset($cabeceras['Accept-Ranges']);

            return response('', 200, $cabeceras + ['X-Accel-Redirect' => '/_ficheros/'.$ruta]);
        }

        return response()->file($path, $cabeceras);
    }

    public function units(Project $project)
    {
        $this->authorizeAccess($project);

        $units = $project->units()
            ->with('typology')
            ->orderBy('floor')
            ->orderBy('sort_order')
            ->orderBy('identifier')
            ->get()
            ->map(function (Unit $unit) {
                return [
                    'id' => $unit->id,
                    'identifier' => $unit->identifier,
                    'floor' => $unit->floor,
                    'bedrooms' => $unit->bedrooms,
                    'bathrooms' => $unit->bathrooms,
                    'area_m2' => $unit->area_m2,
                    'price' => $unit->price,
                    'formatted_price' => $unit->formatted_price,
                    'status' => $unit->status,
                    'typology' => $unit->typology?->name,
                    'has_floor_plan' => (bool) $unit->floor_plan,
                    'floor_plan_url' => $unit->floor_plan ? "/api/units/{$unit->id}/floor-plan" : null,
                    'bbox' => $unit->has_bbox ? [
                        'cx' => $unit->bbox_center_x,
                        'cy' => $unit->bbox_center_y,
                        'cz' => $unit->bbox_center_z,
                        'sx' => $unit->bbox_size_x,
                        'sy' => $unit->bbox_size_y,
                        'sz' => $unit->bbox_size_z,
                    ] : null,
                ];
            });

        $floors = $units->pluck('floor')->unique()->sort()->values();

        return response()->json([
            'units' => $units,
            'floors' => $floors,
            'summary' => [
                'total' => $units->count(),
                'available' => $units->where('status', 'available')->count(),
                'reserved' => $units->where('status', 'reserved')->count(),
                'sold' => $units->where('status', 'sold')->count(),
            ],
        ]);
    }

    /**
     * SECURITY FIX: Added project authorization check before serving floor plan.
     * Previously, any unit's floor plan could be accessed without verifying project visibility.
     */
    public function serveFloorPlan(Unit $unit)
    {
        // Load project and enforce visibility rules
        $unit->load('project');
        $this->authorizeAccess($unit->project);

        $path = $unit->floor_plan;
        if (! $path) {
            abort(404);
        }

        $fullPath = Storage::path($path);
        if (! file_exists($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath, [
            'Content-Type' => mime_content_type($fullPath),
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * SECURITY FIX: Added project authorization + image ownership verification.
     * Previously, gallery images could be accessed without verifying project visibility
     * or that the image belonged to the requested project.
     */
    public function serveGalleryImage(Project $project, ProjectGalleryImage $image)
    {
        // Verify the image belongs to this project (prevent IDOR)
        if ($image->project_id !== $project->id) {
            abort(404);
        }

        // Enforce project visibility rules
        $this->authorizeAccess($project);

        $fullPath = Storage::path($image->image_path);
        if (! file_exists($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath, [
            'Content-Type' => mime_content_type($fullPath),
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function authorizeAccess(Project $project): void
    {
        switch ($project->status) {
            case 'public':
            case 'unlisted':
                return;
            case 'private':
                if (! auth()->check()) {
                    abort(403);
                }

                return;
            case 'draft':
                // El equipo, y la promotora del proyecto: tiene que poder ver
                // su visor antes de que se publique para dar el visto bueno.
                // Antes se enteraba del resultado al publicarlo.
                if (! auth()->check() || ! (auth()->user()->hasRole('superadmin', 'gestor') || auth()->user()->canAccessProject($project))) {
                    abort(404);
                }

                return;
            default:
                abort(404);
        }
    }
}
