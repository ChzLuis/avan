<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'project_id', 'order_id', 'quote_id', 'client_id',
        'type', 'serie', 'correlativo', 'numero',
        'emisor_razon_social', 'emisor_ruc', 'emisor_direccion',
        'client_name', 'client_phone', 'client_email',
        'client_doc_type', 'client_doc_number', 'client_address',
        'subtotal', 'igv', 'total', 'currency', 'igv_included',
        'payment_method', 'paid_at', 'status', 'notes',
        'issue_date', 'due_date',
        'sunat_status', 'sunat_hash', 'sunat_cdr', 'sunat_ticket', 'sunat_error', 'sunat_sent_at',
    ];

    protected $casts = [
        'subtotal'      => 'decimal:2',
        'igv'           => 'decimal:2',
        'total'         => 'decimal:2',
        'igv_included'  => 'boolean',
        'paid_at'       => 'datetime',
        'issue_date'    => 'date',
        'due_date'      => 'date',
        'sunat_sent_at' => 'datetime',
    ];

    public function project() { return $this->belongsTo(Project::class); }
    public function order()   { return $this->belongsTo(Order::class); }
    public function quote()   { return $this->belongsTo(Quote::class); }
    public function client()  { return $this->belongsTo(Client::class); }
    public function items()   { return $this->hasMany(InvoiceItem::class); }

    /** Genera el número formateado: B001-00000001 */
    public static function buildNumero(string $serie, int $correlativo): string
    {
        return $serie . '-' . str_pad($correlativo, 8, '0', STR_PAD_LEFT);
    }

    /** Siguiente correlativo para la serie dada dentro del proyecto */
    public static function nextCorrelativo(int $projectId, string $type, string $serie): int
    {
        $max = static::where('project_id', $projectId)
            ->where('type', $type)
            ->where('serie', $serie)
            ->max('correlativo');
        return ($max ?? 0) + 1;
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            'factura'      => 'Factura',
            'boleta'       => 'Boleta',
            'nota_credito' => 'Nota de Crédito',
            'nota_debito'  => 'Nota de Débito',
            default        => ucfirst($this->type),
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'draft'     => 'Borrador',
            'issued'    => 'Emitida',
            'sent'      => 'Enviada',
            'cancelled' => 'Anulada',
            default     => ucfirst($this->status),
        };
    }
}
