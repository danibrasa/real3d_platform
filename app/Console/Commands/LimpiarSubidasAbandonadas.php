<?php

namespace App\Console\Commands;

use App\Models\UploadChunk;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Recoge las subidas que se quedaron a medias.
 *
 * Un video 360 de trescientos megas se sube en sesenta trozos. Si se cierra el
 * navegador en el cuarenta, los cuarenta primeros se quedan en disco y nadie los
 * recoge nunca: no hay ningun sitio donde alguien vaya a verlos.
 *
 * En produccion habia 28 subidas registradas y 12 sin completar desde febrero.
 * No ocupaban disco de milagro -las carpetas se habian perdido en algun
 * despliegue- pero el dia que ocupen, nadie lo vera venir.
 */
class LimpiarSubidasAbandonadas extends Command
{
    protected $signature = 'subidas:limpiar
                            {--horas=24 : Cuantas horas tiene que llevar parada una subida}
                            {--probar : Enseña que borraria sin borrar nada}';

    protected $description = 'Borra los trozos de las subidas que se quedaron a medias';

    public function handle(): int
    {
        $horas = (int) $this->option('horas');
        $probar = (bool) $this->option('probar');

        $abandonadas = UploadChunk::where('completed', false)
            ->where('created_at', '<', now()->subHours($horas))
            ->get();

        if ($abandonadas->isEmpty()) {
            $this->info('No hay subidas abandonadas.');

            return self::SUCCESS;
        }

        $bytes = 0;
        foreach ($abandonadas as $subida) {
            $bytes += collect(Storage::allFiles($subida->temp_directory))
                ->sum(fn ($f) => Storage::size($f));
        }

        $this->line(sprintf(
            '%d subida(s) parada(s) mas de %dh, %s en disco',
            $abandonadas->count(), $horas, $this->enMegas($bytes)
        ));

        foreach ($abandonadas as $subida) {
            $this->line(sprintf(
                '  %s  %-12s %s',
                $subida->created_at->format('Y-m-d H:i'),
                $subida->file_type,
                $subida->original_name
            ));

            if (! $probar) {
                Storage::deleteDirectory($subida->temp_directory);
                $subida->delete();
            }
        }

        $this->info($probar
            ? 'Nada borrado: quita --probar para hacerlo de verdad.'
            : sprintf('Borradas %d subidas y %s.', $abandonadas->count(), $this->enMegas($bytes)));

        return self::SUCCESS;
    }

    private function enMegas(int $bytes): string
    {
        return $bytes < 1048576
            ? round($bytes / 1024).' KB'
            : round($bytes / 1048576, 1).' MB';
    }
}
