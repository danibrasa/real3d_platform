<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViewerEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'session_id',
        'event_type',
        'event_data',
        'unit_id',
        'ip',
        'user_agent',
        'device_type',
        'referrer',
        'created_at',
    ];

    protected $casts = [
        'event_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
