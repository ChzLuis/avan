<?php
namespace App\Modules\Ventas\Models;

use App\Modules\Catalogo\Models\Product;
use App\Modules\Catalogo\Models\ProductVariant;
use App\Modules\Catalogo\Models\Service;
use Illuminate\Database\Eloquent\Model;
class OrderItem extends Model {
    protected $fillable = ['order_id', 'product_id', 'product_variant_id', 'service_id', 'name', 'variant_snapshot', 'price', 'discount', 'quantity'];
    protected $casts = [
        'discount' => 'decimal:2', 'price' => 'decimal:2', 'variant_snapshot' => 'array'];
    public function order()   { return $this->belongsTo(Order::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function productVariant() { return $this->belongsTo(ProductVariant::class); }
    public function service() { return $this->belongsTo(Service::class); }
}
