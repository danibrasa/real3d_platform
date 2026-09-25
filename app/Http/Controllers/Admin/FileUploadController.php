<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        return response()->json(['upload_id' => $uploadId]);
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

        $upload->increment('received_chunks');

        return response()->json([
            'received' => $upload->received_chunks,
            'total' => $upload->total_chunks,
        ]);
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

        // Delete old file record for this type if exists
        $oldFile = ProjectFile::where('project_id', $project->id)
            ->where('file_type', $upload->file_type)
            ->first();
        if ($oldFile) {
            Storage::delete($oldFile->storage_path);
            $oldFile->delete();
        }

        // Create file record
        ProjectFile::create([
            'project_id' => $project->id,
            'file_type' => $upload->file_type,
            'original_name' => $upload->original_name,
            'storage_path' => $destPath,
            'mime_type' => $this->guessMimeType($upload->original_name),
            'file_size' => filesize($destFullPath),
            'upload_complete' => true,
        ]);

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
