<?php

namespace App\Models;

use App\Models\Traits\HasProjectScope;
use App\Support\Sunat\Catalogos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * La guía de remisión del remitente.
 *
 * La factura dice qué se vendió; la guía dice cómo viajó. En un control de
 * carretera piden esta, no aquella.
 */
class GuiaRemision extends Model
{
    use HasProjectScope;

    protected $table = 'guias_remision';

    protected $fillable = [
        'project_id', 'invoice_id', 'order_id', 'client_id',
        'serie', 'correlativo', 'numero',
        'emisor_razon_social', 'emisor_ruc',
        'destinatario_nombre', 'destinatario_doc_tipo', 'destinatario_doc_numero',
        'motivo_codigo', 'motivo_descripcion', 'fecha_traslado', 'modalidad',
        'peso_total', 'peso_unidad', 'bultos',
        'partida_ubigeo', 'partida_direccion', 'llegada_ubigeo', 'llegada_direccion',
        'transportista_ruc', 'transportista_razon_social', 'transportista_mtc',
        'vehiculo_placa', 'vehiculo_m1l', 'transbordo_programado',
        'conductor_doc_tipo', 'conductor_doc_numero',
        'conductor_nombres', 'conductor_apellidos', 'conductor_licencia',
        'status', 'sunat_status', 'sunat_ticket', 'sunat_hash', 'sunat_cdr',
        'sunat_error', 'sunat_sent_at', 'observaciones', 'created_by',
    ];

    protected $casts = [
        'fecha_traslado' => 'date',
        'peso_total'     => 'decimal:3',
        'vehiculo_m1l'          => 'boolean',
        'transbordo_programado' => 'boolean',
        'sunat_sent_at'  => 'datetime',
    ];

    /** Con transportista contratado; si no, va en vehículo propio. */
    public const PUBLICO  = '01';
    public const PRIVADO  = '02';

    public function project() { return $this->belongsTo(Project::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function order()   { return $this->belongsTo(Order::class); }
    public function client()  { return $this->belongsTo(Client::class); }
    public function items()   { return $this->hasMany(GuiaRemisionItem::class); }
    public function creadoPor() { return $this->belongsTo(User::class, 'created_by'); }

    public static function buildNumero(string $serie, int $correlativo): string
    {
        return $serie.'-'.str_pad((string) $correlativo, Invoice::DIGITOS_CORRELATIVO, '0', STR_PAD_LEFT);
    }

    /**
     * Reserva el siguiente número de la serie.
     *
     * Mismo bloqueo que en las facturas: dos despachos a la vez no pueden
     * llevarse el mismo número de guía.
     *
     * @return array{0:int,1:string}
     */
    public static function emitirNumero(int $projectId, string $serie): array
    {
        return DB::transaction(function () use ($projectId, $serie) {
            $max = static::allProjects()
                ->where('project_id', $projectId)
                ->where('serie', $serie)
                ->lockForUpdate()
                ->max('correlativo');

            $correlativo = ($max ?? 0) + 1;

            return [$correlativo, self::buildNumero($serie, $correlativo)];
        });
    }

    public function esPublico(): bool
    {
        return $this->modalidad === self::PUBLICO;
    }

    /**
     * ¿Este traslado está exento de declarar vehículo y conductor?
     *
     * Solo en privado y solo con el indicador M1/L: SUNAT permite omitir
     * placa, conductor y licencia cuando la carga va en auto, camioneta o
     * moto. En cualquier otro traslado privado esos datos son obligatorios y
     * mandarlos vacíos es motivo de rechazo.
     */
    public function exentoDeVehiculo(): bool
    {
        return ! $this->esPublico() && (bool) $this->vehiculo_m1l;
    }

    public function motivoLegible(): string
    {
        return Catalogos::MOTIVOS_TRASLADO[$this->motivo_codigo] ?? ($this->motivo_descripcion ?: 'Traslado');
    }

    /**
     * Semaforo de documentacion: ¿este traslado tiene el comprobante que su
     * motivo exige? Una guia por VENTA entregada sin factura ni boleta es
     * mercaderia que salio sin sustento de venta — el caso "150 guias pero
     * 100 comprobantes" que nadie puede explicar despues. Derivado del motivo
     * y del vinculo, sin columnas nuevas.
     */
    public function documentacion(): array
    {
        if ($this->invoice_id) {
            return ['vinculada', 'Comprobante vinculado', 'verde'];
        }

        return match ($this->motivo_codigo) {
            '01'    => ['pendiente', 'Pendiente de comprobante', 'ambar'],
            '14'    => ['no_confirmada', 'Venta aún no confirmada', 'gris'],
            '05'    => ['consignacion', 'Consignación · sin comprobante por ahora', 'gris'],
            default => ['no_requiere', 'No requiere comprobante', 'gris'],
        };
    }

    public function estadoSunatLegible(): string
    {
        return match ($this->sunat_status) {
            'accepted' => 'Aceptada por SUNAT',
            'pending'  => 'Enviando a SUNAT...',
            'rejected' => 'Rechazada por SUNAT',
            'error'    => 'No se pudo enviar',
            default    => 'Sin enviar',
        };
    }

    /** Una guía aceptada no se borra: se anula, como cualquier documento fiscal. */
    public function sePuedeBorrar(): bool
    {
        return $this->sunat_status !== 'accepted';
    }
}
