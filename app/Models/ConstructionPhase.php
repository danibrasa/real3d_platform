<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConstructionPhase extends Model
{
    protected $fillable = [
        'project_id', 'name', 'description', 'target_percentage', 'status', 'sort_order',
    ];

    protected $casts = [
        'target_percentage' => 'integer',
        'sort_order' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ConstructionUpdate::class)->orderByDesc('date');
    }

    public function latestUpdate()
    {
        return $this->hasOne(ConstructionUpdate::class)->latestOfMany('date');
    }
}
