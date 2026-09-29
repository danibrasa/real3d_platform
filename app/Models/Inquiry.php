<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    /**
     * Por donde pasa un lead. "Leido" no decia nada de lo que importa: si
     * alguien le ha escrito, si va a visitar, si compro o si era ruido.
     */
    public const NUEVO = 'nuevo';

    public const ESTADOS = ['nuevo', 'contactado', 'visita', 'cerrado', 'descartado'];

    protected $fillable = [
        'project_id',
        'unit_id',
        'name',
        'email',
        'phone',
        'message',
        'read',
        'estado',
        'nota',
    ];

    protected $casts = [
        'read' => 'boolean',
        'estado_en' => 'datetime',
        'contestado_en' => 'datetime',
        'avisado_sin_atender_en' => 'datetime',
    ];

    public function atendidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendido_por');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
