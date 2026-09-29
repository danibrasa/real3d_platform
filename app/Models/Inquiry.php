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

    protected static function booted(): void
    {
        // La primera vez que el lead deja de ser nuevo, por el camino que sea
        // (el panel, un comando, un job): estado_en se mueve con cada cambio
        // de estado, y "contestado en el dia" mide la primera respuesta.
        static::saving(function (Inquiry $inquiry) {
            if ($inquiry->contestado_en === null && $inquiry->isDirty('estado')
                && $inquiry->estado !== null && $inquiry->estado !== 'nuevo'
                && in_array($inquiry->getOriginal('estado'), [null, 'nuevo'], true)) {
                $inquiry->contestado_en = now();
            }
        });
    }

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
