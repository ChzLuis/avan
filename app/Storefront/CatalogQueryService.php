<?php

namespace App\Storefront;

use App\Models\Project;
use App\Models\StoreCatalogProfile;
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
     * Devuelve productos activos del proyecto, filtrados/ordenados/paginados en DB.
     */
    public function paginate(Project $project, Request $request, ?StoreCatalogProfile $profile = null): LengthAwarePaginator
    {
        $query = $project->products()->where('is_available', true)->with(['mainImage', 'category']);

        // Perfil de catálogo: se aplica ANTES de cualquier otro filtro para acotar
        // el alcance. Un perfil restringe a: sus productos asignados + los productos
        // de sus categorías asignadas. Si el perfil no tiene ninguna asignación se
        // trata como "sin restricción" (sólo aporta identidad visual).
        if ($profile) {
            $this->applyProfileScope($query, $project, $profile);
        }

        // Búsqueda (escapada, columnas acotadas)
        $search = trim((string) $request->query('q', ''));
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

        if (is_numeric($request->query('min_price'))) {
            $query->where('price', '>=', max(0, (float) $request->query('min_price')));
        }
        if (is_numeric($request->query('max_price'))) {
            $query->where('price', '<=', max(0, (float) $request->query('max_price')));
        }

        // Orden (whitelist; nunca columna cruda del request)
        $sort = (string) $request->query('sort', 'recommended');
        match ($filtro === 'new' && $sort === 'recommended' ? 'newest' : (in_array($sort, self::SORTS, true) ? $sort : 'recommended')) {
            'price_asc' => $query->orderBy('price')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price')->orderByDesc('id'),
            'name' => $query->orderBy('name')->orderBy('id'),
            'newest' => $query->latest()->orderByDesc('id'),
            default => $query->orderBy('sort_order')->orderByDesc('id'),
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
        // Shape unificado: claves canónicas (computienda) + alias (ecommerce).
        return [
            'id' => $p->id,
            'name' => $p->name,
            'price' => (float) $p->price,
            'comparePrice' => $cp, 'cp' => $cp,
            'image' => $img, 'img' => $img,
            'category' => $p->category?->name, 'cat' => $p->category?->name,
            'catId' => (string) $p->category_id,
            'parentId' => $p->category?->parent_id ? (string) $p->category->parent_id : null,
            'sku' => $p->sku,
            'stock' => $p->stock,
            'url' => \App\Support\ImageVariants::productUrl($p->project ?? \App\Models\Project::where('slug', $slug)->first(), $p->id),
            'wholesalePrice' => filled($p->wholesale_price) ? (float) $p->wholesale_price : null,
            'wholesaleMinQty' => (int) ($p->wholesale_min_qty ?? 1),
            'wholesaleUnit' => (filled($p->wholesale_unit) && !is_numeric($p->wholesale_unit)) ? $p->wholesale_unit : 'unidades',
            'sizes' => $p->sizes,
        ];
    }
}
