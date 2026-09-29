<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Una pieza del material que la promotora entrega para montar el visor.
 *
 * O un fichero subido, o un enlace: los planos y los renders caben por el
 * formulario; un video 360 o un modelo de cientos de megas, no, y para eso
 * vale un enlace a donde lo tenga. Lo que importa es que quede aqui, con
 * fecha, y no en un correo que nadie encuentra.
 */
class MaterialDelProyecto extends Model
{
    protected $table = 'project_material';

    /** Lo que hace falta para montar un visor, en el orden en que se pide. */
    public const TIPOS = ['planos', 'renders', 'imagen_360', 'video_360', 'modelo', 'otro'];

    /** Sin esto no se puede montar nada; lo demas ayuda. */
    public const IMPRESCINDIBLES = ['planos', 'renders'];

    protected $fillable = [
        'project_id', 'tipo', 'original_name', 'storage_path', 'enlace',
        'file_size', 'mime_type', 'subido_por',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    protected static function booted(): void
    {
        // El fichero se va con la fila, se borre desde donde se borre.
        static::deleted(function (MaterialDelProyecto $material) {
            if ($material->storage_path) {
                Storage::delete($material->storage_path);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function esEnlace(): bool
    {
        return $this->enlace !== null;
    }
}
