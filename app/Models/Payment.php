<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Asiento de cobro. Inmutable por contrato: no se edita ni se borra: una
 * correccion es OTRO asiento con `reverses_id`. Ver App\Support\Ledger.
 */
class Payment extends Model
{
    protected $fillable = [
        'project_id', 'payable_type', 'payable_id', 'amount_cents',
        'method', 'reference', 'received_at', 'source', 'user_id',
        'reverses_id', 'reversal_reason', 'meta',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'received_at'  => 'datetime',
        'meta'         => 'array',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function user(): BelongsTo    { return $this->belongsTo(User::class); }

    /** El asiento que este anula, si es una reversion. */
    public function revierteA(): BelongsTo { return $this->belongsTo(self::class, 'reverses_id'); }

    public function esReversion(): bool { return $this->reverses_id !== null; }

    /** Importe con signo para sumar: una reversion resta. */
    public function importeConSigno(): int
    {
        return $this->esReversion() ? -$this->amount_cents : $this->amount_cents;
    }
}
