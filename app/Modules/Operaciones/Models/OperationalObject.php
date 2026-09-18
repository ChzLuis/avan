<?php

namespace App\Modules\Operaciones\Models;

use App\Models\Employee;
use App\Models\Project;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OperationalObject extends Model
{
    protected $fillable = [
        'project_id', 'map_id', 'responsible_id',
        'objecteable_type', 'objecteable_id',
        'type', 'label', 'icon', 'shape', 'color',
        'status', 'priority', 'zone',
        'pos_x', 'pos_y', 'width', 'height', 'rotation',
        'started_at', 'capacity', 'current_amount', 'cost',
        'config', 'alerts', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'config'       => 'array',
        'alerts'       => 'array',
        'is_active'    => 'boolean',
        'started_at'   => 'datetime',
        'current_amount' => 'decimal:2',
        'cost'         => 'decimal:2',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(OperationalMap::class, 'map_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsible_id');
    }

    public function objecteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function events(): HasMany
    {
        return $this->hasMany(OperationalEvent::class, 'object_id')->latest('occurred_at');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(OperationalRequest::class, 'object_id');
    }

    public function pendingRequests(): HasMany
    {
        return $this->requests()->whereIn('status', ['pending', 'in_progress']);
    }

    // ── Computed ──────────────────────────────────────────────────────────────

    /** Minutos en el estado actual */
    public function getElapsedMinutesAttribute(): int
    {
        if (!$this->started_at) return 0;
        return (int) $this->started_at->diffInMinutes(now());
    }

    /** Formato legible del cronómetro: "1h 23m" o "52 min" */
    public function getElapsedLabelAttribute(): string
    {
        $m = $this->elapsed_minutes;
        if ($m < 60) return $m . ' min';
        $h = intdiv($m, 60);
        $r = $m % 60;
        return $h . 'h ' . $r . 'm';
    }

    /** ¿Tiene alertas activas? */
    public function hasAlerts(): bool
    {
        return !empty($this->alerts);
    }

    // ── Mutadores de estado ───────────────────────────────────────────────────

    public function changeStatus(string $newStatus, ?int $userId = null): void
    {
        $old = $this->status;
        $this->status     = $newStatus;
        $this->started_at = now();

        // Limpiar monto al liberar
        if (in_array($newStatus, ['libre', 'disponible'])) {
            $this->current_amount = 0;
            $this->alerts         = [];
        }

        $this->save();

        OperationalEvent::create([
            'project_id'  => $this->project_id,
            'object_id'   => $this->id,
            'user_id'     => $userId,
            'type'        => 'status_change',
            'status_from' => $old,
            'status_to'   => $newStatus,
            'occurred_at' => now(),
        ]);
    }

    public function addAlert(string $text): void
    {
        $alerts   = $this->alerts ?? [];
        $alerts[] = ['text' => $text, 'at' => now()->toISOString()];
        $this->update(['alerts' => $alerts]);
    }

    public function clearAlerts(): void
    {
        $this->update(['alerts' => []]);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithAlerts($query)
    {
        return $query->whereNotNull('alerts')->where('alerts', '!=', '[]');
    }

    // ── Helpers de presentación ───────────────────────────────────────────────

    /** Color de badge según estado */
    public function statusColor(): string
    {
        return match($this->status) {
            'libre', 'disponible'     => '#00B26B',
            'ocupado', 'en_consulta'  => '#3B82F6',
            'reservado'               => '#8B5CF6',
            'alerta', 'urgente'       => '#EF4444',
            'bloqueado'               => '#6B7280',
            'mantenimiento'           => '#F59E0B',
            'en_ruta'                 => '#06B6D4',
            'entregado'               => '#10B981',
            'housekeeping'            => '#F97316',
            default                   => '#94A3B8',
        };
    }

    /** Icono por tipo */
    public function typeIcon(): string
    {
        return match($this->type) {
            'mesa'         => '🪑',
            'consultorio'  => '🏥',
            'habitacion'   => '🛏',
            'vehiculo'     => '🚚',
            'maquina'      => '⚙️',
            'estante'      => '📦',
            'zona'         => '📍',
            'escritorio'   => '💻',
            'sala'         => '🚪',
            'almacen'      => '🏭',
            'empleado'     => '👤',
            default        => '⬜',
        };
    }

    /** Serialización para el mapa (JSON al frontend) */
    public function toMapArray(): array
    {
        return [
            'id'              => $this->id,
            'type'            => $this->type,
            'label'           => $this->label,
            'icon'            => $this->icon ?? $this->typeIcon(),
            'shape'           => $this->shape,
            'color'           => $this->color,
            'status'          => $this->status,
            'status_color'    => $this->statusColor(),
            'priority'        => $this->priority,
            'zone'            => $this->zone,
            'pos_x'           => $this->pos_x,
            'pos_y'           => $this->pos_y,
            'width'           => $this->width,
            'height'          => $this->height,
            'rotation'        => $this->rotation,
            'elapsed_minutes' => $this->elapsed_minutes,
            'elapsed_label'   => $this->elapsed_label,
            'current_amount'  => (float) $this->current_amount,
            'alerts'          => $this->alerts ?? [],
            'alerts_count'    => count($this->alerts ?? []),
            'responsible'     => $this->responsible ? [
                'id'   => $this->responsible->id,
                'name' => $this->responsible->name,
            ] : null,
            'capacity'        => $this->capacity,
            'config'          => $this->config ?? [],
        ];
    }
}
