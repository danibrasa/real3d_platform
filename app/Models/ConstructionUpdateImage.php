<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConstructionUpdateImage extends Model
{
    protected $fillable = [
        'construction_update_id', 'image_path', 'caption', 'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * La actualizacion de obra a la que pertenece esta foto.
     *
     * Se llama constructionUpdate y no update: una relacion llamada update()
     * choca con el metodo update() de Eloquent y deja el modelo inutilizable
     * (PHP rechaza la clase entera por firma incompatible). Nadie usaba la
     * relacion, asi que el fallo estaba latente.
     */
    public function constructionUpdate(): BelongsTo
    {
        return $this->belongsTo(ConstructionUpdate::class, 'construction_update_id');
    }
}
