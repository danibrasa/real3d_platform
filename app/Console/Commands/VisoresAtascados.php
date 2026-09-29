<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
                            {--dias=5 : Dias sin cambiar de estado a partir de los que se avisa}
                            {--base= : Base de datos a mirar (la copia restaurada de produccion)}';

    protected $description = 'Lista los visores pedidos que llevan demasiado sin moverse';

    public function handle(): int
    {
        $dias = (int) $this->option('dias');

        // Por opcion y no por DB_DATABASE en el entorno: con la configuracion
        // cacheada la variable se ignora y se miraria la base de dev creyendo
        // mirar la copia. Se dice que base se miro, para que quien lo lanza
        // pueda comprobarlo.
        $conexion = config('database.default');
        if ($base = $this->option('base')) {
            config(["database.connections.{$conexion}.database" => $base]);
            DB::purge($conexion);
        }
        // La base que responde, no la que se pidio: en MySQL se le pregunta.
        $enUso = DB::connection($conexion)->getDriverName() === 'mysql'
            ? DB::connection($conexion)->selectOne('select database() as b')->b
            : config("database.connections.{$conexion}.database");
        $this->line('base: '.$enUso);

        // La nocturna lo lanza contra la copia de produccion, que lleva el
        // esquema de produccion: hasta que se despliegue la cola, no hay
        // columna que mirar, y eso no es una averia.
        if (! Schema::hasColumn('projects', 'visor_estado')) {
            $this->warn('esta base no tiene todavia la cola del equipo (version anterior a la 4.1): nada que mirar');

            return self::SUCCESS;
        }

        $atascados = Project::whereNotNull('viewer_requested_at')
            // NULL != 'montado' no es verdadero en SQL: un pedido sin estado
            // (dato viejo o inconsistente) se quedaria fuera del aviso.
            ->where(fn ($q) => $q->whereNull('visor_estado')->orWhere('visor_estado', '!=', Project::VISOR_MONTADO))
            // El reloj es el ultimo cambio de estado o, sin el, el pedido:
            // sin esto un pedido de ayer sin estado contaba como atascado
            // desde el minuto cero. Lo dijo el revisor.
            ->whereRaw('COALESCE(visor_estado_en, viewer_requested_at) <= ?', [now()->subDays($dias)])
            ->with('montador')
            ->orderByRaw('COALESCE(visor_estado_en, viewer_requested_at)')
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
