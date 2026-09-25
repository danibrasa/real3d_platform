<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointOfInterest extends Model
{
    protected $table = 'points_of_interest';

    protected $fillable = [
        'project_id',
        'category',
        'name',
        'name_en',
        'latitude',
        'longitude',
        'distance',
        'sort_order',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'sort_order' => 'integer',
    ];

    public const CATEGORIES = [
        'beach'       => ['es' => 'Playa',             'en' => 'Beach'],
        'airport'     => ['es' => 'Aeropuerto',        'en' => 'Airport'],
        'hospital'    => ['es' => 'Hospital',           'en' => 'Hospital'],
        'shopping'    => ['es' => 'Centro comercial',   'en' => 'Shopping center'],
        'restaurant'  => ['es' => 'Restaurante',        'en' => 'Restaurant'],
        'school'      => ['es' => 'Escuela',            'en' => 'School/University'],
        'golf'        => ['es' => 'Golf',               'en' => 'Golf'],
        'marina'      => ['es' => 'Marina',             'en' => 'Marina'],
        'supermarket' => ['es' => 'Supermercado',       'en' => 'Supermarket'],
        'gas_station' => ['es' => 'Gasolinera',         'en' => 'Gas station'],
        'pharmacy'    => ['es' => 'Farmacia',           'en' => 'Pharmacy'],
        'park'        => ['es' => 'Parque',             'en' => 'Park'],
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    protected function translatedName(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'en' && $this->name_en
                ? $this->name_en
                : $this->name,
        );
    }

    public function categoryLabel(): string
    {
        $locale = app()->getLocale() === 'en' ? 'en' : 'es';
        return self::CATEGORIES[$this->category][$locale] ?? $this->category;
    }
}
