<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentPlan extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'is_default',
        'sort_order',
        'discount_type',
        'discount_value',
        'discount_label',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'discount_value' => 'float',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(PaymentMilestone::class)->orderBy('sort_order');
    }

    public function effectivePrice(float $basePrice): float
    {
        if (!$this->discount_type || !$this->discount_value) {
            return $basePrice;
        }

        if ($this->discount_type === 'percentage') {
            return round($basePrice * (1 - $this->discount_value / 100), 2);
        }

        return max(0, $basePrice - $this->discount_value);
    }

    public function hasDiscount(): bool
    {
        return $this->discount_type !== null && $this->discount_value > 0;
    }

    public function discountDisplayLabel(): string
    {
        return $this->discount_label ?: __('landing.discount');
    }
}
