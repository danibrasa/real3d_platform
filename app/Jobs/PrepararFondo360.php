<?php

namespace App\Jobs;

use App\Models\ProjectFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Del fondo 360 que sube el equipo se saca una version ligera para empezar.
 *
 * La foto 360 de un proyecto real pesa 21 MB (8K). El visor la cargaba
 * entera antes de enseñar nada: en 4G, medio minuto de barra. Con una
 * version de 2K (unos 600 KB) el visor pinta en segundos y cambia a la
 * grande cuando llega, sin que el comprador lo note.
 *
 * Se hace aqui, al subir, una vez: hacerlo al servir seria hacerlo en cada
 * visita. Si el original ya es pequeño no hay nada que sacar, y si no se
 * puede leer -- no es una imagen, esta rota -- se dice y se sigue sin
 * variante: el visor cae al original, que es lo que habia.
 */
class PrepararFondo360 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Ancho de la version ligera. 2K equirectangular: suficiente para empezar a mirar. */
    public const ANCHO_LIGERO = 2048;

    public int $tries = 1;

    public function __construct(public int $ficheroId) {}

    public function handle(): void
    {
        $fichero = ProjectFile::find($this->ficheroId);
        if (! $fichero || $fichero->file_type !== 'image_360' || ! $fichero->storage_path) {
            return;
        }

        $ligera = self::reducir(Storage::path($fichero->storage_path), self::ANCHO_LIGERO);
        if ($ligera === null) {
            return;
        }

        $destino = preg_replace('/(\.[a-z0-9]+)?$/i', '', $fichero->storage_path, 1).'-2k.jpg';
        Storage::put($destino, $ligera);

        $fichero->forceFill(['variantes' => ['2k' => $destino]])->save();
    }

    /**
     * El JPEG reducido a ese ancho, o null si no hace falta o no se puede.
     *
     * Publica y sin base para poder probarla con una imagen de verdad.
     * Decodificar un 8K son 130 MB de memoria: se pide sitio antes.
     */
    public static function reducir(string $ruta, int $ancho): ?string
    {
        if (! is_file($ruta)) {
            return null;
        }

        $info = @getimagesize($ruta);
        if (! $info || $info[0] <= $ancho) {
            return null;
        }

        ini_set('memory_limit', '1024M');

        $original = @imagecreatefromstring((string) file_get_contents($ruta));
        if (! $original) {
            report(new \RuntimeException("no se pudo leer el fondo 360 de {$ruta}"));

            return null;
        }

        $alto = (int) round($info[1] * $ancho / $info[0]);
        $reducida = imagecreatetruecolor($ancho, $alto);
        imagecopyresampled($reducida, $original, 0, 0, 0, 0, $ancho, $alto, $info[0], $info[1]);
        imagedestroy($original);

        ob_start();
        imagejpeg($reducida, null, 82);
        imagedestroy($reducida);

        return ob_get_clean();
    }
}
