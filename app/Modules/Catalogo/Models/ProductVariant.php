<?php

namespace App\Modules\Catalogo\Models;

use App\Models\Project;

use App\Models\Traits\HasProjectScope;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasProjectScope;

    protected $fillable = [
        'project_id', 'product_id', 'product_image_id', 'signature',
        'sku', 'barcode', 'price', 'compare_price', 'wholesale_price',
        'stock', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public function project() { return $this->belongsTo(Project::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function image() { return $this->belongsTo(ProductImage::class, 'product_image_id'); }
    public function values()
    {
        return $this->belongsToMany(ProductAttributeValue::class, 'product_variant_values')
            ->withPivot(['project_id', 'product_attribute_id'])
            ->withTimestamps();
    }

    public function effectivePrice(): string
    {
        return (string) ($this->price ?? $this->product?->price ?? '0.00');
    }

    public function effectiveStock(): ?int
    {
        return $this->stock ?? $this->product?->stock;
    }

    public function label(): string
    {
        return $this->values->sortBy(fn ($value) => $value->attribute?->sort_order ?? 0)
            ->pluck('label')->filter()->implode(' / ');
    }
}
