<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    const STATUS_PENDING  = 'pending';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_CLOSED   = 'closed';

    const DISPOSITIONS = [
        'completado'      => 'Atendido con éxito',
        'no_interesado'   => 'No interesado',
        'sin_respuesta'   => 'Sin respuesta',
        'no_califica'     => 'No califica',
        'seguimiento'     => 'Requiere seguimiento',
        'tiempo_expirado' => 'Tiempo expirado',
    ];

    protected $fillable = [
        'cliente_telefono',
        'advisor_id',
        'accepted_at',
        'status',
        'conversation_duration',
        'conversation_expires_at',
        'disposition',
        'warning_sent_at',
        'warning_count',
        'esperando_nota',
    ];

    protected $casts = [
        'accepted_at'             => 'datetime',
        'conversation_expires_at' => 'datetime',
        'warning_sent_at'         => 'datetime',
        'esperando_nota'          => 'boolean',
    ];

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isConversationActive(): bool
    {
        return $this->status === self::STATUS_ASSIGNED
            && $this->accepted_at !== null
            && $this->conversation_expires_at !== null
            && $this->conversation_expires_at->isFuture();
    }

    public function advisor()
    {
        return $this->belongsTo(Advisor::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', self::STATUS_ASSIGNED);
    }
}
