<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectSettingsController extends Controller
{
    public function update(Request $request, Project $project)
    {
        Gate::authorize('edit-viewer-settings');
        $validated = $request->validate([
            'model_rotation' => 'numeric|min:0|max:360',
            'model_scale' => 'numeric|min:1|max:200',
            'model_elevation' => 'numeric|min:-50|max:50',
            'ground_height' => 'numeric|min:-100|max:100',
            'ground_texture_type' => 'in:grass,concrete,dirt,custom',
            'ground_opacity' => 'numeric|min:0|max:100',
            'ground_visible' => 'boolean',
            'video_opacity' => 'numeric|min:0|max:100',
            'video_autoplay' => 'boolean',
            'background_type' => 'in:video,image',
            'lighting_preset' => 'in:morning,noon,evening',
            'camera_position_x' => 'numeric',
            'camera_position_y' => 'numeric',
            'camera_position_z' => 'numeric',
            'camera_target_x' => 'numeric',
            'camera_target_y' => 'numeric',
            'camera_target_z' => 'numeric',
            'wireframe' => 'boolean',
            'real_scale_enabled' => 'boolean',
            'real_dimension_meters' => 'nullable|numeric|min:0.1|max:9999',
            'reference_axis' => 'in:height,width,depth',
        ]);

        $project->settings()->updateOrCreate(
            ['project_id' => $project->id],
            $validated
        );

        return response()->json(['message' => 'Settings guardados.']);
    }
}
