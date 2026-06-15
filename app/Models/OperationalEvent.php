<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalEvent extends Model
{
    protected $fillable = [
        'project_id', 'object_id', 'user_id',
        'type', 'status_from', 'status_to',
        'note', 'payload', 'occurred_at',
    ];

    protected $casts = [
        'payload'     => 'array',
        'occurred_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function object(): BelongsTo
    {
        return $this->belongsTo(OperationalObject::class, 'object_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return match($this->type) {
            'status_change'      => 'Cambio de estado',
            'alert_added'        => 'Alerta agregada',
            'alert_cleared'      => 'Alertas limpiadas',
            'note'               => 'Nota',
            'request'            => 'Solicitud',
            'responsible_change' => 'Responsable cambiado',
            'amount_update'      => 'Monto actualizado',
            'merge'              => 'Objetos unidos',
            'split'              => 'Objetos separados',
            default              => $this->type,
        };
    }
}
