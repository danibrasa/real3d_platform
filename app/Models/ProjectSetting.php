<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSetting extends Model
{
    protected $fillable = [
        "project_id",
        "model_rotation",
        "model_scale",
        "model_elevation",
        "ground_height",
        "ground_texture_type",
        "ground_opacity",
        "ground_visible",
        "video_opacity",
        "video_autoplay",
        "background_type",
        "lighting_preset",
        "camera_position_x",
        "camera_position_y",
        "camera_position_z",
        "camera_target_x",
        "camera_target_y",
        "camera_target_z",
        "wireframe",
    ];

    protected $casts = [
        "model_rotation" => "float",
        "model_scale" => "float",
        "model_elevation" => "float",
        "ground_height" => "float",
        "ground_opacity" => "float",
        "ground_visible" => "boolean",
        "video_opacity" => "float",
        "video_autoplay" => "boolean",
        "background_type" => "string",
        "camera_position_x" => "float",
        "camera_position_y" => "float",
        "camera_position_z" => "float",
        "camera_target_x" => "float",
        "camera_target_y" => "float",
        "camera_target_z" => "float",
        "wireframe" => "boolean",
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
