<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/** Vencimiento de cobro de un documento (una fila por cuota). */
class ReceivableTerm extends Model
{
    protected $fillable = [
        'project_id', 'payable_type', 'payable_id', 'numero', 'due_date', 'amount_cents',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'numero'       => 'integer',
        'amount_cents' => 'integer',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }

    /** Vencido es que la fecha pactada YA paso, no "lleva muchos dias creado". */
    public function vencido(?Carbon $hoy = null): bool
    {
        return $this->due_date->lt(($hoy ?? Carbon::today())->startOfDay());
    }

    public function diasDeAtraso(?Carbon $hoy = null): int
    {
        $hoy = ($hoy ?? Carbon::today())->startOfDay();

        return $this->due_date->lt($hoy) ? (int) $this->due_date->diffInDays($hoy) : 0;
    }
}
