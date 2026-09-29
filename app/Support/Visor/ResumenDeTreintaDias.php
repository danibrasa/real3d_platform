<?php

namespace App\Support\Visor;

use App\Models\Inquiry;
use App\Models\Unit;
use App\Models\ViewerEvent;
use Illuminate\Support\Collection;

/**
 * Los tres numeros que le importan a una promotora: cuanta gente entro,
 * que viviendas miraron, cuantos escribieron. Ultimos treinta dias.
 *
 * Los eventos del visor se guardaban desde el principio y no los leia
 * nadie que no fuera del plan con analiticas. Esto es lo minimo, para todas,
 * en el panel: sin graficas, tres cifras que cuadran con lo que hay en la
 * tabla, y por eso tienen test.
 */
class ResumenDeTreintaDias
{
    public const DIAS = 30;

    public const MAS_VISTAS = 3;

    /**
     * @param  Collection<int, int>  $proyectos  ids de los proyectos de quien mira
     * @return array{visitas: int, mas_vistas: array<int, array{identificador: string, veces: int}>, leads: int}
     */
    public static function de(Collection $proyectos): array
    {
        $desde = now()->subDays(self::DIAS);

        // Una visita es una sesion del visor, no un evento: una persona que
        // abre el visor y toca cinco viviendas es una visita.
        $visitas = ViewerEvent::whereIn('project_id', $proyectos)
            ->where('event_type', 'session_start')
            ->where('created_at', '>=', $desde)
            ->distinct('session_id')
            ->count('session_id');

        $masVistas = ViewerEvent::whereIn('project_id', $proyectos)
            ->where('event_type', 'unit_selected')
            ->whereNotNull('unit_id')
            ->where('created_at', '>=', $desde)
            ->selectRaw('unit_id, count(*) as veces')
            ->groupBy('unit_id')
            ->orderByDesc('veces')
            ->limit(self::MAS_VISTAS)
            ->get();

        $identificadores = Unit::whereIn('id', $masVistas->pluck('unit_id'))->pluck('identifier', 'id');

        $leads = Inquiry::whereIn('project_id', $proyectos)
            ->where('created_at', '>=', $desde)
            ->count();

        return [
            'visitas' => $visitas,
            'mas_vistas' => $masVistas
                ->map(fn ($fila) => [
                    'identificador' => $identificadores[$fila->unit_id] ?? '#'.$fila->unit_id,
                    'veces' => (int) $fila->veces,
                ])
                ->values()
                ->all(),
            'leads' => $leads,
        ];
    }
}
