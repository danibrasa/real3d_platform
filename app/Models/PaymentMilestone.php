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
        'sort_order',
    ];

    protected $casts = [
        'percentage' => 'float',
        'sort_order' => 'integer',
    ];

    public function paymentPlan(): BelongsTo
    {
        return $this->belongsTo(PaymentPlan::class);
    }
}
