<?php

namespace App\Http\Controllers;

use App\Jobs\GenerarImagenesProducto;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductImageTemplate;
use App\Support\Imagen\CompositorProducto;
use App\Support\Imagen\GeneradorImagenProducto;
use App\Support\Imagen\ProcesadorImagenes;
use App\Support\Imagen\ImagenNoProcesable;
use Illuminate\Http\Request;

/**
 * Plantilla automática de imágenes de producto (constructor → Catálogo).
 *
 * Todo se resuelve contra el proyecto activo: ninguna acción acepta un
 * `project_id` del navegador, así que una tienda no puede leer ni regenerar el
 * catálogo de otra por mucho que se manipule la petición.
 */
class ProductImageTemplateController extends Controller
{
    public function __construct(private readonly GeneradorImagenProducto $generador)
    {
    }

    private function proyecto()
    {
        return app('active_project');
    }

    /** Plantilla activa del negocio; se crea con los valores por defecto. */
    private function plantilla(): ProductImageTemplate
    {
        $project = $this->proyecto();

        $plantilla = ProductImageTemplate::where('project_id', $project->id)
            ->where('is_active', true)->first();

        if (! $plantilla) {
            $plantilla = ProductImageTemplate::create([
                'project_id' => $project->id,
                'name' => 'Plantilla principal',
                'is_active' => true,
                'enabled' => false,
                'config' => ProductImageTemplate::DEFAULTS,
            ]);
            $plantilla->forceFill(['hash' => $plantilla->calcularHash()])->save();
        }

        return $plantilla;
    }

    /** Estado para pintar el panel: configuración, cifras y productos de prueba. */
    public function show()
    {
        $project = $this->proyecto();
        $plantilla = $this->plantilla();

        return response()->json([
            'ok' => true,
            'template' => $this->serializar($plantilla),
            'templates' => ProductImageTemplate::where('project_id', $project->id)
                ->orderByDesc('is_active')->orderBy('name')
                ->get(['id', 'name', 'is_active', 'enabled'])->all(),
            'stats' => $this->cifras($plantilla),
            'samples' => $this->muestras($project->id),
            // Para "Aplicar a una categoria": solo las que tienen productos.
            'categories' => \App\Models\Category::where('project_id', $project->id)
                ->where('is_active', true)->withCount('products')
                ->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
                ->filter(fn ($c) => $c->products_count > 0)
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'products' => (int) $c->products_count])
                ->values()->all(),
            'business_logo' => $this->logoDelNegocio($project),
            'logo_changed' => $this->logoCambio($plantilla),
            'defaults' => ProductImageTemplate::DEFAULTS,
        ]);
    }

    /** Guarda la configuración. No compone nada: eso se pide aparte. */
    public function save(Request $request)
    {
        $plantilla = $this->plantilla();

        $data = $request->validate([
            'name' => 'nullable|string|max:80',
            'enabled' => 'nullable|boolean',
            'config' => 'required|array',
            'config.background_type' => 'required|in:white,color,image',
            'config.background_color' => 'nullable|string|max:9',
            'config.background_image' => 'nullable|string|max:500',
            'config.product_scale' => 'required|numeric|min:10|max:100',
            'config.product_x' => 'required|numeric|min:0|max:100',
            'config.product_y' => 'required|numeric|min:0|max:100',
            'config.product_shadow' => 'required|in:none,soft',
            'config.product_autocenter' => 'nullable|boolean',
            'config.watermark_enabled' => 'nullable|boolean',
            'config.watermark_source' => 'required|in:logo,custom',
            'config.watermark_image' => 'nullable|string|max:500',
            'config.watermark_x' => 'required|numeric|min:0|max:100',
            'config.watermark_y' => 'required|numeric|min:0|max:100',
            'config.watermark_scale' => 'required|numeric|min:1|max:100',
            'config.watermark_opacity' => 'required|numeric|min:0|max:100',
            'config.logo_enabled' => 'nullable|boolean',
            'config.logo_source' => 'required|in:logo,custom',
            'config.logo_image' => 'nullable|string|max:500',
            'config.logo_x' => 'required|numeric|min:0|max:100',
            'config.logo_y' => 'required|numeric|min:0|max:100',
            'config.logo_scale' => 'required|numeric|min:1|max:100',
            'config.logo_opacity' => 'required|numeric|min:0|max:100',
            'config.aspect_ratio' => 'required|in:1:1,4:5,3:4',
            'config.output_width' => 'required|integer|min:600|max:2000',
            'config.apply_to_gallery' => 'nullable|boolean',
        ]);

        $config = array_merge(ProductImageTemplate::DEFAULTS, $data['config']);
        // Las rutas de logo/fondo se aceptan solo si apuntan a un archivo real
        // dentro de las carpetas públicas: cierra el paso a rutas inventadas.
        $compositor = app(CompositorProducto::class);
        foreach (['background_image', 'watermark_image', 'logo_image'] as $clave) {
            if (! empty($config[$clave]) && ! $compositor->rutaLocal((string) $config[$clave])) {
                $config[$clave] = null;
            }
        }

        $plantilla->fill([
            'name' => $data['name'] ?? $plantilla->name,
            'enabled' => (bool) ($data['enabled'] ?? $plantilla->enabled),
            'config' => $config,
            'updated_by' => auth()->id(),
        ]);
        $plantilla->hash = $plantilla->calcularHash();
        $plantilla->save();

        \App\Support\Imagen\ResolutorImagenProducto::olvidar();

        return response()->json([
            'ok' => true,
            'template' => $this->serializar($plantilla),
            'stats' => $this->cifras($plantilla),
        ]);
    }

    /** Enciende o apaga la plantilla sin tocar ninguna imagen. */
    public function toggle(Request $request)
    {
        $plantilla = $this->plantilla();
        $plantilla->forceFill(['enabled' => $request->boolean('enabled')])->save();
        \App\Support\Imagen\ResolutorImagenProducto::olvidar();

        return response()->json(['ok' => true, 'enabled' => $plantilla->enabled, 'stats' => $this->cifras($plantilla)]);
    }

    /** Devuelve la configuración por defecto; no borra imágenes generadas. */
    public function reset()
    {
        $plantilla = $this->plantilla();
        $plantilla->fill(['config' => ProductImageTemplate::DEFAULTS, 'updated_by' => auth()->id()]);
        $plantilla->hash = $plantilla->calcularHash();
        $plantilla->save();

        return response()->json(['ok' => true, 'template' => $this->serializar($plantilla)]);
    }

    /** Sube el logo, la marca de agua o el fondo propios de la plantilla. */
    public function upload(Request $request)
    {
        $project = $this->proyecto();
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,avif|max:8192',
            'slot' => 'required|in:background_image,watermark_image,logo_image',
        ]);

        try {
            $resultado = app(ProcesadorImagenes::class)->procesar(
                $request->file('file'),
                "plantillas-producto/{$project->id}",
                'logo',
                disco: 'uploads',
            );
        } catch (ImagenNoProcesable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'url' => $resultado->urlPrincipal()]);
    }

    /**
     * Compone UNA imagen y la devuelve en base64 para la vista previa fiel.
     * Es el mismo compositor que usa el job, así que lo que se ve aquí es
     * exactamente lo que se publicará.
     */
    public function preview(Request $request)
    {
        $project = $this->proyecto();
        $request->validate(['image_id' => 'nullable|integer']);

        $imagen = $this->imagenDeMuestra($project->id, $request->integer('image_id'));
        if (! $imagen) {
            return response()->json(['ok' => false, 'message' => 'Todavía no hay productos con foto para previsualizar.'], 422);
        }

        $plantilla = $this->plantilla();
        $compositor = app(CompositorProducto::class);
        $origen = $compositor->rutaLocal((string) $imagen->url);
        if (! $origen) {
            return response()->json(['ok' => false, 'message' => 'No se encontró el archivo de la foto.'], 422);
        }

        try {
            $bytes = $compositor->componer($origen, $plantilla);
        } catch (ImagenNoProcesable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        $mime = $compositor->extension() === 'webp' ? 'image/webp' : 'image/jpeg';

        return response()->json(['ok' => true, 'data_url' => 'data:'.$mime.';base64,'.base64_encode($bytes)]);
    }

    /**
     * Encola la generación. `scope`: product | selected | category | all.
     * Devuelve cuántas imágenes se van a componer para poder confirmarlo antes.
     */
    public function apply(Request $request)
    {
        $project = $this->proyecto();
        $plantilla = $this->plantilla();

        $data = $request->validate([
            'scope' => 'required|in:product,selected,category,all',
            'product_id' => 'nullable|integer',
            'product_ids' => 'nullable|array|max:2000',
            'product_ids.*' => 'integer',
            'category_id' => 'nullable|integer',
            'force' => 'nullable|boolean',
            'dry_run' => 'nullable|boolean',
        ]);

        // El ámbito se resuelve SIEMPRE dentro del proyecto activo.
        $productos = Product::where('project_id', $project->id);
        match ($data['scope']) {
            'product' => $productos->where('id', (int) ($data['product_id'] ?? 0)),
            'selected' => $productos->whereIn('id', $data['product_ids'] ?? []),
            'category' => $productos->where('category_id', (int) ($data['category_id'] ?? 0)),
            'all' => null,
        };
        $ids = $productos->pluck('id');

        $imagenes = ProductImage::whereIn('product_id', $ids)
            ->when(! ($plantilla->configCompleta()['apply_to_gallery'] ?? false),
                fn ($q) => $q->where('is_main', true))
            ->orderByDesc('is_main')->get(['id']);

        if ($request->boolean('dry_run')) {
            return response()->json(['ok' => true, 'count' => $imagenes->count()]);
        }

        if ($imagenes->isEmpty()) {
            return response()->json(['ok' => true, 'count' => 0, 'message' => 'No hay imágenes que generar en ese ámbito.']);
        }

        $this->generador->marcarPendientes($imagenes);
        GenerarImagenesProducto::dispatch($plantilla->id, $imagenes->pluck('id')->all(), $request->boolean('force'));

        return response()->json([
            'ok' => true,
            'count' => $imagenes->count(),
            'stats' => $this->cifras($plantilla),
        ]);
    }

    /**
     * Guarda la configuracion actual como una plantilla NUEVA y la deja activa.
     * Sirve para tener "Blanco profesional" y "Campana Navidad" y alternar sin
     * volver a ajustar los controles.
     */
    public function saveAs(Request $request)
    {
        $project = $this->proyecto();
        $data = $request->validate(['name' => 'required|string|max:80']);

        $actual = $this->plantilla();

        // Solo una activa por negocio.
        ProductImageTemplate::where('project_id', $project->id)->update(['is_active' => false]);

        $nueva = ProductImageTemplate::create([
            'project_id' => $project->id,
            'name' => $data['name'],
            'is_active' => true,
            'enabled' => $actual->enabled,
            'config' => $actual->configCompleta(),
            'updated_by' => auth()->id(),
        ]);
        $nueva->forceFill(['hash' => $nueva->calcularHash()])->save();
        \App\Support\Imagen\ResolutorImagenProducto::olvidar();

        return response()->json(['ok' => true, 'template' => $this->serializar($nueva), 'stats' => $this->cifras($nueva)]);
    }

    /** Cambia cual es la plantilla activa. No regenera nada por si sola. */
    public function activate(Request $request)
    {
        $project = $this->proyecto();
        $data = $request->validate(['id' => 'required|integer']);

        // El filtro por proyecto es la barrera: un id ajeno no se encuentra.
        $destino = ProductImageTemplate::where('project_id', $project->id)
            ->where('id', $data['id'])->firstOrFail();

        ProductImageTemplate::where('project_id', $project->id)->update(['is_active' => false]);
        $destino->forceFill(['is_active' => true])->save();
        \App\Support\Imagen\ResolutorImagenProducto::olvidar();

        return response()->json(['ok' => true, 'template' => $this->serializar($destino), 'stats' => $this->cifras($destino)]);
    }

    /** Progreso para la barra del panel. */
    public function status()
    {
        return response()->json(['ok' => true, 'stats' => $this->cifras($this->plantilla())]);
    }

    // ─────────────────────────── auxiliares ───────────────────────────

    private function serializar(ProductImageTemplate $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'enabled' => (bool) $p->enabled,
            'is_active' => (bool) $p->is_active,
            'hash' => $p->hash,
            'config' => $p->configCompleta(),
            'output_height' => $p->alto(),
        ];
    }

    /** Cifras del panel: cuántas fotos hay, cuántas generadas y cuántas al día. */
    private function cifras(ProductImageTemplate $plantilla): array
    {
        $projectId = $plantilla->project_id;
        $soloPrincipal = ! ($plantilla->configCompleta()['apply_to_gallery'] ?? false);

        $base = ProductImage::query()
            ->join('products', 'products.id', '=', 'product_images.product_id')
            ->where('products.project_id', $projectId)
            ->when($soloPrincipal, fn ($q) => $q->where('product_images.is_main', true));

        $total = (clone $base)->count();
        $generadas = (clone $base)->whereNotNull('product_images.generated_url')->count();
        $alDia = (clone $base)->where('product_images.generated_hash', $plantilla->calcularHash())->count();
        $enCurso = (clone $base)->whereIn('product_images.generation_status', ['pendiente', 'procesando'])->count();
        $errores = (clone $base)->where('product_images.generation_status', 'error')->count();

        return [
            'products' => Product::where('project_id', $projectId)->count(),
            'images' => $total,
            'generated' => $generadas,
            'up_to_date' => $alDia,
            'outdated' => max(0, $generadas - $alDia),
            'pending' => max(0, $total - $alDia),
            'in_progress' => $enCurso,
            'errors' => $errores,
            'percent' => $total > 0 ? (int) round($alDia * 100 / $total) : 0,
        ];
    }

    /** Productos con foto para el selector de vista previa. */
    private function muestras(int $projectId): array
    {
        return ProductImage::query()
            ->join('products', 'products.id', '=', 'product_images.product_id')
            ->where('products.project_id', $projectId)
            ->where('product_images.is_main', true)
            ->orderBy('products.name')
            ->limit(50)
            ->get(['product_images.id', 'products.name', 'products.id as pid'])
            ->map(fn ($r) => ['id' => (int) $r->id, 'product_id' => (int) $r->pid, 'name' => $r->name])
            ->all();
    }

    private function imagenDeMuestra(int $projectId, ?int $imageId): ?ProductImage
    {
        return ProductImage::query()
            ->join('products', 'products.id', '=', 'product_images.product_id')
            ->where('products.project_id', $projectId)
            ->when($imageId, fn ($q) => $q->where('product_images.id', $imageId))
            ->orderByDesc('product_images.is_main')
            ->select('product_images.*')
            ->first();
    }

    /**
     * ¿Cambió el logo del negocio después de generar las imágenes?
     *
     * Solo AVISA. Regenerar cientos de fotos por un cambio de logo es una
     * decisión del comerciante, no un efecto colateral silencioso de tocar
     * "Datos del negocio".
     */
    private function logoCambio(ProductImageTemplate $plantilla): bool
    {
        $c = $plantilla->configCompleta();
        $usaLogoDelNegocio = (! empty($c['logo_enabled']) && ($c['logo_source'] ?? 'logo') === 'logo')
            || (! empty($c['watermark_enabled']) && ($c['watermark_source'] ?? 'logo') === 'logo');

        if (! $usaLogoDelNegocio) {
            return false;
        }

        $tocado = $plantilla->project?->settings()
            ->whereIn('key', ['header_logo_url', 'logo_url'])
            ->max('updated_at');

        if (! $tocado) {
            return false;
        }

        // La generación más antigua que sigue vigente: si el logo se cambió
        // después, esas imágenes llevan el logo viejo.
        $ultimaGeneracion = ProductImage::query()
            ->join('products', 'products.id', '=', 'product_images.product_id')
            ->where('products.project_id', $plantilla->project_id)
            ->whereNotNull('product_images.generated_at')
            ->min('product_images.generated_at');

        return $ultimaGeneracion !== null && $tocado > $ultimaGeneracion;
    }

    private function logoDelNegocio($project): ?string
    {
        $logo = $project->settings()->where('key', 'header_logo_url')->value('value')
            ?: $project->settings()->where('key', 'logo_url')->value('value')
            ?: $project->logo_url;

        if (! $logo) {
            return null;
        }

        return str_starts_with($logo, 'http') ? $logo : asset('storage/'.ltrim($logo, '/'));
    }
}
