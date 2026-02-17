<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectGalleryImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    public function store(Request $request, Project $project)
    {
        Gate::authorize('manage-gallery', $project);

        $request->validate([
            'images' => 'required|array|min:1',
            'images.*' => 'image|max:5120',
        ]);

        $maxSort = $project->galleryImages()->max('sort_order') ?? 0;

        foreach ($request->file('images') as $image) {
            $path = $image->store("projects/{$project->id}/gallery");
            ProjectGalleryImage::create([
                'project_id' => $project->id,
                'image_path' => $path,
                'sort_order' => ++$maxSort,
            ]);
        }

        return back()->with('success', 'Imagenes subidas.');
    }

    public function destroy(Project $project, ProjectGalleryImage $image)
    {
        Gate::authorize('manage-gallery', $project);

        Storage::delete($image->image_path);
        $image->delete();

        return back()->with('success', 'Imagen eliminada.');
    }
}
