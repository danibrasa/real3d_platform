<?php

namespace App\Jobs;

use App\Models\ProjectFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * El modelo 3D, comprimido con Draco al subirlo.
 *
 * El modelo de un proyecto real pesa 35 MB en GLB sin comprimir; con Draco
 * son 5 u 8, y el visor ya sabe leerlo (lleva DRACOLoader desde el
 * principio, para ficheros que nunca llegaban comprimidos). Se hace aqui,
 * una vez al subir, con gltf-pipeline; el original se conserva y la version
 * comprimida es la que se sirve cuando existe.
 *
 * Si la herramienta no esta, o el fichero no es GLB, o el resultado no pesa
 * menos, no pasa nada: se sirve el original, que es lo que habia.
 */
class ComprimirModelo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public int $ficheroId) {}

    public function handle(): void
    {
        $fichero = ProjectFile::find($this->ficheroId);
        if (! $fichero || $fichero->file_type !== 'model_3d' || ! $fichero->storage_path) {
            return;
        }

        // Solo glTF. Un FBX se sirve tal cual: convertirlo es otro trabajo.
        if (! preg_match('/\.(glb|gltf)$/i', $fichero->original_name)) {
            return;
        }

        $herramienta = config('ficheros.gltf_pipeline');
        if (! $herramienta || ! is_executable($herramienta)) {
            report(new \RuntimeException("no esta gltf-pipeline en {$herramienta}: el modelo #{$fichero->id} se sirve sin comprimir"));

            return;
        }

        $origen = Storage::path($fichero->storage_path);
        $destino = preg_replace('/\.[a-z0-9]+$/i', '', $fichero->storage_path).'-draco.glb';
        $destinoAbs = Storage::path($destino);

        $proceso = new Process([
            $herramienta, '-i', $origen, '-o', $destinoAbs, '-d', '--draco.compressionLevel', '7',
        ]);
        $proceso->setTimeout($this->timeout);
        $proceso->run();

        if (! $proceso->isSuccessful() || ! is_file($destinoAbs)) {
            // Si murio escribiendo (tiempo, memoria, señal), lo que dejo no
            // vale y no debe quedar ahi con nombre de comprimido.
            if (is_file($destinoAbs)) {
                unlink($destinoAbs);
            }
            report(new \RuntimeException("gltf-pipeline fallo con el modelo #{$fichero->id}: ".substr($proceso->getErrorOutput(), 0, 300)));

            return;
        }

        // Si no pesa menos, no vale la pena: el original se queda solo.
        if (filesize($destinoAbs) >= filesize($origen)) {
            unlink($destinoAbs);

            return;
        }

        $fichero->forceFill(['variantes' => array_merge($fichero->variantes ?? [], ['draco' => $destino])])->save();
    }
}
