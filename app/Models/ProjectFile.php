<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFile extends Model
{
    protected $fillable = [
        'variantes',
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
        'variantes' => 'array',
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

    /** La ruta en disco del tamaño pedido, o la del original si no lo hay. */
    public function rutaPara(?string $tam): string
    {
        return ($tam && isset($this->variantes[$tam])) ? $this->variantes[$tam] : $this->storage_path;
    }

    public function tieneVariante(?string $tam): bool
    {
        return (bool) ($tam && isset($this->variantes[$tam]));
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
