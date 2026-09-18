<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\CatalogList;
use App\Models\CatalogValue;
use App\Models\ImportLog;
use App\Models\Project;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $categories = $project->categories()
            ->where('is_active', true)->whereNull('parent_id')->where('type', 'product')
            ->with(['children' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')->get();
        $products   = $project->products()->with(['category','images'])->orderBy('sort_order')->get();

        $brandList    = $project->catalogLists()->where('type', 'brand')->first();
        $brands       = $brandList ? $brandList->values()->where('is_active', true)->orderBy('sort_order')->get() : collect();

        $unitList     = $project->catalogLists()->where('type', 'unit')->first();
        $units        = $unitList ? $unitList->values()->where('is_active', true)->orderBy('sort_order')->get() : collect();

        $supplierList = $project->catalogLists()->where('type', 'proveedor')->first();
        $suppliers    = $supplierList ? $supplierList->values()->where('is_active', true)->orderBy('sort_order')->get() : collect();

        $locationList = $project->catalogLists()->where('type', 'ubicacion')->first();
        $locations    = $locationList ? $locationList->values()->where('is_active', true)->orderBy('sort_order')->get() : collect();

        $taxList      = $project->catalogLists()->where('type', 'impuesto')->first();
        $taxes        = $taxList ? $taxList->values()->where('is_active', true)->orderBy('sort_order')->get() : collect();

        $allCatalogs  = $project->catalogLists()->where('is_active', true)->count();

        // Perfiles de catálogo activos — alimentan el modal "Catálogo PDF".
        $pdfProfiles  = $project->catalogProfiles()->where('is_enabled', true)->orderBy('sort_order')->get(['id', 'name']);

        // ¿Se muestra el campo de tallas/variantes? El rubro no sirve para decidirlo
        // (casi todas las tiendas son "retail" pero muy pocas venden ropa), así que
        // manda el interruptor de Configuración. Si nunca se configuró, se deduce de
        // los datos: si el negocio YA cargó tallas en algún producto, se sigue mostrando.
        $ajusteVariantes = $project->setting('feature_variantes');
        $usaVariantes = $ajusteVariantes !== null && $ajusteVariantes !== ''
            ? $ajusteVariantes === '1'
            : $project->products()->where('options', 'like', '%"sizes"%')->exists();

        return view('catalog.products.index', compact('project', 'categories', 'products', 'brands', 'units', 'suppliers', 'locations', 'taxes', 'allCatalogs', 'pdfProfiles', 'usaVariantes'));
    }

    /**
     * Catálogo imprimible (PDF vía el diálogo del navegador, mismo patrón que
     * las facturas). Filtros: ?category_id= (incluye subcategorías),
     * ?profile_id= (mismo alcance que el perfil aplica en la tienda),
     * ?prices=retail|wholesale|none.
     */
    public function catalogPdf(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        /* En una tienda POR COTIZACION los precios no se publican: el
           catalogo salia con "PEN 0.00" en cada producto, que es peor que
           no poner nada. El parametro `prices` sigue mandando si se pide
           explicitamente, para quien quiera un catalogo interno. */
        $porCotizacion = (string) ($project->setting('store_mode') ?? 'direct') === 'quote'
            && (string) ($project->setting('quote_price_display') ?? 'show') === 'hide';

        $prices = in_array($request->query('prices'), ['retail', 'wholesale', 'none'], true)
            ? $request->query('prices')
            : ($porCotizacion ? 'none' : 'retail');

        // Contenido: main (solo la foto principal por modelo) o full (cada
        // color desplegado como producto propio, con su tarjeta y su foto en
        // grande). Los colores salen de options.color_images, asi que basta la
        // foto principal: ninguna consulta extra. 'variants' se acepta como
        // alias de full por compatibilidad con enlaces guardados. En tiendas
        // sin colores ambos modos producen el PDF de siempre.
        $content = in_array($request->query('content'), ['main', 'variants', 'full'], true)
            ? $request->query('content') : 'full';

        $query = $project->products()->where('is_available', true)
            ->with(['images' => fn ($q) => $q->where('is_main', true), 'category.parent', 'marca']);

        $profile  = null;
        $category = null;

        if ($pid = $request->integer('profile_id')) {
            $profile = \App\Modules\Tienda\Models\StoreCatalogProfile::where('project_id', $project->id)->findOrFail($pid);

            // Misma regla que CatalogQueryService::applyProfileScope (la tienda):
            // productos directos ∪ categorías asignadas (raíces expandidas a hijas),
            // respetando la política de huérfanos del proyecto.
            $catIds = $profile->categories()->pluck('categories.id');
            $expanded = collect();
            if ($catIds->isNotEmpty()) {
                $roots    = $project->categories()->whereIn('id', $catIds)->whereNull('parent_id')->pluck('id');
                $childIds = $roots->isNotEmpty()
                    ? $project->categories()->where('is_active', true)->whereIn('parent_id', $roots)->pluck('id')
                    : collect();
                $expanded = $catIds->merge($childIds)->unique()->values();
            }
            $productIds = $profile->products()->pluck('products.id');

            if ($expanded->isNotEmpty() || $productIds->isNotEmpty()) {
                $orphanPolicy = (string) $project->setting('catalog_profile_orphan_policy', 'hide');
                $query->where(function ($q) use ($expanded, $productIds, $orphanPolicy) {
                    if ($productIds->isNotEmpty()) $q->orWhereIn('id', $productIds->all());
                    if ($expanded->isNotEmpty())   $q->orWhereIn('category_id', $expanded->all());
                    if ($orphanPolicy === 'show_all') $q->orWhereDoesntHave('catalogProfiles');
                });
            }
        } elseif ($cid = $request->integer('category_id')) {
            $category = $project->categories()->findOrFail($cid);
            $childIds = $project->categories()->where('parent_id', $cid)->pluck('id');
            $query->where(fn ($q) => $q->where('category_id', $cid)->orWhereIn('category_id', $childIds));
        }

        $products = $query->orderBy('sort_order')->orderBy('name')->get();

        // Agrupacion del catalogo: por rubro (lo normal, el cliente busca "cables")
        // o por marca, para cuando pide "que tienes de Bticino". Es el mismo
        // catalogo visto de dos maneras; se elige al exportar, no se duplica.
        $agrupar = $request->query('agrupar') === 'marca' ? 'marca' : 'categoria';

        // El prefijo "~" manda "Sin categoria"/"Sin marca" al final del orden.
        $groups = $products->groupBy(function ($p) use ($agrupar) {
            if ($agrupar === 'marca') {
                return $p->marca?->label ?: '~Otras marcas';
            }
            $c = $p->category;
            if (!$c) return '~Sin categoría';
            return $c->parent ? $c->parent->name : $c->name;
        })->sortKeys()->mapWithKeys(fn ($items, $key) => [ltrim($key, '~') => $items]);

        // Marcas del catalogo exportado, para la portada: es lo que comunica que
        // la distribuidora no trabaja una sola marca.
        $marcasCatalogo = $products->map(fn ($p) => $p->marca?->label)
            ->filter()->unique()->sort()->values();


        $settings = $project->settings()->pluck('value', 'key');

        // Presentación: densidad de la grilla y portada.
        $layout = in_array($request->query('layout'), ['grid2', 'grid3', 'grid4'], true)
            ? $request->query('layout') : 'grid3';
        $cover  = $request->query('cover', '1') === '1';

        // URL pública para el QR: el perfil tiene la suya propia.
        $storeUrl = $profile
            ? \App\Modules\Tienda\Support\StorefrontNavigation::profileUrl($project, $profile->slug)
            : \App\Modules\Tienda\Support\StorefrontNavigation::publicUrl($project);

        return view('catalog.products.catalog-pdf', compact(
            'project', 'groups', 'prices', 'profile', 'category', 'settings',
            'layout', 'cover', 'storeUrl', 'content', 'agrupar', 'marcasCatalogo'
        ));
    }

    /** "S, M, L" → options.sizes (conserva otras claves de options). */
    private function applySizes(array $data, ?Product $product = null): array
    {
        if (!array_key_exists('sizes', $data)) return $data;
        $sizes = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', (string) ($data['sizes'] ?? '')))));
        // Parte de lo que ya se acumulo en `$data['options']` (p. ej. las
        // etiquetas) y no del producto: si se releyera del modelo, el ultimo
        // en escribir borraria lo que puso el anterior.
        $options = (array) ($data['options'] ?? $product?->options ?? []);
        if ($sizes) $options['sizes'] = array_slice($sizes, 0, 20);
        else unset($options['sizes']);
        $data['options'] = $options ?: null;
        unset($data['sizes']);

        return $data;
    }

    /**
     * Etiquetas del producto → options.etiquetas.
     *
     * Llega como JSON en un campo oculto porque el formulario es una lista de
     * casillas mas una etiqueta personalizada con sus propios campos: mandarlo
     * como estructura evita inventar veinte nombres de input. El saneo lo hace
     * `EtiquetasProducto`, que descarta claves inventadas y colores que no sean
     * hexadecimales.
     */
    private function applyEtiquetas(array $data, ?Product $product = null): array
    {
        if (!array_key_exists('etiquetas', $data)) return $data;

        $entrada = json_decode((string) ($data['etiquetas'] ?? ''), true);
        $options = (array) ($data['options'] ?? $product?->options ?? []);

        $limpias = is_array($entrada) ? \App\Support\EtiquetasProducto::normaliza($entrada) : null;
        if ($limpias) $options['etiquetas'] = $limpias;
        else unset($options['etiquetas']);

        $data['options'] = $options ?: null;
        unset($data['etiquetas']);

        return $data;
    }

    /**
     * Guarda (o retira) la ficha tecnica del producto.
     *
     * El PDF se guarda bajo `fichas/<project_id>/` para que un tenant no pueda
     * pisar el archivo de otro aunque coincida el nombre. Al reemplazarlo se
     * borra el anterior: si no, cada cambio de ficha dejaria un huerfano en el
     * disco que nadie vuelve a mirar.
     */
    /**
     * Sube la ficha tecnica (PDF) de un producto.
     *
     * Va aparte del guardado normal porque el editor manda el producto como
     * JSON y un archivo no viaja ahi. El enlace externo si viaja en el JSON:
     * es solo texto.
     */
    public function subirFichaTecnica(Request $request, Product $product)
    {
        $project = app('active_project');
        abort_unless($product->project_id === $project->id, 403);

        $request->validate([
            'ficha' => 'required|file|extensions:pdf|mimetypes:application/pdf|max:10240',
        ]);

        $anterior = $product->ficha_tecnica_archivo;
        $ruta = $request->file('ficha')->store('fichas/'.$project->id, 'public');
        $product->update(['ficha_tecnica_archivo' => $ruta]);
        // Al reemplazar se borra el anterior: cada cambio dejaria si no un
        // huerfano en disco que nadie vuelve a mirar.
        if ($anterior && $anterior !== $ruta) Storage::disk('public')->delete($anterior);

        return response()->json([
            'ficha_tecnica_archivo' => $ruta,
            'url'    => asset('storage/'.$ruta),
            'nombre' => basename($ruta),
        ]);
    }

    /** Retira la ficha tecnica y borra el PDF del disco. */
    public function quitarFichaTecnica(Product $product)
    {
        abort_unless($product->project_id === app('active_project')->id, 403);

        if ($product->ficha_tecnica_archivo) {
            Storage::disk('public')->delete($product->ficha_tecnica_archivo);
        }
        $product->update(['ficha_tecnica_archivo' => null]);

        return response()->json(['ok' => true]);
    }

    private function applyFichaTecnica(Request $request, array $data, ?Product $product = null): array
    {
        $anterior = $product?->ficha_tecnica_archivo;

        if ($request->boolean('ficha_tecnica_quitar')) {
            if ($anterior) Storage::disk('public')->delete($anterior);
            $data['ficha_tecnica_archivo'] = null;
            $data['ficha_tecnica_url'] = null;
        } elseif ($request->hasFile('ficha_tecnica_pdf')) {
            $project = app('active_project');
            $data['ficha_tecnica_archivo'] = $request->file('ficha_tecnica_pdf')
                ->store('fichas/'.$project->id, 'public');
            if ($anterior) Storage::disk('public')->delete($anterior);
        }

        // El campo del formulario no existe en la tabla; no debe llegar al save.
        unset($data['ficha_tecnica_pdf'], $data['ficha_tecnica_quitar']);

        return $data;
    }

    /**
     * Destacados de la ficha: hasta 3 pares titulo/detalle con icono.
     *
     * Viven dentro de `options` como las etiquetas, no en columnas propias: son
     * datos de presentacion, varian por rubro y no se consultan por SQL.
     */
    private function applyDestacados(array $data, ?Product $product = null): array
    {
        if (!array_key_exists('destacados', $data)) return $data;

        $iconos = ['escudo', 'sol', 'sensor', 'hoja', 'rayo', 'reloj'];
        $options = (array) ($data['options'] ?? $product?->options ?? []);

        $limpios = collect((array) $data['destacados'])
            ->map(fn ($d) => [
                'i' => in_array($d['i'] ?? '', $iconos, true) ? $d['i'] : 'escudo',
                't' => mb_substr(trim(strip_tags((string) ($d['t'] ?? ''))), 0, 30),
                'd' => mb_substr(trim(strip_tags((string) ($d['d'] ?? ''))), 0, 45),
            ])
            ->filter(fn ($d) => $d['t'] !== '')
            ->take(3)
            ->values()
            ->all();

        if ($limpios) $options['destacados'] = $limpios;
        else unset($options['destacados']);

        $data['options'] = $options ?: null;
        unset($data['destacados']);

        return $data;
    }

    /**
     * Mensajes de validacion en castellano. Sin esto el editor mostraba
     * "The price field is required." al cliente.
     */
    private function mensajes(): array
    {
        return [
            'name.required'      => 'El nombre del producto es obligatorio.',
            'name.max'           => 'El nombre no puede pasar de :max caracteres.',
            'sku.max'            => 'El SKU no puede pasar de :max caracteres.',
            'barcode.max'        => 'El codigo de barras no puede pasar de :max caracteres.',
            'ficha_tecnica_url.url' => 'El enlace de la ficha tecnica debe empezar con http:// o https://.',
            'ficha_tecnica_url.max' => 'El enlace de la ficha tecnica es demasiado largo.',
            'destacados.max'     => 'Como maximo :max destacados por producto.',
            'destacados.*.t.max' => 'El titulo de un destacado no puede pasar de :max caracteres.',
            'destacados.*.d.max' => 'El detalle de un destacado no puede pasar de :max caracteres.',
            'sizes.max'          => 'La lista de tallas es demasiado larga (maximo :max caracteres).',
            'unit.max'           => 'La unidad no puede pasar de :max caracteres.',
            'wholesale_unit.max' => 'La unidad mayorista no puede pasar de :max caracteres.',
            'wholesale_min_qty.min' => 'La cantidad minima mayorista debe ser al menos :min.',
            'tax_rate.max'       => 'El impuesto no puede pasar de :max %.',
            'numeric'            => 'El campo :attribute debe ser un numero.',
            'integer'            => 'El campo :attribute debe ser un numero entero.',
            'min'                => 'El campo :attribute no puede ser negativo.',
            'max'                => 'El campo :attribute es demasiado largo.',
        ];
    }

    private function rules(): array
    {
        return [
            'category_id'      => 'nullable|integer',
            'brand_catalog_id' => 'nullable|integer',
            'name'             => 'required|string|max:100',
            'sku'              => 'nullable|string|max:80',
            'barcode'          => 'nullable|string|max:80',
            'description'      => 'nullable|string',
            'notes'            => 'nullable|string',
            // Ficha tecnica: el PDF que manda el proveedor, o el enlace a su
            // web. `extensions` valida el contenido, no solo el nombre.
            'ficha_tecnica_pdf'    => 'nullable|file|extensions:pdf|mimetypes:application/pdf|max:10240',
            'ficha_tecnica_url'    => 'nullable|url|max:500',
            'ficha_tecnica_quitar' => 'nullable|boolean',
            'destacados'           => 'nullable|array|max:3',
            'destacados.*.i'       => 'nullable|string|max:20',
            'destacados.*.t'       => 'nullable|string|max:30',
            'destacados.*.d'       => 'nullable|string|max:45',
            // El precio puede venir vacio: una distribuidora que trabaja por
            // cotizacion no publica precios y no tiene por que inventar uno.
            // Vacio se guarda como 0 (la columna no admite nulos).
            'price'             => 'nullable|numeric|min:0',
            'price_suggested'  => 'nullable|numeric|min:0',
            'price_min'        => 'nullable|numeric|min:0',
            'price_max'        => 'nullable|numeric|min:0',
            'compare_price'    => 'nullable|numeric|min:0',
            'wholesale_price'  => 'nullable|numeric|min:0',
            'wholesale_min_qty'=> 'nullable|integer|min:1',
            'wholesale_unit'   => 'nullable|string|max:30',
            'cost'             => 'nullable|numeric|min:0',
            'has_tax'          => 'boolean',
            'tax_rate'         => 'nullable|numeric|min:0|max:100',
            'unit'             => 'nullable|string|max:30',
            'stock'            => 'nullable|integer|min:0',
            'stock_min'        => 'nullable|integer|min:0',
            'stock_max'        => 'nullable|integer|min:0',
            'is_available'     => 'boolean',
            'sizes'            => 'nullable|string|max:300',
            // Estructura JSON; el saneo real lo hace EtiquetasProducto.
            'etiquetas'        => 'nullable|string|max:4000',
        ];
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $data = $request->validate($this->rules(), $this->mensajes());
        $data['price']        = $data['price'] ?? 0;
        $data['project_id']   = $project->id;
        $data['is_available'] = $request->boolean('is_available', true);
        // Las etiquetas ANTES que las tallas: `applySizes` parte de
        // `$data['options']`, asi que al reves borraria lo que escriba esta.
        $data = $this->applyEtiquetas($data);
        $data = $this->applySizes($data);
        $data = $this->applyDestacados($data);
        $data = $this->applyFichaTecnica($request, $data);
        // La descripción admite formato básico: se limpia antes de guardar.
        $data['description'] = \App\Support\RichText::clean($data['description'] ?? null);

        // El stock inicial entra por el Kardex, no por el create: así el primer
        // asiento del producto explica de dónde salieron esas unidades.
        $stockInicial = $data['stock'] ?? null;
        $data['stock'] = $stockInicial !== null ? 0 : null;

        $product = Product::create($data);

        if ($stockInicial !== null && (int) $stockInicial !== 0) {
            \App\Modules\Inventario\Support\InventoryLedger::registrar(
                $product, (int) $stockInicial, 'inicial',
                $data['cost'] ?? null, 'Stock inicial al crear el producto'
            );
        }

        if ($request->expectsJson()) {
            return response()->json(['product' => $this->productRow($product)]);
        }
        return back()->with('success', 'Producto creado.');
    }

    public function update(Request $request, Product $product)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($product->project_id === $project->id, 403);

        $data = $request->validate($this->rules(), $this->mensajes());
        $data['price']        = $data['price'] ?? 0;
        $data['is_available'] = $request->boolean('is_available');
        $data = $this->applyEtiquetas($data, $product);
        $data = $this->applyDestacados($data, $product);
        $data = $this->applyFichaTecnica($request, $data, $product);
        $data = $this->applySizes($data, $product);
        $data['description'] = \App\Support\RichText::clean($data['description'] ?? null);

        // Si el usuario cambió el stock a mano, queda registrado en el Kardex.
        // Se saca del update para que el ajuste lo escriba el ledger (y no se
        // pise el saldo que este acaba de calcular).
        $stockPedido = array_key_exists('stock', $data) ? $data['stock'] : null;
        $stockPrevio = $product->stock;
        unset($data['stock']);

        $product->update($data);

        if ($stockPedido !== null && $stockPrevio !== null && (int) $stockPedido !== (int) $stockPrevio) {
            \App\Modules\Inventario\Support\InventoryLedger::ajustarA($product, (int) $stockPedido, 'conteo', 'Ajuste manual desde el editor de producto');
        } elseif ($stockPrevio === null && $stockPedido !== null) {
            // Producto que empieza a llevar inventario: se asienta el stock inicial.
            $product->update(['stock' => (int) $stockPedido]);
            \App\Modules\Inventario\Models\InventoryMovement::create([
                'project_id' => $product->project_id, 'product_id' => $product->id,
                'user_id' => auth()->id(), 'type' => 'in', 'reason' => 'inicial',
                'quantity' => (int) $stockPedido, 'unit_cost' => $product->cost,
                'balance_after' => (int) $stockPedido, 'notes' => 'Stock inicial',
            ]);
        } elseif ($stockPedido === null && $stockPrevio !== null) {
            $product->update(['stock' => null]);   // dejó de llevar inventario
        }

        if ($request->expectsJson()) {
            $product->load('category');
            return response()->json(['product' => $this->productRow($product)]);
        }
        return back()->with('success', 'Producto actualizado.');
    }

    public function destroy(Product $product)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($product->project_id === $project->id, 403);
        $product->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('success', 'Producto eliminado.');
    }

    /** Vacía TODO el catálogo del proyecto activo — solo superadmin, acción irreversible. */
    public function purgeAll(Request $request)
    {
        abort_unless(auth()->user()?->is_superadmin, 403);
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $request->validate(['confirm_slug' => 'required|string']);
        abort_unless($request->input('confirm_slug') === $project->slug, 422, 'La confirmación no coincide con el proyecto.');

        $productIds = $project->products()->pluck('id');
        $count = $productIds->count();

        if ($count > 0) {
            \App\Models\ProductImage::whereIn('product_id', $productIds)->get()->each(function (ProductImage $image) {
                $relativePath = ltrim(str_replace('/avan/public/', '', parse_url($image->url, PHP_URL_PATH)), '/');
                $fullPath = public_path($relativePath);
                if (file_exists($fullPath)) @unlink($fullPath);
            });

            \App\Models\CatalogIntegrationItem::where('local_type', Product::class)
                ->whereIn('local_id', $productIds)->delete();

            Product::whereIn('id', $productIds)->delete();
        }

        \Illuminate\Support\Facades\Log::warning('Catálogo vaciado por superadmin', [
            'project_id' => $project->id, 'project_slug' => $project->slug,
            'user_id' => auth()->id(), 'products_deleted' => $count,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'deleted' => $count]);
        }
        return back()->with('success', "Catálogo vaciado: {$count} producto(s) eliminado(s).");
    }

    public function reorder(Request $request)
    {
        $project = app('active_project');
        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer'])['ids'];
        foreach ($ids as $order => $id) {
            Product::where('id', $id)->where('project_id', $project->id)->update(['sort_order' => $order]);
        }
        return response()->json(['ok' => true]);
    }

    /** Aplica una acción a varios productos seleccionados a la vez. */
    public function bulkAction(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $data = $request->validate([
            'ids'         => 'required|array|min:1',
            'ids.*'       => 'integer',
            'action'      => 'required|string|in:available,unavailable,stock_on,stock_off,tax_on,tax_off,delete,set_category,price_adjust,etiquetas',
            'tax_rate'    => 'nullable|numeric|min:0|max:100',
            'category_id' => 'nullable|integer',
            'price_mode'  => 'nullable|string|in:pct,fixed',
            'price_delta' => 'nullable|numeric',
            // Etiquetas en masa. Las claves se validan de verdad en
            // EtiquetasProducto: aqui solo se acota la forma.
            'etq_mode'    => 'nullable|string|in:agregar,quitar,reemplazar',
            'etq_claves'  => 'nullable|array|max:50',
            'etq_claves.*'=> 'string|max:40',
            'etq_card'    => 'nullable|boolean',
            'etq_ficha'   => 'nullable|boolean',
        ]);
        // La ruta exige catalog.editar, pero 'delete' borra de verdad: ese permiso
        // no se puede resolver en la ruta porque depende del payload.
        if ($data['action'] === 'delete') {
            abort_unless($request->user()?->can('catalog.eliminar'), 403, 'No tienes permiso para eliminar productos.');
        }

        $query = Product::whereIn('id', $data['ids'])->where('project_id', $project->id);

        $count = match ($data['action']) {
            'available'   => $query->update(['is_available' => true]),
            'unavailable' => $query->update(['is_available' => false]),
            // Activar stock solo toca los que estaban sin seguimiento (stock null),
            // para no resetear a 0 el stock de los que ya lo llevaban.
            'stock_on'    => (clone $query)->whereNull('stock')->update(['stock' => 0]),
            'stock_off'   => $this->bulkStockOff($query),
            'tax_on'      => $query->update(array_filter([
                'has_tax'  => true,
                'tax_rate' => $data['tax_rate'] ?? null,
            ], fn ($v) => $v !== null)),
            'tax_off'     => $query->update(['has_tax' => false]),
            'delete'      => $this->bulkDelete($query),
            'set_category' => $this->bulkSetCategory($query, $project, $data['category_id'] ?? null),
            'price_adjust' => $this->bulkPriceAdjust($query, $data['price_mode'] ?? null, $data['price_delta'] ?? null),
            'etiquetas'   => $this->bulkEtiquetas($query, $data),
        };

        return response()->json(['ok' => true, 'count' => $count]);
    }

    /**
     * Etiquetas en masa sobre la seleccion.
     *
     * Va producto a producto y no con un UPDATE unico porque las etiquetas
     * viven dentro de `options`, un JSON que ademas guarda las tallas y otras
     * claves: hay que fusionar, no pisar. El saneo (claves inventadas, colores)
     * lo hace EtiquetasProducto, el mismo que usa la ficha, para que etiquetar
     * en masa y etiquetar uno a uno produzcan exactamente lo mismo.
     *
     *   agregar    -> suma las claves a las que ya tenga
     *   quitar     -> le resta esas claves
     *   reemplazar -> deja exactamente estas (vacio = sin etiquetas)
     *
     * La etiqueta personalizada de cada producto se conserva siempre: es suya.
     */
    private function bulkEtiquetas($query, array $data): int
    {
        $modo   = $data['etq_mode'] ?? 'agregar';
        $claves = array_values(array_filter(array_map('strval', (array) ($data['etq_claves'] ?? []))));
        $n = 0;

        foreach ($query->get() as $producto) {
            $actual = \App\Support\EtiquetasProducto::config($producto);

            $nuevas = match ($modo) {
                'quitar'     => array_values(array_diff($actual['claves'], $claves)),
                'reemplazar' => $claves,
                default      => array_values(array_unique(array_merge($actual['claves'], $claves))),
            };

            $entrada = [
                'card'  => array_key_exists('etq_card', $data) && $data['etq_card'] !== null ? (bool) $data['etq_card'] : $actual['card'],
                'ficha' => array_key_exists('etq_ficha', $data) && $data['etq_ficha'] !== null ? (bool) $data['etq_ficha'] : $actual['ficha'],
                'claves' => $nuevas,
                'personalizada' => $actual['personalizada'],
            ];

            $options = (array) ($producto->options ?? []);
            $limpias = \App\Support\EtiquetasProducto::normaliza($entrada);
            if ($limpias) $options['etiquetas'] = $limpias;
            else unset($options['etiquetas']);

            $producto->options = $options ?: null;
            $producto->save();
            $n++;
        }

        return $n;
    }

    /**
     * Dejar de llevar inventario. El saldo no puede evaporarse sin dejar rastro:
     * se cierra en cero por el Kardex y recién entonces se pone stock a null.
     */
    private function bulkStockOff($query): int
    {
        foreach ((clone $query)->whereNotNull('stock')->where('stock', '!=', 0)->get() as $p) {
            \App\Modules\Inventario\Support\InventoryLedger::ajustarA($p, 0, 'ajuste_negativo', 'Dejó de llevar control de inventario');
        }
        return $query->update(['stock' => null]);
    }

    private function bulkSetCategory($query, $project, ?int $categoryId): int
    {
        if ($categoryId !== null) {
            // No dejar que asignen una categoría de otro proyecto por error de ids.
            abort_unless(
                \App\Models\Category::where('id', $categoryId)->where('project_id', $project->id)->exists(),
                422, 'Categoría inválida.'
            );
        }
        return $query->update(['category_id' => $categoryId]);
    }

    private function bulkPriceAdjust($query, ?string $mode, ?float $delta): int
    {
        abort_if($mode === null || $delta === null, 422, 'Falta el tipo de ajuste o el monto.');
        // GREATEST evita que un ajuste a la baja deje precios en 0 o negativos.
        $expr = $mode === 'pct'
            ? 'GREATEST(0.01, price * (1 + (' . (float) $delta . ') / 100))'
            : 'GREATEST(0.01, price + (' . (float) $delta . '))';
        return $query->update(['price' => \Illuminate\Support\Facades\DB::raw($expr)]);
    }

    private function bulkDelete($query): int
    {
        $productIds = (clone $query)->pluck('id');
        $count = $productIds->count();
        if ($count === 0) return 0;

        ProductImage::whereIn('product_id', $productIds)->get()->each(function (ProductImage $image) {
            $relativePath = ltrim(str_replace('/avan/public/', '', parse_url($image->url, PHP_URL_PATH)), '/');
            $fullPath = public_path($relativePath);
            if (file_exists($fullPath)) @unlink($fullPath);
        });
        \App\Models\CatalogIntegrationItem::where('local_type', Product::class)
            ->whereIn('local_id', $productIds)->delete();
        Product::whereIn('id', $productIds)->delete();

        return $count;
    }

    /** Clona un producto (y sus fotos, como archivos independientes) para variantes rápidas. */
    public function duplicate(Product $product)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($product->project_id === $project->id, 403);

        $copy = $product->replicate(['catalog_integration_id', 'external_sync_status']);
        $copy->name = $product->name . ' (copia)';
        $copy->sku  = $product->sku ? $product->sku . '-' . strtoupper(substr(uniqid(), -4)) : null;
        $copy->sort_order = ((int) Product::where('project_id', $project->id)->max('sort_order')) + 1;
        // La copia hereda el seguimiento de stock pero NO las unidades: duplicar
        // un producto no compra mercadería. Arrancar con el saldo del original
        // inventaría existencias que nadie ingresó y el Kardex no podría explicar.
        $copy->stock = $product->stock === null ? null : 0;
        $copy->save();

        // Se copian los archivos físicos (no solo la URL): si el original y la
        // copia apuntaran al mismo archivo, borrar la imagen de uno borraría
        // también la del otro.
        foreach ($product->images as $img) {
            $relativePath = ltrim(str_replace('/avan/public/', '', parse_url($img->url, PHP_URL_PATH)), '/');
            $sourcePath = public_path($relativePath);
            if (!file_exists($sourcePath)) continue;

            $dir = public_path('uploads/products/' . $copy->id);
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            $filename = time() . '_' . uniqid() . '.jpg';
            if (!@copy($sourcePath, $dir . '/' . $filename)) continue;

            $copy->images()->create([
                'url'        => asset('uploads/products/' . $copy->id . '/' . $filename),
                'is_main'    => $img->is_main,
                'sort_order' => $img->sort_order,
            ]);
        }

        return response()->json(['product' => $this->productRow($copy->fresh(['category', 'images']))]);
    }

    public function export()
    {
        $project  = app('active_project');
        $ids      = request('ids');
        $query    = $project->products()->with(['category', 'category.parent'])->orderBy('sort_order');
        if (is_array($ids) && count($ids) > 0) {
            $query->whereIn('id', $ids);
        }
        $products = $query->get()->map(fn($p) => [
            'nombre'              => $p->name,
            'sku'                 => $p->sku ?? '',
            'codigo_barras'       => $p->barcode ?? '',
            'categoria'           => $p->category?->parent ? $p->category->parent->name : ($p->category?->name ?? ''),
            'subcategoria'        => $p->category?->parent ? $p->category->name : '',
            'precio'              => $p->price,
            'precio_comparacion'  => $p->compare_price ?? '',
            'precio_mayorista'    => $p->wholesale_price ?? '',
            'cantidad_mayorista'  => $p->wholesale_min_qty ?? '',
            'unidad_mayorista'    => $p->wholesale_unit ?? '',
            'costo'               => $p->cost ?? '',
            'unidad'              => $p->unit ?? '',
            'stock'               => $p->stock ?? 0,
            'disponible'          => $p->is_available ? 'si' : 'no',
            'descripcion'         => $p->description ?? '',
            'notas'               => $p->notes ?? '',
        ])->toArray();
        $filename = 'productos_' . $project->slug . '_' . now()->format('Ymd') . '.xlsx';
        $catTree  = $project->categories()->whereNull('parent_id')->where('is_active', true)
            ->with(['children' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')->get();
        return $this->buildProductXlsx($filename, $products, $catTree);
    }

    public function template()
    {
        $project = app('active_project');
        $catTree = $project->categories()->whereNull('parent_id')->where('is_active', true)
            ->with(['children' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')->get();
        return $this->buildProductXlsx('plantilla_productos.xlsx', [], $catTree);
    }

    private function buildProductXlsx(string $filename, array $data, $catTree = null): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Columnas con sus keys para import, label display, sección y color
        // sección: 'base'=negro, 'min'=azul minorista, 'may'=verde mayorista, 'extra'=gris
        $cols = [
            ['nombre',             'PRODUCTO',          'base', true],
            ['sku',                'SKU',               'base', false],
            ['categoria',          'CATEGORÍA',         'base', false],
            ['subcategoria',       'SUB CATEGORÍA',     'base', false],
            ['unidad',             'UNID.',             'min',  false],
            ['precio',             'P. UNIT',           'min',  true],
            ['precio_comparacion', 'P. VENTA',          'min',  false],
            ['unidad_mayorista',   'UNID.',             'may',  false],
            ['precio_mayorista',   'P. X MAYOR',        'may',  false],
            ['precio_comparacion', 'P. VENTA',          'may',  false],
            ['cantidad_mayorista', 'CANT. MÍN.',        'may',  false],
            ['costo',              'COSTO',             'extra',false],
            ['stock',              'STOCK',             'extra',false],
            ['disponible',        'VISIBLE',            'extra',false],
            ['descripcion',        'DESCRIPCIÓN',       'extra',false],
            ['notas',              'NOTAS',             'extra',false],
        ];

        // Keys únicos para import (precio_comparacion aparece dos veces visualmente)
        $importKeys = ['nombre','sku','categoria','subcategoria','unidad','precio','precio_comparacion',
                       'unidad_mayorista','precio_mayorista','cantidad_mayorista','costo','stock','disponible','descripcion','notas'];

        $colorMap = [
            'base'  => ['header' => '2D3748', 'sub' => '4A5568'],
            'min'   => ['header' => '1E40AF', 'sub' => '3B82F6'],
            'may'   => ['header' => '166534', 'sub' => '16A34A'],
            'extra' => ['header' => '6B7280', 'sub' => '9CA3AF'],
        ];

        $spreadsheet = new Spreadsheet();
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Productos');

        // Fila 1: Grupos de sección (merged)
        $sections = [
            ['label' => '',            'color' => '2D3748', 'cols' => 4], // base
            ['label' => 'MINORISTA',   'color' => '1E40AF', 'cols' => 3], // min
            ['label' => 'MAYORISTA',   'color' => '166534', 'cols' => 4], // may
            ['label' => 'DATOS EXTRA', 'color' => '6B7280', 'cols' => 5], // extra
        ];
        $colIdx = 1;
        foreach ($sections as $sec) {
            $startCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $endCol   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + $sec['cols'] - 1);
            if ($sec['cols'] > 1) $sheet1->mergeCells("{$startCol}1:{$endCol}1");
            $sheet1->setCellValue("{$startCol}1", $sec['label']);
            $sheet1->getStyle("{$startCol}1:{$endCol}1")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $sec['color']]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $colIdx += $sec['cols'];
        }
        $sheet1->getRowDimension(1)->setRowHeight(22);

        // Fila 2: Encabezados de columna (key para import) — fila oculta
        $colIdx = 1;
        foreach ($cols as [$key, $label, $sec, $req]) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx) . '2';
            $sheet1->setCellValue($cell, $key);
            $colIdx++;
        }
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($cols));
        $sheet1->getStyle('A2:' . $lastColLetter . '2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 8, 'color' => ['rgb' => '9CA3AF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
        ]);
        $sheet1->getRowDimension(2)->setRowHeight(14);

        // Fila 3: Labels visuales con colores por sección
        $colIdx = 1;
        foreach ($cols as [$key, $label, $sec, $req]) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheet1->setCellValue("{$colLetter}3", $label . ($req ? ' *' : ''));
            $sheet1->getStyle("{$colLetter}3")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorMap[$sec]['sub']]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
            ]);
            $colIdx++;
        }
        $sheet1->getRowDimension(3)->setRowHeight(20);

        // Anchos de columna
        $widths = [35, 15, 20, 20, 12, 12, 12, 15, 12, 12, 12, 12, 10, 10, 35, 25];
        foreach ($widths as $i => $w) {
            $sheet1->getColumnDimensionByColumn($i + 1)->setWidth($w);
        }

        // Filas de datos
        if (empty($data)) {
            $example = ['Arroz Premium 1kg','ARR-001','Abarrotes','Granos y Cereales','unidad','15.90','19.90','saco 25kg','12.00','19.90','25','10.00','100','si','Arroz de grano largo',''];
            $colIdx = 1;
            foreach ($example as $val) {
                $sheet1->setCellValue([$colIdx, 4], $val);
                $colIdx++;
            }
            $sheet1->getStyle('A4:' . $lastColLetter . '4')->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
                'font' => ['italic' => true, 'color' => ['rgb' => '6B7280']],
            ]);
        } else {
            $row = 4;
            foreach ($data as $i => $item) {
                $bg = $i % 2 === 0 ? 'FFFFFF' : 'F8FAFC';
                // precio_comparacion aparece en col 7 (min) y col 10 (may) — mismo valor
                $rowData = [
                    $item['nombre'] ?? '', $item['sku'] ?? '', $item['categoria'] ?? '', $item['subcategoria'] ?? '',
                    $item['unidad'] ?? '', $item['precio'] ?? '', $item['precio_comparacion'] ?? '',
                    $item['unidad_mayorista'] ?? '', $item['precio_mayorista'] ?? '', $item['precio_comparacion'] ?? '',
                    $item['cantidad_mayorista'] ?? '',
                    $item['costo'] ?? '', $item['stock'] ?? '', $item['disponible'] ?? '',
                    $item['descripcion'] ?? '', $item['notas'] ?? '',
                ];
                foreach ($rowData as $ci => $val) {
                    $sheet1->setCellValue([$ci + 1, $row], $val);
                }
                $sheet1->getStyle('A' . $row . ':' . $lastColLetter . $row)->applyFromArray([
                    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                ]);
                $row++;
            }
        }
        $instrucciones = [
            // [columna, descripción, ejemplo, obligatorio]
            // — Columnas del Excel (en el mismo orden que aparecen) —
            ['nombre',             'Nombre del producto. Texto libre, máx. 200 caracteres. Obligatorio.',              'Arroz Premium 1kg',  true],
            ['sku',                'Tu código interno. Si coincide con un producto existente, se actualiza en lugar de crear uno nuevo.', 'ARR-001', false],
            ['categoria',          'Nombre exacto de la categoría padre. Ver hoja "Categorías" para las opciones válidas.', 'Abarrotes',    false],
            ['subcategoria',       'Nombre exacto de la subcategoría. Si se completa, la categoría padre es ignorada.', 'Granos y Cereales', false],
            ['unidad',             'Unidad de medida: unidad / kg / lt / caja / par / docena / etc.',                  'unidad',             false],
            ['precio',             'Precio minorista (unitario) con punto decimal. Obligatorio.',                      '15.90',              true],
            ['precio_comparacion', 'Precio anterior o precio de referencia. Se muestra tachado en la tienda. Déjalo vacío si no aplica.', '19.90', false],
            ['unidad_mayorista',   'Descripción de la presentación para precio mayorista. Ej: saco 25kg, caja x12.',   'saco 25kg',          false],
            ['precio_mayorista',   'Precio especial por volumen. Se activa cuando la cantidad mínima es alcanzada.',    '12.00',              false],
            ['cantidad_mayorista', 'Cantidad mínima de unidades para activar el precio mayorista.',                    '25',                 false],
            ['costo',              'Tu precio de compra o costo interno. No se muestra en la tienda.',                 '10.00',              false],
            ['stock',              'Unidades disponibles en inventario. Deja vacío o 0 si no controlas stock.',        '100',                false],
            ['disponible',         'Visibilidad en la tienda. Escribe exactamente: si → visible, no → oculto.',        'si',                 false],
            ['descripcion',        'Descripción del producto que verá el cliente.',                                    'Arroz de grano largo, seleccionado.', false],
            ['notas',              'Notas internas de tu equipo. No se muestran en la tienda.',                        'Stock en bodega 2',  false],
            // — Columna adicional aceptada al importar (no aparece en el Excel exportado) —
            ['codigo_barras',      'Columna opcional: EAN / UPC / código de barras del producto. Puedes agregarla al Excel si la necesitas.', '7501234567890', false],
        ];
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Instrucciones');
        $sheet2->mergeCells('A1:D1');
        $sheet2->setCellValue('A1', 'Instrucciones de llenado — Productos');
        $sheet2->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D6F42']],
        ]);
        $sheet2->getRowDimension(1)->setRowHeight(28);

        // Fila 2: notas generales
        $sheet2->mergeCells('A2:D2');
        $sheet2->setCellValue('A2', '• Las columnas se detectan por su nombre (fila oculta gris de la hoja Productos) — no importa el orden.  • Si el SKU ya existe, el producto se actualiza. Si no hay SKU, se busca por nombre.  • La columna "disponible" acepta: si → visible, no → oculto.');
        $sheet2->getStyle('A2')->applyFromArray([
            'font'      => ['size' => 8, 'italic' => true, 'color' => ['rgb' => '374151']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet2->getRowDimension(2)->setRowHeight(36);

        foreach (['A3' => 'Columna', 'B3' => '¿Qué escribir?', 'C3' => '¿Obligatorio?', 'D3' => 'Ejemplo'] as $cell => $val) {
            $sheet2->setCellValue($cell, $val);
        }
        $sheet2->getStyle('A3:D3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '155534']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        foreach ($instrucciones as $i => $instr) {
            $row = $i + 4;
            $bg  = $i % 2 === 0 ? 'FFFFFF' : 'F6FBF8';
            // Resaltar la última fila (codigo_barras — columna opcional no en el Excel)
            if ($instr[0] === 'codigo_barras') $bg = 'FFFBEB';
            $sheet2->setCellValue('A' . $row, $instr[0]);
            $sheet2->setCellValue('B' . $row, $instr[1]);
            $sheet2->setCellValue('C' . $row, $instr[3] ? 'Sí' : 'No');
            $sheet2->setCellValue('D' . $row, $instr[2]);
            $sheet2->getStyle("A{$row}:D{$row}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            ]);
        }
        $sheet2->getColumnDimension('A')->setWidth(22);
        $sheet2->getColumnDimension('B')->setWidth(60);
        $sheet2->getColumnDimension('C')->setWidth(14);
        $sheet2->getColumnDimension('D')->setWidth(22);

        // ── Hoja 3: Categorías disponibles ───────────────────────────────────
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Categorías');
        $sheet3->mergeCells('A1:C1');
        $sheet3->setCellValue('A1', 'Categorías disponibles — copia exactamente el nombre');
        $sheet3->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D6F42']],
        ]);
        $sheet3->getRowDimension(1)->setRowHeight(24);
        foreach (['A2' => 'Categoría padre', 'B2' => 'Subcategoría', 'C2' => 'Usar en columna'] as $cell => $val) {
            $sheet3->setCellValue($cell, $val);
        }
        $sheet3->getStyle('A2:C2')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '155534']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $catRow = 3;
        if ($catTree && $catTree->count()) {
            foreach ($catTree as $i => $cat) {
                $bg = $i % 2 === 0 ? 'FFFFFF' : 'F6FBF8';
                if ($cat->children->isEmpty()) {
                    $sheet3->setCellValue('A' . $catRow, $cat->name);
                    $sheet3->setCellValue('B' . $catRow, '');
                    $sheet3->setCellValue('C' . $catRow, 'categoria');
                    $sheet3->getStyle("A{$catRow}:C{$catRow}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]]]);
                    $catRow++;
                } else {
                    foreach ($cat->children as $j => $sub) {
                        $bg2 = ($catRow % 2 === 0) ? 'FFFFFF' : 'F6FBF8';
                        $sheet3->setCellValue('A' . $catRow, $cat->name);
                        $sheet3->setCellValue('B' . $catRow, $sub->name);
                        $sheet3->setCellValue('C' . $catRow, 'categoria + subcategoria');
                        $sheet3->getStyle("A{$catRow}:C{$catRow}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg2]]]);
                        $catRow++;
                    }
                }
            }
        } else {
            $sheet3->setCellValue('A3', 'No hay categorías creadas aún. Créalas desde el módulo de Categorías.');
            $sheet3->getStyle('A3')->getFont()->setItalic(true)->getColor()->setRGB('9CA3AF');
        }
        $sheet3->getColumnDimension('A')->setWidth(28);
        $sheet3->getColumnDimension('B')->setWidth(28);
        $sheet3->getColumnDimension('C')->setWidth(24);

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'       => 'no-cache, no-store',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function import(Request $request)
    {
        $project = app('active_project');
        $request->validate(['file' => 'required|file|extensions:csv,txt,xls,xlsx|max:10240']);
        $path        = $request->file('file')->getRealPath();
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet       = $spreadsheet->getSheet(0);
        $rows        = $sheet->toArray(null, true, true, false);
        $allCats     = $project->categories()->get()->keyBy('name');
        $validKeys   = ['nombre','sku','codigo_barras','categoria','subcategoria','precio','precio_comparacion','precio_mayorista','cantidad_mayorista','unidad_mayorista','costo','unidad','stock','disponible','descripcion','notas'];
        $cleanNum    = fn($v): ?float => ($v !== '' && $v !== null) ? (float) str_replace(',', '.', preg_replace('/[^0-9.,\-]/', '', (string)$v)) : null;
        $header  = null;
        $created = 0; $updated = 0; $skipped = 0; $errors = [];
        $sinCategoria = 0; $catsNoEncontradas = [];
        foreach ($rows as $i => $row) {
            $cleaned = array_map(fn($v) => trim((string)($v ?? '')), $row);
            $lower   = array_map('strtolower', $cleaned);
            if (!$header) {
                if (count(array_intersect($lower, $validKeys)) >= 2) { $header = $lower; }
                continue;
            }
            // Saltar filas que son encabezados visuales (todas las celdas en mayúsculas o vacías — p.ej. fila de labels del xlsx exportado)
            $nonEmpty = array_filter($cleaned, fn($v) => $v !== '');
            $allUpper = $nonEmpty && count(array_filter($nonEmpty, fn($v) => $v === strtoupper($v) && !is_numeric($v))) === count($nonEmpty);
            if ($allUpper) continue;
            $data   = array_combine($header, array_pad($cleaned, count($header), ''));
            $nombre = trim($data['nombre'] ?? '');
            if ($nombre === '' || str_starts_with($nombre, '←')) { $skipped++; continue; }
            try {
                $sku     = trim($data['sku'] ?? '') ?: null;
                $catNombre  = trim($data['categoria'] ?? '');
                $subNombre  = trim($data['subcategoria'] ?? '');
                $categoryId = null;
                if ($subNombre && isset($allCats[$subNombre])) {
                    $categoryId = $allCats[$subNombre]->id;
                } elseif ($catNombre && isset($allCats[$catNombre])) {
                    $categoryId = $allCats[$catNombre]->id;
                } elseif ($subNombre || $catNombre) {
                    $sinCategoria++;
                    $catLabel = $subNombre ?: $catNombre;
                    if (!in_array($catLabel, $catsNoEncontradas)) $catsNoEncontradas[] = $catLabel;
                }
                $payload = [
                    'project_id'    => $project->id,
                    'name'          => $nombre,
                    'sku'           => $sku,
                    'barcode'       => trim($data['codigo_barras'] ?? '') ?: null,
                    'category_id'      => $categoryId,
                    'price'            => $cleanNum($data['precio'] ?? '') ?? 0,
                    'compare_price'    => ($v = $cleanNum($data['precio_comparacion'] ?? '')) && $v > 0 ? $v : null,
                    'wholesale_price'  => ($v = $cleanNum($data['precio_mayorista'] ?? '')) && $v > 0 ? $v : null,
                    'wholesale_min_qty'=> is_numeric($data['cantidad_mayorista'] ?? '') && (int)$data['cantidad_mayorista'] > 0 ? (int)$data['cantidad_mayorista'] : null,
                    'wholesale_unit'   => trim($data['unidad_mayorista'] ?? '') ?: null,
                    'cost'             => ($v = $cleanNum($data['costo'] ?? '')) && $v > 0 ? $v : null,
                    'unit'          => trim($data['unidad'] ?? '') ?: null,
                    'stock'         => is_numeric($data['stock'] ?? '') ? (int)$data['stock'] : 0,
                    'is_available'  => strtolower($data['disponible'] ?? 'si') === 'si',
                    'description'   => trim($data['descripcion'] ?? '') ?: null,
                    'notes'         => trim($data['notas'] ?? '') ?: null,
                ];
                // El stock del Excel no se escribe directo: entra por el Kardex para
                // que quede el asiento de dónde salieron esas unidades.
                $stockImportado = $payload['stock'];
                unset($payload['stock']);

                $existing = $sku ? Product::where('project_id', $project->id)->where('sku', $sku)->first() : null;
                $existing ??= Product::where('project_id', $project->id)->where('name', $nombre)->first();
                if ($existing) {
                    $existing->update($payload);
                    $updated++;
                    $prodImp = $existing;
                } else {
                    $prodImp = Product::create($payload + ['stock' => 0]);
                    $created++;
                }
                \App\Modules\Inventario\Support\InventoryLedger::ajustarA(
                    $prodImp, $stockImportado, 'conteo',
                    'Stock fijado por importación de Excel'
                );
            } catch (\Exception $e) {
                $errors[] = '"' . $nombre . '" (fila ' . $i . '): ' . $e->getMessage();
            }
        }
        try {
            ImportLog::create([
                'project_id' => $project->id,
                'user_id'    => auth()->id(),
                'type'       => 'products',
                'filename'   => $request->file('file')?->getClientOriginalName() ?? '',
                'created'    => $created,
                'updated'    => $updated,
                'skipped'    => $skipped,
                'errors'     => $errors,
                'has_errors' => count($errors) > 0,
            ]);
        } catch (\Exception $e) {}
        $warnings = [];
        if ($sinCategoria > 0) {
            $warnings[] = $sinCategoria . ' producto' . ($sinCategoria > 1 ? 's' : '') . ' importado' . ($sinCategoria > 1 ? 's' : '') . ' sin categoría (no existe en este negocio): ' . implode(', ', $catsNoEncontradas) . '.';
        }
        return response()->json(['created' => $created, 'updated' => $updated, 'skipped' => $skipped, 'errors' => $errors, 'warnings' => $warnings]);
    }

    // ── Exportar catálogo estático para GitHub Pages ──────────────────────────
    public function exportStatic()
    {
        /** @var \App\Models\Project $project */
        $project  = app('active_project');
        $settings = $project->settings()->pluck('value', 'key');

        $categories = $project->categories()
            ->where('is_active', true)
            ->with(['products' => fn($q) => $q->where('is_available', true)->with('mainImage')->orderBy('sort_order')])
            ->orderBy('sort_order')->get();

        $waRaw = preg_replace('/\D/', '', $project->whatsapp ?? $project->phone ?? '');

        $html = view('catalog.products.export-static', compact('project', 'categories', 'settings', 'waRaw'))->render();

        $filename = 'catalogo-' . $project->slug . '.html';

        return response($html, 200, [
            'Content-Type'        => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ── Exportar para Mercado Libre ───────────────────────────────────────────
    public function exportMeli()
    {
        /** @var \App\Models\Project $project */
        $project  = app('active_project');
        $products = $project->products()->with('category')->orderBy('sort_order')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="meli_' . $project->slug . '_' . now()->format('Ymd') . '.csv"',
        ];

        $callback = function () use ($products) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($f, [
                'titulo','precio','moneda','stock_disponible','condicion','descripcion',
                'categoria','sku_vendedor','codigo_barras','costo',
                'precio_comparacion','unidad_de_medida','link_imagen',
            ]);
            foreach ($products as $p) {
                fputcsv($f, [
                    $p->name,
                    $p->price,
                    'PEN',
                    $p->stock ?? 0,
                    'new',
                    strip_tags($p->description ?? ''),
                    $p->category?->name ?? '',
                    $p->sku ?? '',
                    $p->barcode ?? '',
                    $p->cost ?? '',
                    $p->compare_price ?? '',
                    $p->unit ?? 'unidad',
                    '',
                ]);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Exportar para Rappi ───────────────────────────────────────────────────
    public function exportRappi()
    {
        /** @var \App\Models\Project $project */
        $project  = app('active_project');
        $products = $project->products()->with('category')->orderBy('sort_order')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="rappi_' . $project->slug . '_' . now()->format('Ymd') . '.csv"',
        ];

        $callback = function () use ($products) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($f, [
                'product_name','description','price','discount_price','category',
                'sub_category','sku','barcode','unit','quantity',
                'available','image_url',
            ]);
            foreach ($products as $p) {
                fputcsv($f, [
                    $p->name,
                    strip_tags($p->description ?? ''),
                    $p->price,
                    $p->compare_price ?? '',
                    $p->category?->name ?? '',
                    '',
                    $p->sku ?? '',
                    $p->barcode ?? '',
                    $p->unit ?? 'unidad',
                    $p->stock ?? 0,
                    $p->is_available ? '1' : '0',
                    '',
                ]);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Exportar para Shopee ──────────────────────────────────────────────────
    public function exportShopee()
    {
        /** @var \App\Models\Project $project */
        $project  = app('active_project');
        $products = $project->products()->with('category')->orderBy('sort_order')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="shopee_' . $project->slug . '_' . now()->format('Ymd') . '.csv"',
        ];

        $callback = function () use ($products) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($f, [
                'Product Name','Category','Brand','Description',
                'Price','Stock','SKU','Barcode',
                'Weight (kg)','Condition','Pre-order','Days to Ship',
                'Image URL 1',
            ]);
            foreach ($products as $p) {
                fputcsv($f, [
                    $p->name,
                    $p->category?->name ?? '',
                    '',
                    strip_tags($p->description ?? ''),
                    $p->price,
                    $p->stock ?? 0,
                    $p->sku ?? '',
                    $p->barcode ?? '',
                    '',
                    'new',
                    'No',
                    3,
                    '',
                ]);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Reseñas (admin) ───────────────────────────────────────────────────────
    public function reviews()
    {
        $project = project();
        $reviews = \App\Modules\Tienda\Models\Review::where('project_id', $project->id)
            ->with('product')
            ->latest()
            ->get()
            ->map(fn($r) => [
                'id'          => $r->id,
                'product_id'  => $r->product_id,
                'product'     => $r->product?->name,
                'author_name' => $r->author_name,
                'rating'      => $r->rating,
                'comment'     => $r->comment,
                'is_approved' => $r->is_approved,
                'created_at'  => $r->created_at->format('d/m/Y'),
            ]);
        return view('catalog.reviews.index', compact('reviews'));
    }

    public function approveReview(int $id)
    {
        $project = project();
        $review  = \App\Modules\Tienda\Models\Review::where('project_id', $project->id)->findOrFail($id);
        $review->update(['is_approved' => !$review->is_approved]);
        return response()->json(['ok' => true, 'is_approved' => $review->is_approved]);
    }

    public function destroyReview(int $id)
    {
        $project = project();
        \App\Modules\Tienda\Models\Review::where('project_id', $project->id)->findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }

    // ── Imágenes ──────────────────────────────────────────────────────────────
    public function uploadImage(Request $request, Product $product)
    {
        $project = app('active_project');
        abort_unless($product->project_id === $project->id, 403);
        // Se acepta cualquier foto razonable: la del móvil, la de WhatsApp o
        // la que mandó el proveedor. Encajarla en la ficha es trabajo nuestro,
        // no del usuario, así que aquí solo se comprueba que sea una imagen.
        $request->validate(['image' => 'required|file|mimes:jpg,jpeg,png,webp,gif,bmp,avif|max:20480']);

        try {
            $resultado = app(\App\Support\Imagen\ProcesadorImagenes::class)->procesar(
                $request->file('image'),
                'products/'.$product->id,
                'producto',
                disco: 'uploads',
            );
        } catch (\App\Support\Imagen\ImagenNoProcesable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        $url = $resultado->urlPrincipal();

        $isFirst = $product->images()->count() === 0;
        $image   = $product->images()->create([
            'url'        => $url,
            'is_main'    => $isFirst,
            'sort_order' => $product->images()->max('sort_order') + 1,
        ]);

        // Plantilla automatica de imagenes: si el negocio la tiene activa, la
        // foto recien subida entra por el mismo pipeline sin que nadie tenga
        // que abrir el generador. Se encola: componer no debe alargar la
        // subida ni tumbarla si algo falla.
        $this->encolarPlantilla($product, $image);

        return response()->json(['ok' => true, 'image' => ['id' => $image->id, 'url' => $image->url, 'is_main' => $image->is_main]]);
    }

    /**
     * Encola la composicion de una imagen recien creada si el negocio tiene
     * plantilla activa. Silencioso por diseno: que falle el generador no puede
     * impedir que el comerciante suba una foto.
     */
    private function encolarPlantilla(Product $product, ProductImage $image): void
    {
        try {
            $plantilla = \App\Models\ProductImageTemplate::allProjects()
                ->where('project_id', $product->project_id)
                ->where('is_active', true)->where('enabled', true)->first();

            if (! $plantilla) {
                return;
            }
            // Respeta la eleccion de galeria de la plantilla.
            if (! $image->is_main && ! ($plantilla->configCompleta()['apply_to_gallery'] ?? false)) {
                return;
            }

            $image->forceFill(['generation_status' => 'pendiente'])->save();
            \App\Jobs\GenerarImagenesProducto::dispatch($plantilla->id, [$image->id], false);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo encolar la plantilla de imagen', [
                'producto' => $product->id, 'imagen' => $image->id, 'motivo' => $e->getMessage(),
            ]);
        }
    }

    public function deleteImage(Request $request, Product $product, ProductImage $image)
    {
        $project = app('active_project');
        abort_unless($product->project_id === $project->id, 403);

        // Borrar archivo físico
        $relativePath = parse_url($image->url, PHP_URL_PATH);
        $relativePath = ltrim(str_replace('/avan/public/', '', $relativePath), '/');
        $fullPath = public_path($relativePath);
        if (file_exists($fullPath)) @unlink($fullPath);
        $wasMain = $image->is_main;
        $image->delete();

        // Si era la principal, asignar la siguiente
        if ($wasMain) {
            $next = $product->images()->orderBy('sort_order')->first();
            $next?->update(['is_main' => true]);
        }

        return response()->json(['ok' => true]);
    }

    public function setMainImage(Request $request, Product $product, ProductImage $image)
    {
        $project = app('active_project');
        abort_unless($product->project_id === $project->id, 403);

        $product->images()->update(['is_main' => false]);
        $image->update(['is_main' => true]);

        return response()->json(['ok' => true]);
    }

    // ── Helper: fila serializada ──────────────────────────────────────────────
    private function productRow(Product $p): array
    {
        return [
            'id'               => $p->id,
            'name'             => $p->name,
            'sku'              => $p->sku,
            'barcode'          => $p->barcode,
            'description'      => $p->description,
            'notes'            => $p->notes,
            'price'            => (float)$p->price,
            'price_suggested'  => $p->price_suggested !== null ? (float)$p->price_suggested : null,
            'price_min'        => $p->price_min !== null ? (float)$p->price_min : null,
            'price_max'        => $p->price_max !== null ? (float)$p->price_max : null,
            'compare_price'    => $p->compare_price !== null ? (float)$p->compare_price : null,
            'wholesale_price'  => $p->wholesale_price !== null ? (float)$p->wholesale_price : null,
            'sizes'            => implode(', ', $p->sizes),
            'wholesale_min_qty'=> $p->wholesale_min_qty,
            'wholesale_unit'   => $p->wholesale_unit,
            'cost'             => $p->cost !== null ? (float)$p->cost : null,
            'has_tax'          => (bool)$p->has_tax,
            'tax_rate'         => $p->tax_rate !== null ? (float)$p->tax_rate : 18,
            'unit'             => $p->unit,
            'stock'            => $p->stock,
            'stock_min'        => $p->stock_min,
            'stock_max'        => $p->stock_max,
            'is_available'     => (bool)$p->is_available,
            'category_id'      => $p->category_id,
            'brand_catalog_id' => $p->brand_catalog_id,
            'category_name'    => $p->category?->name,
            'images'           => $p->images->map(fn($i) => ['id' => $i->id, 'url' => $i->url, 'is_main' => $i->is_main])->values()->toArray(),
            'main_image'       => $p->mainImage?->url,
        ];
    }
}
