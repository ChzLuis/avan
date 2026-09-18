<?php
namespace App\Modules\Catalogo\Models;

use App\Models\Project;
use App\Modules\Catalogo\Support\EtiquetasProducto;

use App\Modules\Tienda\Models\Review;
use App\Modules\Tienda\Models\StoreCatalogProfile;

use App\Modules\Inventario\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasProjectScope;
class Product extends Model {
    use HasProjectScope;
    protected $fillable = [
        'project_id','category_id','brand_catalog_id',
        'name','sku','barcode','description','notes',
        'ficha_tecnica_archivo','ficha_tecnica_url',
        'price','price_suggested','price_min','price_max','compare_price','wholesale_price','wholesale_min_qty','wholesale_unit','cost','unit',
        'has_tax','tax_rate',
        'stock','stock_min','stock_max','is_available','sort_order','options',
        'catalog_integration_id','external_sync_status','owner_scope',
    ];
    protected $casts = ['is_available' => 'boolean', 'has_tax' => 'boolean', 'tax_rate' => 'decimal:2', 'price' => 'decimal:2', 'price_suggested' => 'decimal:2', 'price_min' => 'decimal:2', 'price_max' => 'decimal:2', 'compare_price' => 'decimal:2', 'wholesale_price' => 'decimal:2', 'cost' => 'decimal:2', 'options' => 'array', 'owner_scope' => 'array'];
    public function project()   { return $this->belongsTo(Project::class); }
    public function category()  { return $this->belongsTo(Category::class); }
    /**
     * Marca del producto. Vive en `catalog_values` (lista de tipo `brand`),
     * no en una tabla propia: asi cada tienda define las suyas sin migracion.
     */
    public function marca()     { return $this->belongsTo(CatalogValue::class, 'brand_catalog_id'); }
    public function images()    { return $this->hasMany(ProductImage::class)->orderBy('sort_order'); }
    /** Kardex del producto: cada entrada y salida, de la mas reciente atras. */
    public function movimientos() { return $this->hasMany(InventoryMovement::class)->latest('id'); }
    public function mainImage() { return $this->hasOne(ProductImage::class)->where('is_main', true); }
    public function reviews()   { return $this->hasMany(Review::class); }
    public function variants()  { return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id'); }
    public function activeVariants() { return $this->variants()->where('is_active', true); }
    public function attributeValues()
    {
        return $this->belongsToMany(ProductAttributeValue::class, 'product_attribute_product_value')
            ->withPivot(['project_id', 'product_attribute_id'])
            ->withTimestamps();
    }
    public function catalogProfiles() { return $this->belongsToMany(StoreCatalogProfile::class, 'store_catalog_profile_product'); }
    /** Integración de catálogo que creó/administra este producto (null = producto manual). */
    public function catalogIntegration() { return $this->belongsTo(CatalogIntegration::class, 'catalog_integration_id'); }
    public function isSyncedFromErp(): bool { return $this->catalog_integration_id !== null; }

    /** Tallas/variantes simples del producto (options.sizes). */
    public function getSizesAttribute(): array
    {
        return array_values(array_filter(array_map('trim', (array) data_get($this->options, 'sizes', []))));
    }

    /**
     * Etiquetas listas para pintar (options.etiquetas).
     *
     * El color y el orden los resuelve `EtiquetasProducto`, no la vista: asi
     * una etiqueta se ve igual en la tarjeta, en la ficha y en cualquier
     * plantilla.
     *
     * @param  string  $donde  'card' (maximo 2) | 'ficha' (todas)
     */
    public function etiquetas(string $donde = 'card'): array
    {
        return \App\Modules\Catalogo\Support\EtiquetasProducto::para($this, $donde);
    }

    /**
     * Ficha tecnica lista para enlazar, o null si el producto no tiene.
     *
     * El archivo subido gana al enlace externo: si el proveedor rehace su web,
     * su URL se cae y la nuestra no. Guardamos ruta relativa, no URL completa,
     * porque el dominio de la tienda cambia (subdominio, dominio propio) y las
     * absolutas guardadas quedarian apuntando al sitio viejo.
     */
    public function getFichaTecnicaUrlResueltaAttribute(): ?string
    {
        $archivo = trim((string) $this->ficha_tecnica_archivo);
        if ($archivo !== '') {
            return str_starts_with($archivo, 'http') ? $archivo : asset('storage/'.ltrim($archivo, '/'));
        }
        $enlace = trim((string) $this->ficha_tecnica_url);
        return $enlace !== '' ? $enlace : null;
    }

    public function getMainImageUrlAttribute(): ?string
    {
        $img = $this->mainImage;
        $url = $img?->url;
        if (!$url) return null;
        if (!str_starts_with($url, 'http')) $url = asset('storage/' . $url);

        // Plantilla automatica de imagenes: si el negocio la tiene activa y
        // esta foto ya tiene su version compuesta, se sirve esa. Si no, la
        // original. La decision vive en el resolutor, no repartida por las
        // vistas. Apagar la plantilla devuelve el catalogo al original sin
        // borrar ningun archivo.
        $generada = \App\Support\Imagen\ResolutorImagenProducto::url(
            $url, $img?->generated_url, $this->project_id
        );
        if ($generada !== $url) {
            // Ya viene compuesta al tamano de la plantilla: no se le aplica el
            // encuadre del perfil, que la recortaria por segunda vez.
            return $generada;
        }

        // Punto único de salida de la foto del producto: aquí cuelgan la ficha,
        // las tarjetas y el JSON que pinta la rejilla del catálogo. Devolver la
        // variante ya encuadrada es lo que hace que todos los productos ocupen
        // lo mismo dentro de su cuadro; si no hay variante, sale la de siempre.
        return \App\Support\Imagen\Img::mejor($url, 'producto');
    }
    public function approvedReviews() { return $this->hasMany(Review::class)->where('is_approved', true)->latest(); }

    public function hasRealVariants(): bool
    {
        return $this->relationLoaded('variants')
            ? $this->variants->contains('is_active', true)
            : $this->activeVariants()->exists();
    }

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
