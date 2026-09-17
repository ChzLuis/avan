<?php

namespace App\Storefront;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

/**
 * Fuente ÚNICA de reglas del Constructor (B0).
 *
 * BuilderProgress y PublishChecklist consumen exactamente este registro:
 * una regla incumplida pesa igual en el porcentaje y en el checklist.
 *
 * Cada regla: code, stage, severity (critical|warning|recommendation),
 * weight, message, target (deep-link etapa.campo), blocks_publish y un
 * evaluator que recibe el contexto ya cargado (sin N+1).
 */
class BuilderRuleRegistry
{
    public const STAGES = [
        'business'   => ['label' => 'Datos del negocio',       'minutes' => 15],
        'appearance' => ['label' => 'Apariencia',              'minutes' => 20],
        // El encabezado y el menu vivian dentro de Apariencia, en una segunda
        // pestaña que nadie encontraba. Son la navegacion de la tienda: merecen
        // su propio paso numerado y no estar escondidos detras de otro.
        'header'     => ['label' => 'Encabezado y menú',       'minutes' => 15],
        'home'       => ['label' => 'Página de inicio',        'minutes' => 45],
        'catalog'    => ['label' => 'Catálogo',                'minutes' => 150],
        'sales'      => ['label' => 'Venta',                   'minutes' => 45],
        'pages'      => ['label' => 'Páginas',                 'minutes' => 25],
        'legal'      => ['label' => 'Footer y legales',        'minutes' => 20],
        'advanced'   => ['label' => 'Configuración',           'minutes' => 15],
        'publish'    => ['label' => 'Revisar y publicar',      'minutes' => 25],
    ];

    /**
     * Contexto de evaluación: una sola pasada de consultas agregadas.
     * Todas las reglas leen de aquí (nunca de la BD directamente).
     */
    public static function context(Project $project): array
    {
        // Igual que BuilderDraftService::effectiveSettings(): el checklist evalúa
        // "¿estás listo para publicar?", así que debe ver lo ya guardado en el
        // Constructor aunque todavía no se haya publicado — no solo lo público.
        $draftValues = DB::table('builder_drafts')
            ->where('project_id', $project->id)->where('resource_type', 'settings')
            ->pluck('payload', 'resource_key')
            ->map(fn ($payload) => json_decode($payload, true)['value'] ?? null)
            ->filter(fn ($v) => $v !== null)->all();
        $settings = array_merge($project->settings()->pluck('value', 'key')->all(), $draftValues);
        $sections = $project->storeSections()->where('page', 'home')
            ->get(['component', 'is_enabled', 'draft_is_enabled', 'content', 'sort_order'])
            ->keyBy('component');
        $pages = $project->storePages()->get(['key', 'is_enabled'])->keyBy('key');

        $products = DB::table('products')->where('project_id', $project->id);
        $counts = [
            'total'         => (clone $products)->count(),
            'published'     => (clone $products)->where('is_available', 1)->count(),
            'without_price' => (clone $products)->where(fn ($q) => $q->whereNull('price')->orWhere('price', '<=', 0))->count(),
            'without_image' => (clone $products)->whereNotExists(function ($q) {
                $q->selectRaw('1')->from('product_images')->whereColumn('product_images.product_id', 'products.id');
            })->count(),
            'without_category' => (clone $products)->whereNull('category_id')->count(),
            'sku_duplicates' => (int) DB::table('products')->where('project_id', $project->id)
                ->whereNotNull('sku')->where('sku', '!=', '')
                ->select('sku')->groupBy('sku')->havingRaw('COUNT(*) > 1')->get()->count(),
            'empty_categories' => DB::table('categories')->where('project_id', $project->id)->where('is_active', 1)
                ->whereNotExists(function ($q) {
                    $q->selectRaw('1')->from('products')->whereColumn('products.category_id', 'categories.id');
                })->count(),
        ];

        // WhatsApp canónico: misma cadena de resolución que usan las plantillas.
        $whatsapp = preg_replace('/\D/', '', (string) ($settings['quote_whatsapp']
            ?? $settings['whatsapp_number'] ?? $settings['contact_whatsapp']
            ?? $project->whatsapp ?? ''));

        $set = fn (string $key) => trim((string) ($settings[$key] ?? '')) !== '';

        return compact('settings', 'sections', 'pages', 'counts', 'whatsapp') + [
            'project' => $project,
            'has' => $set,
            'logo' => $set('logo_url') || filled($project->logo_url ?? null),
            'paymentConfigured' => collect($settings)
                ->filter(fn ($v, $k) => str_starts_with((string) $k, 'payment_') && trim((string) $v) !== '')
                ->isNotEmpty(),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public static function rules(): array
    {
        return [
            // ── Etapa 1: Datos del negocio ──
            // BLOQUEA: una tienda sin nombre no puede publicarse.
            ['code' => 'business.name_missing', 'stage' => 'business', 'severity' => 'attention', 'weight' => 10,
                'message' => 'Tu tienda necesita un nombre comercial.', 'target' => 'business.nombre', 'blocks_publish' => true,
                'evaluator' => fn ($c) => trim((string) ($c['project']->name ?? '')) !== ''],
            // BLOQUEA solo si hay recojo en tienda o local fisico declarado.
            ['code' => 'business.address_missing', 'stage' => 'business', 'severity' => 'attention', 'weight' => 6,
                'message' => 'Declaraste local físico o recojo en tienda: falta la dirección.', 'target' => 'business.direccion',
                'blocks_publish' => fn ($c) => ($c['settings']['has_physical_store'] ?? '0') === '1'
                    || ($c['settings']['pickup_enabled'] ?? '0') === '1',
                'evaluator' => fn ($c) => (($c['settings']['has_physical_store'] ?? '0') !== '1'
                        && ($c['settings']['pickup_enabled'] ?? '0') !== '1')
                    || trim((string) ($c['project']->address ?? '')) !== ''
                    || $c['has']('contact_address')],
            ['code' => 'business.logo_missing', 'stage' => 'business', 'severity' => 'attention', 'weight' => 8,
                'message' => 'Sube el logo de tu negocio.', 'target' => 'business.logo', 'blocks_publish' => false,
                'evaluator' => fn ($c) => $c['logo']],
            ['code' => 'business.whatsapp_missing', 'stage' => 'business', 'severity' => 'attention', 'weight' => 8,
                'message' => 'Registra el WhatsApp de ventas de tu tienda.', 'target' => 'business.whatsapp',
                // BLOQUEA cuando la venta depende de WhatsApp: cotizacion, consulta
                // o pedido. En una tienda de compra online directa, no.
                'blocks_publish' => fn ($c) => ($c['settings']['store_mode'] ?? 'direct') !== 'direct'
                    || in_array($c['settings']['product_button_mode'] ?? '', ['inquiry', 'both'], true),
                'evaluator' => fn ($c) => strlen($c['whatsapp']) >= 9],
            ['code' => 'business.email_missing', 'stage' => 'business', 'severity' => 'recommendation', 'weight' => 2,
                'message' => 'Agrega un correo de contacto.', 'target' => 'business.email', 'blocks_publish' => false,
                'evaluator' => fn ($c) => $c['has']('contact_email')],
            ['code' => 'business.category_missing', 'stage' => 'business', 'severity' => 'recommendation', 'weight' => 2,
                'message' => 'Elige el rubro de tu negocio para recibir recomendaciones.', 'target' => 'business.rubro', 'blocks_publish' => false,
                'evaluator' => fn ($c) => $c['has']('business_category')],

            // ── Etapa 2: Apariencia ──
            ['code' => 'appearance.template_missing', 'stage' => 'appearance', 'severity' => 'attention', 'weight' => 6,
                'message' => 'Elige la plantilla de tu tienda.', 'target' => 'appearance.template', 'blocks_publish' => true,
                'evaluator' => fn ($c) => $c['has']('catalog_template')],
            ['code' => 'appearance.primary_color_missing', 'stage' => 'appearance', 'severity' => 'recommendation', 'weight' => 4,
                'message' => 'Define el color principal de tu marca.', 'target' => 'appearance.colors', 'blocks_publish' => false,
                'evaluator' => fn ($c) => $c['has']('primary_color')],
            // ── Etapa 3: Encabezado y menú ──
            // El `target` manda: el checklist deriva la etapa de su prefijo
            // (`target.split('.')[0]`), asi que tiene que ser `header.` o el
            // enlace del pendiente seguiria llevando a Apariencia.
            ['code' => 'appearance.header_defaults', 'stage' => 'header', 'severity' => 'recommendation', 'weight' => 2,
                'message' => 'Personaliza el encabezado (modelo, colores y barra superior).', 'target' => 'header.preset', 'blocks_publish' => false,
                'evaluator' => fn ($c) => $c['has']('header_preset') || $c['has']('header_bg_color') || $c['has']('announcement_text')],
            ['code' => 'header.menu_missing', 'stage' => 'header', 'severity' => 'recommendation', 'weight' => 3,
                'message' => 'Revisa el menú de tu tienda (Inicio, Tienda, Nosotros…).', 'target' => 'header.menu', 'blocks_publish' => false,
                'evaluator' => fn ($c) => $c['project']->storeMenus()->exists()],

            // ── Etapa 3: Página de inicio ──
            ['code' => 'home.sections_missing', 'stage' => 'home', 'severity' => 'attention', 'weight' => 5,
                'message' => 'Tu página de inicio aún no tiene secciones configuradas.', 'target' => 'home.blocks', 'blocks_publish' => false,
                'evaluator' => fn ($c) => $c['sections']->isNotEmpty()],
            ['code' => 'home.slider_empty', 'stage' => 'home', 'severity' => 'recommendation', 'weight' => 5,
                'message' => 'El Slider principal no tiene imagen ni título.', 'target' => 'home.slider', 'blocks_publish' => false,
                'evaluator' => function ($c) {
                    $hero = $c['sections']->get('hero');
                    if ($hero && !($hero->draft_is_enabled ?? $hero->is_enabled)) return true; // desactivado a propósito
                    return $c['has']('hero_image') || $c['has']('hero_slide_1_image') || $c['has']('hero_title');
                }],
            ['code' => 'home.too_few_sections', 'stage' => 'home', 'severity' => 'recommendation', 'weight' => 3,
                'message' => 'Activa al menos 3 secciones para una portada completa.', 'target' => 'home.blocks', 'blocks_publish' => false,
                'evaluator' => fn ($c) => $c['sections']->filter(fn ($s) => (bool) ($s->draft_is_enabled ?? $s->is_enabled))->count() >= 3],

            // ── Etapa 4: Catálogo ──
            ['code' => 'catalog.no_products', 'stage' => 'catalog', 'severity' => 'attention', 'weight' => 10,
                'message' => 'Tu catálogo no tiene productos.', 'target' => 'catalog.start', 'blocks_publish' => true,
                'evaluator' => fn ($c) => $c['counts']['total'] > 0],
            // Contextual: sin precio es grave en venta directa; en cotización solo informa.
            ['code' => 'catalog.products_without_price', 'stage' => 'catalog',
                'severity' => fn ($c) => (($c['settings']['store_mode'] ?? 'direct') === 'direct') ? 'attention' : 'info',
                'weight' => 8,
                'message' => 'Hay productos sin precio (no podrán comprarse directamente).', 'target' => 'catalog.fix-price',
                'blocks_publish' => fn ($c) => ($c['settings']['store_mode'] ?? 'direct') === 'direct',
                'evaluator' => fn ($c) => $c['counts']['total'] === 0 || $c['counts']['without_price'] === 0,
                'count' => fn ($c) => $c['counts']['without_price']],
            ['code' => 'catalog.products_without_image', 'stage' => 'catalog', 'severity' => 'recommendation', 'weight' => 6,
                'message' => 'Hay productos sin imagen.', 'target' => 'catalog.fix-image', 'blocks_publish' => false,
                'evaluator' => fn ($c) => $c['counts']['total'] === 0 || $c['counts']['without_image'] === 0,
                'count' => fn ($c) => $c['counts']['without_image']],
            ['code' => 'catalog.sku_duplicates', 'stage' => 'catalog', 'severity' => 'attention', 'weight' => 3,
                'message' => 'Existen SKU duplicados.', 'target' => 'catalog.fix-sku', 'blocks_publish' => false,
                'evaluator' => fn ($c) => $c['counts']['sku_duplicates'] === 0,
                'count' => fn ($c) => $c['counts']['sku_duplicates']],
            ['code' => 'catalog.empty_categories', 'stage' => 'catalog', 'severity' => 'info', 'weight' => 2,
                'message' => 'Tienes categorías sin productos.', 'target' => 'catalog.categories', 'blocks_publish' => false,
                'evaluator' => fn ($c) => $c['counts']['empty_categories'] === 0,
                'count' => fn ($c) => $c['counts']['empty_categories']],

            // ── Etapa 5: Venta y operación ──
            ['code' => 'sales.payment_missing', 'stage' => 'sales', 'severity' => 'attention', 'weight' => 6,
                'message' => 'Configura al menos un método de cobro (o cambia a modo cotización).', 'target' => 'sales.payments',
                'blocks_publish' => fn ($c) => ($c['settings']['store_mode'] ?? 'direct') === 'direct',
                'evaluator' => fn ($c) => (($c['settings']['store_mode'] ?? 'direct') !== 'direct') || $c['paymentConfigured'] || strlen($c['whatsapp']) >= 9],
            ['code' => 'sales.shipping_incomplete', 'stage' => 'sales', 'severity' => 'recommendation', 'weight' => 3,
                'message' => 'Activaste envíos pero falta configurar el costo.', 'target' => 'sales.shipping', 'blocks_publish' => false,
                'evaluator' => fn ($c) => (($c['settings']['shipping_enabled'] ?? '0') !== '1') || $c['has']('shipping_cost')],
            ['code' => 'pages.about_missing', 'stage' => 'pages', 'severity' => 'recommendation', 'weight' => 2,
                'message' => 'Completa la página Nosotros para generar confianza.', 'target' => 'pages.about', 'blocks_publish' => false,
                'evaluator' => fn ($c) => (bool) ($c['pages']->get('nosotros')?->is_enabled)],
            ['code' => 'legal.pages_disabled', 'stage' => 'legal', 'severity' => 'recommendation', 'weight' => 3,
                'message' => 'Las páginas legales están desactivadas (privacidad o términos).', 'target' => 'legal.pages', 'blocks_publish' => false,
                'evaluator' => function ($c) {
                    foreach (['privacidad', 'terminos'] as $key) {
                        $page = $c['pages']->get($key);
                        if ($page && !$page->is_enabled) return false; // solo falla si se desactivó explícitamente
                    }
                    return true; // sin fila = texto base activo
                }],
        ];
    }

    /** Evalúa todas las reglas contra un contexto. */
    public static function evaluate(Project $project, ?array $context = null): array
    {
        $ctx = $context ?? self::context($project);
        $results = [];
        foreach (self::rules() as $rule) {
            $evaluator = $rule['evaluator'];
            $passes = (bool) $evaluator($ctx);
            // Severidad contextual: una regla puede decidir su nivel según el
            // modo de la tienda (ej. "sin precio" solo es grave en venta directa).
            $severity = is_callable($rule['severity']) ? $rule['severity']($ctx) : $rule['severity'];
            $results[] = [
                'code' => $rule['code'],
                'stage' => $rule['stage'],
                'severity' => $passes ? 'complete' : $severity,
                'weight' => $rule['weight'],
                'message' => $rule['message'],
                'target' => $rule['target'],
                // Bloqueo contextual: "sin precio" impide publicar en venta directa
                // pero no en una tienda de solo cotizacion.
                'blocks_publish' => !$passes && (bool) (is_callable($rule['blocks_publish'] ?? false)
                    ? $rule['blocks_publish']($ctx) : ($rule['blocks_publish'] ?? false)),
                'complete' => $passes,
                'count' => isset($rule['count']) ? (int) $rule['count']($ctx) : null,
            ];
        }

        return $results;
    }
}
