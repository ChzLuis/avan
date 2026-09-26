<?php

namespace App\Modules\Inventario\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Una orden de compra: lo que se le pidio a un proveedor.
 *
 * Vive abierta hasta que llega todo. Ese "hasta que llega todo" es el motivo
 * de existir del documento: sin el, nadie sabe que el proveedor debe todavia
 * dos unidades de las diez que se pagaron.
 */
class PurchaseOrder extends Model
{
    protected $table = 'purchase_orders';

    protected $fillable = [
        'project_id', 'proveedor_id', 'user_id', 'numero', 'estado',
        'fecha_emision', 'fecha_esperada', 'warehouse_location_id',
        'moneda', 'subtotal', 'igv', 'total', 'notas', 'recibida_at',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_esperada' => 'date',
        'recibida_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'igv' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public const ESTADOS = [
        'borrador' => 'Borrador',
        'enviada' => 'Enviada al proveedor',
        'parcial' => 'Recibida a medias',
        'recibida' => 'Recibida completa',
        'anulada' => 'Anulada',
    ];

    public const COLORES = [
        'borrador' => 'bg-gray-100 text-gray-700',
        'enviada' => 'bg-blue-100 text-blue-800',
        'parcial' => 'bg-amber-100 text-amber-800',
        'recibida' => 'bg-emerald-100 text-emerald-800',
        'anulada' => 'bg-red-100 text-red-700',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function etiquetaEstado(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }

    public function colorEstado(): string
    {
        return self::COLORES[$this->estado] ?? 'bg-gray-100 text-gray-700';
    }

    /** Solo un borrador se puede editar: enviada ya la vio el proveedor. */
    public function esEditable(): bool
    {
        return $this->estado === 'borrador';
    }

    /** Se puede recibir mientras no este cerrada ni anulada. */
    public function admiteRecepcion(): bool
    {
        return in_array($this->estado, ['enviada', 'parcial'], true);
    }

    /** Unidades que el proveedor todavia debe. */
    public function pendiente(): int
    {
        return (int) $this->items->sum(fn ($i) => max(0, $i->cantidad - $i->cantidad_recibida));
    }

    /**
     * Recalcula los totales desde las lineas.
     *
     * El IGV se calcula sobre el subtotal y no se guarda por linea: una orden
     * de compra es un acuerdo comercial, no un comprobante fiscal. El que
     * vale ante SUNAT es la factura del proveedor.
     */
    public function recalcular(float $tasaIgv = 18.0): void
    {
        $subtotal = $this->items->sum(fn ($i) => $i->cantidad * (float) $i->precio_unitario);
        $igv = round($subtotal * ($tasaIgv / 100), 2);

        $this->subtotal = round($subtotal, 2);
        $this->igv = $igv;
        $this->total = round($subtotal + $igv, 2);
        $this->save();
    }

    /**
     * Ajusta el estado segun lo que se lleva recibido.
     *
     * Se llama despues de cada recepcion: una orden a medias tiene que
     * distinguirse de una cerrada, o nadie reclama lo que falta.
     */
    public function actualizarEstadoPorRecepcion(): void
    {
        $this->load('items');

        $pedido = (int) $this->items->sum('cantidad');
        $recibido = (int) $this->items->sum('cantidad_recibida');

        if ($recibido <= 0) {
            return;
        }

        if ($recibido >= $pedido) {
            $this->estado = 'recibida';
            $this->recibida_at = now();
        } else {
            $this->estado = 'parcial';
        }

        $this->save();
    }

    /** Siguiente correlativo del negocio: OC-0001, OC-0002... */
    public static function siguienteNumero(int $projectId): string
    {
        $ultimo = self::where('project_id', $projectId)
            ->orderByDesc('id')
            ->value('numero');

        $n = $ultimo && preg_match('/(\d+)$/', $ultimo, $m) ? ((int) $m[1]) + 1 : 1;

        // Se comprueba que no exista: si dos personas crean una orden a la
        // vez, el correlativo calculado podria repetirse y la tabla lo
        // rechazaria con un error que no dice nada.
        do {
            $numero = 'OC-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
            $n++;
        } while (self::where('project_id', $projectId)->where('numero', $numero)->exists());

        return $numero;
    }
}
