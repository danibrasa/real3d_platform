<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectGalleryImage;
use App\Models\Unit;
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
                'video_360' => $project->getFileByType('video_360')
                    ? "/api/projects/{$project->id}/files/video_360" : null,
                'model_3d' => $project->getFileByType('model_3d')
                    ? "/api/projects/{$project->id}/files/model_3d" : null,
                'ground_texture' => $project->getFileByType('ground_texture')
                    ? "/api/projects/{$project->id}/files/ground_texture" : null,
            ],
        ]);
    }

    public function serveFile(Project $project, string $fileType)
    {
        $this->authorizeAccess($project);

        $file = $project->files()
            ->where('file_type', $fileType)
            ->where('upload_complete', true)
            ->firstOrFail();

        $path = Storage::path($file->storage_path);

        if (!file_exists($path)) {
            abort(404, 'File not found on disk.');
        }

        return response()->file($path, [
            'Content-Type' => $file->mime_type,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, max-age=3600',
        ]);
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

    public function serveFloorPlan(Unit $unit)
    {
        $path = $unit->floor_plan;
        if (!$path) {
            abort(404);
        }

        $fullPath = Storage::path($path);
        if (!file_exists($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath, [
            'Content-Type' => mime_content_type($fullPath),
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function serveGalleryImage(Project $project, ProjectGalleryImage $image)
    {
        $fullPath = Storage::path($image->image_path);
        if (!file_exists($fullPath)) {
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
                if (!auth()->check()) {
                    abort(403);
                }
                return;
            case 'draft':
                if (!auth()->check() || !auth()->user()->hasRole('superadmin', 'gestor')) {
                    abort(404);
                }
                return;
            default:
                abort(404);
        }
    }
}
