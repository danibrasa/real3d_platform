<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMilestone extends Model
{
    protected $fillable = [
        'payment_plan_id',
        'name',
        'percentage',
        'description',
        'due_description',
        'milestone_type',
        'sort_order',
    ];

    protected $casts = [
        'percentage' => 'float',
        'sort_order' => 'integer',
    ];

    const TYPE_ICONS = [
        'reservation' => 'key',
        'signing' => 'document',
        'construction' => 'building',
        'delivery' => 'home',
        'other' => 'circle',
    ];

    const TYPE_COLORS = [
        'reservation' => 'blue',
        'signing' => 'blue',
        'construction' => 'amber',
        'delivery' => 'green',
        'other' => 'gray',
    ];

    /**
     * Get the phase color for this milestone.
     * If type is 'other', uses heuristic based on position in the plan.
     */
    public function phaseColor(?int $index = null, ?int $total = null): string
    {
        $type = $this->milestone_type ?? 'other';

        if ($type !== 'other') {
            return self::TYPE_COLORS[$type] ?? 'gray';
        }

        // Heuristic fallback for 'other' type
        if ($index !== null && $total !== null && $total > 1) {
            if ($index === 0) {
                return 'blue';
            }
            if ($index === $total - 1) {
                return 'green';
            }

            return 'amber';
        }

        return 'gray';
    }

    public function paymentPlan(): BelongsTo
    {
        return $this->belongsTo(PaymentPlan::class);
    }
}
