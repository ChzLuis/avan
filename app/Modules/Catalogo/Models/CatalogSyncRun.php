<?php

namespace App\Modules\Catalogo\Models;

use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Historial de una ejecución de sincronización, con contadores para auditoría. */
class CatalogSyncRun extends Model
{
    protected $fillable = [
        'integration_id', 'mode', 'trigger', 'status',
        'started_at', 'completed_at', 'cursor_start', 'cursor_end',
        'pages_processed', 'items_received', 'items_created', 'items_updated',
        'items_unchanged', 'items_skipped', 'items_failed', 'items_deactivated',
        'error_summary', 'triggered_by',
    ];

    protected $casts = [
        'cursor_start' => 'array',
        'cursor_end' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(CatalogIntegration::class, 'integration_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function durationSeconds(): ?int
    {
        if (!$this->started_at || !$this->completed_at) return null;

        return $this->completed_at->diffInSeconds($this->started_at);
    }
}
