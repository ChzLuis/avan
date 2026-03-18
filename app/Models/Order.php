<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model {
    protected $fillable = ['project_id', 'client_id', 'client_name', 'client_phone', 'client_email', 'status', 'notes', 'total', 'payment_method', 'payment_condition', 'sales_channel', 'payment_status', 'payment_reference', 'payment_gateway'];
    protected $casts = ['total' => 'decimal:2'];
    public function project() { return $this->belongsTo(Project::class); }
    public function client()  { return $this->belongsTo(Client::class); }
    public function items()   { return $this->hasMany(OrderItem::class); }
}
