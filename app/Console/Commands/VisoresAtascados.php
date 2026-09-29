<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Los visores que llevan demasiado sin moverse en la cola del equipo.
 *
 * Una promotora que pide el visor y no ve movimiento en una semana se va.
 * Esto lo lista y sale con 1 si hay alguno, para que la comprobacion
 * nocturna lo cuente como averia: es una averia del negocio, no del codigo.
 */
class VisoresAtascados extends Command
{
    protected $signature = 'visores:atascados
                            {--dias=5 : Dias sin cambiar de estado a partir de los que se avisa}';

    protected $description = 'Lista los visores pedidos que llevan demasiado sin moverse';

    public function handle(): int
    {
        $dias = (int) $this->option('dias');

        // La nocturna lo lanza contra la copia de produccion, que lleva el
        // esquema de produccion: hasta que se despliegue la cola, no hay
        // columna que mirar, y eso no es una averia.
        if (! Schema::hasColumn('projects', 'visor_estado')) {
            $this->warn('esta base no tiene todavia la cola del equipo (version anterior a la 4.1): nada que mirar');

            return self::SUCCESS;
        }

        $atascados = Project::whereNotNull('viewer_requested_at')
            ->where('visor_estado', '!=', Project::VISOR_MONTADO)
            ->where(fn ($q) => $q->whereNull('visor_estado_en')->orWhere('visor_estado_en', '<=', now()->subDays($dias)))
            ->with('montador')
            ->orderBy('visor_estado_en')
            ->get();

        foreach ($atascados as $p) {
            $desde = $p->visor_estado_en ?? $p->viewer_requested_at;
            $this->line(sprintf('  %s: %s desde hace %d dias%s',
                $p->name,
                __('visor.estado_'.($p->visor_estado ?? 'pedido')),
                (int) $desde->diffInDays(now()),
                $p->montador ? ' ('.$p->montador->name.')' : ' (sin nadie asignado)'));
        }

        if ($atascados->isEmpty()) {
            $this->info("visores: ninguno lleva mas de {$dias} dias sin moverse");

            return self::SUCCESS;
        }

        $this->error("VISORES ATASCADOS: {$atascados->count()} llevan mas de {$dias} dias sin moverse");

        return self::FAILURE;
    }
}
