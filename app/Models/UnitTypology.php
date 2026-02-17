<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitTypology extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'bedrooms',
        'bathrooms',
        'area_m2',
        'description',
        'floor_plan_path',
    ];

    protected $casts = [
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'area_m2' => 'float',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'typology_id');
    }
}
