<?php
namespace App\Models;

use App\Models\Traits\HasProjectScope;
use App\Support\Sunat\Catalogos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    use HasProjectScope;

    protected $fillable = [
        'project_id', 'order_id', 'quote_id', 'client_id',
        'afecta_invoice_id', 'afecta_tipo', 'afecta_numero',
        'motivo_codigo', 'motivo_descripcion',
        'type', 'serie', 'correlativo', 'numero',
        'emisor_razon_social', 'emisor_ruc', 'emisor_direccion',
        'client_name', 'client_phone', 'client_email',
        'client_doc_type', 'client_doc_number', 'client_address',
        'subtotal', 'igv', 'total', 'currency', 'igv_included',
        'payment_method', 'paid_at', 'status', 'notes',
        'issue_date', 'due_date',
        'sunat_status', 'sunat_hash', 'sunat_cdr', 'sunat_ticket', 'sunat_error', 'sunat_sent_at',
        'baja_estado', 'baja_ticket', 'baja_motivo', 'baja_error', 'baja_at',
        'created_by',
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
        'baja_at'       => 'datetime',
    ];

    /** Los dígitos del correlativo impreso: F001-00000123. */
    public const DIGITOS_CORRELATIVO = 8;

    public function project() { return $this->belongsTo(Project::class); }
    public function order()   { return $this->belongsTo(Order::class); }
    public function quote()   { return $this->belongsTo(Quote::class); }
    public function client()  { return $this->belongsTo(Client::class); }
    public function items()   { return $this->hasMany(InvoiceItem::class); }
    public function creadoPor() { return $this->belongsTo(User::class, 'created_by'); }

    /** El comprobante que esta nota corrige. */
    public function afecta() { return $this->belongsTo(self::class, 'afecta_invoice_id'); }

    /** Las notas de crédito o débito emitidas sobre este comprobante. */
    public function notas() { return $this->hasMany(self::class, 'afecta_invoice_id'); }

    /**
     * El número formateado: F001-00000123.
     *
     * El correlativo va con ceros a la izquierda hasta ocho dígitos porque así
     * se imprime y así se referencia en una nota de crédito: "F001-1" no es un
     * número de comprobante que SUNAT reconozca en el campo de documento
     * afectado.
     */
    public static function buildNumero(string $serie, int $correlativo): string
    {
        return $serie.'-'.str_pad((string) $correlativo, self::DIGITOS_CORRELATIVO, '0', STR_PAD_LEFT);
    }

    /**
     * El siguiente correlativo de la serie, sin repetirse.
     *
     * Leer el máximo y sumarle uno tiene una carrera: dos cobros a la vez
     * obtienen el mismo número y el segundo revienta contra el índice único
     * —en mitad de una venta, y con SUNAT rechazando el duplicado si llegara a
     * salir—. El bloqueo de fila serializa las dos emisiones.
     *
     * Debe llamarse dentro de una transacción; `emitirNumero()` lo garantiza.
     */
    public static function nextCorrelativo(int $projectId, string $type, string $serie): int
    {
        $max = static::allProjects()
            ->where('project_id', $projectId)
            ->where('type', $type)
            ->where('serie', $serie)
            ->lockForUpdate()
            ->max('correlativo');

        return ($max ?? 0) + 1;
    }

    /**
     * Reserva el siguiente número de una serie y devuelve [correlativo, numero].
     *
     * @return array{0:int,1:string}
     */
    public static function emitirNumero(int $projectId, string $type, string $serie): array
    {
        return DB::transaction(function () use ($projectId, $type, $serie) {
            $correlativo = self::nextCorrelativo($projectId, $type, $serie);

            return [$correlativo, self::buildNumero($serie, $correlativo)];
        });
    }

    /**
     * Camino ÚNICO y seguro de emisión: reserva el correlativo Y crea el
     * comprobante en la MISMA transacción, de modo que el `lockForUpdate` de
     * `nextCorrelativo` se sostiene hasta que el nuevo comprobante existe. Sin
     * esto, dos emisiones simultáneas calculan el mismo MAX+1 y colisionan
     * contra el índice único (RISK-012).
     *
     * @param  \Closure(int $correlativo, string $numero): \App\Models\Invoice $crear
     */
    public static function emitir(int $projectId, string $type, string $serie, ?int $correlativoManual, \Closure $crear): self
    {
        return DB::transaction(function () use ($projectId, $type, $serie, $correlativoManual, $crear) {
            $correlativo = $correlativoManual ?? self::nextCorrelativo($projectId, $type, $serie);
            $numero      = self::buildNumero($serie, $correlativo);

            return $crear($correlativo, $numero);
        });
    }

    /** El código del catálogo 01 que viaja en el XML. */
    public function codigoSunat(): string
    {
        return Catalogos::codigoComprobante($this->type);
    }

    public function esNota(): bool
    {
        return in_array($this->type, ['nota_credito', 'nota_debito'], true);
    }

    /**
     * Notas del CDR cuando SUNAT acepto CON observaciones. El veredicto de
     * SUNAT tiene tres salidas (aceptada, aceptada con observacion, rechazada)
     * y colapsar las dos primeras esconde exactamente lo que SUNAT quiso
     * anotar. Salen del CDR ya guardado: no hace falta columna nueva.
     */
    public function observacionesSunat(): array
    {
        if ($this->sunat_status !== 'accepted' || ! $this->sunat_cdr) {
            return [];
        }
        $resp  = json_decode((string) $this->sunat_cdr, true) ?: [];
        $cdr   = $resp['sunatResponse']['cdrResponse'] ?? $resp['cdrResponse'] ?? [];
        $notas = $cdr['notes'] ?? [];

        return is_array($notas)
            ? array_values(array_filter(array_map(fn ($n) => trim((string) (is_array($n) ? json_encode($n) : $n)), $notas)))
            : [];
    }

    /**
     * Un comprobante aceptado por SUNAT ya no se borra: se corrige con una nota
     * de crédito o se da de baja. Borrar la fila deja al negocio debiendo el
     * IGV de una venta que en su sistema ya no existe.
     */
    public function sePuedeBorrar(): bool
    {
        return $this->sunat_status !== 'accepted' && $this->baja_estado !== 'accepted';
    }

    /**
     * La baja solo cabe sobre una FACTURA que SUNAT ya aceptó.
     *
     * Una boleta no se da de baja de una en una: va en el resumen diario de
     * bajas, que es otro documento y otro endpoint. Ofrecer el botón igual
     * garantizaría el rechazo, y se descubriría con la venta ya anulada en el
     * sistema y viva en SUNAT. Para una boleta, la salida es la nota de crédito.
     */
    public function sePuedeDarDeBaja(): bool
    {
        return $this->sunat_status === 'accepted'
            && $this->type === 'factura'
            && ! in_array($this->baja_estado, ['pending', 'accepted'], true);
    }

    /** Y la nota, sobre una factura o boleta aceptada. */
    public function admiteNota(): bool
    {
        return $this->sunat_status === 'accepted'
            && in_array($this->type, ['factura', 'boleta'], true)
            && $this->baja_estado !== 'accepted';
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

    /** Cómo va el trámite con SUNAT, en una frase. */
    public function estadoSunatLegible(): string
    {
        if ($this->baja_estado === 'accepted') {
            return 'Dada de baja';
        }

        return match ($this->sunat_status) {
            'accepted' => 'Aceptada por SUNAT',
            'pending'  => 'Enviando a SUNAT...',
            'rejected' => 'Rechazada por SUNAT',
            'error'    => 'No se pudo enviar',
            default    => 'Sin enviar',
        };
    }
}
