<?php

namespace App\Models;

use App\Support\Inversion\CalculoInversion;
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
        'identifier',
        'floor',
        'bedrooms',
        'bathrooms',
        'area_m2',
        'price',
        'status',
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

    /**
     * Las cuentas de inversion de esta vivienda, o null si el proyecto no
     * declara precio por noche ni ocupacion.
     */
    public function investment(): ?CalculoInversion
    {
        $calculo = CalculoInversion::paraVivienda($this);

        return $calculo->hayDatos() ? $calculo : null;
    }

    /** Rentabilidad anual, lista para un atributo HTML. */
    public function getInvestmentYieldAttribute(): string
    {
        $i = $this->investment();

        return $i ? (string) round($i->rentabilidad(), 1) : '';
    }

    /** Ingreso neto mensual, listo para un atributo HTML. */
    public function getInvestmentMonthlyAttribute(): string
    {
        $i = $this->investment();

        return $i ? (string) round($i->ingresoMensual()) : '';
    }

    /** Años hasta recuperar la inversion, listos para un atributo HTML. */
    public function getInvestmentPaybackAttribute(): string
    {
        $i = $this->investment();
        $anos = $i?->anosDeRetorno();

        return $anos !== null ? (string) round($anos, 1) : '';
    }
}
