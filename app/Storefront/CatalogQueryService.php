<?php

namespace App\Storefront;

use App\Models\Project;
use App\Models\StoreCatalogProfile;
use App\Models\ProductAttribute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * Fase 2A — Consulta paginada del catálogo desde la base de datos.
 * Aislada por proyecto, con filtros validados y whitelist de orden.
 * No carga todos los productos en memoria.
 */
final class CatalogQueryService
{
    public const PER_PAGE_OPTIONS = [12, 24, 48];
    public const DEFAULT_PER_PAGE = 12;

    public const SORTS = ['recommended', 'newest', 'price_asc', 'price_desc', 'name'];

    /**
     * ¿Este negocio exige precio para enseñar un producto?
     *
     * Regla general: sí. Un producto a S/ 0.00 con botón de carrito genera
     * pedidos sin importe, así que se oculta hasta que traiga precio.
     *
     * La excepción es la tienda que trabaja POR COTIZACIÓN: ahí no hay precios
     * por definición, el catálogo es un muestrario y el visitante pide
     * presupuesto. Exigir precio en ese modo dejaba la tienda vacía y hacía
     * inalcanzable la tarjeta "Precio a solicitud" que la plantilla ya traía.
     *
     * Vive aquí porque este servicio es el que manda en qué se ve del catálogo;
     * el resto de sitios preguntan, no vuelven a decidir.
     */
    public static function exigePrecio(Project $project, ?array $settings = null): bool
    {
        // Quien ya tiene los ajustes cargados los pasa y aquí no se toca la
        // base: el contexto de la tienda se arma con UNA sola lectura de
        // `project_settings` y `StorefrontQueryBudgetTest` lo vigila.
        $modo = $settings !== null
            ? (string) ($settings['store_mode'] ?? 'direct')
            : (string) $project->setting('store_mode', 'direct');

        return ($modo ?: 'direct') !== 'quote';
    }

    /** Filtro de "producto mostrable", con la excepción de cotización aplicada. */
    public static function mostrable($query, Project $project, ?array $settings = null)
    {
        $query->where('is_available', true);

        if (self::exigePrecio($project, $settings)) {
            $query->where('price', '>', 0);
        }

        return $query;
    }

    /**
     * Devuelve productos activos del proyecto, filtrados/ordenados/paginados en DB.
     */
    public function paginate(Project $project, Request $request, ?StoreCatalogProfile $profile = null): LengthAwarePaginator
    {
        // Un producto sin precio no se puede comprar: mostrarlo con "S/ 0.00" y
        // botón de carrito activo genera pedidos sin importe. Se excluye del
        // catálogo hasta que la fuente traiga su precio.
        $query = self::mostrable($project->products(), $project)
            ->with([
                'mainImage', 'category',
                'variants' => fn ($builder) => $builder->where('is_active', true)
                    ->with(['values.attribute', 'image']),
            ]);

        // Perfil de catálogo: se aplica ANTES de cualquier otro filtro para acotar
        // el alcance. Un perfil restringe a: sus productos asignados + los productos
        // de sus categorías asignadas. Si el perfil no tiene ninguna asignación se
        // trata como "sin restricción" (sólo aporta identidad visual).
        if ($profile) {
            $this->applyProfileScope($query, $project, $profile);
        }

        // Búsqueda (escapada, columnas acotadas)
        $search = trim((string) $request->query('q', ''));

        // Agrupación por modelo: en la rejilla se enseña un color por modelo y
        // el resto viajan como opciones dentro de la tarjeta. No se agrupa
        // cuando hay búsqueda: quien escribe "rosado" quiere ver el rosado, no
        // el modelo representado por otro color.
        if ($search === '' && AgrupadorModelos::activoEn($project)) {
            $query->whereIn('id', app(AgrupadorModelos::class)->representantes($project));
        }

        if ($search !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';
            $query->where(function ($b) use ($like) {
                $b->where('name', 'like', $like)->orWhere('sku', 'like', $like)->orWhere('description', 'like', $like);
            });
        }

        // Categoría / subcategoría (solo de esta tienda). Acepta una o varias:
        // ?category=5  ó  ?category[]=5&category[]=8  (multi-selección)
        $requested = collect(\Illuminate\Support\Arr::wrap($request->query('category')))
            ->map(fn ($v) => (int) $v)
            ->filter()
            ->unique();
        if ($requested->isNotEmpty()) {
            $categories = $project->categories()->where('is_active', true)
                ->whereIn('id', $requested->all())->get();
            $ids = [];
            foreach ($categories as $category) {
                if ($category->parent_id) {
                    $ids[] = $category->id;
                } else {
                    // categoría raíz: incluye la propia y sus subcategorías activas
                    $ids = array_merge(
                        $ids,
                        $category->children()->where('is_active', true)->pluck('id')->prepend($category->id)->all()
                    );
                }
            }
            if ($ids) {
                $query->whereIn('category_id', array_values(array_unique($ids)));
            }
        }

        // Accesos "Ofertas" y "Novedades" del menú: llegan como ?filter=sale|new
        // y no estaban implementados, así que devolvían el catálogo completo.
        $filtro = (string) $request->query('filter', '');

        if ($filtro === 'sale' || $request->boolean('sale')) {
            $query->whereNotNull('compare_price')->whereColumn('compare_price', '>', 'price');
        }

        if ($filtro === 'new') {
            // "Novedades" = lo último que entró a la tienda. Se acota a un número
            // fijo de productos en vez de a una ventana de días: si el catálogo
            // se subió de una sola vez, filtrar por fecha devolvía el catálogo
            // entero y el acceso no distinguía nada.
            $ultimos = (clone $query)->reorder()
                ->orderByDesc('created_at')->orderByDesc('id')
                ->limit(16)->pluck('id')->all();

            if ($ultimos) {
                $query->whereIn('id', $ultimos);
            }
        }

        // "En stock": el cliente Ecommerce lo mostraba como filtro y volvia a
        // pedir el catalogo, pero nunca lo enviaba y el servidor no lo conocia:
        // el interruptor no filtraba nada. Sin control de inventario (stock
        // nulo) el producto se considera disponible.
        if ($request->boolean('in_stock')) {
            $query->where(function ($builder) {
                $builder->whereNull('stock')->orWhere('stock', '>', 0)
                    ->orWhereHas('variants', fn ($variants) => $variants->where('is_active', true)
                        ->where(fn ($v) => $v->whereNull('stock')->orWhere('stock', '>', 0)));
            });
        }

        if (is_numeric($request->query('min_price'))) {
            $query->where('price', '>=', max(0, (float) $request->query('min_price')));
        }
        if (is_numeric($request->query('max_price'))) {
            $query->where('price', '<=', max(0, (float) $request->query('max_price')));
        }

        // Atributos dinámicos: AND entre atributos y OR entre los valores del
        // mismo atributo. Los IDs se validan siempre dentro del proyecto para
        // impedir que un filtro de otra tienda altere esta consulta.
        $requestedAttributes = $request->query('attribute', []);
        if (is_array($requestedAttributes)) {
            foreach (array_slice($requestedAttributes, 0, 10, true) as $attributeId => $requestedValues) {
                $attribute = ProductAttribute::allProjects()
                    ->where('project_id', $project->id)
                    ->where('is_active', true)
                    ->where('is_filterable', true)
                    ->find((int) $attributeId);
                if (! $attribute) continue;

                $valueIds = $attribute->values()->where('is_active', true)
                    ->whereIn('id', collect(\Illuminate\Support\Arr::wrap($requestedValues))->map(fn ($id) => (int) $id)->filter()->unique()->all())
                    ->pluck('id')->all();
                if (! $valueIds) continue;

                $query->where(function ($builder) use ($attribute, $valueIds) {
                    $builder->whereHas('attributeValues', fn ($values) => $values
                        ->where('product_attribute_values.product_attribute_id', $attribute->id)
                        ->whereIn('product_attribute_values.id', $valueIds))
                        ->orWhereHas('variants', fn ($variants) => $variants->where('is_active', true)
                            ->whereHas('values', fn ($values) => $values
                                ->where('product_attribute_values.product_attribute_id', $attribute->id)
                                ->whereIn('product_attribute_values.id', $valueIds)));
                });
            }
        }

        // Los productos sin foto se iban delante y el cliente veia una rejilla de
        // marcadores. Con foto primero en el orden recomendado; el resto de
        // criterios los elige el visitante y ahi manda su eleccion.
        $sort = (string) $request->query('sort', 'recommended');
        if (!in_array($sort, self::SORTS, true) || $sort === 'recommended') {
            $query->orderByRaw('EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id = products.id) DESC');
        }

        match ($filtro === 'new' && $sort === 'recommended' ? 'newest' : (in_array($sort, self::SORTS, true) ? $sort : 'recommended')) {
            'price_asc' => $query->orderBy('price')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price')->orderByDesc('id'),
            'name' => $query->orderBy('name')->orderBy('id'),
            'newest' => $query->latest()->orderByDesc('id'),
            // "Recomendado" caia en sort_order y luego id DESC: con un catalogo
            // importado por lotes eso agrupa por categoria y la primera pantalla
            // sale con ocho articulos identicos seguidos. Se intercalan las
            // categorias (uno de cada una, luego el segundo de cada una...), que
            // es lo que hace un escaparate: ensenar variedad primero.
            default => $query->orderBy('sort_order')
                ->orderByRaw('ROW_NUMBER() OVER (PARTITION BY category_id ORDER BY id DESC)')
                ->orderByDesc('id'),
        };

        $perPage = in_array($request->integer('per_page'), self::PER_PAGE_OPTIONS, true)
            ? $request->integer('per_page')
            : self::DEFAULT_PER_PAGE;

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Restringe la consulta al alcance de un perfil de catálogo.
     *
     * Alcance = productos asignados directamente ∪ productos de las categorías
     * asignadas (incluidas subcategorías de una categoría raíz asignada).
     *
     * Política de productos sin asignación (project setting
     * `catalog_profile_orphan_policy`): 'hide' (por defecto, seguro) los oculta;
     * 'show_all' los muestra en todos los perfiles.
     */
    private function applyProfileScope($query, Project $project, StoreCatalogProfile $profile): void
    {
        // IDs de categorías del perfil, expandiendo raíces a sus subcategorías.
        $catIds = $profile->categories()->pluck('categories.id');
        $expanded = collect();
        if ($catIds->isNotEmpty()) {
            $roots = $project->categories()->whereIn('id', $catIds)->whereNull('parent_id')->pluck('id');
            $childIds = $roots->isNotEmpty()
                ? $project->categories()->where('is_active', true)->whereIn('parent_id', $roots)->pluck('id')
                : collect();
            $expanded = $catIds->merge($childIds)->unique()->values();
        }

        $productIds = $profile->products()->pluck('products.id');

        // Perfil sin ninguna asignación → sólo aporta identidad, no restringe.
        if ($expanded->isEmpty() && $productIds->isEmpty()) {
            return;
        }

        // Política de productos globales (sin ningún perfil asignado).
        $orphanPolicy = (string) $project->setting('catalog_profile_orphan_policy', 'hide');

        $query->where(function ($q) use ($expanded, $productIds, $orphanPolicy) {
            if ($productIds->isNotEmpty()) {
                $q->orWhereIn('id', $productIds->all());
            }
            if ($expanded->isNotEmpty()) {
                $q->orWhereIn('category_id', $expanded->all());
            }
            if ($orphanPolicy === 'show_all') {
                // Productos que no pertenecen a NINGÚN perfil se muestran en todos.
                $q->orWhereDoesntHave('catalogProfiles');
            }
        });
    }

    /**
     * Serializa un producto al shape que consumen las tarjetas del catálogo.
     */
    public function toCard(\App\Models\Product $p, string $slug): array
    {
        // Las fotos de producto pesaban varios MB en PNG; si existe su .webp
        // se sirve esa, que es ~90% mas ligera.
        $img = $p->mainImage ? \App\Support\ImageVariants::webp($p->main_image_url) : null;
        $cp = $p->compare_price ? (float) $p->compare_price : null;

        // Con la agrupación activa la tarjeta habla del modelo, no del color:
        // el nombre pierde la coletilla y los colores pasan a ser opciones.
        $project = $p->project ?? \App\Models\Project::where('slug', $slug)->first();
        $agrupa = $project && AgrupadorModelos::activoEn($project);
        $variantes = $agrupa ? app(AgrupadorModelos::class)->variantesDe($project, $p->id, $slug) : [];
        $nombre = $variantes ? AgrupadorModelos::claveModelo($p) : $p->name;
        // Shape unificado: claves canónicas (computienda) + alias (ecommerce).
        return [
            'id' => $p->id,
            'name' => $nombre,
            'price' => (float) $p->price,
            'comparePrice' => $cp, 'cp' => $cp,
            'hasTax' => (bool) $p->has_tax,
            'taxRate' => $p->has_tax ? (float) ($p->tax_rate ?? 18) : null,
            'image' => $img, 'img' => $img,
            'category' => $p->category?->name, 'cat' => $p->category?->name,
            'catId' => (string) $p->category_id,
            'parentId' => $p->category?->parent_id ? (string) $p->category->parent_id : null,
            'sku' => $p->sku,
            'stock' => $p->stock,
            // Directo ordena "mas nuevos" en el cliente con este sello.
            'ts' => $p->created_at?->timestamp ?? 0,
            // Resumen para la vista rapida: sin el, la tarjeta solo decia nombre
            // y precio y el comprador tenia que abrir la ficha para saber que
            // estaba comprando. Se limpia el HTML y se corta a 180 caracteres.
            'resumen' => \Illuminate\Support\Str::limit(
                trim(preg_replace('/\s+/', ' ', strip_tags((string) $p->description))), 180
            ),
            'url' => \App\Support\ImageVariants::productUrl($project, $p->id, $p->name),
            'wholesalePrice' => filled($p->wholesale_price) ? (float) $p->wholesale_price : null,
            'wholesaleMinQty' => (int) ($p->wholesale_min_qty ?? 1),
            'wholesaleUnit' => (filled($p->wholesale_unit) && !is_numeric($p->wholesale_unit)) ? $p->wholesale_unit : 'unidades',
            'sizes' => $p->sizes,
            // Vacío salvo que el modelo tenga de verdad más de un color.
            'variantes' => $variantes,
            'color' => $variantes ? AgrupadorModelos::colorActivo($variantes, $img) : null,
            // Una sola serializacion para los dos motores y la ficha publica.
            'realVariants' => \App\Storefront\VariantPresenter::forProduct($p, $img, $cp),
        ];
    }

    public function facets(Project $project): \Illuminate\Support\Collection
    {
        return ProductAttribute::allProjects()
            ->where('project_id', $project->id)
            ->where('is_active', true)
            ->where('is_filterable', true)
            ->whereHas('values', fn ($query) => $query->where('is_active', true)
                ->where(function ($values) use ($project) {
                    $values->whereHas('products', fn ($products) => $products
                        ->where('products.project_id', $project->id)->where('products.is_available', true))
                        ->orWhereHas('variants', fn ($variants) => $variants
                            ->where('product_variants.project_id', $project->id)->where('product_variants.is_active', true));
                }))
            ->with(['values' => fn ($query) => $query->where('is_active', true)
                ->where(function ($values) use ($project) {
                    $values->whereHas('products', fn ($products) => $products
                        ->where('products.project_id', $project->id)->where('products.is_available', true))
                        ->orWhereHas('variants', fn ($variants) => $variants
                            ->where('product_variants.project_id', $project->id)->where('product_variants.is_active', true));
                })])
            ->orderBy('sort_order')->orderBy('name')->get();
    }
}
