<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadChunk extends Model
{
    protected $fillable = [
        "upload_id",
        "project_id",
        "file_type",
        "original_name",
        "total_chunks",
        "received_chunks",
        "total_size",
        "temp_directory",
        "completed",
    ];

    protected $casts = [
        "completed" => "boolean",
        "total_chunks" => "integer",
        "received_chunks" => "integer",
        "total_size" => "integer",
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
