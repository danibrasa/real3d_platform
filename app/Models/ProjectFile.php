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

    /**
     * Lo que cambia cuando cambia el fichero: va en la direccion (?v=) y en
     * el ETag. Con ella en la direccion, la respuesta puede ser inmutable un
     * ano, porque una direccion nueva es un fichero nuevo.
     */
    public function version(): string
    {
        return $this->id.'-'.($this->updated_at?->timestamp ?? 0);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
