<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Accion del CRM: "llamar a Rosa el martes 10:00", "enviar propuesta", etc.
 * Siempre tiene negocio; puede colgar de un chat y/o de un trato. Al vencer,
 * el asesor asignado recibe un push (una sola vez).
 */
class CrmAccion extends Model
{
    protected $table = 'crm_acciones';

    protected $fillable = [
        'project_id', 'titulo', 'notas', 'tipo', 'vence_at', 'hecho_at', 'recordada_at',
        'asignado_a', 'creado_por', 'wa_conversacion_id', 'trato_id',
    ];

    protected $casts = ['vence_at' => 'datetime', 'hecho_at' => 'datetime', 'recordada_at' => 'datetime'];

    public function conversacion()
    {
        return $this->belongsTo(WaConversacion::class, 'wa_conversacion_id');
    }

    public function trato()
    {
        return $this->belongsTo(CrmTrato::class, 'trato_id');
    }

    /** Vencida = pendiente y con fecha pasada. */
    public function vencida(): bool
    {
        return $this->hecho_at === null && $this->vence_at !== null && $this->vence_at->isPast();
    }

    public function toArrayCrm(): array
    {
        $conv = $this->conversacion;

        return [
            'id'                 => $this->id,
            'titulo'             => $this->titulo,
            'notas'              => $this->notas,
            'tipo'               => $this->tipo,
            'vence_at'           => $this->vence_at?->toISOString(),
            'vence_texto'        => $this->vence_at?->locale('es')->isoFormat('ddd D MMM, HH:mm'),
            'hecho_at'           => $this->hecho_at?->toISOString(),
            'vencida'            => $this->vencida(),
            'asignado_a'         => $this->asignado_a,
            'wa_conversacion_id' => $this->wa_conversacion_id,
            'trato_id'           => $this->trato_id,
            'contacto'           => $conv ? ($conv->cliente_nombre ?: $conv->cliente_telefono) : null,
            'contacto_telefono'  => $conv?->cliente_telefono,
        ];
    }
}
