<?php

namespace App\Http\Controllers;

use App\Storefront\BuilderAccess;
use App\Support\Capacidades;
use App\Storefront\BuilderDraftService;
use App\Storefront\BuilderProgress;
use App\Storefront\BuilderRuleRegistry;
use App\Storefront\PublishChecklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Constructor guiado (B0): entrada, endpoints de lectura (progreso,
 * checklist, métricas) y contrato de borrador/publicación.
 *
 * El proyecto SIEMPRE sale del contexto seguro (active_project, middleware
 * project.member); ningún endpoint acepta project_id arbitrario.
 */
class StoreBuilderController extends Controller
{
    public function __construct(private readonly BuilderDraftService $drafts)
    {
    }

    private function project()
    {
        $project = app('active_project');
        abort_unless(BuilderAccess::allows($project, auth()->user()), 404);

        return $project;
    }

    public function index()
    {
        $project = $this->project();
        // Auto-reparación: proyectos creados antes de que la creación sembrara
        // las secciones de Inicio (o creados por un flujo que aún no lo hacía)
        // no deben quedar sin poder configurar su portada. Idempotente.
        \App\Support\StorefrontSections::ensure($project);
        $this->telemetry($project, 'builder_opened');

        $context = BuilderRuleRegistry::context($project);

        // ── Reutilización de datos maestros ──
        // Datos registrados en columnas antiguas del proyecto (logo, WhatsApp,
        // teléfono, dirección) se siembran como BORRADOR en la clave canónica
        // SOLO si esta está vacía. Nunca se sobrescribe información existente;
        // el usuario los ve precargados y decide publicarlos.
        $masterMap = [
            'business_name' => $project->name,
            'business_category' => $project->category,
            'logo_url' => $project->logo_url,
            'quote_whatsapp' => $project->whatsapp ?: $project->wa_phone,
            'contact_phone' => $project->phone,
            'contact_address' => $project->address,
        ];
        $seeded = false;
        foreach ($masterMap as $key => $legacyValue) {
            $current = trim((string) ($context['settings'][$key] ?? ''));
            if ($current === '' && filled($legacyValue)) {
                $this->drafts->putSetting($project, $key, (string) $legacyValue, auth()->id());
                $seeded = true;
            }
        }
        if ($seeded) {
            $context = BuilderRuleRegistry::context($project); // re-evaluar con lo sembrado
        }

        $supported = collect(\App\Support\CatalogTemplates::supported())
            ->map(fn ($t, $key) => [
                'key' => $key,
                'name' => $t['label'],
                'short' => \Illuminate\Support\Str::limit($t['description'], 70),
                'preview_bg' => $t['preview_bg'],
                'preview_accent' => $t['preview_accent'],
            ])->values()->all();

        return view('settings.builder.index', [
            'project' => $project,
            // Sucursales dentro de la etapa 01: antes era un enlace que sacaba
            // del Constructor y el usuario perdia el hilo del borrador.
            'sedes' => \App\Models\Sede::where('project_id', $project->id)
                ->orderByDesc('is_active')->orderBy('name')
                ->get(['id', 'name', 'address', 'phone', 'is_active']),
            'progress' => BuilderProgress::for($project, $context),
            'checklist' => PublishChecklist::for($project, $context),
            'settingsDraft' => $this->drafts->effectiveSettings($project),
            'hasDrafts' => $this->drafts->hasDrafts($project),
            'presets' => \App\Storefront\StagePresets::all(),
            'templates' => $supported,
            'blocks' => $this->homeBlocks($project),
            // 'root' marca las categorias que la portada muestra como bloques
            // principales: el constructor las separa para saber a cuales conviene
            // poner foto primero. 'products' ayuda a priorizar las mas surtidas.
            'storeCategories' => $project->categories()->where('is_active', true)
                ->withCount('products')
                ->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'image_url', 'parent_id'])
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'image' => $this->publicAsset($c->image_url),
                    'root' => $c->parent_id === null,
                    'products' => (int) ($c->products_count ?? 0),
                ])->values()->all(),
            'saleProducts' => $project->products()->where('is_available', true)
                ->whereNotNull('compare_price')->whereColumn('compare_price', '>', 'price')
                ->orderBy('name')->limit(200)->get(['id', 'name', 'price', 'compare_price'])
                ->map(fn ($pr) => ['id' => $pr->id, 'name' => $pr->name, 'price' => (float) $pr->price, 'compare' => (float) $pr->compare_price])->values()->all(),
            'allProductsLite' => $project->products()->where('is_available', true)
                ->orderBy('name')->limit(300)->get(['id', 'name', 'price'])
                ->map(fn ($pr) => ['id' => $pr->id, 'name' => $pr->name, 'price' => (float) $pr->price])->values()->all(),
            'catalogProfiles' => $project->catalogProfiles()->orderBy('sort_order')->orderBy('name')
                ->get(['id', 'name', 'slug', 'menu_label', 'is_enabled', 'show_in_menu'])
                ->map(fn ($cp) => ['id' => $cp->id, 'name' => $cp->name, 'slug' => $cp->slug,
                    'menu_label' => (string) ($cp->menu_label ?? ''), 'is_enabled' => (bool) $cp->is_enabled,
                    'show_in_menu' => (bool) $cp->show_in_menu])->values()->all(),
            'storePages' => $project->storePages()->orderBy('key')->get(),
            'storeMenu' => tap(\App\Support\StorefrontNavigation::ensure($project))->load(['rootItems.children']),
            'headerSettings' => \App\Support\StorefrontNavigation::headerSettings($project),
            'menuCategories' => $project->categories()->where('is_active', true)->with('parent')->orderBy('sort_order')->orderBy('name')->get(),
            'pages' => $project->storePages()->orderBy('key')->get(),
            'profilesEnabled' => (string) $project->setting('catalog_profiles_enabled', '0') === '1',
            'orphanPolicy' => in_array($project->setting('catalog_profile_orphan_policy', 'hide'), ['hide', 'show_all'], true)
                ? $project->setting('catalog_profile_orphan_policy', 'hide') : 'hide',
        ]);
    }

    public function progress()
    {
        $project = $this->project();

        return response()->json(BuilderProgress::for($project));
    }

    public function checklist()
    {
        $project = $this->project();

        return response()->json(PublishChecklist::for($project));
    }

    public function catalogMetrics()
    {
        $project = $this->project();
        $context = BuilderRuleRegistry::context($project);

        return response()->json(['metrics' => $context['counts']]);
    }

    /** Autosave de settings → SIEMPRE a borrador (nunca a producción). */
    public function saveDraftSettings(Request $request)
    {
        $project = $this->project();
        $data = $request->validate([
            'settings' => ['required', 'array', 'max:60'],
            'settings.*' => ['nullable', 'string', 'max:4000'],
        ]);

        // CAPACIDADES RESTRINGIDAS (matriz de capacidades): hay ajustes que no
        // son "diseño de la tienda" y no basta el permiso para escribirlos.
        //  · SEO técnico: analítica y píxeles inyectan scripts de terceros en
        //    la tienda; robots/schema/verificaciones deciden cómo la indexa
        //    Google.  → `cap_seo_avanzado`
        //  · Motor de la tienda: cambiarlo reescribe la tienda entera.
        //    → `cap_builder_avanzado`
        // Ocultar el control en la vista no protege nada: esta ruta acepta
        // cualquier clave, así que la puerta tiene que estar AQUÍ.
        $restringidas = [
            'seo_avanzado' => [
                'ga_id', 'gtm_id', 'fb_pixel_id', 'tiktok_pixel_id',
                'robots', 'sitemap_enabled',
                'schema_type', 'schema_price_range', 'schema_opening_hours',
                'google_site_verification', 'bing_site_verification',
            ],
            'builder_avanzado' => ['catalog_template'],
        ];
        $vetadas = [];
        foreach ($restringidas as $capacidad => $claves) {
            if (! Capacidades::permite($project, auth()->user(), $capacidad)) {
                $vetadas = array_merge($vetadas, $claves);
            }
        }

        $saved = 0;
        $omitidas = [];
        foreach ($data['settings'] as $key => $value) {
            if (!is_string($key) || !preg_match('/^[a-z0-9_]{1,80}$/', $key)) continue;
            if (in_array($key, $vetadas, true)) { $omitidas[] = $key; continue; }
            $this->drafts->putSetting($project, $key, $value, auth()->id());
            $saved++;
        }

        return response()->json([
            'ok' => true,
            'saved' => $saved,
            // El cliente sabe que algo no se guardó y por qué, en vez de creer
            // que sí y descubrirlo al publicar.
            'omitidas' => $omitidas,
            'progress' => BuilderProgress::for($project),
        ]);
    }

    /**
     * Aplica el PAQUETE DE DISEÑO de un rubro (StagePresets::design):
     * tema visual + variantes de secciones + estructura de portada.
     * Todo va a BORRADOR (settings) y a draft de secciones; nada se publica.
     * El contenido ya configurado por el usuario no se pisa: las semillas solo
     * completan textos/variantes por defecto de secciones aún sin personalizar.
     */
    public function applyDesignPreset(Request $request)
    {
        $project = $this->project();
        $data = $request->validate(['key' => ['required', 'string', 'max:40']]);
        $design = \App\Storefront\StagePresets::design($data['key']);

        if (!$design) {
            return response()->json(['ok' => false, 'message' => 'Este rubro aún no tiene paquete de diseño.'], 404);
        }

        foreach (($design['settings'] ?? []) as $key => $value) {
            $this->drafts->putSetting($project, $key, (string) $value, auth()->id());
        }

        $writes = app(\App\Storefront\StoreSectionWriteService::class);
        $writes->ensureHomeSections($project);
        $sections = $project->storeSections()->where('page', 'home')->get()->keyBy('component');
        $cfg = $design['sections'] ?? [];

        foreach (($cfg['seed'] ?? []) as $component => $seed) {
            $section = $sections->get($component);
            if (!$section) continue;
            $current = $section->contentForPreview();
            $defaults = \App\Support\StorefrontSections::defaults($project)[$component]['content'] ?? [];
            // Solo sembrar si el usuario no personalizó la sección (contenido = defaults).
            $untouched = ($current === [] || $current == $defaults);
            $writes->saveDraft($project, 'home', $component, array_filter([
                'variant' => $seed['variant'] ?? null,
                'content' => $untouched ? array_merge($defaults, $seed['content'] ?? []) : null,
            ], fn ($v) => $v !== null));
        }

        foreach (($cfg['enable'] ?? []) as $component) {
            if ($sections->has($component)) {
                $writes->saveDraft($project, 'home', $component, ['is_enabled' => true]);
            }
        }

        if (!empty($cfg['order'])) {
            $position = array_flip(array_values($cfg['order']));
            foreach ($sections as $component => $section) {
                $writes->saveDraft($project, 'home', $component, [
                    'sort_order' => (($position[$component] ?? 90) + 1) * 10,
                ]);
            }
        }

        $this->telemetry($project, 'builder_design_preset', ['key' => $data['key']]);

        return response()->json(['ok' => true, 'applied' => $data['key']]);
    }

    /** Publicación transaccional: checklist → bloquear críticos → promover. */
    /**
     * Descarta el borrador y vuelve a lo publicado.
     *
     * Por qué hacía falta: el borrador acumula cambios que quizá nadie recuerda
     * haber hecho —basta abrir un selector de color para que se guarde algo— y
     * la única salida era publicar. Un cliente se encontró con once ajustes
     * pendientes (tema oscuro, otro pie, otro estilo de tarjeta) que al publicar
     * le habrían cambiado la tienda entera, y no tenía forma de deshacerlos.
     *
     * No toca nada publicado: solo borra el borrador. La tienda en vivo se queda
     * exactamente como está.
     */
    public function discardDraft(Request $request)
    {
        $project = $this->project();

        $cuantos = \DB::table('builder_drafts')->where('project_id', $project->id)->count();

        if ($cuantos > 0) {
            // Copia de seguridad antes de borrar: si alguien descarta por error,
            // el trabajo no se pierde sin remedio.
            \DB::table('builder_drafts_descartados')->insertUsing(
                ['project_id', 'resource_type', 'resource_key', 'payload', 'descartado_en'],
                \DB::table('builder_drafts')->where('project_id', $project->id)
                    ->selectRaw('project_id, resource_type, resource_key, payload, NOW()')
            );
            \DB::table('builder_drafts')->where('project_id', $project->id)->delete();
        }

        $this->telemetry($project, 'builder_draft_discarded', ['cambios' => $cuantos]);

        return response()->json(['ok' => true, 'descartados' => $cuantos]);
    }

    public function publish(Request $request)
    {
        $project = $this->project();
        $checklist = PublishChecklist::for($project);

        if (!$checklist['can_publish']) {
            $this->telemetry($project, 'builder_publish_blocked', ['criticals' => count($checklist['critical'])]);

            return response()->json(['ok' => false, 'reason' => 'criticals', 'checklist' => $checklist], 422);
        }

        if ($checklist['requires_confirmation'] && !$request->boolean('confirm_warnings')) {
            return response()->json(['ok' => false, 'reason' => 'warnings', 'checklist' => $checklist], 409);
        }

        $this->telemetry($project, 'builder_publish_started');

        try {
            $version = $this->drafts->publish($project, auth()->id(), $checklist);
        } catch (\Throwable $e) {
            report($e);
            $this->telemetry($project, 'builder_publish_failed');

            return response()->json(['ok' => false, 'reason' => 'error', 'message' => 'No se pudo publicar. Inténtalo de nuevo.'], 500);
        }

        $this->telemetry($project, 'builder_published', ['version' => $version]);

        return response()->json([
            'ok' => true,
            'version' => $version,
            'published_at' => now()->toIso8601String(),
            'url' => url('/'.$project->slug),
        ]);
    }

    /**
     * Preview REAL del borrador (sin mocks): renderiza el storefront con los
     * settings publicados + borradores encima y las secciones en modo preview
     * (columnas draft). Nunca afecta al público.
     */
    public function preview(Request $request, PublicController $public)
    {
        $project = $this->project();
        $storeView = in_array($request->query('view'), ['home', 'tienda'], true) ? $request->query('view') : 'home';

        $overlay = array_filter($this->drafts->settingsDrafts($project), fn ($v) => $v !== null);
        // El cambio de plantilla también puede estar en borrador: el preview lo respeta.
        $template = (string) ($overlay['catalog_template'] ?? $project->setting('catalog_template', 'default'));
        $view = PublicController::PRODUCTION_TEMPLATE_VIEWS[$template] ?? null;
        if (!$view || !view()->exists($view)) {
            // Plantillas clásicas: preview existente sin overlay de settings.
            return $public->previewStorefront($project);
        }
        [$tplView, $data] = $public->prepararCatalogo($project, true, $storeView, $overlay);
        $html = view($tplView, $data + ['builderSettingsOverlay' => $overlay])->render();

        // Puente postMessage del preview: resaltar/seleccionar secciones.
        // Se inyecta SOLO aquí; el render público queda intacto.
        $bridge = <<<'JS'
<script>
(function () {
    var ORIGIN = window.location.origin;
    function sections() { return document.querySelectorAll('[data-store-native-section]'); }
    function clear() { sections().forEach(function (el) { el.style.outline = ''; el.style.outlineOffset = ''; }); }
    window.addEventListener('message', function (e) {
        if (e.origin !== ORIGIN || !e.data || typeof e.data.type !== 'string') return;
        if (e.data.type === 'builder:highlight-section') {
            clear();
            var el = document.querySelector('[data-store-native-section="' + String(e.data.component || '').replace(/[^a-z_]/g, '') + '"]');
            if (el) { el.style.outline = '3px solid #4f46e5'; el.style.outlineOffset = '-3px'; el.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
        }
        if (e.data.type === 'builder:clear-highlight') clear();
    });
    sections().forEach(function (el) {
        el.addEventListener('click', function () {
            parent.postMessage({ type: 'storefront:section-selected', component: el.getAttribute('data-store-native-section') }, ORIGIN);
        }, true);
    });
    parent.postMessage({ type: 'storefront:ready' }, ORIGIN);
})();
</script>
JS;
        $html = str_replace('</body>', $bridge.'</body>', $html);

        return response($html)->header('X-Frame-Options', 'SAMEORIGIN');
    }

    /**
     * Biblioteca de iconos (Iconify, sin API key). Búsqueda proxied server-side
     * para no exponer al navegador a terceros ni depender de CORS.
     */
    public function iconSearch(Request $request)
    {
        $this->project();
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) return response()->json(['icons' => []]);

        try {
            $res = \Illuminate\Support\Facades\Http::timeout(6)
                ->get('https://api.iconify.design/search', [
                    'query' => $q, 'limit' => 32, 'prefixes' => 'lucide,tabler,mdi,solar',
                ]);
            $icons = $res->ok() ? ($res->json('icons') ?? []) : [];
        } catch (\Throwable $e) {
            $icons = [];
        }

        return response()->json(['icons' => array_values(array_slice($icons, 0, 32))]);
    }

    /** Asigna un icono Iconify a una categoría: se descarga, sanitiza y guarda EN BORRADOR. */
    public function iconAssign(Request $request)
    {
        $project = $this->project();
        $data = $request->validate([
            'category_id' => ['required', 'integer'],
            'icon' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9-]+:[a-z0-9-]+$/'],
        ]);

        $category = \App\Models\Category::where('project_id', $project->id)->where('id', $data['category_id'])->first();
        abort_unless($category, 404);

        [$prefix, $name] = explode(':', $data['icon'], 2);
        try {
            $res = \Illuminate\Support\Facades\Http::timeout(6)
                ->get("https://api.iconify.design/{$prefix}/{$name}.svg", ['height' => 24]);
            abort_unless($res->ok() && str_contains($res->body(), '<svg'), 422);
            $svg = $this->sanitizeSvg($res->body());
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'No se pudo obtener el icono.'], 502);
        }

        // Borrador: la tienda pública no cambia hasta Publicar.
        $this->drafts->putSetting($project, 'caticon_'.$category->id, $svg, auth()->id());

        return response()->json(['ok' => true, 'category_id' => $category->id, 'svg' => $svg]);
    }

    /** Ruta pública de un asset guardado (acepta URL absoluta o path del disco public). */
    private function publicAsset(?string $path): ?string
    {
        if (!filled($path)) return null;

        return (str_starts_with($path, 'http') || str_starts_with($path, '/'))
            ? $path
            : \Illuminate\Support\Facades\Storage::url($path);
    }

    /** Foto de una categoría (dato de catálogo: se aplica de inmediato, como en la etapa Catálogo). */
    public function categoryPhoto(Request $request)
    {
        $project = $this->project();
        $data = $request->validate([
            'category_id' => ['required', 'integer'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove' => ['nullable', 'boolean'],
        ]);
        $category = \App\Models\Category::where('project_id', $project->id)->where('id', $data['category_id'])->first();
        abort_unless($category, 404);

        if ($request->boolean('remove')) {
            $category->update(['image_url' => null]);

            return response()->json(['ok' => true, 'category_id' => $category->id, 'image_url' => null]);
        }

        abort_unless($request->hasFile('image'), 422);

        // Misma normalización cuadrada que las fotos de producto.
        try {
            $resultado = app(\App\Support\Imagen\ProcesadorImagenes::class)
                ->procesar($request->file('image'), "categories/{$project->id}", 'categoria');
        } catch (\App\Support\Imagen\ImagenNoProcesable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        $category->update(['image_url' => $resultado->principal]);

        return response()->json(['ok' => true, 'category_id' => $category->id, 'image_url' => $resultado->urlPrincipal()]);
    }

    /** Sanitizado estricto del SVG (solo formas; sin scripts/eventos/enlaces). */
    private function sanitizeSvg(string $svg): string
    {
        $svg = preg_replace('/<\s*(script|style|foreignObject|iframe|object|embed)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $svg);
        $svg = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $svg);
        $svg = preg_replace('/\s(?:xlink:)?href\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $svg);
        $svg = preg_replace('/javascript\s*:/i', '', $svg);
        $svg = preg_replace('/<\s*(?!\/?(?:svg|path|g|circle|rect|line|polyline|polygon|ellipse|defs|title)\b)[a-zA-Z][^>]*>/', '', $svg);

        return trim($svg);
    }

    /** B7 — Tiendas desde las que se puede copiar (mismo dueño o superadmin). */
    public function copySources(\App\Storefront\StoreCopyService $copy)
    {
        $project = $this->project();

        return response()->json(['sources' => $copy->sourcesFor(auth()->user(), $project)]);
    }

    /** B7 — Copiar configuración de otra tienda A BORRADOR (resumen previo con dry_run). */
    public function copyStore(Request $request, \App\Storefront\StoreCopyService $copy)
    {
        $project = $this->project();
        $data = $request->validate([
            'source_id' => ['required', 'integer'],
            'parts' => ['required', 'array', 'min:1'],
            'parts.*' => [\Illuminate\Validation\Rule::in(array_keys(\App\Storefront\StoreCopyService::GROUPS))],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $source = \App\Models\Project::findOrFail($data['source_id']);
        abort_unless(auth()->user()->is_superadmin || $source->owner_id === auth()->id(), 403);

        if ($request->boolean('dry_run')) {
            return response()->json(['ok' => true, 'summary' => $copy->summary($source, $data['parts'])]);
        }

        $result = $copy->copy(auth()->user(), $project, $source, $data['parts'], $this->drafts);

        return response()->json(['ok' => true, 'copied' => $result, 'progress' => BuilderProgress::for($project)]);
    }

    /**
     * B4 — Lista de productos para corregir (filtros de los accesos rápidos).
     * Solo lectura, paginada, siempre del proyecto activo.
     */
    public function catalogList(Request $request)
    {
        $project = $this->project();
        $filter = $request->query('filter', 'all');

        $q = \App\Models\Product::query()->where('project_id', $project->id)
            ->with('mainImage:id,product_id,url')
            ->select(['id', 'name', 'sku', 'price', 'category_id', 'is_available']);

        match ($filter) {
            'no_image' => $q->whereDoesntHave('images'),
            'no_price' => $q->where(fn ($w) => $w->whereNull('price')->orWhere('price', '<=', 0)),
            'sku_dup' => $q->whereNotNull('sku')->where('sku', '!=', '')
                ->whereIn('sku', fn ($s) => $s->select('sku')->from('products')
                    ->where('project_id', $project->id)->whereNotNull('sku')->where('sku', '!=', '')
                    ->groupBy('sku')->havingRaw('COUNT(*) > 1')),
            'incomplete' => $q->where(fn ($w) => $w->whereNull('price')->orWhere('price', '<=', 0)
                ->orWhereNull('category_id')->orWhereDoesntHave('images')),
            default => null,
        };

        $page = $q->orderBy('name')->paginate(20);

        return response()->json([
            'total' => $page->total(),
            'items' => collect($page->items())->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->name, 'sku' => $p->sku,
                'price' => (float) $p->price, 'is_available' => (bool) $p->is_available,
                'image' => $p->mainImage?->url ? asset('storage/'.ltrim(preg_replace('#^storage/#', '', $p->mainImage->url), '/')) : null,
                'edit_url' => route('products.index') . '?edit=' . $p->id,
            ]),
            'has_more' => $page->hasMorePages(),
        ]);
    }

    /**
     * B4 — Acciones masivas seguras del catálogo: transaccionales, con permisos
     * y aisladas al proyecto activo (ids ajenos se ignoran). Sin borrado físico.
     */
    public function catalogBulk(Request $request)
    {
        $project = $this->project();
        $data = $request->validate([
            'action' => ['required', \Illuminate\Validation\Rule::in(['publish', 'unpublish', 'set_category', 'price_set', 'price_adjust', 'wholesale_set', 'wholesale_remove'])],
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer'],
            'value' => ['nullable', 'numeric'],
            'category_id' => ['nullable', 'integer'],
            'min_qty' => ['nullable', 'integer', 'min:1'],
        ]);

        $affected = \Illuminate\Support\Facades\DB::transaction(function () use ($project, $data) {
            // Aislamiento: SOLO productos del proyecto activo.
            $q = \App\Models\Product::where('project_id', $project->id)->whereIn('id', $data['ids']);

            return match ($data['action']) {
                'publish' => $q->update(['is_available' => true]),
                'unpublish' => $q->update(['is_available' => false]),
                'set_category' => $q->update(['category_id' => \App\Models\Category::where('project_id', $project->id)->where('id', (int) ($data['category_id'] ?? 0))->value('id')]),
                'price_set' => ($data['value'] ?? null) !== null && $data['value'] >= 0 ? $q->update(['price' => round((float) $data['value'], 2)]) : 0,
                'price_adjust' => $q->get()->each(fn ($p) => $p->update(['price' => max(0, round((float) $p->price * (1 + ((float) ($data['value'] ?? 0)) / 100), 2))]))->count(),
                'wholesale_set' => ($data['value'] ?? null) !== null ? $q->update(['wholesale_price' => round((float) $data['value'], 2), 'wholesale_min_qty' => (int) ($data['min_qty'] ?? 6)]) : 0,
                'wholesale_remove' => $q->update(['wholesale_price' => null, 'wholesale_min_qty' => null]),
            };
        });

        $this->telemetry($project, 'builder_bulk_action', ['action' => $data['action'], 'affected' => $affected]);

        return response()->json(['ok' => true, 'affected' => $affected]);
    }

    /**
     * Bloques de la Página de inicio (nombres oficiales únicos) con su
     * estado EN BORRADOR (draft_* ?? publicado) y su clave nativa del
     * storefront para el resaltado del preview.
     */
    private function homeBlocks($project): array
    {
        $meta = [
            'hero'                => ['label' => 'Slider principal',       'native' => 'hero'],
            'media_banner'        => ['label' => 'Banner multimedia',      'native' => 'media_banner'],
            'benefits'            => ['label' => 'Beneficios',             'native' => 'benefits'],
            'announcements'       => ['label' => 'Anuncios',               'native' => 'promotions'],
            'featured_categories' => ['label' => 'Categorías',             'native' => 'categories'],
            'collection_showcase' => ['label' => 'Colecciones / Ambientes', 'native' => 'collection_showcase'],
            'daily_offer'         => ['label' => 'Oferta con contador',    'native' => 'flash_sale'],
            'discounts'           => ['label' => 'Productos con descuento', 'native' => 'discount_products'],
            'featured_products'   => ['label' => 'Productos destacados',   'native' => 'featured_products'],
            'brands'              => ['label' => 'Marcas',                 'native' => 'brands'],
            'testimonials'        => ['label' => 'Testimonios',            'native' => 'testimonials'],
            'gallery'             => ['label' => 'Galería',                'native' => 'gallery'],
            'faq'                 => ['label' => 'Preguntas frecuentes',   'native' => 'faq'],
            'wa_advisory'         => ['label' => 'Asesoría por WhatsApp',  'native' => 'wa_advisory'],
            'cta_banner'          => ['label' => 'Llamada a la acción',    'native' => 'cta_banner'],
            'locations'           => ['label' => 'Sucursales y ubicación', 'native' => 'locations'],
            'about_preview'       => ['label' => 'Nosotros (resumen)',     'native' => 'about_preview'],
            'info_strip'          => ['label' => 'Banda informativa',       'native' => 'info_strip'],
            'blog'                => ['label' => 'Blog',                   'native' => 'blog'],
        ];

        return $project->storeSections()->where('page', 'home')
            ->whereIn('component', array_keys($meta))
            ->get()
            ->map(fn ($s) => [
                'component' => $s->component,
                'label' => $meta[$s->component]['label'],
                'native' => $meta[$s->component]['native'],
                'enabled' => $s->enabledForPreview(),
                'show_desktop' => (bool) $s->showDesktopForPreview(),
                'show_mobile' => (bool) $s->showMobileForPreview(),
                'sort' => $s->sortOrderForPreview(),
                'has_draft' => (bool) $s->has_draft,
                'content' => $s->contentForPreview(),
            ])
            ->sortBy('sort')->values()->all();
    }

    /** Telemetría interna sin datos sensibles. */
    private function telemetry($project, string $event, array $meta = []): void
    {
        Log::info('builder', ['event' => $event, 'project' => $project->id] + $meta);
    }
}
