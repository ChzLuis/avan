<?php

namespace App\Modules\Crm\Models;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trato (oportunidad de venta). Ver la migracion crm_tratos_y_etapas.
 */
class CrmTrato extends Model
{
    protected $table = 'crm_tratos';

    protected $fillable = [
        'project_id', 'etapa_id', 'titulo', 'client_id', 'wa_conversacion_id',
        'contacto_nombre', 'contacto_telefono', 'valor', 'moneda', 'asesor_id',
        'cierre_estimado', 'orden', 'etapa_desde', 'ganado_at', 'perdido_at',
        'motivo_perdida', 'notas', 'origen',
    ];

    protected $casts = [
        'valor'           => 'decimal:2',
        'cierre_estimado' => 'date',
        'etapa_desde'     => 'datetime',
        'ganado_at'       => 'datetime',
        'perdido_at'      => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function etapa(): BelongsTo
    {
        return $this->belongsTo(CrmEtapa::class, 'etapa_id');
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(WaConversacion::class, 'wa_conversacion_id');
    }

    /**
     * Cambia de etapa dejando constancia: fecha de entrada (dias en etapa) y,
     * si la etapa es de cierre, la marca de ganado o perdido.
     */
    public function moverA(CrmEtapa $etapa, ?string $motivoPerdida = null): void
    {
        $this->etapa_id = $etapa->id;
        $this->etapa_desde = now();
        $this->ganado_at = $etapa->es_ganado ? now() : null;
        $this->perdido_at = $etapa->es_perdido ? now() : null;
        $this->motivo_perdida = $etapa->es_perdido ? $motivoPerdida : null;
        $this->save();
    }

    /** Dias que lleva en la etapa actual. */
    public function diasEnEtapa(): int
    {
        return (int) ($this->etapa_desde ?? $this->created_at)->diffInDays(now());
    }
}
