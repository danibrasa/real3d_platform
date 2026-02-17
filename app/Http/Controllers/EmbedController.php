<?php

namespace App\Http\Controllers;

use App\Models\Project;

class EmbedController extends Controller
{
    public function show(string $slug)
    {
        $project = Project::where('slug', $slug)
            ->whereIn('status', ['public', 'unlisted'])
            ->firstOrFail();

        $project->loadCount([
            'units',
            'units as available_units_count' => fn ($q) => $q->where('status', 'available'),
        ]);

        $units = $project->units()
            ->with('typology')
            ->where('status', 'available')
            ->orderBy('price')
            ->limit(10)
            ->get();

        $priceMin = $project->units()->where('status', 'available')->min('price');
        $priceMax = $project->units()->where('status', 'available')->max('price');

        return view('embed.widget', compact('project', 'units', 'priceMin', 'priceMax'));
    }
}
