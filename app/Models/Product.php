<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasProjectScope;
class Product extends Model {
    use HasProjectScope;
    protected $fillable = [
        'project_id','category_id','brand_catalog_id',
        'name','sku','barcode','description','notes',
        'price','price_suggested','price_min','price_max','compare_price','wholesale_price','wholesale_min_qty','wholesale_unit','cost','unit',
        'stock','stock_min','stock_max','is_available','sort_order','options',
        'catalog_integration_id','external_sync_status','owner_scope',
    ];
    protected $casts = ['is_available' => 'boolean', 'price' => 'decimal:2', 'price_suggested' => 'decimal:2', 'price_min' => 'decimal:2', 'price_max' => 'decimal:2', 'compare_price' => 'decimal:2', 'wholesale_price' => 'decimal:2', 'cost' => 'decimal:2', 'options' => 'array', 'owner_scope' => 'array'];
    public function project()   { return $this->belongsTo(Project::class); }
    public function category()  { return $this->belongsTo(Category::class); }
    public function images()    { return $this->hasMany(ProductImage::class)->orderBy('sort_order'); }
    public function mainImage() { return $this->hasOne(ProductImage::class)->where('is_main', true); }
    public function reviews()   { return $this->hasMany(Review::class); }
    public function catalogProfiles() { return $this->belongsToMany(StoreCatalogProfile::class, 'store_catalog_profile_product'); }
    /** Integración de catálogo que creó/administra este producto (null = producto manual). */
    public function catalogIntegration() { return $this->belongsTo(CatalogIntegration::class, 'catalog_integration_id'); }
    public function isSyncedFromErp(): bool { return $this->catalog_integration_id !== null; }

    /** Tallas/variantes simples del producto (options.sizes). */
    public function getSizesAttribute(): array
    {
        return array_values(array_filter(array_map('trim', (array) data_get($this->options, 'sizes', []))));
    }

    public function getMainImageUrlAttribute(): ?string
    {
        $url = $this->mainImage?->url;
        if (!$url) return null;
        if (str_starts_with($url, 'http')) return $url;
        return asset('storage/' . $url);
    }
    public function approvedReviews() { return $this->hasMany(Review::class)->where('is_approved', true)->latest(); }

    protected static function booted(): void
    {
        static::saving(function (self $product) {
            if ($product->category_id && $product->project_id) {
                $catProjectId = Category::where('id', $product->category_id)->value('project_id');
                if ($catProjectId && $catProjectId !== $product->project_id) {
                    throw new \RuntimeException(
                        "category_id {$product->category_id} pertenece al proyecto {$catProjectId}, no al proyecto {$product->project_id}."
                    );
                }
            }
        });
    }
}
