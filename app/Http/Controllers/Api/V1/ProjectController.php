<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        $query = Project::whereIn('status', ['public', 'unlisted'])
            ->withCount(['units', 'units as available_units_count' => fn ($q) => $q->where('status', 'available')]);

        // Filter by token's allowed projects
        if (! is_null($token->project_ids)) {
            $query->whereIn('id', $token->project_ids);
        }

        $projects = $query->orderBy('name')->get();

        return response()->json([
            'data' => $projects->map(fn (Project $p) => [
                'slug' => $p->slug,
                'name' => $p->name,
                'description' => $p->description,
                'location' => $p->location,
                'tagline' => $p->tagline,
                'status' => $p->status,
                'total_floors' => $p->total_floors,
                'estimated_delivery' => $p->estimated_delivery?->format('Y-m-d'),
                'units_total' => $p->units_count,
                'units_available' => $p->available_units_count,
            ]),
            'meta' => [
                'total' => $projects->count(),
            ],
        ]);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $this->ensurePublicAccess($project);

        $project->load('typologies');
        $project->loadCount([
            'units',
            'units as available_units_count' => fn ($q) => $q->where('status', 'available'),
            'units as reserved_units_count' => fn ($q) => $q->where('status', 'reserved'),
            'units as sold_units_count' => fn ($q) => $q->where('status', 'sold'),
        ]);

        $priceMin = $project->units()->where('status', 'available')->min('price');
        $priceMax = $project->units()->where('status', 'available')->max('price');

        return response()->json([
            'data' => [
                'slug' => $project->slug,
                'name' => $project->name,
                'description' => $project->description,
                'location' => $project->location,
                'tagline' => $project->tagline,
                'status' => $project->status,
                'total_floors' => $project->total_floors,
                'estimated_delivery' => $project->estimated_delivery?->format('Y-m-d'),
                'whatsapp_number' => $project->whatsapp_number,
                'contact_email' => $project->contact_email,
                'typologies' => $project->typologies->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'bedrooms' => $t->bedrooms,
                    'bathrooms' => $t->bathrooms,
                    'area_m2' => $t->area_m2,
                ]),
                'stats' => [
                    'total_units' => $project->units_count,
                    'available' => $project->available_units_count,
                    'reserved' => $project->reserved_units_count,
                    'sold' => $project->sold_units_count,
                    'price_min' => $priceMin,
                    'price_max' => $priceMax,
                ],
            ],
        ]);
    }

    public function units(Request $request, Project $project): JsonResponse
    {
        $this->ensurePublicAccess($project);

        $query = $project->units()->with('typology');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('bedrooms')) {
            $query->where('bedrooms', $request->integer('bedrooms'));
        }
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->input('max_price'));
        }
        if ($request->filled('min_area')) {
            $query->where('area_m2', '>=', $request->input('min_area'));
        }

        $units = $query->orderBy('floor')
            ->orderBy('sort_order')
            ->orderBy('identifier')
            ->get();

        return response()->json([
            'data' => $units->map(fn (Unit $u) => $this->formatUnit($u)),
            'meta' => [
                'total' => $units->count(),
                'filters_applied' => array_filter([
                    'status' => $request->input('status'),
                    'bedrooms' => $request->input('bedrooms'),
                    'min_price' => $request->input('min_price'),
                    'max_price' => $request->input('max_price'),
                    'min_area' => $request->input('min_area'),
                ]),
            ],
        ]);
    }

    public function unit(Request $request, Project $project, Unit $unit): JsonResponse
    {
        $this->ensurePublicAccess($project);

        if ($unit->project_id !== $project->id) {
            abort(404);
        }

        $unit->load('typology');

        return response()->json([
            'data' => $this->formatUnit($unit),
        ]);
    }

    public function availability(Request $request, Project $project): JsonResponse
    {
        $this->ensurePublicAccess($project);

        $stats = $project->units()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved,
                SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold,
                MIN(CASE WHEN status = 'available' THEN price END) as price_min,
                MAX(CASE WHEN status = 'available' THEN price END) as price_max,
                MIN(CASE WHEN status = 'available' THEN area_m2 END) as area_min,
                MAX(CASE WHEN status = 'available' THEN area_m2 END) as area_max
            ")
            ->first();

        $byBedrooms = $project->units()
            ->where('status', 'available')
            ->selectRaw('bedrooms, COUNT(*) as count, MIN(price) as price_from')
            ->groupBy('bedrooms')
            ->orderBy('bedrooms')
            ->get();

        return response()->json([
            'data' => [
                'slug' => $project->slug,
                'name' => $project->name,
                'total' => (int) $stats->total,
                'available' => (int) $stats->available,
                'reserved' => (int) $stats->reserved,
                'sold' => (int) $stats->sold,
                'price_range' => [
                    'min' => $stats->price_min ? (float) $stats->price_min : null,
                    'max' => $stats->price_max ? (float) $stats->price_max : null,
                ],
                'area_range' => [
                    'min' => $stats->area_min ? (float) $stats->area_min : null,
                    'max' => $stats->area_max ? (float) $stats->area_max : null,
                ],
                'by_bedrooms' => $byBedrooms->map(fn ($row) => [
                    'bedrooms' => $row->bedrooms,
                    'available' => $row->count,
                    'price_from' => (float) $row->price_from,
                ]),
            ],
        ]);
    }

    private function formatUnit(Unit $unit): array
    {
        return [
            'id' => $unit->id,
            'identifier' => $unit->identifier,
            'floor' => $unit->floor,
            'bedrooms' => $unit->bedrooms,
            'bathrooms' => $unit->bathrooms,
            'area_m2' => $unit->area_m2,
            'price' => $unit->price,
            'status' => $unit->status,
            'typology' => $unit->typology?->name,
            'has_floor_plan' => (bool) $unit->floor_plan,
        ];
    }

    private function ensurePublicAccess(Project $project): void
    {
        if (! in_array($project->status, ['public', 'unlisted'])) {
            abort(403, 'Project is not publicly accessible.');
        }
    }
}
