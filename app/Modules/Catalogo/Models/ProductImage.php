<?php
namespace App\Modules\Catalogo\Models;
use Illuminate\Database\Eloquent\Model;
class ProductImage extends Model {
    // `url` es la foto ORIGINAL y no se reescribe nunca; la version compuesta
    // por la plantilla del negocio vive en `generated_url`.
    protected $fillable = ['product_id', 'url', 'is_main', 'sort_order',
        'generated_url', 'generated_hash', 'generated_at', 'generation_status', 'generation_error'];
    protected $casts = ['is_main' => 'boolean', 'generated_at' => 'datetime'];
    public function product() { return $this->belongsTo(Product::class); }
    public function variants() { return $this->hasMany(ProductVariant::class); }
}
