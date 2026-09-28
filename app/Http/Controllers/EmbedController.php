<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\Facturacion\PlanDelProyecto;

class EmbedController extends Controller
{
    public function show(string $slug)
    {
        $project = Project::where('slug', $slug)
            ->whereIn('status', ['public', 'unlisted'])
            ->firstOrFail();

        // El widget para la web de la promotora va en los planes de pago, y
        // no lo comprobaba nadie: cualquiera podia incrustarlo gratis. Se
        // responde 404 y no 403 a proposito: esto se pinta dentro de un iframe
        // en la web de un tercero, y ahi una pagina de "no tienes permiso" con
        // nuestra marca queda peor que nada.
        abort_unless(PlanDelProyecto::incluye($project, 'embed_widget'), 404);

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
