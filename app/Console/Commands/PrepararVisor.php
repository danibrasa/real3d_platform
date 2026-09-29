<?php

namespace App\Console\Commands;

use App\Jobs\ComprimirModelo;
use App\Jobs\PrepararFondo360;
use App\Models\ProjectFile;
use Illuminate\Console\Command;

/**
 * Las versiones ligeras de lo que ya estaba subido.
 *
 * El fondo en 2K y el modelo con Draco se sacan al subir; lo que se subio
 * antes de eso no las tiene. Esto lo recorre y las pide, una vez por
 * fichero. Con --ahora se hacen aqui mismo, sin cola, que es lo comodo
 * despues de un despliegue.
 */
class PrepararVisor extends Command
{
    protected $signature = 'visor:preparar
                            {--proyecto= : Solo ese proyecto (id)}
                            {--ahora : Hacerlo aqui en vez de encolarlo}
                            {--otra-vez : Tambien lo que ya tiene version ligera}';

    protected $description = 'Saca el fondo 360 en 2K y el modelo con Draco de lo que ya estaba subido';

    public function handle(): int
    {
        $ficheros = ProjectFile::query()
            ->whereIn('file_type', ['image_360', 'model_3d'])
            ->where('upload_complete', true)
            ->when($this->option('proyecto'), fn ($q, $id) => $q->where('project_id', $id))
            ->when(! $this->option('otra-vez'), fn ($q) => $q->whereNull('variantes'))
            ->orderBy('id')
            ->get();

        foreach ($ficheros as $fichero) {
            $job = $fichero->file_type === 'image_360'
                ? new PrepararFondo360($fichero->id)
                : new ComprimirModelo($fichero->id);

            if ($this->option('ahora')) {
                $job->handle();
                $variantes = $fichero->fresh()->variantes;
                $this->line(sprintf('#%d %s %s: %s', $fichero->id, $fichero->file_type, $fichero->original_name,
                    $variantes ? implode(', ', array_keys($variantes)) : 'sin version ligera (ya era pequeño, o no se pudo)'));
            } else {
                dispatch($job);
                $this->line(sprintf('#%d %s %s: encolado', $fichero->id, $fichero->file_type, $fichero->original_name));
            }
        }

        $this->info("visor: {$ficheros->count()} fichero(s) revisado(s)");

        return self::SUCCESS;
    }
}
