<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Project;
use App\Models\Unit;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function home()
    {
        $featured = Project::portalVisible()
            ->with([
                'files',
                'assignedAgencies.companyProfile',
                'units' => fn ($q) => $q->where('status', 'available')
                    ->select('id', 'project_id', 'price', 'bedrooms', 'status'),
            ])
            ->withCount([
                'units',
                'units as available_units_count' => fn ($q) => $q->where('status', 'available'),
            ])
            ->having('available_units_count', '>', 0)
            ->orderByDesc('available_units_count')
            ->take(6)
            ->get();

        $locations = Project::portalVisible()
            ->whereNotNull('location')->where('location', '!=', '')
            ->distinct()->pluck('location')->sort()->values();

        $stats = [
            'projects' => Project::portalVisible()->count(),
            'units' => Unit::whereHas('project', fn ($q) => $q->portalVisible())
                ->where('status', 'available')->count(),
            'locations' => $locations->count(),
        ];

        $mapProjects = Project::portalVisible()
            ->select('id', 'name', 'slug', 'latitude', 'longitude', 'location', 'thumbnail_path')
            ->withCount(['units as available_units_count' => fn ($q) => $q->where('status', 'available')])
            ->get();

        $latestPosts = BlogPost::published()
            ->with(['category', 'author'])
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('portal.home', compact('featured', 'locations', 'stats', 'mapProjects', 'latestPosts'));
    }

    public function search(Request $request)
    {
        $query = Project::portalVisible()
            ->with([
                'files',
                'assignedAgencies.companyProfile',
                'units' => fn ($q) => $q->where('status', 'available')
                    ->select('id', 'project_id', 'price', 'bedrooms', 'bathrooms', 'area_m2', 'status'),
            ])
            ->withCount([
                'units',
                'units as available_units_count' => fn ($q) => $q->where('status', 'available'),
            ]);

        // Text search
        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($location = $request->get('location')) {
            $query->where('location', $location);
        }

        if ($bedrooms = $request->get('bedrooms')) {
            $query->whereHas('units', fn ($q) => $q->where('bedrooms', '>=', $bedrooms)->where('status', 'available'));
        }

        if ($priceMin = $request->get('price_min')) {
            $query->whereHas('units', fn ($q) => $q->where('price', '>=', $priceMin)->where('status', 'available'));
        }
        if ($priceMax = $request->get('price_max')) {
            $query->whereHas('units', fn ($q) => $q->where('price', '<=', $priceMax)->where('status', 'available'));
        }

        if ($delivery = $request->get('delivery')) {
            $query->where('estimated_delivery', '<=', $delivery);
        }

        $sort = $request->get('sort', 'newest');
        $query->when($sort === 'newest', fn ($q) => $q->latest())
            ->when($sort === 'name', fn ($q) => $q->orderBy('name'))
            ->when($sort === 'price_asc', fn ($q) => $q->orderBy(
                Unit::selectRaw('MIN(price)')->whereColumn('project_id', 'projects.id')->where('status', 'available')
            ))
            ->when($sort === 'price_desc', fn ($q) => $q->orderByDesc(
                Unit::selectRaw('MIN(price)')->whereColumn('project_id', 'projects.id')->where('status', 'available')
            ))
            ->when($sort === 'availability', fn ($q) => $q->orderByDesc('available_units_count'));

        $projects = $query->paginate(12)->withQueryString();

        $locations = Project::portalVisible()
            ->whereNotNull('location')->where('location', '!=', '')
            ->distinct()->pluck('location')->sort()->values();

        $bedroomOptions = Unit::whereHas('project', fn ($q) => $q->portalVisible())
            ->where('status', 'available')
            ->distinct()->pluck('bedrooms')->sort()->values();

        $mapBounds = null;
        if ($projects->count()) {
            $coords = $projects->getCollection();
            $mapBounds = [
                'south' => $coords->min('latitude'),
                'north' => $coords->max('latitude'),
                'west' => $coords->min('longitude'),
                'east' => $coords->max('longitude'),
            ];
        }

        return view('portal.search', compact('projects', 'locations', 'bedroomOptions', 'mapBounds'));
    }

    public function locations(Request $request)
    {
        $q = $request->get('q', '');
        $locations = Project::portalVisible()
            ->whereNotNull('location')
            ->where('location', 'like', "%{$q}%")
            ->distinct()
            ->pluck('location')
            ->sort()
            ->values();

        return response()->json($locations);
    }

    public function mapProjects(Request $request)
    {
        $query = Project::portalVisible();

        if ($location = $request->get('location')) {
            $query->where('location', $location);
        }
        if ($bedrooms = $request->get('bedrooms')) {
            $query->whereHas('units', fn ($q) => $q->where('bedrooms', '>=', $bedrooms)->where('status', 'available'));
        }
        if ($priceMin = $request->get('price_min')) {
            $query->whereHas('units', fn ($q) => $q->where('price', '>=', $priceMin)->where('status', 'available'));
        }
        if ($priceMax = $request->get('price_max')) {
            $query->whereHas('units', fn ($q) => $q->where('price', '<=', $priceMax)->where('status', 'available'));
        }
        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $projects = $query->select('id', 'name', 'slug', 'latitude', 'longitude', 'location', 'thumbnail_path')
            ->withCount(['units as available_units_count' => fn ($q) => $q->where('status', 'available')])
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'lat' => $p->latitude,
                'lng' => $p->longitude,
                'location' => $p->location,
                'available' => $p->available_units_count,
                'url' => route('viewer.landing', $p->slug),
                'thumbnail' => $p->thumbnail_path
                    ? url('api/projects/'.$p->id.'/files/thumbnail')
                    : null,
            ]);

        return response()->json($projects);
    }
}
