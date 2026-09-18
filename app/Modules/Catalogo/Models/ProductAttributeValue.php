<?php

namespace App\Modules\Catalogo\Models;

use App\Models\Project;

use App\Models\Traits\HasProjectScope;
use Illuminate\Database\Eloquent\Model;

class ProductAttributeValue extends Model
{
    use HasProjectScope;

    protected $fillable = [
        'project_id', 'product_attribute_id', 'label', 'value',
        'color_hex', 'is_active', 'sort_order',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function project() { return $this->belongsTo(Project::class); }
    public function attribute() { return $this->belongsTo(ProductAttribute::class, 'product_attribute_id'); }
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_attribute_product_value')
            ->withPivot(['project_id', 'product_attribute_id'])
            ->withTimestamps();
    }
    public function variants()
    {
        return $this->belongsToMany(ProductVariant::class, 'product_variant_values')
            ->withPivot(['project_id', 'product_attribute_id'])
            ->withTimestamps();
    }
}
