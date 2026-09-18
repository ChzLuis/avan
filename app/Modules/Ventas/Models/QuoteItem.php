<?php
namespace App\Modules\Ventas\Models;
use Illuminate\Database\Eloquent\Model;
class QuoteItem extends Model {
    protected $fillable = ['quote_id', 'product_id', 'description', 'sku', 'brand', 'unit', 'price', 'discount', 'quantity'];
    protected $casts = [
        'discount' => 'decimal:2','price' => 'decimal:2'];
    public function quote() { return $this->belongsTo(Quote::class); }
}
