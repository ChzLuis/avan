<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasProjectScope;
class Product extends Model {
    use HasProjectScope;
    protected $fillable = [
        'project_id','category_id','brand_catalog_id',
        'name','sku','barcode','description','notes',
        'price','compare_price','wholesale_price','wholesale_min_qty','wholesale_unit','cost','unit',
        'stock','stock_min','stock_max','is_available','sort_order',
    ];
    protected $casts = ['is_available' => 'boolean', 'price' => 'decimal:2', 'compare_price' => 'decimal:2', 'wholesale_price' => 'decimal:2', 'cost' => 'decimal:2'];
    public function project()   { return $this->belongsTo(Project::class); }
    public function category()  { return $this->belongsTo(Category::class); }
    public function images()    { return $this->hasMany(ProductImage::class)->orderBy('sort_order'); }
    public function mainImage() { return $this->hasOne(ProductImage::class)->where('is_main', true); }
    public function reviews()   { return $this->hasMany(Review::class); }
    public function approvedReviews() { return $this->hasMany(Review::class)->where('is_approved', true)->latest(); }
}
