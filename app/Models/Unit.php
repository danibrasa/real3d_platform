<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use Auditable;

    protected $fillable = [
        'project_id',
        'typology_id',
        'buyer_id',
        'identifier',
        'floor',
        'bedrooms',
        'bathrooms',
        'area_m2',
        'price',
        'status',
        'sold_at',
        'floor_plan_path',
        'notes',
        'sort_order',
        'bbox_center_x',
        'bbox_center_y',
        'bbox_center_z',
        'bbox_size_x',
        'bbox_size_y',
        'bbox_size_z',
    ];

    protected $casts = [
        'floor' => 'integer',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'area_m2' => 'float',
        'price' => 'float',
        'sort_order' => 'integer',
        'sold_at' => 'date',
        'bbox_center_x' => 'float',
        'bbox_center_y' => 'float',
        'bbox_center_z' => 'float',
        'bbox_size_x' => 'float',
        'bbox_size_y' => 'float',
        'bbox_size_z' => 'float',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function typology(): BelongsTo
    {
        return $this->belongsTo(UnitTypology::class, 'typology_id');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    protected function hasBbox(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->bbox_center_x !== null && $this->bbox_size_x !== null,
        );
    }

    protected function floorPlan(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->floor_plan_path ?? $this->typology?->floor_plan_path,
        );
    }

    protected function formattedPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => 'USD '.number_format($this->price, 0, '.', ','),
        );
    }

    /** Quien ha comprado esta vivienda, si ya esta vendida. */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /** Los pagos que el comprador ha hecho de verdad. */
    public function payments(): HasMany
    {
        return $this->hasMany(BuyerPayment::class)->orderBy('paid_on');
    }

    /** Suma de lo pagado hasta hoy. */
    public function totalPaid(): float
    {
        return (float) ($this->relationLoaded('payments')
            ? $this->payments->sum('amount')
            : $this->payments()->sum('amount'));
    }

    /** Lo que queda por pagar segun el precio de la vivienda. */
    public function pendingAmount(): float
    {
        return max(0, (float) $this->price - $this->totalPaid());
    }

    /** Porcentaje pagado, para la barra de progreso. */
    public function paidPercent(): float
    {
        return $this->price > 0 ? min(100, $this->totalPaid() / $this->price * 100) : 0.0;
    }
}
