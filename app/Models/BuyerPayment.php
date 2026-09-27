<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un pago que el comprador ya ha hecho.
 *
 * Distinto de PaymentMilestone, que es el plan teorico del proyecto: esto es lo
 * que se pago de verdad, cuando y con que referencia. Es lo que quiere ver
 * quien lleva dos años transfiriendo dinero a otro pais.
 */
class BuyerPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'payment_milestone_id',
        'concept',
        'amount',
        'paid_on',
        'reference',
        'notes',
        'registered_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_on' => 'date',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(PaymentMilestone::class, 'payment_milestone_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
