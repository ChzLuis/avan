<?php

namespace App\Modules\Finanzas\Models;

use App\Modules\Catalogo\Models\Product;

use Illuminate\Database\Eloquent\Model;

/** Una línea de lo que viaja: qué es, cuánto y en qué unidad. */
class GuiaRemisionItem extends Model
{
    protected $table = 'guia_remision_items';

    protected $fillable = ['guia_remision_id', 'product_id', 'codigo', 'description', 'unit', 'quantity'];

    protected $casts = ['quantity' => 'decimal:3'];

    public function guia()    { return $this->belongsTo(GuiaRemision::class, 'guia_remision_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}
