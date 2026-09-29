<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PrepararFondo360;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\UploadChunk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    public function initUpload(Request $request, Project $project)
    {
        Gate::authorize('upload-files');
        $validated = $request->validate([
            'file_type' => 'required|in:video_360,model_3d,ground_texture,thumbnail,image_360',
            'original_name' => 'required|string|max:255',
            'total_size' => 'required|integer|min:1',
            'total_chunks' => 'required|integer|min:1',
        ]);

        // Lo que no cabe, no entra: un video 360 de 315 MB son cinco minutos
        // de 4G para un comprador, y en produccion habia cuatro copias del
        // mismo. Se dice cuanto es el tope, que la salida es recomprimir.
        $maximoMb = config('ficheros.maximos_mb')[$validated['file_type']] ?? null;
        if ($maximoMb && $validated['total_size'] > $maximoMb * 1048576) {
            return response()->json([
                'error' => __('ficheros.demasiado_grande', ['tipo' => $validated['file_type'], 'mb' => $maximoMb]),
            ], 422);
        }

        // Mirar la cuota tambien aqui ahorra subir trescientos megas para que
        // al final se rechacen. La comprobacion de verdad sigue estando al
        // completar, porque entre una y otra pueden subirse otros ficheros.
        $agency = $project->assignedAgencies()->first();
        if ($agency?->companyProfile
            && ! $agency->companyProfile->hasStorageAvailable($validated['total_size'])) {
            return response()->json(['error' => __('billing.storage_quota_exceeded')], 403);
        }

        // Si ya hay una subida a medias de este mismo fichero, se continua
        // donde se quedo en vez de empezar de cero.
        //
        // Un video 360 de trescientos megas son sesenta trozos. Si la conexion
        // se corta en el cuarenta, hasta ahora se tiraban los cuarenta y habia
        // que subirlo entero otra vez: en produccion, 12 de 28 subidas nunca
        // llegaron a terminar.
        $aMedias = UploadChunk::where('project_id', $project->id)
            ->where('file_type', $validated['file_type'])
            ->where('original_name', $validated['original_name'])
            ->where('total_size', $validated['total_size'])
            ->where('completed', false)
            ->where('created_at', '>', now()->subHours(24))
            ->latest('id')
            ->first();

        if ($aMedias) {
            return response()->json([
                'upload_id' => $aMedias->upload_id,
                'trozos_ya_subidos' => $this->trozosPresentes($aMedias),
            ]);
        }

        $uploadId = Str::uuid()->toString();
        $tempDir = "uploads/chunks/{$uploadId}";
        Storage::makeDirectory($tempDir);

        $upload = UploadChunk::create([
            'upload_id' => $uploadId,
            'project_id' => $project->id,
            'file_type' => $validated['file_type'],
            'original_name' => $validated['original_name'],
            'total_chunks' => $validated['total_chunks'],
            'total_size' => $validated['total_size'],
            'temp_directory' => $tempDir,
        ]);

        return response()->json(['upload_id' => $uploadId, 'trozos_ya_subidos' => []]);
    }

    public function uploadChunk(Request $request, Project $project)
    {
        Gate::authorize('upload-files');

        $request->validate([
            'upload_id' => 'required|uuid',
            'chunk_index' => 'required|integer|min:0',
            'chunk' => 'required|file',
        ]);

        $upload = UploadChunk::where('upload_id', $request->upload_id)
            ->where('project_id', $project->id)
            ->firstOrFail();

        $chunkFile = $request->file('chunk');
        $chunkPath = $upload->temp_directory."/chunk_{$request->chunk_index}";
        Storage::put($chunkPath, file_get_contents($chunkFile->getRealPath()));

        // Se cuenta lo que hay en disco, no las veces que se ha llamado: el
        // navegador reintenta un trozo hasta tres veces, y con increment() cada
        // reintento sumaba uno, asi que el contador acababa por encima del total.
        $upload->update(['received_chunks' => count($this->trozosPresentes($upload))]);

        return response()->json([
            'received' => $upload->received_chunks,
            'total' => $upload->total_chunks,
        ]);
    }

    /**
     * Que trozos hay ya en disco, por su numero.
     *
     * @return array<int, int>
     */
    private function trozosPresentes(UploadChunk $upload): array
    {
        $numeros = [];

        foreach (Storage::files($upload->temp_directory) as $ruta) {
            if (preg_match('/chunk_(\d+)$/', $ruta, $coincidencias)) {
                $numeros[] = (int) $coincidencias[1];
            }
        }

        sort($numeros);

        return $numeros;
    }

    public function completeUpload(Request $request, Project $project)
    {
        Gate::authorize('upload-files');

        $request->validate([
            'upload_id' => 'required|uuid',
        ]);

        $upload = UploadChunk::where('upload_id', $request->upload_id)
            ->where('project_id', $project->id)
            ->firstOrFail();

        // Storage quota check for inmobiliaria tenants
        $agency = $project->assignedAgencies()->first();
        if ($agency) {
            $company = $agency->companyProfile;
            if ($company && ! $company->hasStorageAvailable($upload->total_size ?? 0)) {
                // Sin esto, cada intento rechazado se quedaba en disco para
                // siempre: quien se pasa de cuota reintenta, y cada reintento
                // deja el fichero entero sin que nada lo recoja.
                Storage::deleteDirectory($upload->temp_directory);
                $upload->update(['completed' => false, 'total_chunks' => 0]);

                return response()->json(['error' => __('billing.storage_quota_exceeded')], 403);
            }
        }

        // Determine destination path
        $typeDir = match ($upload->file_type) {
            'video_360' => 'video',
            'model_3d' => 'model',
            'ground_texture' => 'texture',
            'thumbnail' => 'thumbnail',
            'image_360' => 'image360',
        };
        $destDir = "projects/{$project->id}/{$typeDir}";
        Storage::makeDirectory($destDir);
        $destPath = "{$destDir}/{$upload->original_name}";

        // El registro anterior se borra ANTES de escribir, no despues.
        //
        // Al reves -que es como estaba- el fichero nuevo se escribia en la ruta
        // de destino y acto seguido se borraba el registro viejo, que apuntaba a
        // ESA MISMA ruta cuando el nombre coincidia. Es decir: el equipo corregia
        // un modelo, lo volvia a subir con el mismo nombre, y se quedaba sin
        // fichero y sin registro. Lo encontro el primer test que se le escribio
        // a este camino.
        $anterior = ProjectFile::where('project_id', $project->id)
            ->where('file_type', $upload->file_type)
            ->first();

        if ($anterior) {
            if ($anterior->storage_path !== $destPath) {
                Storage::delete($anterior->storage_path);
            }
            $anterior->delete();
        }

        // Assemble chunks
        $destFullPath = Storage::path($destPath);
        $outFile = fopen($destFullPath, 'wb');

        for ($i = 0; $i < $upload->total_chunks; $i++) {
            $chunkPath = Storage::path($upload->temp_directory."/chunk_{$i}");
            if (! file_exists($chunkPath)) {
                fclose($outFile);

                return response()->json(['error' => "Chunk {$i} missing"], 422);
            }
            $chunkData = file_get_contents($chunkPath);
            fwrite($outFile, $chunkData);
        }
        fclose($outFile);

        // El tope, contra los bytes que han llegado y no contra lo que el
        // cliente declaro al empezar: declarar poco y mandar mas trozos se
        // colaba. Lo que no cabe se tira, con sus trozos.
        $maximoMb = config('ficheros.maximos_mb')[$upload->file_type] ?? null;
        if ($maximoMb && filesize($destFullPath) > $maximoMb * 1048576) {
            Storage::delete($destPath);
            Storage::deleteDirectory($upload->temp_directory);
            $upload->update(['completed' => false, 'total_chunks' => 0]);

            return response()->json([
                'error' => __('ficheros.demasiado_grande', ['tipo' => $upload->file_type, 'mb' => $maximoMb]),
            ], 422);
        }

        // Create file record
        $ficheroNuevo = ProjectFile::create([
            'project_id' => $project->id,
            'file_type' => $upload->file_type,
            'original_name' => $upload->original_name,
            'storage_path' => $destPath,
            'mime_type' => $this->guessMimeType($upload->original_name),
            'file_size' => filesize($destFullPath),
            'upload_complete' => true,
        ]);

        // Del fondo 360 se saca una version ligera para que el visor empiece
        // a pintar en segundos y no en medio minuto. En cola: decodificar un
        // 8K tarda y no es cosa de la peticion.
        if ($ficheroNuevo->file_type === 'image_360') {
            PrepararFondo360::dispatch($ficheroNuevo->id);
        }

        // Recalculate storage for tenant
        $agency = $project->assignedAgencies()->first();
        if ($agency?->companyProfile) {
            $agency->companyProfile->recalculateStorage();
        }

        // Cleanup temp chunks
        Storage::deleteDirectory($upload->temp_directory);
        $upload->update(['completed' => true]);

        return response()->json(['message' => 'Upload completo.', 'file_type' => $upload->file_type]);
    }

    private function guessMimeType(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($ext) {
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'glb' => 'model/gltf-binary',
            'gltf' => 'model/gltf+json',
            'fbx' => 'application/octet-stream',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
