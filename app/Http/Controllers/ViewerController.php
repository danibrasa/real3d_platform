<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Unit;

class ViewerController extends Controller
{
    public function welcome()
    {
        $projects = Project::with('files')
            ->withCount([
                'units',
                'units as available_units_count' => function ($q) {
                    $q->where('status', 'available');
                },
            ])
            ->where('status', 'public')
            ->latest()
            ->take(6)
            ->get();

        return view('welcome', compact('projects'));
    }

    public function index()
    {
        $query = Project::with(['files', 'assignedAgencies.companyProfile', 'units' => fn ($q) => $q->where('status', 'available')->select('id', 'project_id', 'price', 'status')])->withCount([
            'units',
            'units as available_units_count' => function ($q) {
                $q->where('status', 'available');
            },
        ]);

        if (auth()->check()) {
            $query->whereIn('status', ['public', 'private']);
        } else {
            $query->where('status', 'public');
        }

        // Search
        if ($search = request('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by location
        if ($location = request('location')) {
            $query->where('location', $location);
        }

        // Filter by bedroom count (projects that have units with X bedrooms)
        if ($bedrooms = request('bedrooms')) {
            $query->whereHas('units', function ($q) use ($bedrooms) {
                $q->where('bedrooms', $bedrooms)->where('status', 'available');
            });
        }

        // Filter by price range
        if ($priceMin = request('price_min')) {
            $query->whereHas('units', function ($q) use ($priceMin) {
                $q->where('price', '>=', $priceMin)->where('status', 'available');
            });
        }
        if ($priceMax = request('price_max')) {
            $query->whereHas('units', function ($q) use ($priceMax) {
                $q->where('price', '<=', $priceMax)->where('status', 'available');
            });
        }

        // Sort
        $sort = request('sort', 'newest');
        $query->when($sort === 'newest', fn ($q) => $q->latest())
              ->when($sort === 'name', fn ($q) => $q->orderBy('name'))
              ->when($sort === 'price_asc', fn ($q) => $q->orderBy(
                  \App\Models\Unit::selectRaw('MIN(price)')->whereColumn('project_id', 'projects.id')->where('status', 'available')
              ))
              ->when($sort === 'price_desc', fn ($q) => $q->orderByDesc(
                  \App\Models\Unit::selectRaw('MIN(price)')->whereColumn('project_id', 'projects.id')->where('status', 'available')
              ));

        $projects = $query->paginate(12)->withQueryString();

        // Data for filters
        $locations = Project::where('status', 'public')
            ->whereNotNull('location')->where('location', '!=', '')
            ->distinct()->pluck('location')->sort()->values();

        $bedroomOptions = \App\Models\Unit::whereHas('project', fn ($q) => $q->where('status', 'public'))
            ->where('status', 'available')
            ->distinct()->pluck('bedrooms')->sort()->values();

        return view('viewer.index', compact('projects', 'locations', 'bedroomOptions'));
    }

    public function landing(Project $project)
    {
        $this->authorizeAccess($project);

        $project->load([
            'settings',
            'files',
            'typologies',
            'galleryImages',
            'paymentPlans.milestones',
            'constructionPhases',
            'constructionUpdates' => fn ($q) => $q->with('phase', 'images')->orderByDesc('date'),
            'pointsOfInterest',
            'units' => fn ($q) => $q->with('typology')->orderBy('floor')->orderBy('sort_order')->orderBy('identifier'),
        ]);

        $stats = [
            'total_units' => $project->units->count(),
            'available' => $project->units->where('status', 'available')->count(),
            'reserved' => $project->units->where('status', 'reserved')->count(),
            'sold' => $project->units->where('status', 'sold')->count(),
        ];

        $availableUnits = $project->units->where('status', 'available');
        $priceMin = $availableUnits->min('price');
        $priceMax = $availableUnits->max('price');

        return view('viewer.landing', compact('project', 'stats', 'priceMin', 'priceMax'));
    }

    public function show(Project $project)
    {
        $this->authorizeAccess($project);

        $project->load('settings', 'files');

        return view('viewer.show', compact('project'));
    }

    public function unitDetail(Project $project, Unit $unit)
    {
        $this->authorizeAccess($project);

        if ($unit->project_id !== $project->id) {
            abort(404);
        }

        $project->load([
            'settings',
            'files',
            'typologies',
            'galleryImages',
            'paymentPlans.milestones',
            'units' => fn ($q) => $q->with('typology')->orderBy('floor')->orderBy('sort_order')->orderBy('identifier'),
        ]);

        $unit->load('typology');

        // Similar units: same typology or same bedrooms, excluding sold and current unit
        $similarUnits = $project->units
            ->where('id', '!=', $unit->id)
            ->where('status', '!=', 'sold')
            ->filter(function ($u) use ($unit) {
                return ($unit->typology_id && $u->typology_id === $unit->typology_id)
                    || $u->bedrooms === $unit->bedrooms;
            })
            ->take(4);

        return view('viewer.unit-detail', compact('project', 'unit', 'similarUnits'));
    }

    private function authorizeAccess(Project $project): void
    {
        switch ($project->status) {
            case 'public':
            case 'unlisted':
                return;
            case 'private':
                if (!auth()->check()) {
                    abort(redirect()->route('login'));
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
