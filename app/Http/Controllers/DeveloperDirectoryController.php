<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;

class DeveloperDirectoryController extends Controller
{
    public function index()
    {
        $developers = CompanyProfile::where('is_verified', true)
            ->where('show_in_directory', true)
            ->withCount(['user' => function ($q) {
                // We need a different approach - count projects via user
            }])
            ->with('user')
            ->latest()
            ->paginate(24);

        // Add project counts
        $developers->getCollection()->transform(function ($developer) {
            $developer->projects_count = $developer->user->assignedProjects()
                ->where('status', 'public')
                ->count();

            return $developer;
        });

        return view('directory.index', compact('developers'));
    }

    public function show(CompanyProfile $developer)
    {
        if (! $developer->show_in_directory) {
            abort(404);
        }

        $developer->load('user');

        $projects = $developer->user->assignedProjects()
            ->where('status', 'public')
            ->with(['files', 'units' => fn ($q) => $q->where('status', 'available')->select('id', 'project_id', 'price', 'status')])
            ->withCount([
                'units',
                'units as available_units_count' => fn ($q) => $q->where('status', 'available'),
            ])
            ->latest()
            ->paginate(12);

        return view('directory.show', compact('developer', 'projects'));
    }
}
