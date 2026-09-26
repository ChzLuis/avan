<?php

namespace App\Modules\Inventario\Models;

use App\Modules\Catalogo\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una linea de la orden: que se pidio, cuanto y a que precio.
 */
class PurchaseOrderItem extends Model
{
    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id', 'product_id', 'cantidad', 'cantidad_recibida', 'precio_unitario',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'cantidad_recibida' => 'integer',
        'precio_unitario' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    /** Lo que el proveedor todavia debe de esta linea. */
    public function pendiente(): int
    {
        return max(0, $this->cantidad - $this->cantidad_recibida);
    }

    public function totalLinea(): float
    {
        return round($this->cantidad * (float) $this->precio_unitario, 2);
    }
}
