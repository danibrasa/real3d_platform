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

    public function update(): BelongsTo
    {
        return $this->belongsTo(ConstructionUpdate::class, 'construction_update_id');
    }
}
