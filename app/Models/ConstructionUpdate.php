<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConstructionUpdate extends Model
{
    protected $fillable = [
        'project_id', 'construction_phase_id', 'title', 'description',
        'date', 'progress_percentage', 'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'progress_percentage' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ConstructionPhase::class, 'construction_phase_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ConstructionUpdateImage::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
