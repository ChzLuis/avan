<?php
namespace App\Modules\Ventas\Models;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasProjectScope;
class Order extends Model {
    use HasProjectScope;
    protected $fillable = [
        'project_id', 'serie', 'correlativo', 'numero', 'created_by', 'client_id', 'quote_id', 'client_name', 'client_phone', 'client_email',
        'status', 'notes', 'coupon_code', 'discount', 'delivery_address', 'shipping_cost', 'total',
        'payment_method', 'payment_condition', 'sales_channel', 'payment_status', 'payment_reference',
        'payment_gateway', 'wa_number', 'wa_status', 'payment_proof',
        'document_status', 'document_type', 'document_number',
        'delivery_type', 'promised_at', 'advance_amount',
        'table_number', 'order_type', 'kitchen_status', 'kitchen_at', 'ready_at',
        // delivery fields
        'delivery_status', 'delivery_person_id', 'delivery_person_name',
        'delivery_assigned_at', 'delivery_dispatched_at', 'delivery_delivered_at',
        'delivery_distance_km', 'delivery_notes', 'delivery_proof',
        // laundry fields
        'tag_code', 'pieces_count', 'laundry_status', 'laundry_status_at', 'ready_notified_at',
    ];
    /**
     * Numeracion por negocio, igual que en Cotizaciones.
     *
     * El `id` es global entre todos los proyectos: el primer pedido de un
     * cliente nuevo salia como "PED-187". El correlativo empieza en 1 para
     * cada negocio y el UNIQUE del esquema garantiza que no se repita.
     */
    protected static function booted(): void
    {
        static::creating(function (self $order) {
            if ($order->correlativo !== null || empty($order->project_id)) {
                return;
            }
            $order->serie = $order->serie ?: 'PED';
            // max()+1 con bloqueo: sin el, dos pedidos simultaneos del mismo
            // negocio toman el mismo numero y chocan contra el UNIQUE.
            $siguiente = static::query()->withoutGlobalScopes()
                ->where('project_id', $order->project_id)
                ->where('serie', $order->serie)
                ->lockForUpdate()
                ->max('correlativo');
            $order->correlativo = (int) $siguiente + 1;
            $order->numero = $order->serie.'-'.str_pad((string) $order->correlativo, 5, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Como se nombra este pedido en pantalla, siempre igual. Las filas
     * anteriores a la numeracion caen al id para no quedarse sin nombre.
     */
    public function getEtiquetaAttribute(): string
    {
        return $this->numero ?: 'PED-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    /** Quien creo el pedido. */
    public function autor() { return $this->belongsTo(User::class, 'created_by'); }

    /** Cotizacion de origen (FK canonica desde F1b; nullable). */
    public function quote() { return $this->belongsTo(Quote::class); }

    protected $casts = [
        'total'                   => 'decimal:2',
        'shipping_cost'           => 'decimal:2',
        'discount'                => 'decimal:2',
        'kitchen_at'              => 'datetime',
        'ready_at'                => 'datetime',
        'delivery_assigned_at'    => 'datetime',
        'delivery_dispatched_at'  => 'datetime',
        'delivery_delivered_at'   => 'datetime',
        'ready_notified_at'       => 'datetime',
        'laundry_status_at'       => 'datetime',
        'pieces_count'            => 'integer',
        'promised_at'             => 'datetime',
        'advance_amount'          => 'decimal:2',
    ];
    public function project() { return $this->belongsTo(Project::class); }
    public function client()  { return $this->belongsTo(Client::class); }
    public function items()   { return $this->hasMany(OrderItem::class); }
}
