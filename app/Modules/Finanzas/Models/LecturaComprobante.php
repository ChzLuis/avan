<?php

namespace App\Modules\Finanzas\Models;

use App\Models\User;

use App\Models\Traits\HasProjectScope;
use Illuminate\Database\Eloquent\Model;

/**
 * Una lectura de comprobante: qué se subió, qué se leyó y en qué acabó.
 *
 * Estados: procesando · procesado · requiere_revision · utilizado · error.
 */
class LecturaComprobante extends Model
{
    use HasProjectScope;

    protected $table = 'lecturas_comprobantes';

    protected $fillable = [
        'project_id', 'user_id', 'invoice_id',
        'estado', 'archivo', 'mime', 'bytes', 'motor',
        'resultado', 'avisos', 'mensaje_error',
        'doc_tipo', 'doc_serie', 'doc_numero',
    ];

    protected $casts = [
        'resultado' => 'array',
        'avisos'    => 'array',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
