<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code', 'symbol', 'name', 'exchange_rate', 'is_default', 'is_active', 'decimal_places',
    ];

    protected $casts = [
        'exchange_rate' => 'float',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'decimal_places' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
