<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;

/**
 * Lo que lleva mas de treinta dias en la papelera se borra del todo.
 *
 * Hasta entonces el proyecto se puede recuperar tal cual, ficheros
 * incluidos. Al borrarlo del todo se van los ficheros del disco y la cuota
 * de la promotora se recalcula: eso lo hace el modelo al borrar en firme,
 * para que pase igual desde aqui que desde cualquier otro sitio.
 */
class VaciarPapeleraDeProyectos extends Command
{
    protected $signature = 'proyectos:vaciar-papelera
                            {--dias=30 : Dias en la papelera antes de borrar del todo}';

    protected $description = 'Borra del todo los proyectos que llevan demasiado en la papelera';

    public function handle(): int
    {
        $dias = (int) $this->option('dias');
        $limite = now()->subDays($dias);

        $caducados = Project::onlyTrashed()
            ->where('deleted_at', '<=', $limite)
            ->get();

        foreach ($caducados as $proyecto) {
            $proyecto->forceDelete();
            $this->line("borrado del todo: {$proyecto->name} (#{$proyecto->id}), en la papelera desde {$proyecto->deleted_at->toDateString()}");
        }

        $this->info("papelera: {$caducados->count()} proyecto(s) borrado(s) del todo tras {$dias} dias");

        return self::SUCCESS;
    }
}
