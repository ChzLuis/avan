<?php
namespace App\Modules\Finanzas\Models;

use App\Modules\Catalogo\Models\Product;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'product_id',
        'description', 'unit', 'quantity', 'unit_price', 'discount', 'igv_amount', 'total',
    ];

    protected $casts = [
        'quantity'   => 'decimal:3',
        'discount'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'igv_amount' => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
