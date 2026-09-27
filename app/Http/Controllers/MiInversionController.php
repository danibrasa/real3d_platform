<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

/**
 * Lo que ve quien ya compro.
 *
 * Entre la firma y la entrega pasan dos o tres años en los que el comprador
 * solo tiene recibos y confianza. Aqui ve en que va su obra, que ha pagado y
 * que le queda, sin tener que escribir a nadie. Y la promotora sube las fotos
 * una vez en lugar de contestar ochenta veces lo mismo.
 */
class MiInversionController extends Controller
{
    /** Las viviendas que ha comprado esta persona. */
    public function index(Request $request)
    {
        $unidades = $this->unidadesDe($request)
            ->with(['project', 'typology'])
            ->get();

        abort_if($unidades->isEmpty(), 403);

        // Con una sola vivienda no tiene sentido una lista de un elemento.
        if ($unidades->count() === 1) {
            return redirect()->route('mi-inversion.show', $unidades->first());
        }

        return view('mi-inversion.index', compact('unidades'));
    }

    /** El detalle de una vivienda comprada. */
    public function show(Request $request, Unit $unit)
    {
        abort_unless($this->esSuya($request, $unit), 403);

        $unit->load([
            'typology',
            'payments.milestone',
            'project.constructionPhases',
            'project.constructionUpdates' => fn ($q) => $q->with('images')->orderByDesc('date'),
            'project.paymentPlans.milestones',
        ]);

        $project = $unit->project;

        return view('mi-inversion.show', [
            'unit' => $unit,
            'project' => $project,
            'pagos' => $unit->payments,
            'pagado' => $unit->totalPaid(),
            'pendiente' => $unit->pendingAmount(),
            'porcentajePagado' => $unit->paidPercent(),
            'avanceObra' => $this->avanceDeObra($project),
            'fases' => $project->constructionPhases,
            'actualizaciones' => $project->constructionUpdates,
        ]);
    }

    /**
     * Porcentaje de obra, tomado de la ultima actualizacion publicada.
     *
     * Se prefiere el dato declarado a calcularlo con las fases: la promotora
     * sabe mejor que nadie por donde va, y un numero inventado por nosotros
     * seria peor que ninguno.
     */
    private function avanceDeObra($project): ?float
    {
        $ultima = $project->constructionUpdates->first();

        return $ultima?->progress_percentage !== null
            ? (float) $ultima->progress_percentage
            : null;
    }

    private function unidadesDe(Request $request)
    {
        return Unit::query()->where('buyer_id', $request->user()->id);
    }

    private function esSuya(Request $request, Unit $unit): bool
    {
        return $unit->buyer_id === $request->user()->id;
    }
}
