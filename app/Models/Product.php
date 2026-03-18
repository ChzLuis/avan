<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Product extends Model {
    protected $fillable = [
        'project_id','category_id','brand_catalog_id',
        'name','sku','barcode','description','notes',
        'price','compare_price','cost','unit',
        'stock','is_available','sort_order',
    ];
    protected $casts = ['is_available' => 'boolean', 'price' => 'decimal:2', 'compare_price' => 'decimal:2', 'cost' => 'decimal:2'];
    public function project()   { return $this->belongsTo(Project::class); }
    public function category()  { return $this->belongsTo(Category::class); }
    public function images()    { return $this->hasMany(ProductImage::class)->orderBy('sort_order'); }
    public function mainImage() { return $this->hasOne(ProductImage::class)->where('is_main', true); }
}
