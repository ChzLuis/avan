<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Propuesta comercial (proforma) enviada a un cliente.
 * El contenido de la propuesta es una plantilla fija; aquí viven los datos
 * variables: cliente, rubro, precios, extras contratados y estado.
 */
class Proposal extends Model
{
    protected $fillable = [
        'project_id', 'number', 'token',
        'client_name', 'business_name', 'rubro', 'client_phone', 'client_email', 'city',
        'price', 'price_renewal', 'products_included', 'valid_days',
        'extras', 'extra_notes', 'status', 'sent_at',
    ];

    protected $casts = [
        'extras'        => 'array',
        'sent_at'       => 'datetime',
        'price'         => 'decimal:2',
        'price_renewal' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** Fecha hasta la que es válida la propuesta. */
    public function getValidaHastaAttribute(): \Carbon\Carbon
    {
        return ($this->created_at ?? now())->copy()->addDays($this->valid_days ?: 15);
    }

    /** Servicios adicionales de pago único. */
    public function getExtrasUnicosAttribute(): array
    {
        return collect($this->extras ?? [])->where('periodo', 'unico')->values()->all();
    }

    /** Servicios adicionales recurrentes (mensuales / por sesión). */
    public function getExtrasRecurrentesAttribute(): array
    {
        return collect($this->extras ?? [])->filter(fn ($e) => ($e['periodo'] ?? '') !== 'unico')->values()->all();
    }
}
