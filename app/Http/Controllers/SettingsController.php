<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Models\Project;
use App\Models\Module;
use App\Models\Coupon;
use App\Modules\Crm\Models\WaCanal;
use App\Support\CatalogTemplates;
use App\Support\StorefrontSections;
use App\Support\StorefrontNavigation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\ProjectTemplate;
use App\Storefront\StorefrontContextBuilder;
use App\Storefront\ProjectSettingWriteService;

class SettingsController extends Controller
{
    public function __construct(
        private readonly StorefrontContextBuilder $storefrontContexts,
        private readonly ProjectSettingWriteService $projectSettingWrites,
    ) {
    }

    public function index(Request $request)
    {
        $userId = auth()->id();
        $isSuperadmin = auth()->user()->is_superadmin ?? false;

        $projects = $isSuperadmin
            ? Project::orderBy('name')->get()
            : Project::where('owner_id', $userId)
                ->orWhereHas('members', fn($q) => $q->where('user_id', $userId))
                ->orderBy('name')
                ->get();

        // Si viene ?p=ID, mostrar ese proyecto en el panel derecho
        if ($request->p && $pid = (int) $request->p) {
            $project = $isSuperadmin
                ? Project::findOrFail($pid)
                : ($projects->firstWhere('id', $pid) ?? app('active_project'));
        } else {
            $project = app('active_project');
        }

        return view('settings.index', compact('project', 'projects'));
    }

    public function update(Request $request)
    {
        $userId = auth()->id();
        $isSuperadmin = auth()->user()->is_superadmin ?? false;
        // Permitir editar cualquier proyecto del usuario, no solo el activo
        if ($request->input('project_id')) {
            $project = Project::findOrFail($request->input('project_id'));
            $this->authorizeProject($project);
        } else {
            /** @var \App\Models\Project $project */
            $project = app('active_project');
            $this->authorizeProject($project);
        }
        // Sección wa_phone — guardado directo
        if ($request->input('section') === 'wa_phone') {
            $project->update(['wa_phone' => preg_replace('/\D/', '', $request->input('wa_phone', '')) ?: null]);
            return back()->with('success', 'Número del bot guardado.');
        }

        // Sección show_wa_button
        if ($request->input('section') === 'show_wa_button') {
            $project->settings()->updateOrCreate(['key' => 'show_wa_button'], ['value' => $request->boolean('show_wa_button') ? '1' : '0']);
            return back()->with('success', 'Configuración guardada.');
        }

        $customDomainRule = $isSuperadmin
            ? 'nullable|string|max:150|unique:projects,custom_domain,'.$project->id
            : 'prohibited';

        $data = $request->validate([
            'name'          => 'sometimes|required|string|max:100',
            'slug'          => 'nullable|string|max:120|regex:/^[a-z0-9\-]+$/|unique:projects,slug,'.$project->id,
            'description'   => 'nullable|string|max:500',
            'category'      => 'nullable|string|max:80',
            'phone'         => 'nullable|string|max:30',
            'whatsapp'      => 'nullable|string|max:30',
            'address'       => 'nullable|string|max:200',
            'custom_domain' => $customDomainRule,
        ]);

        // Normalizar custom_domain
        if (isset($data['custom_domain'])) {
            $data['custom_domain'] = $data['custom_domain']
                ? strtolower(trim($data['custom_domain']))
                : null;
        }

        // Si el slug viene vacío o no fue enviado, regenerarlo desde el nombre
        if (!empty($data['name']) && empty($data['slug'])) {
            $base = Str::slug($data['name']);
            $slug = $base;
            $i = 2;
            while (Project::where('slug', $slug)->where('id', '!=', $project->id)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $data['slug'] = $slug;
        }

        if (!empty($data)) {
            $oldDomain = $project->custom_domain;
            $project->update($data);

            // Si cambió el custom_domain, configurar nginx+SSL en VPS automáticamente
            $newDomain = $project->fresh()->custom_domain;
            if ($newDomain && $newDomain !== $oldDomain) {
                $this->setupDomainOnVps($newDomain);
            }
        }

        $settingsKeys = [
            'ruc', 'razon_social', 'email', 'country', 'currency', 'sunat_url', 'sunat_url_prod',
            'nubefact_url', 'nubefact_token', 'serie_factura', 'serie_boleta', 'apiperu_token',
            'billing_provider', 'apisperu_token', 'apisperu_ubigeo',
            // Lector de comprobantes: interruptor, motor y clave del negocio.
            // La clave es de cada empresa; sin ella cae en la global de Eskala.
            'lector_comprobantes', 'lector_motor', 'lector_api_key', 'lector_modelo',
            // Series de nota y guia: SUNAT las autoriza por negocio.
            'serie_nota_credito', 'serie_nota_debito', 'serie_guia', 'cuentas_bancarias',
            // Redes sociales
            'facebook_url', 'instagram_url', 'tiktok_url', 'youtube_url', 'twitter_url', 'linkedin_url',
            // SEO
            'seo_title', 'seo_description', 'seo_keywords',
            // Envío
            'shipping_enabled', 'shipping_cost', 'shipping_free_from', 'require_address',
            // Modalidades de venta. Apagadas, el catálogo esconde los campos que
            // no aplican: una tienda que solo vende al detalle cargaba con precio
            // mayorista, cantidad mínima y unidad en cada producto.
            'feature_mayorista', 'feature_revendedores', 'feature_variantes',
        ];
        foreach ($settingsKeys as $key) {
            if ($request->has($key)) {
                $project->settings()->updateOrCreate(['key' => $key], ['value' => $request->input($key)]);
            }
        }
        return redirect()
            ->route('settings', ['p' => $project->id, 's' => $request->input('_tab', 'datos')])
            ->with('success', 'Configuración guardada.');
    }

    public function seo(Request $request)
    {
        $userId = auth()->id();
        $isSuperadmin = auth()->user()->is_superadmin ?? false;
        $projects = $isSuperadmin
            ? Project::orderBy('name')->get()
            : Project::where('owner_id', $userId)
                ->orWhereHas('members', fn($q) => $q->where('user_id', $userId))
                ->orderBy('name')->get();
        if ($request->p && $pid = (int) $request->p) {
            $project = $projects->firstWhere('id', $pid) ?? app('active_project');
        } else {
            $project = app('active_project');
        }
        return view('settings.seo', compact('project', 'projects'));
    }

    public function updateSeo(Request $request)
    {
        $userId = auth()->id();
        if ($request->input('project_id')) {
            $project = $isSuperadmin
                ? Project::findOrFail($request->input('project_id'))
                : Project::where('owner_id', $userId)->findOrFail($request->input('project_id'));
        } else {
            $project = app('active_project');
            $this->authorizeProject($project);
        }
        $seoKeys = [
            'seo_title', 'seo_description', 'seo_keywords', 'seo_canonical',
            'og_title', 'og_description', 'og_image',
            'ga_id', 'gtm_id', 'fb_pixel_id', 'tiktok_pixel_id',
            'robots', 'sitemap_enabled',
            'schema_type', 'schema_price_range', 'schema_opening_hours',
            'google_site_verification', 'bing_site_verification',
        ];
        foreach ($seoKeys as $key) {
            if ($request->has($key)) {
                $project->settings()->updateOrCreate(['key' => $key], ['value' => $request->input($key) ?? '']);
            }
        }
        return redirect()
            ->route('settings.seo', ['p' => $project->id, 's' => $request->input('_tab', 'basico')])
            ->with('success', 'SEO guardado.');
    }

    public function modules(Request $request)
    {
        $isSuperadmin = auth()->user()->is_superadmin ?? false;
        $userId = auth()->id();

        if ($request->p && $isSuperadmin) {
            $project = Project::findOrFail((int) $request->p);
        } else {
            $project = app('active_project');
            $this->authorizeProject($project);
        }

        $allModules      = Module::where('is_active', true)->orderBy('sort_order')->get();
        $activeModuleIds = $project->modules()->wherePivot('is_active', true)->pluck('modules.id')->toArray();
        return view('settings.modules', compact('project', 'allModules', 'activeModuleIds'));
    }

    public function updateModules(Request $request)
    {
        $isSuperadmin = auth()->user()->is_superadmin ?? false;
        $userId = auth()->id();

        if ($request->p && $isSuperadmin) {
            $project = Project::findOrFail((int) $request->p);
        } else {
            $project = app('active_project');
            $this->authorizeProject($project);
        }

        // Toggle individual desde el centro de control
        if ($request->has('module_id')) {
            $moduleId = (int) $request->input('module_id');
            $enabled  = $request->input('enabled') === '1';
            $project->modules()->syncWithoutDetaching([$moduleId => ['is_active' => $enabled]]);
            $cat = $request->input('redirect_cat', 'plataformas');
            return redirect()->route('settings.modules', ['project' => $project->id, 'cat' => $cat])
                ->with('success', $enabled ? 'Módulo activado.' : 'Módulo desactivado.');
        }

        // Sync completo (usado desde projects/panel)
        $moduleIds = $request->input('modules', []);
        $sync = [];
        foreach (Module::all() as $module) {
            $sync[$module->id] = ['is_active' => in_array($module->id, $moduleIds)];
        }
        $project->modules()->sync($sync);
        return back()->with('success', 'Módulos actualizados.');
    }

    public function design(\Illuminate\Http\Request $request)
    {
        // Diseño retirado: el Constructor es la única pantalla de diseño.
        // Todos sus controles ya viven en las etapas del Constructor.
        // ?classic=1 se conserva solo como salida de emergencia del superadmin.
        if (!($request->boolean('classic') && auth()->user()?->is_superadmin)) {
            return redirect()->route('settings.builder');
        }

        /** @var \App\Models\Project $project */
        $project = app('active_project');
        StorefrontSections::ensure($project);
        $sections = $project->storeSections()->orderBy('page')->orderBy('sort_order')->get();
        $homeSections = $project->storeSections()->where('page', 'home')->orderBy('sort_order')->get()
            ->groupBy('component')->map->first()
            ->sortBy(fn ($section) => $section->draft_sort_order ?? $section->sort_order)->values();
        $storeCategories = $project->categories()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $storeProducts = $project->products()->where('is_available', true)->with('mainImage')->orderBy('name')->get();
        $storeSectionNames = StorefrontSections::COMPONENTS;
        $pages = $project->storePages()->orderBy('key')->get();
        $popup = $project->storePopups()->latest()->first();
        $messages = $project->contactMessages()->latest()->take(20)->get();
        $complaints = $project->complaints()->latest()->take(20)->get();
        $storeMenu = StorefrontNavigation::ensure($project);
        $storeMenu->load(['rootItems.children']);
        $headerSettings = StorefrontNavigation::headerSettings($project);
        $menuCategories = $project->categories()->where('is_active', true)->with('parent')->orderBy('sort_order')->orderBy('name')->get();
        return view('settings.design', compact(
            'project', 'sections', 'homeSections', 'storeCategories', 'storeProducts', 'storeSectionNames',
            'pages', 'popup', 'messages', 'complaints', 'storeMenu', 'headerSettings', 'menuCategories'
        ));
    }

    /**
     * Nuevo Diseñador visual (shell de estructura + preview + inspector).
     * Rediseño por fases — convive con design() (el anterior) hasta validarse.
     * Reutiliza exactamente los mismos datos y el mismo guardado (updateDesign).
     */
    public function designer()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        StorefrontSections::ensure($project);
        $homeSections = $project->storeSections()->where('page', 'home')->orderBy('sort_order')->get()
            ->groupBy('component')->map->first()
            ->sortBy(fn ($section) => $section->draft_sort_order ?? $section->sort_order)->values();
        $storeCategories = $project->categories()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $storeProducts = $project->products()->where('is_available', true)->with('mainImage')->orderBy('name')->get();
        $storeSectionNames = StorefrontSections::COMPONENTS;
        $pages = $project->storePages()->orderBy('key')->get();
        $popup = $project->storePopups()->latest()->first();
        $storeMenu = StorefrontNavigation::ensure($project);
        $storeMenu->load(['rootItems.children']);
        $headerSettings = StorefrontNavigation::headerSettings($project);
        $menuCategories = $project->categories()->where('is_active', true)->with('parent')->orderBy('sort_order')->orderBy('name')->get();
        $settings = $project->settings()->pluck('value', 'key')->toArray();

        return view('settings.designer.index', compact(
            'project', 'homeSections', 'storeCategories', 'storeProducts', 'storeSectionNames',
            'pages', 'popup', 'storeMenu', 'headerSettings', 'menuCategories', 'settings'
        ));
    }

    public function uploadLogo(Request $request)
    {
        $project = app('active_project');
        $this->authorizeProject($project);
        $type = $request->input('type', 'logo');
        // El diseñador también sube videos cortos para el banner multimedia.
        $isVideo = str_starts_with($type, 'section_video');
        // Lista explícita en vez de la regla 'image': esa regla rechaza AVIF
        // (formato habitual de imágenes descargadas de la web) y SVG. El tope
        // sube a 10MB porque las fotos de banner reales superan los 4MB;
        // nginx permite 20M así que la petición sí llega hasta aquí.
        $request->validate(['file' => $isVideo
            ? 'required|file|mimetypes:video/mp4,video/webm|max:51200'
            : 'required|file|mimes:jpg,jpeg,png,gif,bmp,webp,avif,svg|max:20480']);

        $file    = $request->file('file');
        $carpeta = $isVideo ? "store-sections/{$project->id}" : "logos/{$project->id}";

        // Vídeos y SVG no pasan por el procesador: el primero no es una imagen
        // de mapa de bits y el segundo ya escala solo sin perder calidad.
        $esSvg = strtolower((string) $file->getClientOriginalExtension()) === 'svg';
        if ($isVideo || $esSvg) {
            $path = $file->store($carpeta, 'public');

            return response()->json(['path' => $path, 'url' => asset('storage/'.$path)]);
        }

        try {
            $resultado = app(\App\Support\Imagen\ProcesadorImagenes::class)
                ->procesar($file, $carpeta, self::perfilDeTipo($type));
        } catch (\App\Support\Imagen\ImagenNoProcesable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'path' => $resultado->principal,
            'url'  => $resultado->urlPrincipal(),
        ]);
    }

    /**
     * Qué encuadre necesita cada hueco del diseñador. El nombre del tipo lo
     * pone el propio constructor al subir, así que basta con traducirlo.
     */
    private static function perfilDeTipo(string $type): string
    {
        return match (true) {
            in_array($type, ['logo', 'logo_url', 'favicon', 'yape_qr'], true) => 'logo',
            $type === 'category_visual'                                       => 'categoria',
            str_ends_with($type, '_mobile')                                   => 'banner_movil',
            in_array($type, ['hero', 'promo', 'banner'], true)                => 'banner',
            str_starts_with($type, 'section_')                                => 'seccion',
            default                                                           => 'generico',
        };
    }

    public function importLogs()
    {
        $project = app('active_project');
        $logs = ImportLog::where('project_id', $project->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn($l) => [
                'id'         => $l->id,
                'type'       => $l->type,
                'filename'   => $l->filename,
                'created'    => $l->created,
                'updated'    => $l->updated,
                'skipped'    => $l->skipped,
                'has_errors' => $l->has_errors,
                'errors'     => $l->errors ?? [],
                'date'       => $l->created_at->format('d/m/Y H:i'),
                'date_diff'  => $l->created_at->diffForHumans(),
            ]);
        return response()->json(['logs' => $logs]);
    }

    public function updateDesign(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeProject($project);
        $keys = [
            // Marca
            'primary_color','secondary_color','whatsapp_msg',
            'logo_url','logo_height','favicon_url',
            'font_title','font_body','border_radius','currency_symbol',
            'header_bg_color','header_text_color','header_height','menu_align','header_sticky_mode',
            'footer_bg_color','footer_text_color','footer_logo_height',
            'facebook_url','instagram_url','tiktok_url','youtube_url','twitter_url','linkedin_url',
            // Estilos globales de secciones
            'section_style_preset','section_spacing','section_heading_align','section_background_mode',
            'section_show_dividers','section_card_shadow','featured_products_view','catalog_products_view',
            // Orden de secciones de Inicio
            'home_section_order',
            // Portada — Hero
            'hero_title','hero_subtitle','hero_badge','hero_bg_color','popup_bg_color',
            'hero_image_2','hero_image_3','hero_image_4','hero_image_5','hero_show_content',
            'hero_mobile_image_1','hero_mobile_image_2','hero_mobile_image_3','hero_mobile_image_4','hero_mobile_image_5',
            'hero_autoplay','hero_pause_hover','hero_show_arrows','hero_show_dots','hero_duration','hero_transition','hero_mobile_height',
            'hero_slide_1_enabled','hero_slide_2_enabled','hero_slide_3_enabled','hero_slide_4_enabled','hero_slide_5_enabled',
            'hero_slide_1_show_content','hero_slide_2_show_content','hero_slide_3_show_content','hero_slide_4_show_content','hero_slide_5_show_content',
            'hero_slide_1_align','hero_slide_2_align','hero_slide_3_align','hero_slide_4_align','hero_slide_5_align',
            'hero_slide_1_position','hero_slide_2_position','hero_slide_3_position','hero_slide_4_position','hero_slide_5_position',
            'hero_slide_1_overlay','hero_slide_2_overlay','hero_slide_3_overlay','hero_slide_4_overlay','hero_slide_5_overlay',
            'hero_slide_1_cta1_show','hero_slide_2_cta1_show','hero_slide_3_cta1_show','hero_slide_4_cta1_show','hero_slide_5_cta1_show',
            'hero_slide_1_cta1_text','hero_slide_2_cta1_text','hero_slide_3_cta1_text','hero_slide_4_cta1_text','hero_slide_5_cta1_text',
            'hero_slide_1_cta1_url','hero_slide_2_cta1_url','hero_slide_3_cta1_url','hero_slide_4_cta1_url','hero_slide_5_cta1_url',
            'hero_slide_1_cta2_show','hero_slide_2_cta2_show','hero_slide_3_cta2_show','hero_slide_4_cta2_show','hero_slide_5_cta2_show',
            'hero_slide_1_cta2_text','hero_slide_2_cta2_text','hero_slide_3_cta2_text','hero_slide_4_cta2_text','hero_slide_5_cta2_text',
            'hero_slide_1_cta2_url','hero_slide_2_cta2_url','hero_slide_3_cta2_url','hero_slide_4_cta2_url','hero_slide_5_cta2_url',
            'hero_title_1','hero_title_2','hero_title_3','hero_title_4','hero_title_5',
            'hero_subtitle_1','hero_subtitle_2','hero_subtitle_3','hero_subtitle_4','hero_subtitle_5',
            'hero_badge_1','hero_badge_2','hero_badge_3','hero_badge_4','hero_badge_5',
            'hero_image','hero_overlay','hero_align','hero_height',
            'hero_cta1_show','hero_cta1_text','hero_cta2_show','hero_cta2_text',
            // Portada — Banners y extras
            'featured_categories_enabled','featured_categories_title','featured_categories_subtitle',
            'featured_categories_show_all','featured_categories_all_text','featured_categories_visual',
            'featured_categories_style','featured_categories_shape','featured_categories_image_fit',
            'featured_categories_items','featured_categories_columns','featured_categories_mobile_columns',
            'featured_categories_limit','featured_categories_hide_empty','featured_categories_show_count',
            'featured_categories_mobile_carousel','featured_categories_radius',
            'featured_categories_section_bg','featured_categories_card_bg',
            'featured_categories_text_color','featured_categories_accent',
            'promo_enabled','promo_section_title','promo_section_subtitle','promo_style','promo_columns',
            'promo_height','promo_mobile_height','promo_overlay','promo_autoplay','promo_duration','promo_show_dots','promo_order',
            'promo_item_1_enabled','promo_item_2_enabled','promo_item_3_enabled',
            'promo_image_1','promo_image_2','promo_image_3',
            'promo_mobile_image_1','promo_mobile_image_2','promo_mobile_image_3',
            'promo_title_1','promo_title_2','promo_title_3',
            'promo_subtitle_1','promo_subtitle_2','promo_subtitle_3',
            'promo_cta_text_1','promo_cta_text_2','promo_cta_text_3',
            'promo_cta_url_1','promo_cta_url_2','promo_cta_url_3',
            'promo_align_1','promo_align_2','promo_align_3',
            'banner1_title','banner1_sub','banner2_title','banner2_sub',
            'announcement_text','announcement_bg','announcement_color','announcement_font_size','announcement_align','announcement_full_width','announcement_show',
            'countdown_label','countdown_end',
            'split_left_title','split_left_sub','split_right_title','split_right_sub',
            'trust_section_enabled','trust_section_title','trust_section_subtitle','trust_section_style',
            'trust_section_columns','trust_section_mobile_columns','trust_section_radius',
            'trust_section_bg','trust_card_bg','trust_text_color','trust_accent_color',
            'trust_show_descriptions','trust_mobile_carousel',
            'trust_item_1_enabled','trust_item_2_enabled','trust_item_3_enabled','trust_item_4_enabled',
            'trust_icon_1','trust_text_1','trust_description_1',
            'trust_icon_2','trust_text_2','trust_description_2',
            'trust_icon_3','trust_text_3','trust_description_3',
            'trust_icon_4','trust_text_4','trust_description_4',
            'tab1_label','tab2_label','tab3_label',
            // Catálogo — Grid
            'catalog_section_title','card_style','catalog_cols_desktop','catalog_cols_mobile',
            'catalog_filter_price','catalog_filter_cats','catalog_filter_sale','catalog_filter_search',
            'catalog_badge_sale','catalog_badge_new','catalog_badge_featured','catalog_badge_sold_out',
            'catalog_show_ratings','catalog_quick_view','catalog_show_sku','catalog_show_stock','wholesale_enabled',
            'catalog_watermark','catalog_watermark_opacity',
            'brands_page_title','brands_page_subtitle','promo_cards_title','promo_cards_subtitle','promo_cards_limit','catalog_pdf_title','catalog_pdf_subtitle',
            'pdp_ficha_text','pdp_tab_instalacion','pdp_tab_instalacion_label','pdp_tab_envios','pdp_tab_envios_label','pdp_tab_garantia','pdp_tab_garantia_label','pdp_trust_1_title','pdp_trust_1_text','pdp_trust_2_title','pdp_trust_2_text','pdp_trust_3_title','pdp_trust_3_text',
            // Catálogo — Botones y flotantes
            'btn_cart_text','btn_quote_text','btn_shape','btn_show_icon',
            'float_cart_show','float_cart_pos','float_wa_show','float_wa_tooltip','float_wa_pos',
            // Sistema — Venta y envío
            'store_mode','quote_price_display','quote_whatsapp','quote_whatsapp_country','quote_wa_msg',
            'product_button_mode','btn_inquiry_text',
            'featured_categories_band_bg','featured_categories_band_text','flash_sale_style','flash_sale_accent',
            'shipping_enabled','shipping_cost','shipping_free_from','require_address',
            'show_flash_sale','show_testimonials','show_newsletter','show_trust_strip',
            // Sistema — Pagos
            'payment_yape_number','payment_yape_name','payment_yape_qr','payment_plin_number','payment_bank_details','payment_manual_instructions',
            'payment_bank_bcp','payment_bank_interbank','payment_bank_bbva','payment_bank_nacion','payment_bank_scotiabank',
            'culqi_public_key','culqi_mode',
            // Sistema — Footer y textos
            'footer_tagline','footer_copyright','footer_dev_text',
            'contact_email','contact_phone','business_hours',
            'footer_benefit_1_icon','footer_benefit_1_text',
            'footer_benefit_2_icon','footer_benefit_2_text',
            'footer_benefit_3_icon','footer_benefit_3_text',
            'footer_pages','footer_store_pages',
            'footer_newsletter_title','footer_newsletter_url',
            'footer_show_social','footer_show_categories','footer_show_newsletter',
            'footer_show_benefits','footer_show_address',
            'cart_title','cart_empty_msg','btn_checkout_text','btn_send_quote_text',
            'txt_no_results','txt_search_placeholder','txt_view_more','txt_all_cats',
            'checkout_fields',
            // Sistema — Login
            'login_bg_type','login_color1','login_color2','login_bg_image','login_heading','login_subtitle',
            // Sistema — SEO
            'seo_title','seo_description','seo_keywords','seo_robots','seo_canonical',
        ];
        // Sanitizar la personalización visual por categoría.
        if ($request->has('featured_categories_items')) {
            $decoded = json_decode((string) $request->input('featured_categories_items'), true);
            $clean = [];
            if (is_array($decoded)) {
                foreach ($decoded as $categoryId => $item) {
                    if (!is_numeric($categoryId) || !is_array($item)) continue;
                    $clean[(string)((int)$categoryId)] = [
                        'visual' => in_array($item['visual'] ?? 'inherit', ['inherit','image','icon','initial'], true) ? $item['visual'] : 'inherit',
                        'icon' => preg_match('/^[a-z0-9\-]+$/', (string)($item['icon'] ?? 'default')) ? (string)$item['icon'] : 'default',
                        'image' => mb_substr(trim((string)($item['image'] ?? '')), 0, 500),
                        'fit' => in_array($item['fit'] ?? 'cover', ['cover','contain'], true) ? $item['fit'] : 'cover',
                        'shape' => in_array($item['shape'] ?? 'inherit', ['inherit','rounded','square','circle'], true) ? $item['shape'] : 'inherit',
                    ];
                }
            }
            $request->merge(['featured_categories_items' => json_encode($clean, JSON_UNESCAPED_SLASHES)]);
        }

        // Sanitizar el orden interno de los anuncios promocionales.
        if ($request->has('promo_order')) {
            $allowedPromoSlots = [1,2,3];
            $requestedPromoOrder = array_values(array_unique(array_filter(
                array_map('intval', explode(',', (string) $request->input('promo_order'))),
                fn ($slot) => in_array($slot, $allowedPromoSlots, true)
            )));
            $request->merge([
                'promo_order' => implode(',', array_merge(
                    $requestedPromoOrder,
                    array_values(array_diff($allowedPromoSlots, $requestedPromoOrder))
                )),
            ]);
        }

        // Sanitizar el orden de Inicio para impedir claves desconocidas o duplicadas.
        if ($request->has('home_section_order')) {
            $allowedHomeSections = ['hero','benefits','promotions','categories','flash_sale','discount_products','featured_products','blog','catalog','custom_page'];
            $requestedOrder = array_values(array_unique(array_filter(
                array_map('trim', explode(',', (string) $request->input('home_section_order'))),
                fn ($key) => in_array($key, $allowedHomeSections, true)
            )));
            $request->merge([
                'home_section_order' => implode(',', array_merge(
                    $requestedOrder,
                    array_values(array_diff($allowedHomeSections, $requestedOrder))
                )),
            ]);
        }

        foreach ($request->only($keys) as $key => $value) {
            $project->settings()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Guardar también el estado apagado, pero solo para la pestaña enviada.
        $booleanKeysByTab = [
            'portada' => ['section_card_shadow','section_show_dividers','promo_item_3_enabled','promo_item_2_enabled','promo_item_1_enabled','promo_show_dots','promo_autoplay','promo_enabled','trust_section_enabled','trust_show_descriptions','trust_mobile_carousel','trust_item_1_enabled','trust_item_2_enabled','trust_item_3_enabled','trust_item_4_enabled','featured_categories_enabled','featured_categories_show_all','featured_categories_hide_empty','featured_categories_show_count','featured_categories_mobile_carousel','hero_cta1_show','hero_cta2_show','hero_autoplay','hero_pause_hover','hero_show_arrows','hero_show_dots','hero_slide_1_enabled','hero_slide_2_enabled','hero_slide_3_enabled','hero_slide_4_enabled','hero_slide_5_enabled','hero_slide_1_show_content','hero_slide_2_show_content','hero_slide_3_show_content','hero_slide_4_show_content','hero_slide_5_show_content','hero_slide_1_cta1_show','hero_slide_2_cta1_show','hero_slide_3_cta1_show','hero_slide_4_cta1_show','hero_slide_5_cta1_show','hero_slide_1_cta2_show','hero_slide_2_cta2_show','hero_slide_3_cta2_show','hero_slide_4_cta2_show','hero_slide_5_cta2_show'],
            'catalogo' => ['catalog_filter_price','catalog_filter_cats','catalog_filter_sale','catalog_filter_search','catalog_show_ratings','catalog_quick_view','catalog_show_sku','catalog_show_stock','wholesale_enabled','catalog_watermark','btn_show_icon','float_cart_show','float_wa_show'],
            'sistema' => ['shipping_enabled','require_address','show_flash_sale','show_testimonials','show_newsletter','show_trust_strip','footer_show_social','footer_show_categories','footer_show_newsletter','footer_show_benefits','footer_show_address','payment_manual_enabled','culqi_enabled','mp_enabled'],
        ];
        $designTab = (string) $request->input('_design_tab', '');
        foreach ($booleanKeysByTab[$designTab] ?? [] as $boolKey) {
            $project->settings()->updateOrCreate(
                ['key' => $boolKey],
                ['value' => $request->input($boolKey) === '1' ? '1' : '0']
            );
        }

        // Mantener sincronizada la plantilla personalizada activa.
        // Algunos storefronts mezclan sus settings con project_settings;
        // por eso actualizamos ambos para que el diseñador siempre gane.
        $activeProjectTemplate = ProjectTemplate::where('project_id', $project->id)
            ->where('is_active', true)
            ->first();

        if ($activeProjectTemplate) {
            $templateSettings = (array) ($activeProjectTemplate->settings ?? []);
            $keysToSync = array_values(array_unique(array_merge(
                $keys,
                $booleanKeysByTab[$designTab] ?? []
            )));
            $latestValues = $project->settings()
                ->whereIn('key', $keysToSync)
                ->pluck('value', 'key')
                ->toArray();

            foreach ($latestValues as $key => $value) {
                $templateSettings[$key] = $value;
            }

            $activeProjectTemplate->settings = $templateSettings;
            $activeProjectTemplate->save();
        }

        // Arrays de checkboxes (solo pestaña sistema)
        if ($designTab === 'sistema') {
            $this->projectSettingWrites->saveJsonArrays($project, [
                'accepted_payments' => $request->input('accepted_payments', []),
                'payment_manual_methods' => $request->input('payment_manual_methods', []),
            ]);
        }

        // Llaves secretas: solo guardar si se envió algo que no sea la máscara
        $this->projectSettingWrites->saveSecrets($project, [
            'culqi_secret_key' => $request->input('culqi_secret_key', ''),
            'mp_access_token' => $request->input('mp_access_token', ''),
        ]);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['ok' => true]);
        }
        // El tab activo del Constructor Visual se controla por el query param ?cv=
        // (lo lee Alpine). Devolver al mismo tab evita el "salto a página en blanco".
        $validTabs = ['navegacion', 'inicio', 'marca', 'portada', 'catalogo', 'paginas', 'checkout', 'sistema'];
        $cv = in_array($designTab, $validTabs, true) ? $designTab : 'marca';
        return redirect()->route('settings.design', ['s' => 'constructor', 'cv' => $cv])
            ->with('success', 'Configuración guardada.');
    }

    public function qr()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $baseUrl = $this->publicCatalogUrl($project);
        $isPublicUrl = $baseUrl !== '';

        // La vista lee los ajustes de la tienda a traves del contexto; sin el
        // la pantalla entera reventaba con "Undefined variable $storefrontContext".
        $storefrontContext = $this->storefrontContexts->forProject($project);

        return view('settings.qr', compact('project', 'baseUrl', 'isPublicUrl', 'storefrontContext'));
    }

    private function publicCatalogUrl(Project $project): string
    {
        $candidates = [];
        if (filled($project->custom_domain)) {
            $candidates[] = ['root' => 'https://'.trim((string) $project->custom_domain, '/'), 'append_slug' => false];
        }

        $candidates[] = ['root' => request()->getSchemeAndHttpHost(), 'append_slug' => true];
        $candidates[] = ['root' => (string) config('app.url'), 'append_slug' => true];

        foreach ($candidates as $candidate) {
            $root = rtrim(trim($candidate['root']), '/');
            $scheme = strtolower((string) parse_url($root, PHP_URL_SCHEME));
            $host = strtolower((string) parse_url($root, PHP_URL_HOST));
            if (!in_array($scheme, ['http', 'https'], true) || !$this->isPublicQrHost($host)) {
                continue;
            }

            return $candidate['append_slug'] ? $root.'/'.$project->slug : $root;
        }

        return '';
    }

    private function isPublicQrHost(string $host): bool
    {
        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return (bool) filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
        }

        if (!str_contains($host, '.') || preg_match('/\.(?:local|localhost|test|internal|invalid)$/i', $host)) {
            return false;
        }

        return (bool) filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
    }

    public function updateQr(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeProject($project);

        $data = $request->validate([
            'qr_mode'        => 'required|in:catalog,orders',
            'qr_table_count' => 'required|integer|min:1|max:50',
            'qr_reception'   => 'required|in:auto,manual',
            'qr_payment'     => 'required|in:cashier,waiter',
            'qr_schedule'    => 'nullable|array',
            'qr_size'         => 'nullable|integer|min:160|max:1200',
            'qr_margin'       => 'nullable|integer|min:0|max:12',
            'qr_foreground'   => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'qr_background'   => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'qr_header_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'qr_top_text'     => 'nullable|string|max:120',
            'qr_bottom_text'  => 'nullable|string|max:120',
            'qr_share_message'=> 'nullable|string|max:500',
            'qr_preset'       => 'nullable|in:classic,brand,orange,dark,minimal',
            'qr_quality'      => 'nullable|in:standard,high',
            'qr_show_logo'    => 'nullable|boolean',
            'qr_logo_size'    => 'nullable|integer|min:40|max:220',
            'qr_template'     => 'nullable|in:marca,promocional,minimal,redes,impresion',
            'qr_tinte'        => 'nullable|integer|min:0|max:95',
            'qr_card_color'   => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'qr_body_color'   => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'qr_background_style' => 'nullable|in:solido,degradado',
            'qr_icons'        => 'nullable|string|max:120',
            'qr_scale'        => 'nullable|integer|min:80|max:115',
            'qr_brands'       => 'nullable|string|max:200',
            'qr_show_brands'  => 'nullable|boolean',
            'qr_show_trama'   => 'nullable|boolean',
            'qr_show_brackets'=> 'nullable|boolean',
            'qr_format'       => 'nullable|in:flyer,historia,post,cuadrado,impresion',
            'qr_subtitle'     => 'nullable|string|max:140',
            'qr_benefits'     => 'nullable|string|max:400',
            'qr_text_color'   => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'qr_show_benefits'=> 'nullable|boolean',
            'qr_show_name'    => 'nullable|boolean',
            'qr_show_bixo'    => 'nullable|boolean',
            'qr_logo_in_qr'   => 'nullable|boolean',
            'qr_show_url'     => 'nullable|boolean',
        ]);

        foreach (['qr_mode','qr_table_count','qr_reception','qr_payment'] as $key) {
            $project->settings()->updateOrCreate(['key' => $key], ['value' => $data[$key]]);
        }

        foreach (['qr_size','qr_margin','qr_foreground','qr_background','qr_header_color',
                  'qr_top_text','qr_bottom_text','qr_share_message','qr_preset','qr_quality','qr_logo_size',
                  'qr_template','qr_format','qr_subtitle','qr_benefits','qr_text_color',
                  'qr_tinte','qr_card_color','qr_body_color','qr_background_style','qr_icons','qr_scale','qr_brands'] as $key) {
            if (array_key_exists($key, $data)) {
                $project->settings()->updateOrCreate(['key' => $key], ['value' => $data[$key]]);
            }
        }
        foreach (['qr_show_logo', 'qr_show_url', 'qr_show_benefits', 'qr_show_name', 'qr_show_bixo', 'qr_logo_in_qr', 'qr_show_trama', 'qr_show_brackets', 'qr_show_brands'] as $key) {
            $project->settings()->updateOrCreate([
                'key' => $key,
            ], ['value' => $request->boolean($key) ? '1' : '0']);
        }

        if (!empty($data['qr_schedule'])) {
            $project->settings()->updateOrCreate(
                ['key' => 'qr_schedule'],
                ['value' => json_encode($data['qr_schedule'])]
            );
        }

        return response()->json(['ok' => true]);
    }

    public function payments()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        return view('settings.payments', compact('project'));
    }

    public function updatePayments(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeProject($project);

        $keys = [
            'payment_yape_number','payment_yape_name','payment_yape_qr','payment_plin_number',
            'payment_bank_details','payment_manual_instructions',
            'payment_bank_bcp','payment_bank_interbank','payment_bank_bbva','payment_bank_nacion','payment_bank_scotiabank',
            'culqi_public_key','culqi_mode',
            'store_mode','quote_price_display',
            'quote_whatsapp','quote_whatsapp_country','quote_wa_msg',
        ];
        foreach ($request->only($keys) as $key => $value) {
            $project->settings()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        foreach (['payment_manual_enabled', 'culqi_enabled', 'mp_enabled'] as $boolKey) {
            $project->settings()->updateOrCreate(
                ['key' => $boolKey],
                ['value' => $request->input($boolKey) === '1' ? '1' : '0']
            );
        }

        if ($request->has('accepted_payments') || $request->query('s') === 'methods') {
            $project->settings()->updateOrCreate(
                ['key' => 'accepted_payments'],
                ['value' => json_encode($request->input('accepted_payments', []))]
            );
        }
        if ($request->has('payment_manual_methods') || $request->query('s') === 'manual') {
            $project->settings()->updateOrCreate(
                ['key' => 'payment_manual_methods'],
                ['value' => json_encode($request->input('payment_manual_methods', []))]
            );
        }

        foreach (['culqi_secret_key', 'mp_access_token'] as $secretKey) {
            $val = $request->input($secretKey, '');
            if ($val && !str_starts_with($val, '••')) {
                $project->settings()->updateOrCreate(['key' => $secretKey], ['value' => $val]);
            }
        }

        return back()->with('success', 'Configuración de pagos guardada.');
    }

    public function applyTemplate(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeProject($project);

        $templateKey = $request->input('template');

        // El orden importa: `get()` exige `string` y una plantilla vacia llega
        // como NULL (la convierte `ConvertEmptyStringsToNull`), asi que
        // llamarla primero devolvia **500** en vez de un 422 limpio.
        // `isSupported()` acepta null, de modo que filtra antes de tocar nada.
        //
        // Y estar en el catalogo NO basta: hay que estar SOPORTADA.
        // `CatalogTemplates::isSupported()` ya existia para esto —las
        // soportadas son ecommerce, direct y computienda— pero aqui no se
        // usaba, asi que se podia aplicar cualquier clave del catalogo. Entre
        // ellas `editorial`, `luxe` y `bistro`, que ni siquiera tienen Blade
        // (3 de 18): al elegirlas la tienda caia a la plantilla por defecto
        // respondiendo 200, y el negocio veia una portada distinta de la que
        // escogio, para siempre y sin que nadie se enterara.
        if (! CatalogTemplates::isSupported($templateKey)) {
            return response()->json([
                'ok' => false,
                'message' => 'Esa plantilla no está disponible. Elige una del catálogo activo.',
            ], 422);
        }

        $template = CatalogTemplates::get($templateKey);
        if (! $template) {
            return response()->json(['ok' => false, 'message' => 'Plantilla no encontrada.'], 422);
        }

        // Guardar la clave de plantilla activa
        $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => $templateKey]);
        ProjectTemplate::where('project_id', $project->id)->where('is_active', true)->update(['is_active' => false]);

        // La plantilla solo aporta valores iniciales. Los ajustes del proyecto son
        // globales y siempre deben sobrevivir a un cambio de plantilla.
        $skip = ['culqi_secret_key', 'mp_access_token', 'culqi_public_key'];
        $preserved = 0;
        foreach ($template['settings'] as $key => $value) {
            if (in_array($key, $skip)) continue;
            $existing = $project->settings()->where('key', $key)->value('value');
            if ($existing !== null && $existing !== '') {
                $preserved++;
                continue;
            }
            $project->settings()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Si se solicita, guardar esta configuración como plantilla personalizada del proyecto
        if ($request->input('save_as_template') === '1') {
            $name = $request->input('template_name') ?: ('Plantilla ' . ucfirst($templateKey));
            // Desactivar otras plantillas activas del proyecto
            \App\Models\ProjectTemplate::where('project_id', $project->id)->where('is_active', true)->update(['is_active' => false]);
            \App\Models\ProjectTemplate::create([
                'project_id' => $project->id,
                'name'       => $name,
                'description'=> 'Generada desde aplicación de plantilla ' . $templateKey,
                'settings'   => $template['settings'],
                'is_active'  => true,
            ]);
        }

        return response()->json([
            'ok' => true,
            'template' => $templateKey,
            'preserved' => $preserved,
            'theme' => \App\Support\StorefrontTheme::resolve(['catalog_template' => $templateKey]),
            // `StorefrontNavigation::publicUrl()` ya resuelve el dominio
            // propio y no se usaba aqui: un negocio con dominio propio
            // recibia el enlace a arindg.com/slug en vez de al suyo.
            'public_url' => StorefrontNavigation::publicUrl($project),
        ]);
    }

    /** Aplicar una plantilla guardada del propio proyecto o activarla */
    public function applyProjectTemplate(Request $request)
    {
        $project = app('active_project');
        $this->authorizeProject($project);

        $id = (int) $request->input('id');
        $template = ProjectTemplate::where('project_id', $project->id)->find($id);
        if (!$template) {
            return response()->json(['ok' => false, 'message' => 'Plantilla no encontrada.'], 404);
        }

        // Activar la plantilla para el proyecto
        ProjectTemplate::where('project_id', $project->id)->where('is_active', true)->update(['is_active' => false]);
        $template->is_active = true; $template->save();

        // Una plantilla personalizada puede cambiar la estructura activa, pero
        // sus valores guardados no deben borrar la configuración global actual.
        $templateSettings = (array) $template->settings;
        $templateKey = $templateSettings['catalog_template'] ?? null;
        if ($templateKey && CatalogTemplates::get($templateKey)) {
            $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => $templateKey]);
        }

        // Si se solicita aplicar la plantilla, solo completar valores vacíos.
        if ($request->input('apply_to_project') === '1') {
            $skip = ['culqi_secret_key', 'mp_access_token', 'culqi_public_key'];
            foreach ($templateSettings as $key => $value) {
                if (in_array($key, $skip)) continue;
                if ($key === 'catalog_template') continue;
                $existing = $project->settings()->where('key', $key)->value('value');
                if ($existing !== null && $existing !== '') continue;
                $project->settings()->updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }

        return response()->json(['ok' => true, 'id' => $template->id]);
    }

    public function storeProjectTemplate(Request $request)
    {
        $project = app('active_project');
        $this->authorizeProject($project);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
        ]);

        // Tomar settings del request (JSON string) o del proyecto actual
        if ($request->filled('settings')) {
            $settings = json_decode($request->input('settings'), true) ?: [];
        } else {
            $settings = $project->settings()->pluck('value', 'key')->toArray();
        }

        ProjectTemplate::where('project_id', $project->id)->where('is_active', true)->update(['is_active' => false]);
        $tpl = ProjectTemplate::create([
            'project_id' => $project->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'settings' => $settings,
            'is_active' => true,
        ]);

        return back()->with('success', 'Plantilla creada.');
    }

    public function updateProjectTemplate(Request $request, $id)
    {
        $project = app('active_project');
        $this->authorizeProject($project);
        $tpl = ProjectTemplate::where('project_id', $project->id)->findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'description' => 'nullable|string',
            'settings' => 'nullable',
        ]);

        if (isset($data['name'])) $tpl->name = $data['name'];
        if (array_key_exists('description', $data)) $tpl->description = $data['description'];
        if ($request->filled('settings')) $tpl->settings = json_decode($request->input('settings'), true) ?: $tpl->settings;
        $tpl->save();

        return back()->with('success', 'Plantilla actualizada.');
    }

    public function destroyProjectTemplate(Request $request, $id)
    {
        $project = app('active_project');
        $this->authorizeProject($project);
        $tpl = ProjectTemplate::where('project_id', $project->id)->findOrFail($id);
        $tpl->delete();
        return back()->with('success', 'Plantilla eliminada.');
    }

    /** Guardar el flujo de estados activo del proyecto (según su rubro). */
    public function updateFlow(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeProject($project);

        // Estados marcados (los vacíos vienen de toggles apagados)
        $states = array_filter((array) $request->input('states', []));

        // Filtrar a estados válidos del rubro + forzar los "core"
        $valid = array_keys(\App\Support\OrderFlow::catalog($project));
        $states = array_values(array_intersect($states, $valid));
        foreach (\App\Support\OrderFlow::coreKeys($project) as $core) {
            if (!in_array($core, $states)) $states[] = $core;
        }

        // Guardar en clave nueva (order_flow) y también en legacy para compat.
        $project->settings()->updateOrCreate(['key' => 'order_flow'],   ['value' => json_encode(array_values($states))]);
        $project->settings()->updateOrCreate(['key' => 'laundry_flow'], ['value' => json_encode(array_values($states))]);

        // Config por estado: 3 tiempos (objetivo/alerta/crítico), alerta, color, aviso WA y mensaje
        $target  = (array) $request->input('time_target', []);
        $warn    = (array) $request->input('time_warn', []);
        $crit    = (array) $request->input('time_critical', []);
        $alert   = (array) $request->input('alert', []);
        $color   = (array) $request->input('color', []);
        $notify  = (array) $request->input('notify', []);
        $waMsg   = (array) $request->input('wa_message', []);

        $intOrNull = fn($v) => (isset($v) && $v !== '' && (int) $v > 0) ? (int) $v : null;

        $config = [];
        foreach ($valid as $key) {
            $entry = [];
            if ($t = $intOrNull($target[$key] ?? null)) $entry['time_target']   = $t;
            if ($w = $intOrNull($warn[$key]   ?? null)) $entry['time_warn']     = $w;
            if ($c = $intOrNull($crit[$key]   ?? null)) $entry['time_critical'] = $c;
            if (!empty($alert[$key]))  $entry['alert']  = true;
            if (!empty($color[$key]))  $entry['color']  = substr((string) $color[$key], 0, 7);
            if (isset($notify[$key]))  $entry['notify'] = !empty($notify[$key]);
            if (!empty($waMsg[$key]))  $entry['wa_message'] = mb_substr((string) $waMsg[$key], 0, 500);
            if ($entry) $config[$key] = $entry;
        }

        $project->settings()->updateOrCreate(['key' => 'order_flow_config'],   ['value' => json_encode($config)]);
        $project->settings()->updateOrCreate(['key' => 'laundry_flow_config'], ['value' => json_encode($config)]);

        return back()->with('success', 'Flujo de estados actualizado.');
    }

    /** Guardar el diagrama (posiciones de nodos + transiciones dibujadas). */
    public function updateDiagram(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeProject($project);

        $valid = array_keys(\App\Support\OrderFlow::catalog($project));

        // Posiciones: { key: {x, y} }
        $positions = [];
        foreach ((array) $request->input('positions', []) as $key => $pos) {
            if (!in_array($key, $valid)) continue;
            $positions[$key] = [
                'x' => (int) ($pos['x'] ?? 0),
                'y' => (int) ($pos['y'] ?? 0),
            ];
        }

        // Transiciones: [ {from, to}, ... ]
        $transitions = [];
        foreach ((array) $request->input('transitions', []) as $t) {
            $from = $t['from'] ?? null;
            $to   = $t['to'] ?? null;
            if ($from && $to && in_array($from, $valid) && in_array($to, $valid) && $from !== $to) {
                $transitions[] = ['from' => $from, 'to' => $to];
            }
        }

        $project->settings()->updateOrCreate(
            ['key' => 'order_flow_diagram'],
            ['value' => json_encode(['positions' => $positions, 'transitions' => $transitions])]
        );

        return response()->json(['ok' => true, 'message' => 'Diagrama guardado.']);
    }

    public function storeCoupon(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeProject($project);
        $data = $request->validate([
            'code'       => 'required|string|max:50',
            'type'       => 'required|in:percent,fixed',
            'value'      => 'required|numeric|min:0.01',
            'min_order'  => 'nullable|numeric|min:0',
            'max_uses'   => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after_or_equal:today',
        ]);
        $data['code']      = strtoupper(trim($data['code']));
        $data['min_order'] = $data['min_order'] ?? 0;
        $coupon = $project->coupons()->updateOrCreate(
            ['code' => $data['code']],
            array_merge($data, ['is_active' => true, 'uses_count' => 0])
        );
        return response()->json(['ok' => true, 'coupon' => $coupon]);
    }

    public function destroyCoupon(int $id)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeProject($project);
        $project->coupons()->findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }

    public function toggleCoupon(int $id)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeProject($project);
        $coupon = $project->coupons()->findOrFail($id);
        $coupon->update(['is_active' => !$coupon->is_active]);
        return response()->json(['ok' => true, 'is_active' => $coupon->is_active]);
    }

    public function storeCanal(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_if(!$project, 403);

        $data = $request->validate([
            'id'                 => 'nullable|integer',
            'nombre'             => 'required|string|max:80',
            'telefono'           => 'nullable|string|max:30',
            'phone_number_id'    => 'nullable|string|max:80',
            'access_token'       => 'nullable|string',
            'verify_token'       => 'nullable|string|max:100',
            'mensaje_bienvenida' => 'nullable|string|max:500',
            'mensaje_ausencia'   => 'nullable|string|max:500',
        ]);

        if (!empty($data['id'])) {
            $canal = WaCanal::where('project_id', $project->id)->findOrFail($data['id']);
            if (empty($data['access_token'])) unset($data['access_token']);
            if (empty($data['verify_token']))  unset($data['verify_token']);
            $canal->update($data);
        } else {
            $canal = WaCanal::create(array_merge($data, [
                'project_id' => $project->id,
                'tipo'       => 'bixo',
                'activo'     => true,
            ]));
        }

        return response()->json(['ok' => true, 'canal' => $canal->makeVisible(['access_token', 'verify_token'])]);
    }

    public function destroyCanal(WaCanal $canal)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($canal->project_id === $project->id, 403);
        $canal->delete();
        return response()->json(['ok' => true]);
    }

    private function setupDomainOnVps(string $domain): void
    {
        $domain = preg_replace('/[^a-z0-9\.\-]/', '', strtolower($domain));
        if (!$domain) return;

        try {
            $ctx = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => 'Content-Type: application/json',
                    'content' => json_encode(['domain' => $domain, 'secret' => 'bixo_domain_webhook_2026']),
                    'timeout' => 3,
                    'ignore_errors' => true,
                ],
            ]);
            @file_get_contents('http://127.0.0.1:9876/setup-domain', false, $ctx);
        } catch (\Throwable $e) {
            // silencioso — no bloquear al usuario
        }
    }
}
