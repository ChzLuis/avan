<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasProjectScope;
class Order extends Model {
    use HasProjectScope;
    protected $fillable = [
        'project_id', 'created_by', 'client_id', 'client_name', 'client_phone', 'client_email',
        'status', 'notes', 'coupon_code', 'discount', 'delivery_address', 'shipping_cost', 'total',
        'payment_method', 'payment_condition', 'sales_channel', 'payment_status', 'payment_reference',
        'payment_gateway', 'wa_number', 'wa_status', 'payment_proof',
        'table_number', 'order_type', 'kitchen_status', 'kitchen_at', 'ready_at',
        // delivery fields
        'delivery_status', 'delivery_person_id', 'delivery_person_name',
        'delivery_assigned_at', 'delivery_dispatched_at', 'delivery_delivered_at',
        'delivery_distance_km', 'delivery_notes', 'delivery_proof',
    ];
    protected $casts = [
        'total'                   => 'decimal:2',
        'shipping_cost'           => 'decimal:2',
        'discount'                => 'decimal:2',
        'kitchen_at'              => 'datetime',
        'ready_at'                => 'datetime',
        'delivery_assigned_at'    => 'datetime',
        'delivery_dispatched_at'  => 'datetime',
        'delivery_delivered_at'   => 'datetime',
    ];
    public function project() { return $this->belongsTo(Project::class); }
    public function client()  { return $this->belongsTo(Client::class); }
    public function items()   { return $this->hasMany(OrderItem::class); }
}
