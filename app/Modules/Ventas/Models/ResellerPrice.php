<?php

namespace App\Modules\Ventas\Models;

use App\Models\Project;
use App\Models\User;

use App\Modules\Catalogo\Models\Product;

use Illuminate\Database\Eloquent\Model;

/**
 * Precio propio de un revendedor para un producto.
 * No modifica products.price (eso afectaría a todos): guarda aquí el precio
 * del revendedor y si el producto está en su catálogo compartible.
 */
class ResellerPrice extends Model
{
    protected $fillable = ['project_id', 'user_id', 'product_id', 'price', 'in_catalog'];
    protected $casts = ['price' => 'decimal:2', 'in_catalog' => 'boolean'];

    public function product() { return $this->belongsTo(Product::class); }
    public function user()    { return $this->belongsTo(User::class); }
    public function project() { return $this->belongsTo(Project::class); }
}
