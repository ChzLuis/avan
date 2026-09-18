<?php

namespace App\Modules\Operaciones\Models;

use App\Models\Employee;
use App\Models\Project;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalRequest extends Model
{
    protected $fillable = [
        'project_id', 'object_id', 'assigned_to', 'created_by',
        'type', 'title', 'description', 'status', 'priority', 'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function object(): BelongsTo
    {
        return $this->belongsTo(OperationalObject::class, 'object_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        $labels = [
            'cuenta'        => 'Solicitud de cuenta',
            'limpieza'      => 'Limpieza requerida',
            'mantenimiento' => 'Mantenimiento',
            'reposicion'    => 'Reposicion de stock',
            'traslado'      => 'Traslado',
            'urgente'       => 'URGENTE',
            'nota'          => 'Nota interna',
        ];
        return $labels[$this->type] ?? $this->type;
    }

    public function statusColor(): string
    {
        $colors = [
            'pending'     => '#F59E0B',
            'in_progress' => '#3B82F6',
            'done'        => '#00B26B',
            'cancelled'   => '#6B7280',
        ];
        return $colors[$this->status] ?? '#94A3B8';
    }
}
