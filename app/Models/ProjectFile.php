<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFile extends Model
{
    protected $fillable = [
        'project_id',
        'file_type',
        'original_name',
        'storage_path',
        'mime_type',
        'file_size',
        'upload_complete',
    ];

    protected $casts = [
        'upload_complete' => 'boolean',
        'file_size' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
