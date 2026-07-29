<!DOCTYPE html>
@php
    // Fuente de verdad: los ajustes guardados desde el diseñador.
    // Esto evita que una plantilla personalizada activa sobrescriba cambios nuevos.
    $settings = isset($storefrontContext) ? $storefrontContext->globalSettings() : (array) ($settings ?? []);
    $color = static fn ($v, $fallback) => is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : $fallback;
    $assetUrl = static function ($value) {
        if (blank($value)) return null;
        $value = trim((string) $value);
        if (preg_match('#^(https?:)?//#i', $value) || str_starts_with($value, 'data:')) return $value;
        return asset('storage/' . ltrim(preg_replace('#^storage/#', '', $value), '/'));
    };
    $primary = $color($settings['primary_color'] ?? null, '#2563eb');
    $secondary = $color($settings['secondary_color'] ?? null, '#0f172a');
    $headerBg = $color($settings['header_bg_color'] ?? null, '#ffffff');
    $headerText = $color($settings['header_text_color'] ?? null, '#0f172a');
    $heroBg = $color($settings['hero_bg_color'] ?? null, '#f1f5f9');

    // Sistema global de estilos para todas las secciones de la plantilla.
    $sectionPreset = in_array(($settings['section_style_preset'] ?? 'modern'), ['modern','minimal'], true)
        ? ($settings['section_style_preset'] ?? 'modern') : 'modern';
    $sectionSpacing = in_array(($settings['section_spacing'] ?? 'comfortable'), ['compact','comfortable'], true)
        ? ($settings['section_spacing'] ?? 'comfortable') : 'comfortable';
    $sectionHeadingAlign = in_array(($settings['section_heading_align'] ?? 'left'), ['left','center'], true)
        ? ($settings['section_heading_align'] ?? 'left') : 'left';
    $sectionBackgroundMode = in_array(($settings['section_background_mode'] ?? 'alternate'), ['white','soft','alternate'], true)
        ? ($settings['section_background_mode'] ?? 'alternate') : 'alternate';
    $sectionShowDividers = (string) ($settings['section_show_dividers'] ?? '1') !== '0';
    $sectionCardShadow = (string) ($settings['section_card_shadow'] ?? '1') !== '0';

    // Dos vistas adicionales para productos.
    $featuredProductsView = in_array(($settings['featured_products_view'] ?? 'cards'), ['cards','editorial'], true)
        ? ($settings['featured_products_view'] ?? 'cards') : 'cards';
    $catalogProductsView = in_array(($settings['catalog_products_view'] ?? 'cards'), ['cards','compact'], true)
        ? ($settings['catalog_products_view'] ?? 'cards') : 'cards';
    $currency = $settings['currency_symbol'] ?? 'S/';
    $storeName = $settings['seo_title'] ?? $project->name;
    $tagline = $settings['footer_tagline'] ?? $project->description ?? 'Soluciones tecnológicas para empresas y hogares.';
    $heroTitle = $settings['hero_title'] ?? 'Tecnología confiable para cada necesidad';
    $heroSubtitle = $settings['hero_subtitle'] ?? 'Equipos, accesorios y soporte especializado con atención profesional.';
    $heroBadge = trim(preg_replace('/[\p{So}\p{Cs}]/u', '', $settings['hero_badge'] ?? 'Tecnología y soporte especializado'));
    $heroCta = $settings['hero_cta1_text'] ?? 'Explorar productos';
    $heroCtaVisible = (string) ($settings['hero_cta1_show'] ?? '1') !== '0';
    $contactCta = $settings['hero_cta2_text'] ?? 'Solicitar asesoría';
    $contactCtaVisible = (string) ($settings['hero_cta2_show'] ?? '1') !== '0';
    $catalogTitle = $settings['catalog_section_title'] ?? 'Productos y soluciones';
    $cartText = $settings['btn_cart_text'] ?? $settings['cart_button_text'] ?? 'Agregar al carrito';
    $checkoutText = $settings['btn_checkout_text'] ?? $settings['checkout_button_text'] ?? 'Finalizar pedido';
    $quoteMode = ($settings['store_mode'] ?? 'direct') === 'quote';
    $hidePrices = $quoteMode && ($settings['quote_price_display'] ?? 'show') === 'hide';
    $showSku = (string) ($settings['catalog_show_sku'] ?? '0') === '1';
    $showStock = (string) ($settings['catalog_show_stock'] ?? '1') !== '0';
    $showRatings = (string) ($settings['catalog_show_ratings'] ?? '0') === '1';
    $wholesale = (string) ($settings['wholesale_enabled'] ?? '0') === '1';
    $columns = max(2, min(5, (int) ($settings['catalog_cols_desktop'] ?? 4)));
    $mobileColumns = max(1, min(2, (int) ($settings['catalog_cols_mobile'] ?? 2)));
    $radiusSetting = $settings['border_radius'] ?? 8;
    $radius = match ((string) $radiusSetting) {
        'sharp' => 0,
        'rounded' => 12,
        'pill' => 24,
        default => max(0, min(24, (int) $radiusSetting)),
    };
    $logoUrl = $assetUrl($settings['logo_url'] ?? $project->logo_url ?? null);
    $waSource = $settings['whatsapp_number']
        ?? $settings['quote_whatsapp']
        ?? $settings['contact_whatsapp']
        ?? $settings['store_whatsapp']
        ?? $project->whatsapp
        ?? $project->phone
        ?? '';
    $waRaw = preg_replace('/\D/', '', (string) $waSource);
    $whatsapp = $waRaw && !str_starts_with($waRaw, '51') ? '51' . $waRaw : $waRaw;
    $phone = $settings['contact_phone'] ?? $project->phone ?? '';
    $shopUrl = $project->custom_domain
        ? 'https://' . trim($project->custom_domain, '/') . '/tienda'
        : url('/' . $project->slug . '/tienda');
    $email = $settings['contact_email'] ?? $project->email ?? '';
    // Personalización adicional (antes ignorada)
    $announcementText = trim($settings['announcement_text'] ?? '');
    $announcementBg = $color($settings['announcement_bg'] ?? null, $primary);
    $waTooltip        = $settings['float_wa_tooltip'] ?? '¿Necesitas ayuda?';
    $waFloatShow      = (string) ($settings['float_wa_show']
        ?? $settings['float_whatsapp_enabled']
        ?? $settings['whatsapp_float_enabled']
        ?? '1') !== '0';
    $waMsg            = $settings['quote_wa_msg']
        ?? $settings['whatsapp_msg']
        ?? 'Hola, quiero más información sobre sus productos.';
    $waFloatPos = in_array(($settings['float_wa_pos'] ?? 'right'), ['left','right'], true)
        ? ($settings['float_wa_pos'] ?? 'right') : 'right';
    $badgeSale        = $settings['catalog_badge_sale'] ?? 'OFERTA';
    $badgeNew         = $settings['catalog_badge_new'] ?? 'NUEVO';
    $filterSearch     = (string) ($settings['catalog_filter_search'] ?? '1') !== '0';
    $filterCats       = (string) ($settings['catalog_filter_cats'] ?? '1') !== '0';
    $filterPrice      = (string) ($settings['catalog_filter_price'] ?? '1') !== '0';
    $footerCopyright  = $settings['footer_copyright'] ?? ('© ' . date('Y') . ' ' . $storeName . '. Todos los derechos reservados.');
    $footerLogoHeight = max(24, min(100, (int) ($settings['footer_logo_height'] ?? 50)));
    $showSocial       = (string) ($settings['footer_show_social'] ?? '1') !== '0';
    $social = array_filter([
        'Instagram' => $settings['instagram_url'] ?? '',
        'Facebook'  => $settings['facebook_url'] ?? '',
        'TikTok'    => $settings['tiktok_url'] ?? '',
        'YouTube'   => $settings['youtube_url'] ?? '',
        'LinkedIn'  => $settings['linkedin_url'] ?? '',
    ]);
    // Barra de beneficios del footer
    $showBenefits = (string) ($settings['footer_show_benefits'] ?? '1') !== '0';
    $benefits = array_values(array_filter([
        ['i' => $settings['footer_benefit_1_icon'] ?? '🚚', 't' => $settings['footer_benefit_1_text'] ?? ''],
        ['i' => $settings['footer_benefit_2_icon'] ?? '🔒', 't' => $settings['footer_benefit_2_text'] ?? ''],
        ['i' => $settings['footer_benefit_3_icon'] ?? '💬', 't' => $settings['footer_benefit_3_text'] ?? ''],
    ], fn($b) => trim($b['t']) !== ''));
    // Datos de contacto y config para el footer premium
    $footerAddress   = $settings['contact_address'] ?? $settings['footer_address'] ?? $project->address ?? '';
    $footerHours     = $settings['business_hours'] ?? $settings['contact_hours'] ?? '';
    $footerDevText   = $settings['footer_dev_text'] ?? '';
    $showFooterPay   = (string) ($settings['footer_show_payments'] ?? '1') !== '0';
    $footerPayments  = json_decode($settings['accepted_payments'] ?? '[]', true) ?: [];
    $showFooterSsl   = (string) ($settings['footer_show_ssl'] ?? '1') !== '0';
    // Reusa los "trust" del hero como fila de beneficios del footer (mismos íconos SVG del sistema)
    $footerTrust = array_values(array_filter([
        ['t' => $settings['trust_text_1'] ?? 'Compra segura',       'k' => 'shield'],
        ['t' => $settings['trust_text_2'] ?? 'Despacho coordinado', 'k' => 'truck'],
        ['t' => $settings['trust_text_3'] ?? 'Asesoría',            'k' => 'support'],
        ['t' => $settings['trust_text_4'] ?? 'Garantía',           'k' => 'warranty'],
    ], fn($b) => trim($b['t']) !== ''));
    // Forma de botones y textos de UI
    $btnShape   = $settings['btn_shape'] ?? 'rounded'; // rounded | pill | square
    $btnRadius  = $btnShape === 'pill' ? '999px' : ($btnShape === 'square' ? '2px' : 'calc(var(--radius)*.75)');
    $btnShowIcon = (string) ($settings['btn_show_icon'] ?? '1') !== '0';
    $txtNoResults = $settings['txt_no_results'] ?? 'No se encontraron productos.';
    $txtSearchPlaceholder = $settings['txt_search_placeholder'] ?? 'Buscar productos...';
    $txtViewMore = $settings['txt_view_more'] ?? 'Ver más';
    $cartTitle  = $settings['cart_title'] ?? 'Tu carrito';
    $cartEmpty  = $settings['cart_empty_msg'] ?? 'Tu carrito está vacío.';
    // Hero overlay (oscurecer imagen de fondo)
    $heroOverlay = max(0, min(100, (int) ($settings['hero_overlay'] ?? 40)));

    // Sincronización con el constructor visual de Inicio.
    // store_sections es la única fuente principal para orden, publicación y visibilidad.
    $homeSectionKeys = ['hero','benefits','promotions','categories','flash_sale','discount_products','featured_products','blog'];

    $componentAliases = [
        'hero' => 'hero',
        'main_banner' => 'hero',
        'banner' => 'hero',
        'banner_principal' => 'hero',

        'benefits' => 'benefits',
        'store_benefits' => 'benefits',
        'trust_strip' => 'benefits',
        'info_strip' => 'benefits',
        'benefits_bar' => 'benefits',

        'announcements' => 'promotions',
        'announcement' => 'promotions',
        'announcement_block' => 'promotions',
        'promotions' => 'promotions',
        'promo_banners' => 'promotions',
        'ads' => 'promotions',

        'featured_categories' => 'categories',
        'categories' => 'categories',
        'main_categories' => 'categories',
        'category_grid' => 'categories',

        'flash_sale' => 'flash_sale',
        'today_only' => 'flash_sale',
        'solo_hoy' => 'flash_sale',

        'discount_products' => 'discount_products',
        'sale_products' => 'discount_products',
        'products_on_sale' => 'discount_products',

        'featured_products' => 'featured_products',

        'blog' => 'blog',
        'informative_blog' => 'blog',
        'blog_informativo' => 'blog',
    ];

    $resolveNativeSection = static function ($section) use ($componentAliases) {
        $component = trim((string) ($section->component ?? $section->key ?? ''));
        if (isset($componentAliases[$component])) return $componentAliases[$component];

        $label = trim(implode(' ', array_filter([
            $component,
            (string) ($section->name ?? ''),
            (string) ($section->title ?? ''),
        ])));
        $slug = \Illuminate\Support\Str::slug($label, '_');

        if (str_contains($slug, 'banner_principal') || str_contains($slug, 'hero')) return 'hero';
        if (str_contains($slug, 'beneficio')) return 'benefits';
        if (str_contains($slug, 'anuncio') || str_contains($slug, 'promocion')) return 'promotions';
        if (str_contains($slug, 'categoria')) return 'categories';
        if (str_contains($slug, 'solo_por_hoy') || str_contains($slug, 'flash')) return 'flash_sale';
        if (str_contains($slug, 'descuento')) return 'discount_products';
        if (str_contains($slug, 'destacado')) return 'featured_products';
        if (str_contains($slug, 'blog')) return 'blog';

        return null;
    };

    // Fuente pública real: el controlador entrega únicamente secciones habilitadas
    // y dentro de su periodo de publicación. Una sección en borrador no debe aparecer.
    $homeSectionRecords = collect($sections ?? [])
        ->values()
        ->filter(fn ($section) => ($section->page ?? 'home') === 'home')
        ->sortBy(fn ($section) => $section->sort_order ?? 999)
        ->values();

    $constructorOrder = $homeSectionRecords
        ->sortBy(fn ($section) => $section->draft_sort_order ?? $section->sort_order ?? 999)
        ->map($resolveNativeSection)
        ->filter()
        ->unique()
        ->values()
        ->all();

    // Compatibilidad con proyectos antiguos que todavía no tienen store_sections.
    $legacyOrder = array_values(array_filter(
        array_map('trim', explode(',', (string) ($settings['home_section_order'] ?? ''))),
        fn ($key) => in_array($key, $homeSectionKeys, true)
    ));

    $preferredOrder = count($constructorOrder) ? $constructorOrder : $legacyOrder;
    $homeSectionOrder = array_values(array_unique(array_merge($preferredOrder, $homeSectionKeys)));
    $homeSectionPositions = array_flip($homeSectionOrder);
    $sectionOrder = static fn (string $key): int => (int) ($homeSectionPositions[$key] ?? 999);

    // La consulta pública ya entrega únicamente secciones publicadas y habilitadas.
    // Si existen registros, una sección nativa solo se muestra cuando está publicada.
    $publishedNativeSections = $homeSectionRecords
        ->map($resolveNativeSection)
        ->filter()
        ->unique()
        ->values()
        ->all();

    $homeSectionByNativeKey = $homeSectionRecords
        ->mapWithKeys(function ($section) use ($resolveNativeSection) {
            $key = $resolveNativeSection($section);
            return $key ? [$key => $section] : [];
        });

    $sectionContentFor = static function (string $key) use ($homeSectionByNativeKey): array {
        $section = $homeSectionByNativeKey->get($key);
        return is_array($section?->content) ? $section->content : [];
    };

    $hasPublishedSectionRegistry = $homeSectionRecords->isNotEmpty();
    $isHomeSectionVisible = static fn (string $key): bool =>
        !$hasPublishedSectionRegistry || in_array($key, $publishedNativeSections, true);

    // Categorías destacadas configurables.
    // Inicio controla qué categorías aparecen; Portada controla su estilo visual.
    $categoriesSectionContent = $sectionContentFor('categories');
    $featuredCatsEnabled = (string) ($settings['featured_categories_enabled'] ?? '1') !== '0';
    $featuredCatsTitle = trim($settings['featured_categories_title'] ?? $categoriesSectionContent['title'] ?? 'Explora por categoría');
    $featuredCatsSubtitle = trim($settings['featured_categories_subtitle'] ?? '');
    $featuredCatsShowAll = (string) ($settings['featured_categories_show_all'] ?? '1') !== '0';
    $featuredCatsAllText = trim($settings['featured_categories_all_text'] ?? 'Ver todo');
    $featuredCatsVisual = in_array(($settings['featured_categories_visual'] ?? 'auto'), ['auto','image','icon','initial'], true)
        ? ($settings['featured_categories_visual'] ?? 'auto') : 'auto';
    $featuredCatsStyle = in_array(($settings['featured_categories_style'] ?? 'image-top'), ['image-top','overlay','minimal','horizontal'], true)
        ? ($settings['featured_categories_style'] ?? 'image-top') : 'image-top';
    $featuredCatsColumns = max(2, min(6, (int) ($settings['featured_categories_columns'] ?? 4)));
    $featuredCatsMobileColumns = max(1, min(2, (int) ($settings['featured_categories_mobile_columns'] ?? 2)));
    $featuredCatsLimit = max(1, min(12, (int) ($categoriesSectionContent['count'] ?? $categoriesSectionContent['limit'] ?? $settings['featured_categories_limit'] ?? 8)));
    $featuredCatsSelectedIds = collect($categoriesSectionContent['category_ids'] ?? $categoriesSectionContent['selected_categories'] ?? $categoriesSectionContent['categories'] ?? [])
        ->map(fn ($value) => (string) (is_array($value) ? ($value['id'] ?? $value['value'] ?? '') : $value))
        ->filter()->unique()->values();
    $featuredCatsHideEmpty = (string) ($settings['featured_categories_hide_empty'] ?? '1') !== '0';
    $featuredCatsShowCount = (string) ($settings['featured_categories_show_count'] ?? '1') !== '0';
    $featuredCatsMobileCarousel = (string) ($settings['featured_categories_mobile_carousel'] ?? '0') === '1';
    $featuredCatsRadius = max(0, min(32, (int) ($settings['featured_categories_radius'] ?? 18)));
    $featuredCatsSectionBg = $color($settings['featured_categories_section_bg'] ?? null, '#f8fafc');
    $featuredCatsCardBg = $color($settings['featured_categories_card_bg'] ?? null, '#ffffff');
    $featuredCatsTextColor = $color($settings['featured_categories_text_color'] ?? null, '#0f172a');
    $featuredCatsAccent = $color($settings['featured_categories_accent'] ?? null, $primary);
    $featuredCatsShape = in_array(($settings['featured_categories_shape'] ?? 'rounded'), ['rounded','square','circle'], true)
        ? ($settings['featured_categories_shape'] ?? 'rounded') : 'rounded';
    $featuredCatsImageFit = in_array(($settings['featured_categories_image_fit'] ?? 'cover'), ['cover','contain'], true)
        ? ($settings['featured_categories_image_fit'] ?? 'cover') : 'cover';
    $featuredCatsItems = json_decode($settings['featured_categories_items'] ?? '{}', true);
    $featuredCatsItems = is_array($featuredCatsItems) ? $featuredCatsItems : [];


    // Sección de beneficios / compra con confianza
    $trustSectionEnabled = (string) ($settings['trust_section_enabled'] ?? '1') !== '0';
    $trustSectionTitle = trim($settings['trust_section_title'] ?? 'Compra con confianza');
    $trustSectionSubtitle = trim($settings['trust_section_subtitle'] ?? 'Beneficios pensados para darte una mejor experiencia.');
    $trustSectionStyle = in_array(($settings['trust_section_style'] ?? 'cards'), ['cards','compact','icons-top'], true)
        ? ($settings['trust_section_style'] ?? 'cards') : 'cards';
    $trustSectionColumns = max(2, min(4, (int) ($settings['trust_section_columns'] ?? 4)));
    $trustSectionMobileColumns = max(1, min(2, (int) ($settings['trust_section_mobile_columns'] ?? 1)));
    $trustSectionRadius = max(0, min(28, (int) ($settings['trust_section_radius'] ?? 16)));
    $trustSectionBg = $color($settings['trust_section_bg'] ?? null, '#f8fafc');
    $trustCardBg = $color($settings['trust_card_bg'] ?? null, '#ffffff');
    $trustTextColor = $color($settings['trust_text_color'] ?? null, '#0f172a');
    $trustAccentColor = $color($settings['trust_accent_color'] ?? null, $primary);
    $trustShowDescriptions = (string) ($settings['trust_show_descriptions'] ?? '1') !== '0';
    $trustMobileCarousel = (string) ($settings['trust_mobile_carousel'] ?? '0') === '1';

    $trustBenefits = array_values(array_filter([
        [
            'title' => trim($settings['trust_text_1'] ?? 'Retiro en tienda'),
            'description' => trim($settings['trust_description_1'] ?? 'Coordina y recoge tu pedido.'),
            'icon' => $settings['trust_icon_1'] ?? 'store',
            'enabled' => (string) ($settings['trust_item_1_enabled'] ?? '1') !== '0',
        ],
        [
            'title' => trim($settings['trust_text_2'] ?? 'Envíos a todo el Perú'),
            'description' => trim($settings['trust_description_2'] ?? 'Cobertura según destino.'),
            'icon' => $settings['trust_icon_2'] ?? 'truck',
            'enabled' => (string) ($settings['trust_item_2_enabled'] ?? '1') !== '0',
        ],
        [
            'title' => trim($settings['trust_text_3'] ?? 'Entrega express'),
            'description' => trim($settings['trust_description_3'] ?? 'Consulta disponibilidad en tu zona.'),
            'icon' => $settings['trust_icon_3'] ?? 'clock',
            'enabled' => (string) ($settings['trust_item_3_enabled'] ?? '1') !== '0',
        ],
        [
            'title' => trim($settings['trust_text_4'] ?? 'Diseños exclusivos'),
            'description' => trim($settings['trust_description_4'] ?? 'Opciones seleccionadas para ti.'),
            'icon' => $settings['trust_icon_4'] ?? 'sparkles',
            'enabled' => (string) ($settings['trust_item_4_enabled'] ?? '1') !== '0',
        ],
    ], fn ($item) => $item['enabled'] && $item['title'] !== ''));

    $allProducts = $categories->flatMap(fn ($cat) => $cat->products->concat($cat->children->flatMap(fn ($child) => $child->products)))->unique('id')->values();
    $catalogProducts = collect();
    foreach ($categories as $category) {
        foreach ($category->products as $product) {
            $catalogProducts->push([
                'id' => (string) $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => (float) $product->price,
                'comparePrice' => filled($product->compare_price) ? (float) $product->compare_price : null,
                'stock' => $product->stock === null ? null : (int) $product->stock,
                'image' => $product->main_image_url,
                'category' => $category->name,
                'categoryId' => (string) $category->id,
                'parentId' => null,
                'url' => is_numeric($product->id) ? route('public.product', [$project->slug, $product->id]) : null,
                'wholesalePrice' => filled($product->wholesale_price) ? (float) $product->wholesale_price : null,
                'wholesaleMinQty' => (int) ($product->wholesale_min_qty ?? 1),
                'wholesaleUnit' => $product->wholesale_unit ?? 'unidades',
            ]);
        }
        foreach ($category->children as $subcategory) {
            foreach ($subcategory->products as $product) {
                $catalogProducts->push([
                    'id' => (string) $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => (float) $product->price,
                    'comparePrice' => filled($product->compare_price) ? (float) $product->compare_price : null,
                    'stock' => $product->stock === null ? null : (int) $product->stock,
                    'image' => $product->main_image_url,
                    'category' => $subcategory->name,
                    'categoryId' => (string) $subcategory->id,
                    'parentId' => (string) $category->id,
                    'url' => is_numeric($product->id) ? route('public.product', [$project->slug, $product->id]) : null,
                    'wholesalePrice' => filled($product->wholesale_price) ? (float) $product->wholesale_price : null,
                    'wholesaleMinQty' => (int) ($product->wholesale_min_qty ?? 1),
                    'wholesaleUnit' => $product->wholesale_unit ?? 'unidades',
                ]);
            }
        }
    }
    $catalogProducts = $catalogProducts->unique('id')->values();
    $catalogCategoryIndex = $categories->flatMap(function ($category) {
        return collect([[
            'id' => (string) $category->id,
            'name' => $category->name,
            'parentId' => null,
        ]])->concat($category->children->map(fn ($subcategory) => [
            'id' => (string) $subcategory->id,
            'name' => $subcategory->name,
            'parentId' => (string) $category->id,
        ]));
    })->values();
    $catalogColumns = min(4, $columns);
    $categoryCount = $categories->count() + $categories->sum(fn ($cat) => $cat->children->count());
    $heroProduct = $featured->first(fn ($product) => filled($product->main_image_url));
    $heroImage = $assetUrl($settings['hero_image'] ?? null) ?: $heroProduct?->main_image_url;
    // Slider principal configurable: 1 a 5 slides, imagen PC y móvil.
    // Debe definirse antes de construir $heroSlides porque cada slide lo usa como valor por defecto.
    $heroAlign = in_array(($settings['hero_align'] ?? 'left'), ['left', 'center', 'right'], true)
        ? ($settings['hero_align'] ?? 'left')
        : 'left';

    $heroShowContent = (string) ($settings['hero_show_content'] ?? '1') !== '0';
    $heroAutoplay = (string) ($settings['hero_autoplay'] ?? '1') !== '0';
    $heroShowArrows = (string) ($settings['hero_show_arrows'] ?? '1') !== '0';
    $heroShowDots = (string) ($settings['hero_show_dots'] ?? '1') !== '0';
    $heroPauseHover = (string) ($settings['hero_pause_hover'] ?? '1') !== '0';
    $heroDuration = max(3000, min(12000, (int) ($settings['hero_duration'] ?? 6000)));
    $heroTransition = in_array(($settings['hero_transition'] ?? 'fade'), ['fade','slide'], true)
        ? ($settings['hero_transition'] ?? 'fade') : 'fade';
    $heroMobileHeight = max(360, min(760, (int) ($settings['hero_mobile_height'] ?? 520)));

    $heroSlides = [];
    foreach (range(1, 5) as $n) {
        $desktopKey = $n === 1 ? 'hero_image' : "hero_image_{$n}";
        $desktop = $assetUrl($settings[$desktopKey] ?? null);
        $enabled = (string) ($settings["hero_slide_{$n}_enabled"] ?? '1') !== '0';
        if (!$desktop || !$enabled) continue;
        $mobile = $assetUrl($settings["hero_mobile_image_{$n}"] ?? null);
        $align = in_array(($settings["hero_slide_{$n}_align"] ?? $heroAlign), ['left','center','right'], true)
            ? ($settings["hero_slide_{$n}_align"] ?? $heroAlign) : 'left';
        $position = in_array(($settings["hero_slide_{$n}_position"] ?? 'center'), ['left','center','right','top','bottom'], true)
            ? ($settings["hero_slide_{$n}_position"] ?? 'center') : 'center';
        $overlay = max(0, min(90, (int) ($settings["hero_slide_{$n}_overlay"] ?? $heroOverlay)));
        $slideContentEnabled = (string) ($settings["hero_slide_{$n}_show_content"] ?? '1') !== '0';
        // La opción global "Solo imagen" siempre tiene prioridad.
        $showContent = $heroShowContent && $slideContentEnabled;
        $cta1Show = $showContent
            && (string) ($settings["hero_slide_{$n}_cta1_show"] ?? ($heroCtaVisible ? '1' : '0')) !== '0';
        $cta2Show = $showContent
            && (string) ($settings["hero_slide_{$n}_cta2_show"] ?? ($contactCtaVisible ? '1' : '0')) !== '0';
        $heroSlides[] = [
            'slot'      => $n,
            'img'       => $desktop,
            'mobileImg' => $mobile,
            'badge'     => trim($settings["hero_badge_{$n}"] ?? ($n === 1 ? ($settings['hero_badge'] ?? '') : '')),
            'title'     => trim($settings["hero_title_{$n}"] ?? ($n === 1 ? ($settings['hero_title'] ?? '') : '')),
            'sub'       => trim($settings["hero_subtitle_{$n}"] ?? ($n === 1 ? ($settings['hero_subtitle'] ?? '') : '')),
            'align'     => $align,
            'position'  => $position,
            'overlay'   => $overlay,
            'showContent' => $showContent,
            'cta1Show'  => $cta1Show,
            'cta1Text'  => trim($settings["hero_slide_{$n}_cta1_text"] ?? $heroCta),
            'cta1Url'   => trim($settings["hero_slide_{$n}_cta1_url"] ?? '#catalogo'),
            'cta2Show'  => $cta2Show,
            'cta2Text'  => trim($settings["hero_slide_{$n}_cta2_text"] ?? $contactCta),
            'cta2Url'   => trim($settings["hero_slide_{$n}_cta2_url"] ?? ($whatsapp ? "https://wa.me/{$whatsapp}" : '')),
        ];
    }
    $heroRgb = array_map('hexdec', str_split(ltrim($heroBg, '#'), 2));
    $heroLuminance = (0.2126 * $heroRgb[0] + 0.7152 * $heroRgb[1] + 0.0722 * $heroRgb[2]) / 255;
    $heroText = $heroImage || $heroLuminance < .48 ? '#ffffff' : '#0f172a';
    $heroMuted = $heroImage || $heroLuminance < .48 ? '#e2e8f0' : '#536276';
    $heroAccent = $heroImage || $heroLuminance < .48 ? '#dbeafe' : $primary;
    // --- Personalización del panel de Diseño (antes ignorada) ---
    // Tipografías: acepta font_title/font_body o font (compat)
    $fontTitle = trim($settings['font_title'] ?? $settings['font'] ?? 'Inter') ?: 'Inter';
    $fontBody  = trim($settings['font_body'] ?? $settings['font'] ?? 'Inter') ?: 'Inter';
    $fontList  = collect([$fontTitle, $fontBody, 'Inter'])->unique()->filter()->values();
    $googleFonts = $fontList->map(fn ($f) => str_replace(' ', '+', $f) . ':wght@400;500;600;700')->implode('&family=');
    // Alto del header
    $headerHeight = max(56, min(120, (int) ($settings['header_height'] ?? 78)));
    $logoHeight = max(28, min(90, (int) ($settings['header_logo_height'] ?? $settings['logo_height'] ?? 48)));
    $menuAlign = in_array(($settings['menu_align'] ?? 'left'), ['left','center','right'], true) ? ($settings['menu_align'] ?? 'left') : 'left';
    // Hero: alto
    $heroHeightMap = ['small' => 360, 'medium' => 460, 'large' => 560];
    $heroMinH   = $heroHeightMap[$settings['hero_height'] ?? 'medium'] ?? 460;
    // Colores del footer
    $footerBg   = $color($settings['footer_bg_color'] ?? null, $secondary);
    $footerText = $color($settings['footer_text_color'] ?? null, '#94a3b8');
    // Anuncios promocionales configurables: hasta 3 piezas.
    $promoEnabled = (string) ($settings['promo_enabled'] ?? '1') !== '0';
    $promoSectionTitle = trim($settings['promo_section_title'] ?? 'Promociones');
    $promoSectionSubtitle = trim($settings['promo_section_subtitle'] ?? '');
    $promoStyle = in_array(($settings['promo_style'] ?? 'slider'), ['slider','grid'], true)
        ? ($settings['promo_style'] ?? 'slider') : 'slider';
    $promoColumns = max(1, min(3, (int) ($settings['promo_columns'] ?? 3)));
    $promoHeight = max(220, min(620, (int) ($settings['promo_height'] ?? 360)));
    $promoMobileHeight = max(220, min(520, (int) ($settings['promo_mobile_height'] ?? 300)));
    $promoOverlay = max(0, min(90, (int) ($settings['promo_overlay'] ?? 48)));
    $promoAutoplay = (string) ($settings['promo_autoplay'] ?? '1') !== '0';
    $promoDuration = max(3000, min(12000, (int) ($settings['promo_duration'] ?? 5500)));
    $promoShowDots = (string) ($settings['promo_show_dots'] ?? '1') !== '0';

    $promoAllowedOrder = [1,2,3];
    $promoSavedOrder = array_values(array_filter(
        array_map('intval', explode(',', (string) ($settings['promo_order'] ?? '1,2,3'))),
        fn ($slot) => in_array($slot, $promoAllowedOrder, true)
    ));
    $promoOrder = array_values(array_unique(array_merge($promoSavedOrder, $promoAllowedOrder)));

    $promoItemsBySlot = [];
    foreach (range(1, 3) as $slot) {
        $desktopImage = $assetUrl($settings["promo_image_{$slot}"] ?? null);
        $mobileImage = $assetUrl($settings["promo_mobile_image_{$slot}"] ?? null);
        $title = trim($settings["promo_title_{$slot}"] ?? ($settings["banner{$slot}_title"] ?? ''));
        $subtitle = trim($settings["promo_subtitle_{$slot}"] ?? ($settings["banner{$slot}_sub"] ?? ''));
        $enabled = (string) ($settings["promo_item_{$slot}_enabled"] ?? '1') !== '0';

        if (!$enabled || ($title === '' && $subtitle === '' && !$desktopImage)) continue;

        $promoItemsBySlot[$slot] = [
            'slot' => $slot,
            'title' => $title,
            'subtitle' => $subtitle,
            'image' => $desktopImage,
            'mobileImage' => $mobileImage,
            'ctaText' => trim($settings["promo_cta_text_{$slot}"] ?? 'Ver promoción'),
            'ctaUrl' => trim($settings["promo_cta_url_{$slot}"] ?? $shopUrl),
            'align' => in_array(($settings["promo_align_{$slot}"] ?? 'left'), ['left','center','right'], true)
                ? ($settings["promo_align_{$slot}"] ?? 'left') : 'left',
        ];
    }

    $promoItems = collect($promoOrder)
        ->map(fn ($slot) => $promoItemsBySlot[$slot] ?? null)
        ->filter()
        ->values()
        ->all();
@endphp
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $settings['seo_description'] ?? $heroSubtitle }}">
    <title>{{ $storeName }}</title>
    @if(!empty($settings['favicon_url']))
    <link rel="icon" href="{{ $assetUrl($settings['favicon_url']) }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ $googleFonts }}&display=swap" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root{
          --primary:{{ $primary }};
          --secondary:{{ $secondary }};
          --header-bg:{{ $headerBg }};
          --header-text:{{ $headerText }};
          --hero-bg:{{ $heroBg }};
          --hero-text:{{ $heroText }};
          --hero-muted:{{ $heroMuted }};
          --hero-accent:{{ $heroAccent }};
          --radius:{{ $radius }}px;
          --btn-radius:{{ $btnRadius }};
          --columns:{{ $columns }};
          --mobile-columns:{{ $mobileColumns }};
          --border:#e2e8f0;
          --surface:#ffffff;
          --surface-soft:#f8fafc;
          --text-strong:#0f172a;
          --text:#334155;
          --muted:#64748b;
          --shadow-sm:0 8px 20px rgba(15,23,42,.05);
          --shadow-md:0 14px 32px rgba(15,23,42,.08);
          --shadow-lg:0 24px 54px rgba(15,23,42,.12);
          --radius-sm:10px;
          --radius-md:16px;
          --radius-lg:22px;
          --font-title:'{{ $fontTitle }}',system-ui,sans-serif;
          --font-body:'{{ $fontBody }}',system-ui,sans-serif;
          --header-h:{{ $headerHeight }}px;
          --logo-h:{{ $logoHeight }}px;
          --footer-bg:{{ $footerBg }};
          --footer-text:{{ $footerText }};
          --hero-mobile-h:{{ $heroMobileHeight }}px;
        }
        .product-action,.catalog-card-action,.button{border-radius:var(--btn-radius)!important}

        /* ═══ Sistema global de estilos de secciones ═══ */
        body.section-spacing-compact [data-store-native-section]{padding-top:42px!important;padding-bottom:42px!important}
        body.section-spacing-comfortable [data-store-native-section]{padding-top:68px!important;padding-bottom:68px!important}
        body.section-heading-center .home-section-head,
        body.section-heading-center .trust-section-head,
        body.section-heading-center .promo-section-head,
        body.section-heading-center .pf-head,
        body.section-heading-center .section-heading{align-items:center!important;text-align:center!important}
        body.section-heading-center .home-section-copy,
        body.section-heading-center .trust-section-head,
        body.section-heading-center .promo-section-head>div,
        body.section-heading-center .section-heading>div{margin-left:auto;margin-right:auto}
        body.section-heading-center .home-see-all,
        body.section-heading-center .pf-seeall{margin-left:auto}
        body.section-bg-white [data-store-native-section]{background:#fff!important}
        body.section-bg-soft [data-store-native-section]{background:#f8fafc!important}
        body.section-bg-alternate [data-store-native-section]:nth-of-type(even){background:#f8fafc}
        body.section-bg-alternate [data-store-native-section]:nth-of-type(odd){background:#fff}
        body.section-no-dividers [data-store-native-section]{border-top:0!important;border-bottom:0!important}
        body.section-dividers [data-store-native-section]{border-bottom:1px solid var(--border)}
        body.section-no-shadows .home-cat-card,
        body.section-no-shadows .trust-card,
        body.section-no-shadows .pf-card,
        body.section-no-shadows .catalog-card,
        body.section-no-shadows .promo-slider-shell,
        body.section-no-shadows .promo-grid .promo-slide{box-shadow:none!important}

        body.section-preset-minimal [data-store-native-section]{background:#fff}
        body.section-preset-minimal .home-cat-card,
        body.section-preset-minimal .trust-card,
        body.section-preset-minimal .pf-card,
        body.section-preset-minimal .catalog-card{border-radius:6px!important;box-shadow:none!important}
        body.section-preset-minimal .home-cat-card:hover,
        body.section-preset-minimal .trust-card:hover,
        body.section-preset-minimal .pf-card:hover,
        body.section-preset-minimal .catalog-card:hover{transform:none!important;border-color:#94a3b8!important}
        body.section-preset-minimal .home-cat-icon,
        body.section-preset-minimal .trust-icon{border-radius:6px!important}
        body.section-preset-minimal .promo-slider-shell,
        body.section-preset-minimal .promo-grid .promo-slide{border-radius:6px!important;box-shadow:none!important}
        body.section-preset-minimal .promo-copy strong,
        body.section-preset-minimal .pf-title,
        body.section-preset-minimal .section-heading h2,
        body.section-preset-minimal .home-section-head h2,
        body.section-preset-minimal .trust-section-head h2{letter-spacing:-.025em!important}
        body.section-preset-modern .home-cat-card,
        body.section-preset-modern .trust-card,
        body.section-preset-modern .pf-card,
        body.section-preset-modern .catalog-card{transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease}
        body.section-preset-modern .home-cat-card:hover,
        body.section-preset-modern .trust-card:hover,
        body.section-preset-modern .pf-card:hover,
        body.section-preset-modern .catalog-card:hover{transform:translateY(-5px)}

        /* Vista editorial de productos destacados */
        body.featured-view-editorial .pf-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:24px}
        body.featured-view-editorial .pf-card{display:grid;grid-template-columns:42% 58%;min-height:280px}
        body.featured-view-editorial .pf-media{aspect-ratio:auto;height:100%;border-bottom:0;border-right:1px solid #eef2f7}
        body.featured-view-editorial .pf-media img{padding:26px}
        body.featured-view-editorial .pf-body{padding:28px}
        body.featured-view-editorial .pf-name{font-size:18px;line-height:1.35}
        body.featured-view-editorial .pf-price{font-size:23px}
        body.featured-view-editorial .pf-action{margin:0 28px 24px}
        @media(max-width:900px){body.featured-view-editorial .pf-grid{grid-template-columns:1fr}}
        @media(max-width:560px){
            body.featured-view-editorial .pf-card{grid-template-columns:1fr}
            body.featured-view-editorial .pf-media{height:220px;border-right:0;border-bottom:1px solid #eef2f7}
            body.featured-view-editorial .pf-body{padding:18px}
            body.featured-view-editorial .pf-action{margin:0 18px 18px}
        }

        /* Vista compacta de catálogo */
        body.catalog-view-compact .catalog-product-grid{grid-template-columns:1fr;gap:10px}
        body.catalog-view-compact .catalog-card{display:grid;grid-template-columns:150px minmax(0,1fr) 190px;align-items:center;min-height:150px}
        body.catalog-view-compact .catalog-card-media{height:150px;aspect-ratio:auto;border-bottom:0;border-right:1px solid #eef2f7}
        body.catalog-view-compact .catalog-card-body{min-height:0;padding:18px}
        body.catalog-view-compact .catalog-card-name{min-height:0;font-size:15px}
        body.catalog-view-compact .catalog-card-action{margin:0 16px 0 0;align-self:center}
        @media(max-width:720px){
            body.catalog-view-compact .catalog-card{grid-template-columns:110px minmax(0,1fr)}
            body.catalog-view-compact .catalog-card-media{height:130px}
            body.catalog-view-compact .catalog-card-action{grid-column:1/-1;margin:0 10px 10px}
        }
        body{font-family:var(--font-body)}h1,h2,h3,.hero-copy h1,.section-heading h2,.category-title,.brand-name{font-family:var(--font-title)}
        .header-main{min-height:var(--header-h)!important}
        .hero-inner{min-height:{{ $heroMinH }}px!important}
        @if($heroAlign==='center').hero-inner{grid-template-columns:1fr!important;text-align:center}.hero-copy{margin:0 auto}.hero-actions,.eyebrow{justify-content:center}.hero-visual{display:none}@endif
        @if($heroAlign==='right').hero-inner{direction:rtl}.hero-copy{direction:ltr}@endif
        .native-footer{color:var(--footer-text)!important;background:var(--footer-bg)!important}
        /* ═══ Footer premium ═══ */
        .site-footer{margin-top:60px;color:#cbd5e1;background:linear-gradient(180deg,var(--footer-bg) 0%,color-mix(in srgb,var(--footer-bg) 82%,#000) 100%)}
        .footer-main{display:grid;grid-template-columns:1.6fr 1fr 1fr 1.3fr;gap:48px;padding:56px 0 44px}
        .footer-brand{min-width:0}
        .footer-logo{display:flex;align-items:center;gap:12px;margin-bottom:16px}.footer-logo img{height:{{ $footerLogoHeight }}px;width:auto;max-width:180px;object-fit:contain;background:#fff;padding:5px 8px;border-radius:8px}.footer-logo-mark{width:46px;height:46px;display:grid;place-items:center;background:var(--primary);color:#fff;border-radius:12px;font-size:22px;font-weight:800}.footer-logo strong{color:#fff;font-family:var(--font-title);font-size:19px;letter-spacing:-.02em}
        .footer-desc{margin:0 0 22px;color:#94a3b8;font-size:13.5px;line-height:1.7;max-width:340px}
        .footer-trust{display:grid;grid-template-columns:1fr 1fr;gap:12px 18px;margin-bottom:24px}.footer-trust-item{display:flex;align-items:center;gap:9px;color:#cbd5e1;font-size:12.5px;font-weight:600}.footer-trust-item svg{width:22px;height:22px;flex:0 0 22px;color:var(--primary);filter:brightness(1.4)}
        .footer-social{display:flex;gap:10px}.footer-social a{width:40px;height:40px;display:grid;place-items:center;color:#cbd5e1;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.09);border-radius:11px;transition:.2s}.footer-social a svg{width:19px;height:19px}.footer-social a:hover{color:#fff;background:var(--primary);border-color:var(--primary);transform:translateY(-3px)}
        .footer-title{margin:0 0 20px;color:#fff;font-family:var(--font-title);font-size:15px;font-weight:800;letter-spacing:.01em;position:relative;padding-bottom:12px}.footer-title:after{content:"";position:absolute;left:0;bottom:0;width:34px;height:2px;background:var(--primary);border-radius:2px}
        .footer-links{list-style:none;margin:0;padding:0}.footer-links li{border-bottom:1px solid rgba(255,255,255,.06)}.footer-links li:last-child{border-bottom:0}.footer-links a{display:flex;align-items:center;gap:10px;padding:11px 0;color:#94a3b8;font-size:13.5px;transition:.18s}.footer-links a:hover{color:#fff;padding-left:6px}.footer-cat-ico{width:22px;height:22px;object-fit:cover;border-radius:5px;flex:0 0 22px}
        .footer-contact{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:16px}.footer-contact li{display:flex;align-items:center;gap:12px;color:#cbd5e1;font-size:13.5px;line-height:1.45}.footer-contact li>svg{width:38px;height:38px;flex:0 0 38px;padding:9px;color:var(--primary);background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.09);border-radius:10px;filter:brightness(1.4)}.footer-contact a{color:#cbd5e1;transition:.18s}.footer-contact a:hover{color:#fff}
        .footer-help{display:flex;align-items:center;gap:12px;margin-top:24px;padding:16px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:14px}.footer-help>svg{width:26px;height:26px;flex:0 0 26px;color:var(--primary);filter:brightness(1.4)}.footer-help div{flex:1;min-width:0}.footer-help strong{display:block;color:#fff;font-size:13.5px}.footer-help span{display:block;color:#94a3b8;font-size:11.5px;margin-top:2px}.footer-help-btn{flex:0 0 auto;padding:9px 16px;color:#fff;background:var(--primary);border-radius:var(--btn-radius);font-size:12.5px;font-weight:700;white-space:nowrap;transition:.18s}.footer-help-btn:hover{filter:brightness(1.1);transform:translateY(-2px)}
        .footer-bottom{border-top:1px solid rgba(255,255,255,.08)}.footer-bottom-inner{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:20px;padding:20px 0}
        .footer-secure{display:flex;align-items:center;gap:11px}.footer-secure>svg{width:34px;height:34px;flex:0 0 34px;color:#94a3b8}.footer-secure strong{display:block;color:#cbd5e1;font-size:12.5px;font-weight:700}.footer-secure span{display:block;color:#7c8ba1;font-size:11px}
        .footer-copy{color:#94a3b8;font-size:12.5px;text-align:center;white-space:nowrap}
        .footer-pay{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}.footer-pay-logo{display:inline-flex}.footer-pay-logo svg{height:26px;width:auto;border-radius:4px;box-shadow:0 1px 4px rgba(0,0,0,.25)}
        @media(max-width:1000px){.footer-main{grid-template-columns:1fr 1fr;gap:36px 40px}.footer-brand{grid-column:1/-1}}
        @media(max-width:760px){.footer-bottom-inner{grid-template-columns:1fr;justify-items:center;text-align:center;gap:16px}.footer-pay{justify-content:center}.footer-secure{justify-content:center}}
        @media(max-width:600px){.footer-main{grid-template-columns:1fr;gap:32px;padding:40px 0 30px}.footer-trust{grid-template-columns:1fr 1fr}}
        /* Secciones de la home (portada) */

        /* ═══ Secciones especiales del constructor de Inicio ═══ */
        .special-home-section{padding:64px 0;border-bottom:1px solid var(--border)}
        .special-section-head{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin-bottom:26px}
        .special-section-head h2{margin:0;color:var(--secondary);font-size:clamp(25px,3vw,36px);line-height:1.1;letter-spacing:-.04em}
        .special-section-head p{margin:9px 0 0;color:#64748b;font-size:14px;line-height:1.6}
        .flash-sale-section{position:relative;overflow:hidden;color:#fff;background:linear-gradient(120deg,var(--secondary),color-mix(in srgb,var(--primary) 60%,#020617))}
        .flash-sale-section::before{content:"";position:absolute;inset:-40% auto auto 62%;width:520px;height:520px;border-radius:50%;background:color-mix(in srgb,var(--primary) 30%,transparent);filter:blur(20px)}
        .flash-sale-section .container{position:relative;z-index:1}
        .flash-sale-section .special-section-head h2,.flash-sale-section .special-section-head p{color:#fff}
        .flash-label{display:inline-flex;align-items:center;gap:8px;margin-bottom:12px;padding:7px 11px;border:1px solid rgba(255,255,255,.22);border-radius:999px;background:rgba(255,255,255,.10);font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
        .special-product-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
        .special-product-card{min-width:0;overflow:hidden;background:#fff;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 12px 34px rgba(15,23,42,.08)}
        .special-product-media{position:relative;display:block;height:190px;background:#f8fafc;border-bottom:1px solid #eef2f7}
        .special-product-media img{width:100%;height:100%;object-fit:contain;padding:18px}
        .special-product-badge{position:absolute;left:12px;top:12px;padding:6px 9px;color:#fff;background:#dc2626;border-radius:999px;font-size:10px;font-weight:900}
        .special-product-body{padding:16px}.special-product-body small{display:block;color:#64748b;font-size:11px}.special-product-body strong{display:block;margin-top:6px;color:#0f172a;font-size:14px;line-height:1.4}.special-product-prices{display:flex;align-items:baseline;gap:8px;margin-top:12px}.special-product-prices b{color:var(--primary);font-size:19px}.special-product-prices del{color:#94a3b8;font-size:12px}
        .discount-section{background:#fff}
        .blog-section{background:#f8fafc}
        .blog-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
        .blog-card{overflow:hidden;background:#fff;border:1px solid #e2e8f0;border-radius:18px;transition:.22s}.blog-card:hover{transform:translateY(-4px);box-shadow:0 16px 38px rgba(15,23,42,.09)}
        .blog-card-image{display:block;height:180px;background:linear-gradient(145deg,#e2e8f0,#f8fafc)}.blog-card-image img{width:100%;height:100%;object-fit:cover}
        .blog-card-body{padding:20px}.blog-card-body small{color:var(--primary);font-size:10px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}.blog-card-body h3{margin:8px 0 0;color:#0f172a;font-size:18px;line-height:1.35}.blog-card-body p{margin:9px 0 0;color:#64748b;font-size:13px;line-height:1.65}.blog-card-link{display:inline-flex;margin-top:14px;color:var(--primary);font-size:12px;font-weight:800}
        @media(max-width:980px){.special-product-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.blog-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:560px){.special-home-section{padding:44px 0}.special-section-head{align-items:flex-start;margin-bottom:20px}.special-product-grid,.blog-grid{grid-template-columns:1fr}.special-product-media{height:210px}.blog-card-image{height:190px}}

        .home-cats{padding:64px 0;border-bottom:1px solid var(--border);background:{{ $featuredCatsSectionBg }}}
        .home-featured{padding:56px 0;border-bottom:1px solid var(--border);background:var(--surface)}
        .home-section-head{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin-bottom:28px}
        .home-section-copy{max-width:680px}.home-section-head h2{margin:0;color:var(--secondary);font-size:clamp(24px,2.8vw,34px);letter-spacing:-.04em;line-height:1.1}
        .home-section-head p{margin:9px 0 0;color:#64748b;font-size:14px;line-height:1.6}
        .home-see-all{display:inline-flex;align-items:center;gap:8px;color:var(--primary);font-size:13px;font-weight:800;white-space:nowrap}
        .home-see-all svg{width:16px;height:16px;transition:transform .2s}.home-see-all:hover svg{transform:translateX(4px)}
        .home-cats-grid{display:grid;grid-template-columns:repeat({{ $featuredCatsColumns }},minmax(0,1fr));gap:18px}
        .home-cat-card{position:relative;min-width:0;overflow:hidden;background:{{ $featuredCatsCardBg }};border:1px solid color-mix(in srgb,{{ $featuredCatsAccent }} 16%,#dbe3ef);border-radius:{{ $featuredCatsRadius }}px;transition:transform .22s ease,border-color .22s ease,box-shadow .22s ease}
        .home-cat-card:hover{transform:translateY(-5px);border-color:color-mix(in srgb,{{ $featuredCatsAccent }} 60%,#fff);box-shadow:0 18px 44px color-mix(in srgb,{{ $featuredCatsAccent }} 16%,transparent)}
        .home-cat-media{position:relative;display:grid;place-items:center;height:142px;overflow:hidden;background:linear-gradient(145deg,color-mix(in srgb,{{ $featuredCatsAccent }} 9%,#fff),color-mix(in srgb,{{ $featuredCatsAccent }} 3%,#fff))}
        .home-cat-media img{width:100%;height:100%;object-fit:{{ $featuredCatsImageFit }};transition:transform .35s ease}.home-cat-card:hover .home-cat-media img{transform:scale(1.055)}
        .home-cats.shape-square .home-cat-card,.home-cats.shape-square .home-cat-icon{border-radius:0}
        .home-cats.shape-circle .home-cat-card{overflow:visible;text-align:center;background:transparent;border:0;border-radius:0;box-shadow:none}
        .home-cats.shape-circle .home-cat-card:hover{box-shadow:none}
        .home-cats.shape-circle .home-cat-media{width:156px;height:156px;margin:0 auto;border-radius:50%;border:1px solid color-mix(in srgb,{{ $featuredCatsAccent }} 18%,#dbe3ef);box-shadow:0 12px 30px rgba(15,23,42,.08)}
        .home-cats.shape-circle .home-cat-media img{border-radius:50%}
        .home-cats.shape-circle .home-cat-icon{width:74px;height:74px;border-radius:50%}
        .home-cats.shape-circle .home-cat-content{display:block;padding:14px 8px 0}.home-cats.shape-circle .home-cat-arrow{display:none}
        .home-cats.shape-circle .home-cat-card strong{white-space:normal}
        .home-cat-icon{width:66px;height:66px;display:grid;place-items:center;color:{{ $featuredCatsAccent }};background:#fff;border:1px solid color-mix(in srgb,{{ $featuredCatsAccent }} 22%,#fff);border-radius:18px;box-shadow:0 10px 28px rgba(15,23,42,.08);transition:.22s}
        .home-cat-card:hover .home-cat-icon{color:#fff;background:{{ $featuredCatsAccent }};border-color:{{ $featuredCatsAccent }}}.home-cat-icon svg{width:31px;height:31px}
        .home-cat-initial{font-family:var(--font-title);font-size:26px;font-weight:800}
        .home-cat-content{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:17px 18px 19px}
        .home-cat-copy{min-width:0}.home-cat-card strong{display:block;color:{{ $featuredCatsTextColor }};font-size:15px;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .home-cat-card small{display:block;margin-top:5px;color:#64748b;font-size:11.5px}.home-cat-arrow{width:32px;height:32px;display:grid;place-items:center;flex:0 0 32px;color:{{ $featuredCatsAccent }};background:color-mix(in srgb,{{ $featuredCatsAccent }} 9%,#fff);border-radius:999px;transition:.2s}
        .home-cat-arrow svg{width:15px;height:15px}.home-cat-card:hover .home-cat-arrow{color:#fff;background:{{ $featuredCatsAccent }};transform:translateX(2px)}
        .home-cats.style-minimal .home-cat-card{text-align:center}.home-cats.style-minimal .home-cat-media{height:auto;padding:25px 16px 4px;background:transparent}.home-cats.style-minimal .home-cat-content{display:block;padding:12px 16px 22px}.home-cats.style-minimal .home-cat-arrow{display:none}
        .home-cats.style-horizontal .home-cat-card{display:flex;align-items:center}.home-cats.style-horizontal .home-cat-media{width:104px;height:104px;flex:0 0 104px}.home-cats.style-horizontal .home-cat-content{flex:1}
        .home-cats.style-overlay .home-cat-media{height:210px}.home-cats.style-overlay .home-cat-content{position:absolute;left:0;right:0;bottom:0;z-index:2;color:#fff;background:linear-gradient(transparent,rgba(2,6,23,.88));padding:52px 18px 18px}.home-cats.style-overlay .home-cat-card strong,.home-cats.style-overlay .home-cat-card small{color:#fff}.home-cats.style-overlay .home-cat-arrow{background:rgba(255,255,255,.17);color:#fff}

        .home-prod-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}
        /* ═══ Productos destacados premium ═══ */
        .pf-section{padding:72px 0;background:linear-gradient(180deg,#fff,#f8fafc)}
        .pf-head{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin-bottom:40px}
        .pf-badge{display:inline-flex;align-items:center;gap:8px;padding:7px 14px;color:var(--primary);background:color-mix(in srgb,var(--primary) 9%,#fff);border:1px solid color-mix(in srgb,var(--primary) 18%,#fff);border-radius:999px;font-size:11px;font-weight:800;letter-spacing:.1em}
        .pf-badge svg{width:15px;height:15px}
        .pf-title{margin:16px 0 8px;color:var(--secondary);font-family:var(--font-title);font-size:clamp(28px,3.6vw,44px);font-weight:800;letter-spacing:-.03em;line-height:1.05}
        .pf-desc{max-width:520px;margin:0;color:#64748b;font-size:15px;line-height:1.65}
        .pf-seeall{flex:0 0 auto;display:inline-flex;align-items:center;gap:8px;padding:12px 20px;color:var(--secondary);background:#fff;border:1px solid var(--border);border-radius:999px;font-size:13px;font-weight:700;box-shadow:0 4px 14px rgba(15,23,42,.05);transition:.2s}
        .pf-seeall:hover{color:#fff;background:var(--primary);border-color:var(--primary);transform:translateY(-2px)}
        .pf-seeall svg{width:16px;height:16px}
        .pf-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:22px}
        .pf-card{display:flex;flex-direction:column;overflow:hidden;background:#fff;border:1px solid #eef1f6;border-radius:20px;box-shadow:0 2px 10px rgba(15,23,42,.04);transition:transform .25s ease,box-shadow .25s ease}
        .pf-card:hover{transform:translateY(-6px);box-shadow:0 22px 44px rgba(15,23,42,.12)}
        .pf-media{position:relative;aspect-ratio:1/.92;display:grid;place-items:center;overflow:hidden;background:#f7f8fb}
        .pf-media img{width:100%;height:100%;padding:24px;object-fit:contain;transition:transform .4s ease}
        .pf-card:hover .pf-media img{transform:scale(1.06)}
        .pf-ph{width:56px;color:#cbd5e1}
        .pf-flag{position:absolute;top:14px;left:14px;padding:6px 11px;border-radius:999px;font-size:10.5px;font-weight:800;letter-spacing:.03em;color:#fff}
        .pf-flag-sale{background:#ef4444}.pf-flag-new{background:var(--primary)}.pf-flag-hot{background:#0f172a}
        .pf-fav{position:absolute;top:12px;right:12px;display:grid;place-items:center;width:38px;height:38px;color:#94a3b8;background:#fff;border:1px solid #eef1f6;border-radius:999px;cursor:pointer;box-shadow:0 3px 10px rgba(15,23,42,.08);transition:.18s}
        .pf-fav:hover{color:#ef4444}.pf-fav.on{color:#fff;background:#ef4444;border-color:#ef4444}.pf-fav.on svg{fill:#fff}.pf-fav svg{width:19px;height:19px}
        .pf-body{display:flex;flex:1;flex-direction:column;padding:20px}
        .pf-cat{color:var(--primary);font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
        .pf-name{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin:9px 0 0;color:var(--secondary);font-size:16px;font-weight:700;line-height:1.35;min-height:44px}
        .pf-name:hover{color:var(--primary)}
        .pf-specs{display:flex;flex-wrap:wrap;gap:7px;margin-top:14px}
        .pf-specs span{display:inline-flex;align-items:center;gap:5px;padding:4px 9px;color:#475569;background:#f1f5f9;border-radius:7px;font-size:11px;font-weight:600}
        .pf-specs svg{width:13px;height:13px;color:var(--primary)}
        .pf-prices{display:flex;flex-wrap:wrap;align-items:baseline;gap:9px;margin-top:auto;padding-top:18px}
        .pf-price{color:var(--secondary);font-size:23px;font-weight:800;letter-spacing:-.02em}
        .pf-compare{color:#94a3b8;font-size:13px;text-decoration:line-through}
        .pf-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;margin-top:16px;padding:11px 16px;color:#fff;background:var(--secondary);border-radius:12px;font-size:13px;font-weight:700;transition:.2s}
        .pf-btn:hover{background:var(--primary)}.pf-btn svg{width:15px;height:15px}
        @media(max-width:1000px){.pf-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.pf-head{align-items:flex-start;flex-direction:column}}
        @media(max-width:560px){.pf-grid{grid-template-columns:1fr;gap:16px}.pf-section{padding:48px 0}}
        .home-cta-row{display:flex;justify-content:center;margin-top:34px}
        /* Páginas Nosotros / Contacto */
        .store-page{padding:44px 0 72px}.store-page-title{margin:14px 0 24px;color:var(--secondary);font-size:clamp(26px,3.4vw,40px);letter-spacing:-.03em}.store-page-hero{width:100%;max-height:360px;object-fit:cover;border-radius:var(--radius);margin-bottom:28px}.store-page-body{color:#475569;font-size:16px;line-height:1.8;white-space:pre-line}.store-page-block{margin-top:30px;padding-top:24px;border-top:1px solid var(--border)}.store-page-block h2{margin:0 0 10px;color:var(--secondary);font-size:20px}.store-page-block p{margin:0;color:#475569;line-height:1.8;white-space:pre-line}
        .store-page-contact{display:grid;grid-template-columns:1fr 1.2fr;gap:36px;margin-top:28px;align-items:start}.store-contact-info p{margin:0 0 12px;color:#475569;font-size:15px}.store-contact-info a{color:var(--primary)}.store-contact-form{display:flex;flex-direction:column;gap:12px;background:var(--surface);padding:24px;border:1px solid var(--border);border-radius:var(--radius)}.store-contact-form input,.store-contact-form textarea{width:100%;padding:11px 13px;border:1px solid #cbd5e1;border-radius:calc(var(--radius)*.75);font:inherit;font-size:14px;outline:0}.store-contact-form input:focus,.store-contact-form textarea:focus{border-color:var(--primary)}.store-contact-form .button{margin-top:4px}
        @media(max-width:800px){.store-page-contact{grid-template-columns:1fr}}
        @media(max-width:900px){.home-cats-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.home-prod-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:560px){
        .home-cats{padding:42px 0}.home-section-head{align-items:flex-start;margin-bottom:20px}.home-section-head p{font-size:13px}
        .home-cats-grid{grid-template-columns:repeat({{ $featuredCatsMobileColumns }},minmax(0,1fr));gap:11px}
        .home-cats.mobile-carousel .home-cats-grid{display:flex;overflow-x:auto;scroll-snap-type:x mandatory;padding:2px 2px 14px;scrollbar-width:none}.home-cats.mobile-carousel .home-cats-grid::-webkit-scrollbar{display:none}
        .home-cats.mobile-carousel .home-cat-card{flex:0 0 78%;scroll-snap-align:start}
        .home-cat-media{height:105px}.home-cat-content{padding:13px}.home-cat-card strong{font-size:13px}.home-cat-arrow{display:none}
        .home-cats.shape-circle .home-cat-media{width:112px;height:112px}.home-cats.shape-circle .home-cat-content{padding:10px 4px 0}
        .home-cats.style-horizontal .home-cat-card{display:block}.home-cats.style-horizontal .home-cat-media{width:100%;height:105px}
        .home-cats.style-overlay .home-cat-media{height:170px}
        .home-prod-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
    }

        .promo-section{padding:64px 0;background:#fff;border-bottom:1px solid var(--border)}
        .promo-section-head{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:26px}
        .promo-section-head h2{margin:0;color:var(--secondary);font-size:clamp(26px,3vw,38px);letter-spacing:-.045em;line-height:1.1}
        .promo-section-head p{margin:9px 0 0;color:#64748b;font-size:14px}
        .promo-slider-shell{position:relative;overflow:hidden;border-radius:calc(var(--radius) * 1.5);box-shadow:0 18px 46px rgba(15,23,42,.10)}
        .promo-track{position:relative}
        .promo-slide{position:relative;min-height:{{ $promoHeight }}px;display:flex;align-items:flex-end;overflow:hidden;background:linear-gradient(145deg,color-mix(in srgb,var(--primary) 25%,#f8fafc),var(--secondary))}
        .promo-slide picture,.promo-slide picture img{position:absolute;inset:0;width:100%;height:100%}.promo-slide picture img{object-fit:cover}
        .promo-slide::after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,rgba(2,6,23,calc({{ $promoOverlay }} / 100)) 0%,rgba(2,6,23,.36) 50%,rgba(2,6,23,.10) 100%)}
        .promo-copy{position:relative;z-index:2;width:min(620px,82%);padding:clamp(28px,5vw,58px);color:#fff}
        .promo-copy.align-center{margin:auto;text-align:center}.promo-copy.align-right{margin-left:auto;text-align:right}
        .promo-kicker{display:inline-flex;margin-bottom:12px;padding:6px 10px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.24);border-radius:999px;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;backdrop-filter:blur(8px)}
        .promo-copy strong{display:block;font-family:var(--font-title);font-size:clamp(27px,4.2vw,52px);line-height:1.04;letter-spacing:-.045em}
        .promo-copy span{display:block;margin-top:12px;color:rgba(255,255,255,.88);font-size:clamp(14px,1.7vw,18px);line-height:1.6}
        .promo-cta{display:inline-flex;align-items:center;gap:8px;margin-top:22px;padding:12px 20px;color:var(--secondary);background:#fff;border-radius:var(--btn-radius);font-size:13px;font-weight:800;transition:.2s}
        .promo-cta:hover{transform:translateY(-2px);filter:brightness(.98)}
        .promo-cta svg{width:16px;height:16px}
        .promo-dots{position:absolute;left:50%;bottom:18px;z-index:4;display:flex;gap:7px;transform:translateX(-50%)}
        .promo-dots button{width:9px;height:9px;padding:0;background:rgba(255,255,255,.48);border:0;border-radius:999px;cursor:pointer;transition:.2s}
        .promo-dots button.is-active{width:28px;background:#fff}
        .promo-grid{display:grid;grid-template-columns:repeat({{ $promoColumns }},minmax(0,1fr));gap:18px}
        .promo-grid .promo-slide{min-height:320px;border-radius:calc(var(--radius) * 1.4);box-shadow:0 14px 36px rgba(15,23,42,.09)}
        .promo-grid .promo-copy{width:100%;padding:26px}.promo-grid .promo-copy strong{font-size:clamp(22px,2.5vw,34px)}.promo-grid .promo-copy span{font-size:14px}
        @media(max-width:900px){.promo-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.promo-grid .promo-slide:last-child:nth-child(odd){grid-column:1/-1}}
        @media(max-width:640px){
            .promo-section{padding:42px 0}.promo-section-head{align-items:flex-start;margin-bottom:20px}
            .promo-slider-shell{border-radius:14px}.promo-slide{min-height:{{ $promoMobileHeight }}px}
            .promo-slide::after{background:linear-gradient(0deg,rgba(2,6,23,.84),rgba(2,6,23,.12))}
            .promo-copy,.promo-copy.align-right,.promo-copy.align-center{width:100%;margin:0;padding:28px 22px;text-align:left}
            .promo-copy strong{font-size:30px}.promo-copy span{font-size:14px}.promo-dots{bottom:12px}
            .promo-grid{grid-template-columns:1fr;gap:14px}.promo-grid .promo-slide,.promo-grid .promo-slide:last-child:nth-child(odd){min-height:{{ $promoMobileHeight }}px;grid-column:auto}
        }

        *{box-sizing:border-box}html{scroll-behavior:smooth;overflow-x:hidden}body{max-width:100%;margin:0;overflow-x:hidden;color:#172033;background:#fff;font-family:'Inter',system-ui,sans-serif;-webkit-font-smoothing:antialiased}button,input,select{font:inherit}a{color:inherit;text-decoration:none}img{display:block;max-width:100%}[x-cloak]{display:none!important}.container{width:min(1180px,calc(100% - 40px));margin:auto}
        .topbar{color:#cbd5e1;background:var(--secondary);font-size:12px}.topbar-inner{min-height:34px;display:flex;align-items:center;justify-content:space-between;gap:24px}.topbar p{margin:0}.contact-links{display:flex;gap:22px}.contact-links a:hover{color:#fff}
        .store-header{position:sticky;top:0;z-index:40;color:var(--header-text);background:var(--header-bg);border-bottom:1px solid var(--border)}.store-header>.container.header-main{width:min(1360px,calc(100% - 48px))}.header-main{min-height:78px;display:grid;grid-template-columns:minmax(220px,.9fr) minmax(320px,1.7fr) minmax(200px,.7fr);align-items:center;gap:28px}.brand{min-width:0;display:flex;align-items:center;gap:13px}.brand-logo{height:var(--logo-h);width:auto;max-width:180px;object-fit:contain;flex:0 0 auto}.brand-mark{width:44px;height:44px;display:grid;place-items:center;flex:0 0 44px;color:#fff;background:var(--primary);border-radius:var(--radius);font-weight:700}.brand-copy{min-width:0}.brand-name,.brand-tagline{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.brand-name{color:var(--header-text);font-size:17px;font-weight:700;letter-spacing:-.02em}.brand-tagline{margin-top:3px;color:#64748b;font-size:11px}
        .search{position:relative}.search input{width:100%;height:44px;padding:0 47px 0 15px;color:#172033;background:#fff;border:1px solid #cbd5e1;border-radius:var(--radius);outline:0}.search input:focus,.toolbar input:focus,.toolbar select:focus{border-color:var(--primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 13%,transparent)}.search svg{position:absolute;top:12px;right:15px;width:20px;color:#64748b}.header-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px}.phone-copy{display:flex;flex-direction:column;padding-right:16px;border-right:1px solid var(--border)}.phone-copy small{color:#64748b;font-size:10px}.phone-copy strong{margin-top:3px;font-size:12px}.cart-trigger{min-width:52px;height:44px;display:flex;align-items:center;justify-content:center;gap:8px;color:var(--header-text);background:#fff;border:1px solid #cbd5e1;border-radius:var(--radius);cursor:pointer}.cart-trigger svg{width:20px}.cart-count{min-width:20px;height:20px;display:grid;place-items:center;padding:0 5px;color:#fff;background:var(--primary);border-radius:20px;font-size:10px;font-weight:700}
        .category-nav{background:#fff;border-bottom:1px solid var(--border)}.category-list{min-height:48px;display:flex;align-items:center;gap:4px;overflow-x:auto;scrollbar-width:none}.category-list::-webkit-scrollbar{display:none}.category-list a{flex:0 0 auto;padding:9px 13px;color:#475569;border-radius:5px;font-size:13px;font-weight:500}.category-list a.is-active{color:#fff;background:var(--secondary)}.category-list a:hover{color:var(--primary);background:#f1f5f9}
        /* Mega-menú de categorías */
        .category-nav{position:relative}.category-bar{display:flex;align-items:center;gap:10px;min-height:52px}
        .category-bar .category-list{flex:1;justify-content:flex-start}
        .category-bar.menu-center .category-list{justify-content:center}
        .category-bar.menu-right .category-list{justify-content:flex-end}
        .mega-trigger{flex:0 0 auto}.mega-btn{display:inline-flex;align-items:center;gap:8px;height:40px;padding:0 16px;color:#fff;background:var(--primary);border:0;border-radius:var(--btn-radius);font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap}.mega-btn:hover{filter:brightness(.94)}.mega-caret{transition:transform .2s}
        .mega-panel{position:absolute;left:0;right:0;top:100%;z-index:50;background:#fff;border-top:1px solid var(--border);box-shadow:0 20px 40px rgba(15,23,42,.14)}
        .mega-grid{display:grid;grid-template-columns:260px 1fr;min-height:280px;gap:0}
        .mega-cats{border-right:1px solid var(--border);padding:14px 0;background:var(--surface)}
        .mega-cat-item{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:11px 20px;color:#334155;font-size:14px;font-weight:600;cursor:pointer}.mega-cat-item:hover,.mega-cat-item.is-active{color:var(--primary);background:#fff}.mega-cat-item svg{color:#94a3b8}
        .mega-subs{padding:26px 30px}.mega-sub-title{display:inline-flex;align-items:baseline;gap:10px;margin-bottom:16px;color:var(--secondary);font-size:17px;font-weight:800;letter-spacing:-.02em}.mega-sub-title small{color:var(--primary);font-size:12px;font-weight:700}.mega-sub-title:hover small{text-decoration:underline}
        .mega-sub-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px 24px}.mega-sub-link{padding:6px 0;color:#475569;font-size:13.5px}.mega-sub-link:hover{color:var(--primary)}
        .mega-sub-empty{color:#64748b;font-size:14px}
        @media(max-width:900px){.mega-panel{display:none!important}.mega-btn{display:none}}
        .professional-hero{overflow:hidden;background:var(--hero-bg);border-bottom:1px solid var(--border)}.hero-inner{min-height:460px;display:grid;grid-template-columns:minmax(0,1.05fr) minmax(360px,.95fr);align-items:center;gap:68px;padding:64px 0}.hero-copy,.hero-visual{min-width:0}.eyebrow{display:inline-flex;align-items:center;gap:9px;margin-bottom:20px;color:var(--hero-accent);font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.eyebrow:before{width:26px;height:2px;content:'';background:currentColor}.hero-copy h1{max-width:720px;margin:0;overflow-wrap:break-word;color:var(--hero-text);font-size:clamp(35px,4.3vw,58px);font-weight:700;letter-spacing:-.045em;line-height:1.08}.hero-copy>p{max-width:640px;margin:23px 0 0;overflow-wrap:break-word;color:var(--hero-muted);font-size:17px;line-height:1.75}.hero-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:30px}.button{min-height:46px;display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:0 20px;border:1px solid transparent;border-radius:var(--radius);font-size:13px;font-weight:700;cursor:pointer;transition:.15s ease}.button svg{width:17px}.button-primary{color:#fff;background:var(--primary)}.button-primary:hover{filter:brightness(.9)}.button-secondary{color:var(--secondary);background:#fff;border-color:#cbd5e1}.button-secondary:hover{color:var(--primary);border-color:var(--primary)}
        /* ═══ HERO PREMIUM (fotorealista, azul marino + luces) ═══ */
        .premium-hero{position:relative;overflow:hidden;min-height:560px;display:flex;align-items:center;background:radial-gradient(120% 120% at 80% 0%,#12245c 0%,#0b1736 45%,#070f26 100%)}
        .premium-hero.has-bg{background-size:cover;background-position:center right;background-repeat:no-repeat}
        .ph-slide{position:absolute;inset:0;z-index:0;overflow:hidden}
        .ph-picture,.ph-picture img{width:100%;height:100%;display:block}.ph-picture img{object-fit:cover;object-position:center}
        .ph-shade{position:absolute;inset:0;background:linear-gradient(90deg,rgba(7,15,38,.94) 0%,rgba(7,15,38,.70) 38%,rgba(7,15,38,.12) 68%,rgba(7,15,38,.02) 100%);pointer-events:none}
        .ph-arrow{position:absolute;z-index:8;top:50%;transform:translateY(-50%);display:grid;place-items:center;width:48px;height:48px;color:#fff;background:rgba(15,23,42,.42);border:1px solid rgba(255,255,255,.28);border-radius:999px;cursor:pointer;backdrop-filter:blur(10px);box-shadow:0 10px 30px rgba(0,0,0,.2);transition:.2s}
        .ph-arrow:hover{background:var(--primary);border-color:var(--primary);transform:translateY(-50%) scale(1.04)}
        .ph-arrow:focus-visible{outline:3px solid rgba(255,255,255,.8);outline-offset:3px}.ph-arrow svg{width:21px;height:21px}
        .ph-arrow-prev{left:24px}.ph-arrow-next{right:24px}
        .ph-dots{position:absolute;z-index:8;left:50%;bottom:22px;transform:translateX(-50%);display:flex;align-items:center;gap:9px;padding:8px 11px;background:rgba(15,23,42,.35);border:1px solid rgba(255,255,255,.18);border-radius:999px;backdrop-filter:blur(10px)}
        .ph-dot{width:9px;height:9px;padding:0;background:rgba(255,255,255,.5);border:0;border-radius:999px;cursor:pointer;transition:.25s}.ph-dot.is-active{width:28px;background:#fff}
        .ph-slide-copy{max-width:620px}.premium-hero .hero-actions{align-items:center}.premium-hero .button{box-shadow:0 12px 28px rgba(0,0,0,.18)}
        .premium-hero{min-height:var(--hero-desktop-h,560px)}.premium-hero-inner{min-height:var(--hero-desktop-h,560px)}
        .premium-hero-copy.is-center{margin-inline:auto;text-align:center}.premium-hero-copy.is-center .hero-actions{justify-content:center}.premium-hero-copy.is-center .ph-eyebrow{margin-inline:auto}
        .premium-hero-copy.is-right{margin-left:auto;text-align:right}.premium-hero-copy.is-right .hero-actions{justify-content:flex-end}.premium-hero-copy.is-right .ph-eyebrow{margin-left:auto}
        .ph-picture img.pos-left{object-position:left center}.ph-picture img.pos-right{object-position:right center}.ph-picture img.pos-top{object-position:center top}.ph-picture img.pos-bottom{object-position:center bottom}
        .ph-slide-copy .hero-actions{margin-top:28px}.ph-slide-copy .button{min-width:148px}.ph-slide-copy .button-ghost{background:rgba(15,23,42,.34)}
        @media(max-width:760px){.premium-hero-copy.is-right{text-align:center}.premium-hero-copy.is-right .hero-actions{justify-content:center}.premium-hero-copy.is-right .ph-eyebrow{margin-inline:auto}.ph-slide-copy .button{width:100%;max-width:320px}.ph-slide-copy .hero-actions{flex-direction:column}}
        @media(max-width:760px){.premium-hero{min-height:var(--hero-mobile-h)}.premium-hero-inner{min-height:var(--hero-mobile-h);padding:52px 28px 74px}.ph-shade{background:linear-gradient(180deg,rgba(7,15,38,.32),rgba(7,15,38,.86) 72%,rgba(7,15,38,.94))}.ph-picture img{object-position:center}.ph-arrow{width:40px;height:40px}.ph-arrow-prev{left:10px}.ph-arrow-next{right:10px}.ph-dots{bottom:14px}.ph-title{font-size:clamp(31px,10vw,46px)}.ph-sub{font-size:14px}.premium-hero .hero-actions{justify-content:center}.premium-hero-copy{text-align:center;margin:auto}.ph-eyebrow{margin-inline:auto}}
        .premium-hero.has-bg .premium-hero-inner{grid-template-columns:1fr}
        .premium-hero.has-bg .premium-hero-copy{max-width:560px}
        @media(max-width:960px){.premium-hero.has-bg{background-image:linear-gradient(180deg,rgba(11,23,54,.72),rgba(11,23,54,.92)),var(--hero-bg-img)!important;background-position:center}}
        .premium-hero-bg{position:absolute;inset:0;overflow:hidden;pointer-events:none}
        .ph-glow{position:absolute;border-radius:50%;filter:blur(90px);opacity:.55}
        .ph-glow-1{width:520px;height:520px;top:-140px;right:-80px;background:radial-gradient(circle,#2563eb 0%,transparent 70%)}
        .ph-glow-2{width:440px;height:440px;bottom:-160px;left:8%;background:radial-gradient(circle,#7c3aed 0%,transparent 70%);opacity:.4}
        .ph-grid{position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.04) 1px,transparent 1px);background-size:52px 52px;mask-image:radial-gradient(120% 80% at 60% 30%,#000 30%,transparent 75%)}
        .ph-line{position:absolute;height:1px;width:60%;background:linear-gradient(90deg,transparent,rgba(37,99,235,.7),transparent);opacity:.6}
        .ph-line-1{top:32%;left:-10%;transform:rotate(-8deg)}
        .ph-line-2{bottom:26%;right:-10%;width:50%;background:linear-gradient(90deg,transparent,rgba(124,58,237,.6),transparent);transform:rotate(6deg)}
        .premium-hero-inner{position:relative;z-index:2;display:grid;grid-template-columns:40% 60%;align-items:center;gap:40px;padding:70px 0;width:min(1180px,calc(100% - 40px))}
        .premium-hero-copy{min-width:0}
        .ph-eyebrow{display:inline-flex;align-items:center;gap:10px;margin-bottom:22px;padding:7px 14px;color:#93c5fd;background:rgba(37,99,235,.12);border:1px solid rgba(37,99,235,.3);border-radius:999px;font-size:11.5px;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
        .ph-title{margin:0;color:#fff;font-family:var(--font-title);font-size:clamp(38px,4.6vw,62px);font-weight:800;line-height:1.04;letter-spacing:-.03em;text-transform:uppercase;text-shadow:0 4px 30px rgba(37,99,235,.25)}
        .ph-sub{max-width:440px;margin:22px 0 0;color:#aab6d4;font-size:16.5px;line-height:1.7}
        .premium-hero .hero-actions{margin-top:32px}
        .premium-hero .button-primary{background:linear-gradient(135deg,#2563eb,#1d4ed8);box-shadow:0 10px 30px rgba(37,99,235,.4);min-height:50px;padding:0 26px;font-size:14px}
        .premium-hero .button-primary:hover{filter:brightness(1.08);transform:translateY(-2px)}
        .premium-hero .button-ghost{min-height:50px;padding:0 24px;color:#dbe4ff;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.16);border-radius:var(--btn-radius);font-size:14px;font-weight:700;display:inline-flex;align-items:center;gap:8px;transition:.18s}
        .premium-hero .button-ghost:hover{background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.3)}
        .premium-hero-visual{position:relative;min-height:420px;display:flex;align-items:center;justify-content:center}
        .ph-stage{position:relative;width:100%;height:100%;min-height:420px;display:flex;align-items:center;justify-content:center}
        .ph-main{position:relative;z-index:3;width:min(560px,92%);filter:drop-shadow(0 40px 70px rgba(0,0,0,.55)) drop-shadow(0 0 40px rgba(37,99,235,.25))}
        .ph-stage-clean .ph-main{width:min(680px,100%)}
        .ph-main img{width:100%;height:auto;object-fit:contain;animation:phFloat 6s ease-in-out infinite}
        .ph-float{position:absolute;z-index:4;border-radius:16px;overflow:hidden;background:rgba(255,255,255,.03);backdrop-filter:blur(2px);filter:drop-shadow(0 20px 40px rgba(0,0,0,.5))}
        .ph-float img{width:100%;height:100%;object-fit:contain}
        .ph-float-a{width:130px;height:130px;top:6%;right:4%;animation:phFloat 5s ease-in-out .3s infinite}
        .ph-float-b{width:150px;height:110px;bottom:8%;left:0;animation:phFloat 5.5s ease-in-out .6s infinite}
        .ph-float-c{width:100px;height:100px;bottom:20%;right:10%;animation:phFloat 4.5s ease-in-out .9s infinite}
        .ph-reflection{position:absolute;z-index:2;bottom:6%;left:50%;transform:translateX(-50%);width:60%;height:60px;background:radial-gradient(ellipse at center,rgba(37,99,235,.35),transparent 70%);filter:blur(20px)}
        @keyframes phFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-14px)}}
        @media(prefers-reduced-motion:reduce){.ph-main img,.ph-float{animation:none}}
        @media(max-width:960px){.premium-hero{min-height:0}.premium-hero-inner{grid-template-columns:1fr;gap:36px;padding:52px 0;text-align:center}.ph-eyebrow{margin-inline:auto}.premium-hero .hero-actions{justify-content:center}.ph-sub{margin-inline:auto}.premium-hero-visual{min-height:320px}.ph-float-a{right:12%}.ph-float-c{right:16%}}
        @media(max-width:560px){.ph-float{display:none}.ph-main{width:82%}}
        .hero-visual{min-height:330px;display:flex;align-items:center}.hero-frame{width:100%;aspect-ratio:4/3;overflow:hidden;background:#fff;border:1px solid #dbe3ed;border-radius:calc(var(--radius)*1.25);box-shadow:0 20px 45px rgba(15,23,42,.1)}.hero-frame>img{width:100%;height:100%;padding:28px;object-fit:contain}.hero-fallback{width:100%;height:100%;display:flex;flex-direction:column;justify-content:center;padding:42px;color:#475569}.hero-fallback>svg{width:42px;color:var(--primary)}.hero-fallback>strong{margin-top:20px;color:#0f172a;font-size:21px}.hero-fallback>span{margin-top:8px;font-size:14px;line-height:1.6}.hero-metrics{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1px;margin-top:28px;overflow:hidden;background:var(--border);border:1px solid var(--border);border-radius:calc(var(--radius)*.75)}.hero-metric{padding:16px;background:#fff}.hero-metric b{display:block;color:#0f172a;font-size:21px}.hero-metric small{display:block;margin-top:4px;color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.05em}

        .trust-section{padding:64px 0;background:{{ $trustSectionBg }};border-bottom:1px solid var(--border)}
        .trust-section-head{margin-bottom:28px;max-width:720px}.trust-section-head h2{margin:0;color:{{ $trustTextColor }};font-size:clamp(25px,2.8vw,36px);line-height:1.1;letter-spacing:-.045em}.trust-section-head p{margin:10px 0 0;color:#64748b;font-size:15px;line-height:1.6}
        .trust-grid{display:grid;grid-template-columns:repeat({{ $trustSectionColumns }},minmax(0,1fr));gap:16px}
        .trust-card{display:flex;align-items:flex-start;gap:15px;min-width:0;padding:22px;background:{{ $trustCardBg }};border:1px solid color-mix(in srgb,{{ $trustAccentColor }} 14%,#dbe3ef);border-radius:{{ $trustSectionRadius }}px;box-shadow:0 10px 30px rgba(15,23,42,.045);transition:transform .22s ease,border-color .22s ease,box-shadow .22s ease}
        .trust-card:hover{transform:translateY(-4px);border-color:color-mix(in srgb,{{ $trustAccentColor }} 55%,#fff);box-shadow:0 18px 42px color-mix(in srgb,{{ $trustAccentColor }} 13%,transparent)}
        .trust-icon{width:48px;height:48px;display:grid;place-items:center;flex:0 0 48px;color:{{ $trustAccentColor }};background:color-mix(in srgb,{{ $trustAccentColor }} 10%,#fff);border:1px solid color-mix(in srgb,{{ $trustAccentColor }} 16%,#fff);border-radius:14px;transition:.22s}
        .trust-card:hover .trust-icon{color:#fff;background:{{ $trustAccentColor }};border-color:{{ $trustAccentColor }}}.trust-icon svg{width:23px;height:23px}
        .trust-card-copy{min-width:0}.trust-card strong{display:block;color:{{ $trustTextColor }};font-size:15px;line-height:1.35}.trust-card span{display:block;margin-top:6px;color:#64748b;font-size:13px;line-height:1.55}
        .trust-section.style-compact .trust-section-head{text-align:center;margin-left:auto;margin-right:auto}.trust-section.style-compact .trust-card{align-items:center;padding:17px 18px;box-shadow:none}.trust-section.style-compact .trust-icon{width:42px;height:42px;flex-basis:42px}
        .trust-section.style-icons-top .trust-section-head{text-align:center;margin-left:auto;margin-right:auto}.trust-section.style-icons-top .trust-card{display:block;text-align:center;padding:26px 20px}.trust-section.style-icons-top .trust-icon{margin:0 auto 15px}

        .catalog{padding:72px 0 90px}.section-heading{display:flex;align-items:end;justify-content:space-between;gap:32px;margin-bottom:26px}.section-heading h2{margin:0;color:var(--secondary);font-size:clamp(26px,3vw,36px);letter-spacing:-.035em}.section-heading p{max-width:550px;margin:10px 0 0;color:#64748b;font-size:14px;line-height:1.65}.product-total{flex:0 0 auto;color:#64748b;font-size:12px}.catalog-toolbar{display:grid;grid-template-columns:minmax(260px,1fr) minmax(240px,.75fr);gap:14px;margin-bottom:30px;padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius)}.toolbar{position:relative}.toolbar input,.toolbar select{width:100%;height:44px;padding:0 14px;color:#1e293b;background:#fff;border:1px solid #cbd5e1;border-radius:calc(var(--radius)*.75);outline:0}.toolbar input{padding-left:42px}.toolbar svg{position:absolute;top:12px;left:13px;width:19px;color:#64748b}
        .category-section{scroll-margin-top:145px;margin-top:54px}.category-section:first-of-type{margin-top:0}.category-title{display:flex;align-items:center;gap:14px;margin:0 0 18px;color:var(--secondary);font-size:18px}.category-title:after{height:1px;flex:1;content:'';background:var(--border)}.product-grid{display:grid;grid-template-columns:repeat(var(--columns),minmax(0,1fr));gap:18px}.product-card{min-width:0;display:flex;flex-direction:column;overflow:hidden;background:#fff;border:1px solid var(--border);border-radius:var(--radius);transition:border-color .15s ease,box-shadow .15s ease}.product-card:hover{border-color:#cbd5e1;box-shadow:0 10px 28px rgba(15,23,42,.08)}.product-image{position:relative;aspect-ratio:1;display:grid;place-items:center;overflow:hidden;background:#fff;border-bottom:1px solid #edf1f5}.product-image img{width:100%;height:100%;padding:22px;object-fit:contain}.image-empty{width:52px;color:#cbd5e1}.product-badge{position:absolute;top:12px;left:12px;z-index:2;padding:5px 8px;color:#fff;background:var(--primary);border-radius:4px;font-size:9px;font-weight:700;letter-spacing:.05em}.product-body{min-height:183px;display:flex;flex:1;flex-direction:column;padding:16px}.product-meta{min-height:17px;display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:7px;color:#7c899b;font-size:10px}.available{color:#18794e}.unavailable{color:#b42318}.product-name{color:#243044;font-size:13px;font-weight:600;line-height:1.5}.product-name:hover{color:var(--primary)}.rating{margin-top:8px;color:#a16207;font-size:11px}.price-row{display:flex;flex-wrap:wrap;align-items:baseline;gap:7px;margin-top:auto;padding-top:15px}.price{color:var(--secondary);font-size:18px;font-weight:700}.compare-price{color:#94a3b8;font-size:11px;text-decoration:line-through}.quote-price{color:var(--primary);font-size:14px;font-weight:700}.wholesale{margin-top:5px;color:#64748b;font-size:10px}.product-action{width:100%;min-height:40px;margin-top:14px;color:#fff;background:var(--secondary);border:0;border-radius:calc(var(--radius)*.75);font-size:11px;font-weight:700;cursor:pointer}.product-action:hover{background:var(--primary)}.product-action:disabled{color:#94a3b8;background:#e2e8f0;cursor:not-allowed}.empty{padding:50px 20px;color:#64748b;text-align:center;border:1px dashed #cbd5e1;border-radius:var(--radius)}
        /* Catálogo avanzado: experiencia de filtros de Ecommerce con identidad CompuTienda. */
        .catalog-breadcrumb{display:flex;align-items:center;gap:8px;margin-bottom:10px;color:#64748b;font-size:12px}.catalog-breadcrumb a:hover{color:var(--primary)}
        .catalog-experience{display:grid;grid-template-columns:280px minmax(0,1fr);align-items:start;gap:30px;margin-top:28px}.catalog-filter-panel{position:sticky;top:96px;max-height:calc(100vh - 116px);overflow-y:auto;background:#fff;border:1px solid var(--border);border-radius:12px;scrollbar-width:thin}.catalog-filter-header{position:sticky;top:0;z-index:2;min-height:52px;display:flex;align-items:center;justify-content:space-between;padding:0 16px;background:#fff;border-bottom:1px solid var(--border)}.catalog-filter-header strong{font-size:14px}.catalog-clear{min-height:44px;padding:0;color:var(--primary);background:transparent;border:0;font-size:12px;font-weight:700;cursor:pointer}.catalog-clear[disabled]{opacity:0;pointer-events:none}
        .catalog-filter-group{border-top:1px solid var(--border)}.catalog-filter-group:first-of-type{border-top:0}.catalog-filter-summary{min-height:48px;display:flex;align-items:center;gap:8px;padding:0 16px;list-style:none;font-size:13px;font-weight:700;cursor:pointer;user-select:none}.catalog-filter-summary::-webkit-details-marker{display:none}.catalog-filter-summary svg{width:17px;margin-left:auto;color:#64748b;transition:transform .18s ease}.catalog-filter-group[open]>.catalog-filter-summary svg{transform:rotate(180deg)}.catalog-filter-badge{min-width:19px;height:19px;display:grid;place-items:center;padding:0 5px;color:#fff;background:var(--primary);border-radius:999px;font-size:10px}.catalog-filter-body{display:flex;flex-direction:column;gap:3px;padding:0 16px 14px}.catalog-filter-search input{width:100%;height:38px;margin-bottom:7px;padding:0 11px;color:#172033;background:var(--surface);border:1px solid #cbd5e1;border-radius:6px;font-size:12px;outline:0}.catalog-filter-search input:focus{border-color:var(--primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 12%,transparent)}
        .catalog-filter-option{min-height:36px;display:flex;align-items:center;gap:9px;padding:5px 6px;border-radius:6px;font-size:12px;cursor:pointer}.catalog-filter-option:hover{background:var(--surface)}.catalog-filter-option input{appearance:none;width:17px;height:17px;display:grid;place-items:center;flex:0 0 17px;margin:0;background:#fff;border:1.5px solid #cbd5e1;border-radius:50%;cursor:pointer}.catalog-filter-option input:checked{background:var(--primary);border-color:var(--primary);box-shadow:inset 0 0 0 4px #fff}.catalog-filter-option span{min-width:0;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.catalog-filter-option small{padding:2px 6px;color:#64748b;background:var(--surface);border-radius:999px;font-size:10px}.catalog-subcategories{margin:1px 0 5px 4px;padding-left:8px;border-left:2px solid var(--border)}.catalog-filter-option-sub{color:#475569}.catalog-filter-checkbox input{border-radius:4px}.catalog-filter-checkbox input:checked{box-shadow:none}.catalog-filter-checkbox input:checked:after{content:'✓';color:#fff;font-size:11px;font-weight:800}
        .catalog-price-inputs{display:grid;grid-template-columns:1fr 1fr;gap:8px}.catalog-price-inputs label{display:flex;flex-direction:column;gap:5px;color:#64748b;font-size:10px}.catalog-price-inputs input{width:100%;height:38px;padding:0 9px;color:#172033;background:var(--surface);border:1px solid #cbd5e1;border-radius:6px;font-size:12px;outline:0}.catalog-price-inputs input:focus{border-color:var(--primary)}.catalog-range{position:relative;height:4px;margin:18px 5px 10px;background:#e2e8f0;border-radius:999px}.catalog-range span{position:absolute;height:4px;background:var(--primary);border-radius:999px}.catalog-range input{position:absolute;top:0;width:100%;height:4px;margin:0;appearance:none;background:transparent;pointer-events:none}.catalog-range input::-webkit-slider-thumb{width:18px;height:18px;appearance:none;background:#fff;border:2px solid var(--primary);border-radius:50%;box-shadow:0 1px 5px rgba(15,23,42,.2);pointer-events:auto}.catalog-range input::-moz-range-thumb{width:18px;height:18px;background:#fff;border:2px solid var(--primary);border-radius:50%;pointer-events:auto}
        .catalog-results{min-width:0}.catalog-chips{min-height:32px;display:flex;flex-wrap:wrap;gap:7px;margin-bottom:12px}.catalog-chip{min-height:32px;display:inline-flex;align-items:center;gap:7px;padding:0 11px;color:var(--primary);background:color-mix(in srgb,var(--primary) 9%,white);border:1px solid color-mix(in srgb,var(--primary) 18%,white);border-radius:999px;font-size:11px;font-weight:600;cursor:pointer}.catalog-chip:hover{background:color-mix(in srgb,var(--primary) 15%,white)}.catalog-chip-neutral{color:#475569;background:var(--surface);border-color:var(--border)}.catalog-results-head{min-height:46px;display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:14px}.catalog-results-count{color:#64748b;font-size:12px}.catalog-results-count strong{color:#172033}.catalog-results-tools{display:flex;align-items:center;gap:8px}.catalog-sort{height:42px;min-width:205px;padding:0 36px 0 13px;color:#172033;background:#fff;border:1px solid #cbd5e1;border-radius:6px;font-size:12px;outline:0}.catalog-sort:focus{border-color:var(--primary)}.catalog-mobile-filter{display:none;min-height:44px;align-items:center;gap:8px;padding:0 14px;color:#fff;background:var(--secondary);border:0;border-radius:6px;font-size:12px;font-weight:700}.catalog-mobile-filter svg{width:17px}.catalog-mobile-filter span{min-width:18px;height:18px;display:grid;place-items:center;background:var(--primary);border-radius:999px;font-size:9px}
        .catalog-product-grid{display:grid;grid-template-columns:repeat({{ $catalogColumns }},minmax(0,1fr));gap:18px}.catalog-card{min-width:0;display:flex;flex-direction:column;overflow:hidden;background:#fff;border:1px solid var(--border);border-radius:12px;transition:border-color .18s ease,box-shadow .18s ease,transform .18s ease}.catalog-card:hover{transform:translateY(-2px);border-color:#cbd5e1;box-shadow:0 12px 30px rgba(15,23,42,.08)}.catalog-card-media{position:relative;aspect-ratio:1;display:grid;place-items:center;overflow:hidden;background:#fafaf9;border-bottom:1px solid #f1f5f9}.catalog-card-media img{width:100%;height:100%;padding:18px;object-fit:contain;transition:transform .25s ease}.catalog-card:hover .catalog-card-media img{transform:scale(1.035)}.catalog-card-placeholder{width:52px;color:#cbd5e1}.catalog-discount{position:absolute;top:12px;left:12px;z-index:1;padding:6px 9px;color:#fff;background:var(--primary);border-radius:6px;font-size:10px;font-weight:800}.catalog-sold-out{position:absolute;top:12px;right:12px;padding:6px 8px;color:#fff;background:#475569;border-radius:6px;font-size:9px;font-weight:700}.catalog-card-body{min-height:128px;display:flex;flex:1;flex-direction:column;padding:14px}.catalog-card-category{overflow:hidden;color:#7c899b;font-size:9px;font-weight:700;letter-spacing:.07em;text-overflow:ellipsis;text-transform:uppercase;white-space:nowrap}.catalog-card-name{min-height:40px;margin-top:7px;color:#172033;font-size:13px;font-weight:700;line-height:1.45}.catalog-card-name:hover{color:var(--primary)}.catalog-card-prices{display:flex;flex-wrap:wrap;align-items:baseline;gap:7px;margin-top:auto;padding-top:14px}.catalog-card-price{color:var(--secondary);font-size:17px;font-weight:800}.catalog-card-compare{color:#94a3b8;font-size:11px;text-decoration:line-through}.catalog-card-percent{padding:3px 6px;color:#fff;background:var(--primary);border-radius:4px;font-size:9px;font-weight:800}.catalog-card-wholesale{display:block;margin-top:5px;color:#64748b;font-size:9px}.catalog-card-action{min-height:44px;margin:0 10px 10px;color:#fff;background:var(--primary);border:0;border-radius:6px;font-size:11px;font-weight:800;cursor:pointer}.catalog-card-action:hover{filter:brightness(.92)}.catalog-card-action:disabled{color:#94a3b8;background:#e2e8f0;cursor:not-allowed}.catalog-empty{grid-column:1/-1;padding:64px 22px;text-align:center;background:#fff;border:1px dashed #cbd5e1;border-radius:12px}.catalog-empty svg{width:42px;margin:0 auto;color:#94a3b8}.catalog-empty h3{margin:15px 0 6px;color:#172033;font-size:18px}.catalog-empty p{margin:0 0 18px;color:#64748b;font-size:13px}
        .catalog-filter-layer{position:fixed;inset:0;z-index:95}.catalog-filter-overlay{position:absolute;inset:0;background:rgba(15,23,42,.56)}.catalog-filter-drawer{position:absolute;right:0;bottom:0;left:0;max-height:88vh;overflow-y:auto;background:#fff;border-radius:18px 18px 0 0;box-shadow:0 -18px 50px rgba(15,23,42,.2)}.catalog-filter-drawer-head{position:sticky;top:0;z-index:2;min-height:58px;display:flex;align-items:center;justify-content:space-between;padding:0 16px;background:#fff;border-bottom:1px solid var(--border)}.catalog-filter-drawer-head strong{font-size:16px}.catalog-filter-drawer-body{padding-bottom:76px}.catalog-filter-drawer-foot{position:sticky;right:0;bottom:0;left:0;display:grid;grid-template-columns:1fr 2fr;gap:8px;padding:12px 16px;background:#fff;border-top:1px solid var(--border)}.catalog-filter-drawer-foot button{min-height:46px;border-radius:7px;font-size:12px;font-weight:800;cursor:pointer}.catalog-filter-drawer-foot button:first-child{color:#475569;background:#fff;border:1px solid #cbd5e1}.catalog-filter-drawer-foot button:last-child{color:#fff;background:var(--primary);border:1px solid var(--primary)}
        .native-footer{padding:26px 0;color:#94a3b8;background:var(--secondary);font-size:12px;text-align:center}.drawer-layer{position:fixed;inset:0;z-index:80}.drawer-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.52)}.cart-drawer{position:absolute;top:0;right:0;width:min(430px,100%);height:100%;display:flex;flex-direction:column;background:#fff;box-shadow:-20px 0 50px rgba(15,23,42,.18)}.drawer-head{min-height:72px;display:flex;align-items:center;justify-content:space-between;padding:0 24px;border-bottom:1px solid var(--border)}.drawer-head h2{margin:0;color:var(--secondary);font-size:18px}.icon-button{width:44px;height:44px;display:grid;place-items:center;color:#475569;background:#fff;border:1px solid var(--border);border-radius:var(--radius);cursor:pointer}.icon-button svg{width:18px}.drawer-content{flex:1;overflow:auto;padding:22px 24px}.cart-empty{margin:80px 0 0;color:#64748b;font-size:14px;text-align:center}.cart-item{display:grid;grid-template-columns:1fr auto auto;align-items:center;gap:12px;padding:16px 0;border-bottom:1px solid var(--border)}.cart-item strong{display:block;color:var(--secondary);font-size:13px;line-height:1.45}.cart-item small{display:block;margin-top:4px;color:#64748b}.quantity{display:flex;align-items:center;border:1px solid #cbd5e1;border-radius:6px}.quantity button{width:40px;height:40px;color:#475569;background:#fff;border:0;cursor:pointer}.quantity span{width:28px;font-size:12px;text-align:center}.remove{width:44px;height:44px;display:grid;place-items:center;padding:0;color:#94a3b8;background:transparent;border:0;cursor:pointer}.remove svg{width:17px}.drawer-footer{padding:20px 24px 24px;border-top:1px solid var(--border)}.cart-total{display:flex;justify-content:space-between;margin-bottom:16px;color:var(--secondary);font-size:16px;font-weight:700}.drawer-footer .button{width:100%}.drawer-note{margin:11px 0 0;color:#64748b;font-size:10px;line-height:1.5;text-align:center}
        a:focus-visible,button:focus-visible,input:focus-visible,select:focus-visible{outline:3px solid color-mix(in srgb,var(--primary) 45%,white);outline-offset:2px}.product-action{min-height:44px}.category-list a{min-height:44px;display:inline-flex;align-items:center}
        .sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
        @media(max-width:900px){.header-main{grid-template-columns:1fr auto;gap:18px}.search{grid-column:1/-1;grid-row:2;padding-bottom:14px}.phone-copy{display:none}.hero-inner{min-height:0;grid-template-columns:1fr;gap:40px;padding:54px 0}.hero-visual{min-height:0}.hero-frame{max-width:620px;aspect-ratio:16/10}.trust-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.product-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
        @media(max-width:640px){.container{width:calc(100% - 28px)}#bixo-runtime-announcement{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.topbar-inner{min-height:32px;justify-content:center}.contact-links{display:none}.topbar p{max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.header-main{min-height:66px;gap:12px}.brand-logo{width:40px;height:40px;flex-basis:40px}.brand-mark{width:38px;height:38px;flex-basis:38px}.brand-tagline{display:none}.brand-name{max-width:190px;font-size:15px}.category-list{min-height:44px}.hero-inner{width:calc(100% - 28px);gap:32px;padding:42px 0}.hero-copy,.hero-visual{width:100%;max-width:calc(100vw - 28px)}.hero-copy h1{width:100%;max-width:calc(100vw - 28px);white-space:normal!important;font-size:30px;line-height:1.14}.hero-copy>p{width:100%;max-width:calc(100vw - 28px);white-space:normal!important;margin-top:18px;font-size:15px;line-height:1.65}.hero-actions{width:100%;max-width:calc(100vw - 28px);margin-top:25px}.hero-actions .button{width:100%;max-width:100%}.hero-frame{width:100%;max-width:calc(100vw - 28px);aspect-ratio:auto;min-height:340px}.hero-frame>img{padding:18px}.hero-fallback{padding:24px}.hero-metrics{margin-top:22px}.trust-section{padding:44px 0}.trust-section-head{margin-bottom:20px}.trust-grid{grid-template-columns:repeat({{ $trustSectionMobileColumns }},minmax(0,1fr));gap:11px}.trust-card{padding:17px}.trust-section.mobile-carousel .trust-grid{display:flex;overflow-x:auto;scroll-snap-type:x mandatory;padding:2px 2px 14px;scrollbar-width:none}.trust-section.mobile-carousel .trust-grid::-webkit-scrollbar{display:none}.trust-section.mobile-carousel .trust-card{flex:0 0 84%;scroll-snap-align:start}.catalog{padding:52px 0 70px}.section-heading{align-items:flex-start;flex-direction:column;gap:8px}.catalog-toolbar{grid-template-columns:1fr;padding:12px}.category-section{margin-top:44px}.product-grid{grid-template-columns:repeat(var(--mobile-columns),minmax(0,1fr));gap:10px}.product-body{min-height:174px;padding:12px}.product-image img{padding:14px}.product-name{font-size:12px}.price{font-size:16px}.product-action{min-height:44px;padding:0 5px}.quantity button{width:44px;height:44px}}
        @media(max-width:1100px){.catalog-product-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.catalog-experience{grid-template-columns:250px minmax(0,1fr);gap:20px}}
        @media(max-width:900px){.catalog-experience{grid-template-columns:1fr}.catalog-filter-panel{display:none}.catalog-mobile-filter{display:inline-flex}.catalog-results-head{align-items:flex-start}.catalog-results-tools{flex-wrap:wrap;justify-content:flex-end}}
        @media(max-width:640px){.catalog-experience{margin-top:20px}.catalog-breadcrumb{margin-bottom:7px}.catalog-chips{flex-wrap:nowrap;overflow-x:auto;padding-bottom:3px;scrollbar-width:none}.catalog-chip{flex:0 0 auto}.catalog-results-head{align-items:stretch;flex-direction:column}.catalog-results-tools{display:grid;grid-template-columns:auto 1fr;width:100%}.catalog-sort{width:100%;min-width:0}.catalog-product-grid{grid-template-columns:repeat(var(--mobile-columns),minmax(0,1fr));gap:10px}.catalog-card-body{min-height:120px;padding:11px}.catalog-card-media img{padding:12px}.catalog-card-name{min-height:36px;font-size:12px}.catalog-card-price{font-size:15px}.catalog-card-percent{display:none}.catalog-card-action{margin:0 7px 7px;padding:0 5px;font-size:10px}.catalog-filter-option{min-height:44px}.catalog-filter-summary{min-height:52px}}
        @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}*,*:before,*:after{transition-duration:.01ms!important;animation-duration:.01ms!important;animation-iteration-count:1!important}.catalog-card:hover{transform:none}}

        .official-whatsapp-float{position:fixed;overflow:hidden;right:22px;bottom:22px;z-index:60;width:58px;height:58px;display:grid;place-items:center;color:#fff;background:#25D366;border:2px solid rgba(255,255,255,.9);border-radius:50%;box-shadow:0 10px 28px rgba(37,211,102,.38);transition:transform .18s ease,box-shadow .18s ease}
        .official-whatsapp-float.wa-pos-left{left:22px;right:auto}
        .official-whatsapp-float.wa-pos-right{right:22px;left:auto}
        @media(max-width:560px){.official-whatsapp-float.wa-pos-left{left:16px;right:auto}.official-whatsapp-float.wa-pos-right{right:16px;left:auto}}
        .official-whatsapp-float:hover{transform:translateY(-3px) scale(1.04);box-shadow:0 15px 34px rgba(37,211,102,.48)}
        .official-whatsapp-float:focus-visible{outline:3px solid rgba(37,211,102,.35);outline-offset:4px}
        @media(max-width:560px){.official-whatsapp-float{right:16px;bottom:16px;width:54px;height:54px}.official-whatsapp-float svg{width:29px;height:29px}}
    
        /* ═══════════════════════════════════════════════════════
           AJUSTE VISUAL FINAL — estilo más serio y corporativo
           ═══════════════════════════════════════════════════════ */
        .topbar{background:linear-gradient(90deg,#0f172a,#162033)!important;color:#fff!important;font-size:12px!important;letter-spacing:.02em}
        .header{background:rgba(255,255,255,.96)!important;backdrop-filter:saturate(180%) blur(10px)!important;border-bottom:1px solid #e5e7eb!important}
        .header-main{grid-template-columns:320px minmax(360px,1fr) auto!important;gap:32px!important;padding:26px 0!important}
        .brand{gap:16px!important}
        .brand-logo{height:64px!important}
        .brand-copy strong{font-size:15px!important;letter-spacing:-.01em!important;color:#0f172a!important}
        .brand-copy span,.brand-copy small{color:#64748b!important}
        .searchbox input{height:58px!important;border:1px solid #d7dfea!important;border-radius:18px!important;padding:0 56px 0 20px!important;font-size:15px!important;background:#fff!important;box-shadow:0 8px 20px rgba(15,23,42,.05)!important}
        .searchbox button{right:8px!important;top:8px!important;background:#f8fafc!important;color:#475569!important;border-radius:12px!important}
        .searchbox button:hover{background:#eef2f7!important;color:#0f172a!important}
        .cart-btn{width:76px!important;height:56px!important;border:1px solid #d7dfea!important;border-radius:18px!important;box-shadow:0 8px 20px rgba(15,23,42,.05)!important}
        .menu{gap:8px!important}
        .menu a{border-radius:14px!important;font-weight:700!important;transition:.18s ease!important}
        .menu a.active,.menu a:hover{background:#0f172a!important;color:#fff!important;box-shadow:0 10px 22px rgba(15,23,42,.14)!important}
        .category-toggle{padding:0 22px!important;border-radius:16px!important;background:linear-gradient(135deg,#2563eb,#3b82f6)!important;font-weight:800!important;box-shadow:0 14px 28px rgba(37,99,235,.24)!important}
        .hero-overlay{background:linear-gradient(90deg,rgba(15,23,42,.78),rgba(15,23,42,.34) 42%,rgba(15,23,42,.10))!important}
        .premium-hero-copy{padding:78px 0!important}
        .ph-title{letter-spacing:-.045em!important}
        .ph-sub{font-size:17px!important;line-height:1.68!important;color:rgba(255,255,255,.90)!important}
        .button-primary{background:linear-gradient(135deg,#2563eb,#3b82f6)!important;color:#fff!important;box-shadow:0 12px 28px rgba(37,99,235,.26)!important}
        .home-cats,.trust-section,.pf-section,.promo-section,.catalog,.store-page{padding-block:72px!important}
        .section-heading h2,.home-section-head h2,.trust-section-head h2,.pf-title{font-size:clamp(30px,3.4vw,46px)!important;letter-spacing:-.035em!important;color:#0f172a!important}
        .section-heading p,.home-section-copy p,.trust-section-head p,.pf-desc{color:#64748b!important;line-height:1.7!important}
        .home-cat-card,.trust-card,.pf-card,.catalog-card,.promo-slide{background:#fff!important;border:1px solid #e2e8f0!important;border-radius:20px!important;box-shadow:0 8px 20px rgba(15,23,42,.05)!important}
        .home-cat-card:hover,.trust-card:hover,.pf-card:hover,.catalog-card:hover,.promo-slide:hover{box-shadow:0 14px 32px rgba(15,23,42,.08)!important}
        .home-cat-icon,.trust-icon{background:linear-gradient(180deg,#f8fbff,#eef4ff)!important;border:1px solid #dbeafe!important}
        .home-cat-name,.trust-card-title,.pf-name,.catalog-card-name{color:#0f172a!important}
        .promo-slide{overflow:hidden!important}
        .promo-slide::after{content:"";position:absolute;inset:0;border-radius:inherit;box-shadow:inset 0 1px 0 rgba(255,255,255,.2);pointer-events:none}
        .promo-slide-content{position:relative;z-index:2;padding:34px!important}
        .promo-slide-eyebrow{font-size:11px!important;font-weight:800!important;letter-spacing:.12em!important;text-transform:uppercase!important;opacity:.92}
        .promo-slide-title{font-size:clamp(28px,3.2vw,42px)!important;line-height:1.03!important;font-weight:900!important;letter-spacing:-.04em!important}
        .promo-slide-desc{font-size:15px!important;line-height:1.7!important;opacity:.96!important;max-width:540px}
        .promo-slide .button{min-height:48px!important;border-radius:14px!important}
        .catalog-toolbar{display:flex!important;align-items:center!important;justify-content:space-between!important;gap:16px!important;padding:18px 20px!important;border:1px solid #e2e8f0!important;border-radius:18px!important;background:#fff!important;box-shadow:0 8px 20px rgba(15,23,42,.05)!important}
        .catalog-toolbar select,.catalog-toolbar input{min-height:44px!important;border:1px solid #d7dfeb!important;border-radius:12px!important;background:#fff!important;padding:0 14px!important}
        .footer{background:linear-gradient(180deg,#0f172a,#101826)!important;color:#e2e8f0!important}
        .footer a{color:#cbd5e1!important}
        .footer a:hover{color:#fff!important}
        .footer-logo img{filter:drop-shadow(0 4px 10px rgba(0,0,0,.18))}
        .official-whatsapp-float{position:fixed!important;overflow:hidden!important;bottom:24px!important;z-index:99999!important;width:64px!important;height:64px!important;display:flex!important;align-items:center!important;justify-content:center!important;border-radius:999px!important;background:#25D366!important;box-shadow:0 18px 35px rgba(37,211,102,.36)!important;visibility:visible!important;opacity:1!important}
        .official-whatsapp-float.wa-pos-left{left:22px!important;right:auto!important}
        .official-whatsapp-float.wa-pos-right{right:22px!important;left:auto!important}
        .official-whatsapp-float:hover{transform:translateY(-2px) scale(1.02)!important;box-shadow:0 20px 38px rgba(37,211,102,.42)!important}
        .official-whatsapp-float svg{display:block!important;width:31px!important;height:31px!important}
        .official-whatsapp-float::before{content:'';position:absolute;inset:0;border-radius:inherit;box-shadow:inset 0 0 0 2px rgba(255,255,255,.35)}
        .official-whatsapp-float::after{content:'';position:absolute;inset:-8px;border-radius:inherit;border:1px solid rgba(37,211,102,.22);animation:waPulse 2.4s ease-out infinite}
        @keyframes waPulse{0%{transform:scale(.92);opacity:.55}70%{transform:scale(1.18);opacity:0}100%{transform:scale(1.18);opacity:0}}
        @media(max-width:768px){
          .header-main{grid-template-columns:1fr!important;gap:18px!important;padding:18px 0!important}
          .searchbox input{height:52px!important}
          .actions{justify-content:space-between!important}
          .nav-inner{flex-direction:column!important;align-items:stretch!important}
          .menu{width:100%!important;overflow:auto!important;flex-wrap:nowrap!important;padding-bottom:4px!important}
          .menu a{white-space:nowrap!important}
          .home-cats,.trust-section,.pf-section,.promo-section,.catalog,.store-page{padding-block:54px!important}
          .promo-slide-content{padding:22px!important}
          .promo-slide-title{font-size:28px!important}
          .official-whatsapp-float{bottom:18px!important;width:58px!important;height:58px!important}
          .official-whatsapp-float.wa-pos-left{left:16px!important}
          .official-whatsapp-float.wa-pos-right{right:16px!important}
        }

    
        /* ═══════════════════════════════════════════════════════
           ALINEACIÓN DE CABECERA Y MENÚ
           ═══════════════════════════════════════════════════════ */
        :root{--header-layout-width:1360px}
        .topbar>.container.topbar-inner,
        .store-header>.container.header-main,
        .category-nav>.container.category-bar{
          width:min(var(--header-layout-width),calc(100% - 48px))!important;
          max-width:none!important;
          margin-inline:auto!important;
          padding-inline:0!important;
        }
        .store-header>.container.header-main{
          min-height:var(--header-h)!important;
        }
        .category-nav>.container.category-bar{
          min-height:72px!important;
        }
        .brand--logo-only{
          gap:0!important;
          justify-content:flex-start!important;
        }
        .brand--logo-only .brand-logo{
          width:auto!important;
          height:var(--logo-h)!important;
          max-width:230px!important;
          object-fit:contain!important;
        }
        .brand--text{
          gap:13px!important;
        }
        .brand--text .brand-copy{
          display:block!important;
        }
        .category-bar .mega-trigger{
          margin-left:0!important;
        }
        @media(max-width:768px){
          .topbar>.container.topbar-inner,
          .store-header>.container.header-main,
          .category-nav>.container.category-bar{
            width:min(100% - 28px,var(--header-layout-width))!important;
          }
          .brand--logo-only .brand-logo{
            max-width:190px!important;
          }
          .category-nav>.container.category-bar{
            min-height:60px!important;
          }
        }

    
        /* Formas individuales de categorías destacadas */
        .home-cat-card.item-shape-square{border-radius:0!important}
        .home-cat-card.item-shape-square .home-cat-icon,.home-cat-card.item-shape-square .home-cat-media,.home-cat-card.item-shape-square .home-cat-media img{border-radius:0!important}
        .home-cat-card.item-shape-rounded{border-radius:{{ $featuredCatsRadius }}px!important}
        .home-cat-card.item-shape-circle{overflow:visible!important;border:0!important;border-radius:0!important;background:transparent!important;box-shadow:none!important;text-align:center!important}
        .home-cat-card.item-shape-circle:hover{box-shadow:none!important}
        .home-cat-card.item-shape-circle .home-cat-media{width:156px!important;height:156px!important;margin-inline:auto!important;border:1px solid color-mix(in srgb,{{ $featuredCatsAccent }} 18%,#dbe3ef)!important;border-radius:50%!important;box-shadow:0 12px 30px rgba(15,23,42,.08)!important}
        .home-cat-card.item-shape-circle .home-cat-media img,.home-cat-card.item-shape-circle .home-cat-icon{border-radius:50%!important}
        .home-cat-card.item-shape-circle .home-cat-content{display:block!important;padding:14px 8px 0!important}
        .home-cat-card.item-shape-circle .home-cat-arrow{display:none!important}
        @media(max-width:640px){.home-cat-card.item-shape-circle .home-cat-media{width:112px!important;height:112px!important}}

    
        .flash-sale-section{background:#f8fafc}
        .flash-campaign{position:relative;min-height:330px;display:flex;align-items:center;overflow:hidden;padding:42px;color:#fff;border-radius:24px;background-position:center;background-size:cover;box-shadow:0 24px 60px rgba(15,23,42,.16)}
        .flash-campaign-copy{position:relative;z-index:2;max-width:650px}
        .flash-campaign h2{margin:12px 0;color:#fff!important;font-size:clamp(34px,5vw,58px);line-height:1.02;letter-spacing:-.045em}
        .flash-campaign p{max-width:580px;margin:0 0 24px;color:rgba(255,255,255,.86);font-size:16px;line-height:1.65}
        .flash-countdown{display:flex;flex-wrap:wrap;gap:10px;margin:0 0 24px}.flash-countdown>div{min-width:74px;padding:12px 10px;border:1px solid rgba(255,255,255,.20);border-radius:14px;background:rgba(255,255,255,.10);text-align:center;backdrop-filter:blur(10px)}
        .flash-countdown strong{display:block;font-size:22px;line-height:1}.flash-countdown span{display:block;margin-top:5px;color:rgba(255,255,255,.72);font-size:9px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.flash-product-grid{margin-top:24px}
        @media(max-width:640px){.flash-campaign{min-height:360px;padding:28px 22px;border-radius:18px}.flash-countdown>div{min-width:62px}}

    </style>
</head>
<body x-data="professionalStore()" x-init="init()"
      class="section-preset-{{ $sectionPreset }} section-spacing-{{ $sectionSpacing }} section-heading-{{ $sectionHeadingAlign }} section-bg-{{ $sectionBackgroundMode }} {{ $sectionShowDividers ? 'section-dividers' : 'section-no-dividers' }} {{ $sectionCardShadow ? 'section-card-shadows' : 'section-no-shadows' }} featured-view-{{ $featuredProductsView }} catalog-view-{{ $catalogProductsView }}">
    <div class="topbar" style="background:{{ $announcementBg }}"><div class="container topbar-inner"><p>{{ $announcementText ?: 'Atención especializada para tu compra' }}</p><div class="contact-links">@if($phone)<a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a>@endif @if($email)<a href="mailto:{{ $email }}">{{ $email }}</a>@endif</div></div></div>

    <header class="store-header">
        <div class="container header-main">
            <a class="brand {{ $logoUrl ? 'brand--logo-only' : 'brand--text' }}"
               href="{{ route('public.catalog', $project->slug) }}"
               aria-label="Inicio de {{ $storeName }}">
                @if($logoUrl)
                    <img class="brand-logo" src="{{ $logoUrl }}" alt="Logo de {{ $storeName }}">
                @else
                    <span class="brand-mark">{{ mb_strtoupper(mb_substr($storeName, 0, 1)) }}</span>
                    <span class="brand-copy">
                        <span class="brand-name">{{ $storeName }}</span>
                        @if(trim($tagline) !== '')
                            <span class="brand-tagline">{{ Str::limit($tagline, 62) }}</span>
                        @endif
                    </span>
                @endif
            </a>
            <label class="search"><span class="sr-only">Buscar en el catálogo</span><input type="search" x-model.debounce.200ms="query" placeholder="{{ $txtSearchPlaceholder }}" autocomplete="off"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg></label>
            <div class="header-actions">
                @if($phone)<span class="phone-copy"><small>Atención comercial</small><strong>{{ $phone }}</strong></span>@endif
                <button class="cart-trigger" type="button" @click="cartOpen=true" aria-label="Abrir carrito"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 3h2l2.1 11.2a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 2-1.6L21 7H6"></path><circle cx="10" cy="20" r="1"></circle><circle cx="18" cy="20" r="1"></circle></svg><span class="cart-count" x-text="itemCount()" aria-live="polite">0</span></button>
            </div>
        </div>
    </header>

    @php
        // Menú del Constructor (si existe); si no, cae a las categorías
        $menuRoots = ($storeMenu ?? null)?->rootItems?->where('is_enabled', true) ?? collect();
        // ¿Qué item del menú corresponde a la página actual? (para marcarlo activo)
        $activeDest = match($storeView ?? 'home') {
            'tienda'   => 'shop',
            'nosotros' => 'about',
            'contacto' => 'contact',
            default    => 'home',
        };
    @endphp
    @php
        // URL base de la tienda (para enlazar categorías/subcategorías)
        $shopBase = \App\Support\StorefrontNavigation::resolveUrl($project, new \App\Models\StoreMenuItem(['destination_type' => 'shop']));
        $rootCats = $categories->filter(fn($c) => $c->children->count() > 0 || true);
    @endphp
    <nav class="category-nav" aria-label="Navegación" x-data="{ mega: false, megaCat: {{ $categories->first()->id ?? 'null' }} }" @mouseleave="mega=false">
        <div class="container category-bar menu-{{ $menuAlign }}">
            {{-- Botón mega-menú de categorías --}}
            @if($categories->count())
            <div class="mega-trigger" @mouseenter="mega=true" @click="mega=!mega">
                <button type="button" class="mega-btn">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    Todas las categorías
                    <svg class="mega-caret" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" :style="mega && 'transform:rotate(180deg)'"><path d="M6 9l6 6 6-6"></path></svg>
                </button>
            </div>
            @endif

            {{-- Menú del Constructor (Inicio, Productos, Nosotros, Contacto) --}}
            <div class="category-list">
            @if($menuRoots->count())
                @foreach($menuRoots as $mItem)
                    @php $isActive = in_array($mItem->destination_type, [$activeDest], true) || ($activeDest === 'shop' && in_array($mItem->destination_type, ['shop','products'], true)); @endphp
                    @if(in_array($mItem->destination_type, ['category','subcategory']) && $mItem->destination_id)
                        {{-- Las categorías del menú siempre abren la vista Tienda con el filtro aplicado.
                             Así no se muestran el hero, beneficios ni otras secciones de Inicio. --}}
                        <a href="{{ $shopBase }}?category={{ (int) $mItem->destination_id }}"
                           class="{{ request('category') == $mItem->destination_id ? 'is-active' : '' }}">
                            {{ $mItem->label }}
                        </a>
                    @else
                        <a href="{{ \App\Support\StorefrontNavigation::resolveUrl($project, $mItem) }}"
                           class="{{ $isActive ? 'is-active' : '' }}"
                           @if($mItem->target === '_blank') target="_blank" rel="noopener" @endif>{{ $mItem->label }}</a>
                    @endif
                @endforeach
            @else
                <a href="{{ $shopBase }}">Todos los productos</a>
                @foreach($categories as $category)<a href="{{ $shopBase }}?category={{ $category->id }}">{{ $category->name }}</a>@endforeach
            @endif
            </div>
        </div>

        {{-- Panel desplegable del mega-menú --}}
        @if($categories->count())
        <div class="mega-panel" x-show="mega" x-cloak x-transition.opacity @mouseenter="mega=true">
            <div class="container mega-grid">
                {{-- Columna izquierda: categorías raíz --}}
                <div class="mega-cats">
                    @foreach($categories as $cat)
                    <a href="{{ $shopBase }}?category={{ $cat->id }}"
                       class="mega-cat-item" :class="megaCat==={{ $cat->id }} && 'is-active'"
                       @mouseenter="megaCat={{ $cat->id }}">
                        <span>{{ $cat->name }}</span>
                        @if($cat->children->count())<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"></path></svg>@endif
                    </a>
                    @endforeach
                </div>
                {{-- Columna derecha: subcategorías de la categoría activa --}}
                <div class="mega-subs">
                    @foreach($categories as $cat)
                    <div x-show="megaCat==={{ $cat->id }}" x-cloak class="mega-sub-panel">
                        <a href="{{ $shopBase }}?category={{ $cat->id }}" class="mega-sub-title">{{ $cat->name }} <small>Ver todo →</small></a>
                        @if($cat->children->count())
                        <div class="mega-sub-grid">
                            @foreach($cat->children as $sub)
                            <a href="{{ $shopBase }}?category={{ $sub->id }}" class="mega-sub-link">{{ $sub->name }}</a>
                            @endforeach
                        </div>
                        @else
                        <p class="mega-sub-empty">Explora todos los productos de {{ $cat->name }}.</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </nav>

    <main id="storefront-main" style="display:flex;flex-direction:column">
        @php $storeView = $storeView ?? 'home'; @endphp

        {{-- Hero + slider + beneficios: solo en INICIO --}}
        @if($storeView === 'home')
        @php
            // Composición de productos reales para el hero (guiado por lo cargado):
            // busca por nombre productos típicos; si no, usa los destacados / primeros.
            $pool = $allProducts->filter(fn($p) => filled($p->main_image_url))->values();
            $pickBy = function(array $words) use ($pool) {
                foreach ($pool as $p) {
                    $n = mb_strtolower($p->name);
                    foreach ($words as $w) if (str_contains($n, $w)) return $p;
                }
                return null;
            };
            $heroLaptop = $pickBy(['laptop','macbook','notebook','portátil']) ?: $pool->get(0);
            $heroHead   = $pickBy(['audífono','audifono','headset','auricular','cloud']) ?: $pool->get(1);
            $heroMouse  = $pickBy(['mouse']) ?: $pool->get(2);
            $heroKb     = $pickBy(['teclado','keyboard','mecánico','mecanico']) ?: $pool->get(3);
            $heroMain   = $heroImage ?: $heroLaptop?->main_image_url;
        @endphp
        @if($isHomeSectionVisible('hero') && count($heroSlides))
        {{-- Slider profesional configurable por diapositiva. --}}
        <section class="premium-hero has-bg" id="storefront-hero" data-store-native-section="hero"
                 style="--hero-desktop-h:{{ $heroMinH }}px;--hero-mobile-h:{{ $heroMobileHeight }}px;order:{{ $sectionOrder('hero') }}"
                 x-data="{ s:0, n:{{ count($heroSlides) }}, dur:{{ $heroDuration }}, autoplay:{{ $heroAutoplay ? 'true' : 'false' }}, pauseHover:{{ $heroPauseHover ? 'true' : 'false' }}, timer:null,
                    go(i){ this.s=(i+this.n)%this.n; this.restart(); }, next(){ this.go(this.s+1); }, prev(){ this.go(this.s-1); },
                    stop(){ clearInterval(this.timer); this.timer=null; }, restart(){ this.stop(); if(!this.autoplay || this.n<2) return; this.timer=setInterval(()=>this.next(),this.dur); }
                 }"
                 x-init="restart()" @mouseenter="if(pauseHover) stop()" @mouseleave="if(pauseHover) restart()"
                 @keydown.right.prevent="next()" @keydown.left.prevent="prev()" tabindex="0">
            @foreach($heroSlides as $i => $sl)
            <div class="ph-slide" x-show="s==={{ $i }}"
                 @if($heroTransition==='fade') x-transition.opacity.duration.700ms @else x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-x-8" x-transition:enter-end="opacity-100 translate-x-0" @endif
                 @if($i>0) style="display:none" @endif>
                <picture class="ph-picture">
                    @if($sl['mobileImg'])<source media="(max-width: 767px)" srcset="{{ $sl['mobileImg'] }}">@endif
                    <img class="pos-{{ $sl['position'] }}" src="{{ $sl['img'] }}" alt="{{ $sl['title'] ?: $storeName }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" fetchpriority="{{ $i === 0 ? 'high' : 'auto' }}">
                </picture>
                @if($sl['showContent'])<span class="ph-shade" style="opacity:{{ $sl['overlay'] / 100 }}" aria-hidden="true"></span>@endif
            </div>
            @endforeach
            <div class="container premium-hero-inner">
                @foreach($heroSlides as $i => $sl)
                <div class="premium-hero-copy is-{{ $sl['align'] }}" x-show="s==={{ $i }}" x-transition.opacity.duration.450ms @if($i>0) style="display:none" @endif>
                    @if($sl['showContent'])
                    <div class="ph-slide-copy">
                        @if($sl['badge'])<span class="ph-eyebrow">{{ $sl['badge'] }}</span>@endif
                        @if($sl['title'] ?: $heroTitle)<h1 class="ph-title">{{ $sl['title'] ?: $heroTitle }}</h1>@endif
                        @if($sl['sub'] ?: $heroSubtitle)<p class="ph-sub">{{ $sl['sub'] ?: $heroSubtitle }}</p>@endif
                        @if(($sl['cta1Show'] && $sl['cta1Text']) || ($sl['cta2Show'] && $sl['cta2Text'] && $sl['cta2Url']))
                        <div class="hero-actions">
                            @if($sl['cta1Show'] && $sl['cta1Text'])<a class="button button-primary" href="{{ $sl['cta1Url'] ?: '#catalogo' }}">{{ $sl['cta1Text'] }}<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14M14 6l6 6-6 6"></path></svg></a>@endif
                            @if($sl['cta2Show'] && $sl['cta2Text'] && $sl['cta2Url'])<a class="button button-ghost" href="{{ $sl['cta2Url'] }}" @if(str_starts_with($sl['cta2Url'],'http')) target="_blank" rel="noopener" @endif>{{ $sl['cta2Text'] }}</a>@endif
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @if(count($heroSlides) > 1 && $heroShowArrows)
            <button type="button" class="ph-arrow ph-arrow-prev" @click="prev()" aria-label="Imagen anterior"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 6l-6 6 6 6"></path></svg></button>
            <button type="button" class="ph-arrow ph-arrow-next" @click="next()" aria-label="Imagen siguiente"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 6l6 6-6 6"></path></svg></button>
            @endif
            @if(count($heroSlides) > 1 && $heroShowDots)
            <div class="ph-dots" role="tablist" aria-label="Imágenes del slider">
                @foreach($heroSlides as $i => $sl)<button type="button" class="ph-dot" :class="s==={{ $i }}?'is-active':''" @click="go({{ $i }})" aria-label="Ir a imagen {{ $i+1 }}"></button>@endforeach
            </div>
            @endif
        </section>
        @elseif($isHomeSectionVisible('hero'))
        {{-- Sin imágenes cargadas: hero con composición de productos (fallback) --}}
        <section class="premium-hero" id="storefront-hero" data-store-native-section="hero" style="order:{{ $sectionOrder('hero') }}">
            <div class="premium-hero-bg" aria-hidden="true"><span class="ph-glow ph-glow-1"></span><span class="ph-glow ph-glow-2"></span><span class="ph-grid"></span><span class="ph-line ph-line-1"></span><span class="ph-line ph-line-2"></span></div>
            <div class="container premium-hero-inner">
                @if($heroShowContent)
                <div class="premium-hero-copy">
                    <span class="ph-eyebrow">{{ $heroBadge ?: 'ENVÍO A TODO EL PERÚ' }}</span>
                    <h1 class="ph-title">{{ $heroTitle }}</h1>
                    <p class="ph-sub">{{ $heroSubtitle }}</p>
                    <div class="hero-actions">
                        @if($heroCtaVisible)<a class="button button-primary" href="#catalogo">{{ $heroCta }}<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14M14 6l6 6-6 6"></path></svg></a>@endif
                        @if($contactCtaVisible && $whatsapp)<a class="button button-ghost" href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener">{{ $contactCta }}</a>@endif
                    </div>
                </div>
                @endif
                <div class="premium-hero-visual" aria-hidden="true">
                    @if($heroMain)
                    <div class="ph-stage">
                        <div class="ph-main"><img src="{{ $heroMain }}" alt="" loading="eager"></div>
                        @if($heroHead)<div class="ph-float ph-float-a"><img src="{{ $heroHead->main_image_url }}" alt=""></div>@endif
                        @if($heroKb)<div class="ph-float ph-float-b"><img src="{{ $heroKb->main_image_url }}" alt=""></div>@endif
                        @if($heroMouse)<div class="ph-float ph-float-c"><img src="{{ $heroMouse->main_image_url }}" alt=""></div>@endif
                        <div class="ph-reflection"></div>
                    </div>
                    @endif
                </div>
            </div>
        </section>
        @endif

        @if($isHomeSectionVisible('promotions') && $promoEnabled && count($promoItems))
        <section class="promo-section" aria-label="Promociones" data-store-native-section="announcements" style="order:{{ $sectionOrder('promotions') }}"
                 x-data="{ active:0, total:{{ count($promoItems) }}, timer:null,
                           start(){ if({{ $promoAutoplay ? 'true' : 'false' }} && this.total>1){ this.timer=setInterval(()=>this.active=(this.active+1)%this.total,{{ $promoDuration }}); } },
                           stop(){ if(this.timer){clearInterval(this.timer);this.timer=null;} } }"
                 x-init="start()" @mouseenter="stop()" @mouseleave="start()">
            <div class="container">
                @if($promoSectionTitle !== '' || $promoSectionSubtitle !== '')
                <div class="promo-section-head">
                    <div>
                        @if($promoSectionTitle !== '')<h2>{{ $promoSectionTitle }}</h2>@endif
                        @if($promoSectionSubtitle !== '')<p>{{ $promoSectionSubtitle }}</p>@endif
                    </div>
                </div>
                @endif

                @if($promoStyle === 'grid')
                <div class="promo-grid">
                    @foreach($promoItems as $promo)
                    <article class="promo-slide">
                        @if($promo['image'])
                        <picture>
                            @if($promo['mobileImage'])<source media="(max-width:640px)" srcset="{{ $promo['mobileImage'] }}">@endif
                            <img src="{{ $promo['image'] }}" alt="{{ $promo['title'] }}" loading="lazy">
                        </picture>
                        @endif
                        <div class="promo-copy align-{{ $promo['align'] }}">
                            <small class="promo-kicker">Promoción</small>
                            @if($promo['title'])<strong>{{ $promo['title'] }}</strong>@endif
                            @if($promo['subtitle'])<span>{{ $promo['subtitle'] }}</span>@endif
                            @if($promo['ctaText'] && $promo['ctaUrl'])
                            <a class="promo-cta" href="{{ $promo['ctaUrl'] }}">
                                {{ $promo['ctaText'] }}
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M14 6l6 6-6 6"></path></svg>
                            </a>
                            @endif
                        </div>
                    </article>
                    @endforeach
                </div>
                @else
                <div class="promo-slider-shell">
                    <div class="promo-track">
                        @foreach($promoItems as $i => $promo)
                        <article class="promo-slide" x-show="active==={{ $i }}" x-transition.opacity.duration.500ms @if($i>0)style="display:none"@endif>
                            @if($promo['image'])
                            <picture>
                                @if($promo['mobileImage'])<source media="(max-width:640px)" srcset="{{ $promo['mobileImage'] }}">@endif
                                <img src="{{ $promo['image'] }}" alt="{{ $promo['title'] }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}">
                            </picture>
                            @endif
                            <div class="promo-copy align-{{ $promo['align'] }}">
                                <small class="promo-kicker">Promoción</small>
                                @if($promo['title'])<strong>{{ $promo['title'] }}</strong>@endif
                                @if($promo['subtitle'])<span>{{ $promo['subtitle'] }}</span>@endif
                                @if($promo['ctaText'] && $promo['ctaUrl'])
                                <a class="promo-cta" href="{{ $promo['ctaUrl'] }}">
                                    {{ $promo['ctaText'] }}
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M14 6l6 6-6 6"></path></svg>
                                </a>
                                @endif
                            </div>
                        </article>
                        @endforeach
                    </div>
                    @if($promoShowDots && count($promoItems) > 1)
                    <div class="promo-dots">
                        @foreach($promoItems as $i => $promo)
                        <button type="button" :class="active==={{ $i }} && 'is-active'" @click="active={{ $i }};stop();start()" aria-label="Ver promoción {{ $i+1 }}"></button>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </section>
        @endif

        {{-- Categorías destacadas configurables --}}
        @if($isHomeSectionVisible('categories') && $featuredCatsEnabled && $categories->count())
        @php
            $categoryIconPaths = [
                'default' => '<rect x="4" y="4" width="16" height="16" rx="2"></rect><rect x="8" y="8" width="8" height="8" rx="1"></rect>',
                'pc' => '<rect x="4" y="3" width="16" height="12" rx="1.5"></rect><path d="M9 19h6M12 15v4"></path>',
                'laptop' => '<rect x="3" y="4" width="18" height="12" rx="1.5"></rect><path d="M2 20h20M8 20l1-3M16 20l-1-3"></path>',
                'monitor' => '<rect x="3" y="4" width="18" height="12" rx="1.5"></rect><path d="M8 20h8M12 16v4"></path>',
                'impresora' => '<path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><path d="M6 14h12v7H6z"></path>',
                'camara' => '<path d="M4 7h4l2-3h4l2 3h4v13H4z"></path><circle cx="12" cy="13" r="4"></circle>',
                'camera-security' => '<path d="M4 7h12l4 4-4 4H4z"></path><circle cx="10" cy="11" r="2"></circle><path d="M10 15v4M7 19h6"></path>',
                'disco' => '<rect x="4" y="3" width="16" height="18" rx="2"></rect><circle cx="12" cy="11" r="4"></circle><path d="M8 18h8"></path>',
                'teclado' => '<rect x="2" y="6" width="20" height="12" rx="2"></rect><path d="M6 10h.01M10 10h.01M14 10h.01M18 10h.01M6 14h12"></path>',
                'mouse' => '<rect x="7" y="3" width="10" height="18" rx="5"></rect><path d="M12 7v3"></path>',
                'audio' => '<path d="M4 14h4l5 4V6L8 10H4zM17 9a4 4 0 0 1 0 6M19 6a8 8 0 0 1 0 12"></path>',
                'celular' => '<rect x="7" y="2" width="10" height="20" rx="2"></rect><path d="M11 18h2"></path>',
                'router' => '<rect x="3" y="11" width="18" height="8" rx="2"></rect><path d="M7 15h.01M11 15h.01M17 15h.01M8 8a6 6 0 0 1 8 0M10 10a3 3 0 0 1 4 0"></path>',
                'gaming' => '<path d="M7 8h10a5 5 0 0 1 4.7 6.7l-1 2.8a2 2 0 0 1-3.3.8L15 16H9l-2.4 2.3a2 2 0 0 1-3.3-.8l-1-2.8A5 5 0 0 1 7 8z"></path><path d="M8 11v4M6 13h4M16 12h.01M18 14h.01"></path>',
                'chip' => '<rect x="7" y="7" width="10" height="10" rx="2"></rect><path d="M9 1v3M15 1v3M9 20v3M15 20v3M20 9h3M20 14h3M1 9h3M1 14h3"></path>',
                'cable' => '<path d="M7 7V3M5 3h4M17 21v-4M15 21h4M7 7c0 7 10 3 10 10"></path>',
                'escritorio' => '<rect x="4" y="3" width="16" height="12" rx="1.5"></rect><path d="M9 19h6M8 15v4M16 15v4M12 15v4"></path>',
            ];
            $autoCategoryIcon = static function ($name) {
                $name = mb_strtolower($name ?? '');
                if (str_contains($name, 'laptop')) return 'laptop';
                if (str_contains($name, 'impres')) return 'impresora';
                if (str_contains($name, 'seguridad')) return 'camera-security';
                if (str_contains($name, 'cámara') || str_contains($name, 'camara')) return 'camara';
                if (str_contains($name, 'disco') || str_contains($name, 'memoria') || str_contains($name, 'almacen')) return 'disco';
                if (str_contains($name, 'monitor')) return 'monitor';
                if (str_contains($name, 'teclado')) return 'teclado';
                if (str_contains($name, 'mouse')) return 'mouse';
                if (str_contains($name, 'audio') || str_contains($name, 'audífono')) return 'audio';
                if (str_contains($name, 'celular')) return 'celular';
                if (str_contains($name, 'router') || str_contains($name, 'wifi')) return 'router';
                if (str_contains($name, 'gaming')) return 'gaming';
                if (str_contains($name, 'procesador') || str_contains($name, 'chip')) return 'chip';
                if (str_contains($name, 'cable')) return 'cable';
                if (str_contains($name, 'escritorio')) return 'escritorio';
                if (str_contains($name, 'comput')) return 'pc';
                return 'default';
            };

            $featuredCategoryItems = $categories->map(function ($cat) use ($featuredCatsItems) {
                $custom = $featuredCatsItems[(string) $cat->id] ?? [];
                $count = $cat->products->count() + $cat->children->sum(fn ($child) => $child->products->count());
                $image = $custom['image'] ?? data_get($cat, 'image_url') ?? data_get($cat, 'image') ?? data_get($cat, 'cover_url');
                return [
                    'model' => $cat,
                    'count' => $count,
                    'image' => $image,
                    'visual' => $custom['visual'] ?? 'inherit',
                    'icon' => $custom['icon'] ?? null,
                    'fit' => $custom['fit'] ?? null,
                    'shape' => $custom['shape'] ?? 'inherit',
                ];
            });
            if ($featuredCatsSelectedIds->isNotEmpty()) {
                $featuredCategoryItems = $featuredCategoryItems
                    ->filter(fn ($item) => $featuredCatsSelectedIds->contains((string) $item['model']->id))
                    ->sortBy(fn ($item) => $featuredCatsSelectedIds->search((string) $item['model']->id))
                    ->values();
            }
            if ($featuredCatsHideEmpty) $featuredCategoryItems = $featuredCategoryItems->filter(fn ($item) => $item['count'] > 0);
            $featuredCategoryItems = $featuredCategoryItems->take($featuredCatsLimit)->values();
        @endphp

        @if($featuredCategoryItems->isNotEmpty())
        <section class="home-cats style-{{ $featuredCatsStyle }} shape-{{ $featuredCatsShape }} {{ $featuredCatsMobileCarousel ? 'mobile-carousel' : '' }}" data-store-native-section="featured_categories" style="order:{{ $sectionOrder('categories') }}">
            <div class="container">
                <div class="home-section-head">
                    <div class="home-section-copy">
                        <h2>{{ $featuredCatsTitle }}</h2>
                        @if($featuredCatsSubtitle)<p>{{ $featuredCatsSubtitle }}</p>@endif
                    </div>
                    @if($featuredCatsShowAll)
                    <a href="{{ $shopUrl }}" class="home-see-all">{{ $featuredCatsAllText }}
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M14 6l6 6-6 6"></path></svg>
                    </a>
                    @endif
                </div>

                <div class="home-cats-grid">
                    @foreach($featuredCategoryItems as $item)
                    @php
                        $cat = $item['model'];
                        $catImage = $assetUrl($item['image']);
                        $visual = in_array($item['visual'], ['image','icon','initial'], true) ? $item['visual'] : $featuredCatsVisual;
                        $useImage = $visual === 'image' || ($visual === 'auto' && $catImage);
                        $iconKey = $item['icon'] && isset($categoryIconPaths[$item['icon']]) ? $item['icon'] : $autoCategoryIcon($cat->name);
                        $imageFit = in_array($item['fit'], ['cover','contain'], true) ? $item['fit'] : $featuredCatsImageFit;
                        $itemShape = in_array($item['shape'], ['rounded','square','circle'], true) ? $item['shape'] : $featuredCatsShape;
                    @endphp
                    <a href="{{ $shopUrl }}?category={{ $cat->id }}" class="home-cat-card item-shape-{{ $itemShape }}">
                        <div class="home-cat-media">
                            @if($useImage && $catImage)
                                <img src="{{ $catImage }}" alt="{{ $cat->name }}" loading="lazy" style="object-fit:{{ $imageFit }}">
                            @elseif($visual === 'initial')
                                <span class="home-cat-icon home-cat-initial">{{ mb_strtoupper(mb_substr($cat->name,0,1)) }}</span>
                            @else
                                <span class="home-cat-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        {!! $categoryIconPaths[$iconKey] !!}
                                    </svg>
                                </span>
                            @endif
                        </div>
                        <div class="home-cat-content">
                            <div class="home-cat-copy">
                                <strong>{{ $cat->name }}</strong>
                                @if($featuredCatsShowCount)<small>{{ $item['count'] }} {{ $item['count'] === 1 ? 'producto' : 'productos' }}</small>@endif
                            </div>
                            <span class="home-cat-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M14 6l6 6-6 6"></path></svg></span>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
        </section>
        @endif
        @endif

        @if($isHomeSectionVisible('benefits') && $trustSectionEnabled && count($trustBenefits))
        @php
            $trustIconPaths = [
                'store' => '<path d="M3 9l2-5h14l2 5"></path><path d="M5 9v11h14V9"></path><path d="M9 20v-6h6v6"></path><path d="M3 9h18"></path>',
                'truck' => '<path d="M3 6h11v11H3z"></path><path d="M14 10h4l3 3v4h-7z"></path><circle cx="7" cy="19" r="2"></circle><circle cx="18" cy="19" r="2"></circle>',
                'clock' => '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path>',
                'sparkles' => '<path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z"></path><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15z"></path>',
                'shield' => '<path d="M12 3 4 6v5c0 5 3.3 8.2 8 10 4.7-1.8 8-5 8-10V6l-8-3Z"></path><path d="m8.5 12 2.2 2.2 4.8-5"></path>',
                'support' => '<path d="M4 13a8 8 0 0 1 16 0"></path><path d="M4 13v5h3v-5H4zM17 13v5h3v-5h-3z"></path><path d="M17 19c-1 1-2.5 2-5 2"></path>',
                'warranty' => '<path d="M12 3l3 2 3.5-.5.5 3.5 2 3-2 3-.5 3.5-3.5-.5-3 2-3-2-3.5.5L6 14l-2-3 2-3 .5-3.5L10 5l2-2z"></path><path d="m9 12 2 2 4-4"></path>',
                'default' => '<circle cx="12" cy="12" r="9"></circle><path d="M12 8v4M12 16h.01"></path>',
            ];
        @endphp
        <section class="trust-section style-{{ $trustSectionStyle }} {{ $trustMobileCarousel ? 'mobile-carousel' : '' }}" aria-label="Beneficios" data-store-native-section="benefits" style="order:{{ $sectionOrder('benefits') }}">
            <div class="container">
                <div class="trust-section-head">
                    <h2>{{ $trustSectionTitle }}</h2>
                    @if($trustSectionSubtitle !== '')<p>{{ $trustSectionSubtitle }}</p>@endif
                </div>
                <div class="trust-grid">
                    @foreach($trustBenefits as $benefit)
                    <article class="trust-card">
                        <span class="trust-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                {!! $trustIconPaths[$benefit['icon']] ?? $trustIconPaths['default'] !!}
                            </svg>
                        </span>
                        <div class="trust-card-copy">
                            <strong>{{ $benefit['title'] }}</strong>
                            @if($trustShowDescriptions && $benefit['description'] !== '')<span>{{ $benefit['description'] }}</span>@endif
                        </div>
                    </article>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- ═══ SOLO POR HOY / FLASH SALE ═══ --}}
        @php
            $salePool = $allProducts->filter(fn ($product) => filled($product->compare_price) && (float) $product->compare_price > (float) $product->price)->values();
            $selectSectionProducts = static function (array $content, $pool, int $limit) {
                $ids = collect($content['product_ids'] ?? $content['products'] ?? [])->map(fn ($id) => (string) (is_array($id) ? ($id['id'] ?? '') : $id))->filter();
                $selected = $ids->isNotEmpty() ? $pool->filter(fn ($product) => $ids->contains((string) $product->id)) : $pool;
                return $selected->take($limit)->values();
            };
            $flashContent = $sectionContentFor('flash_sale');
            $flashProducts = $selectSectionProducts($flashContent,$salePool,max(1,min(8,(int)($flashContent['count'] ?? 4))));
            $flashTitle = trim($flashContent['title'] ?? 'Solo por hoy');
            $flashSubtitle = trim($flashContent['subtitle'] ?? $flashContent['description'] ?? 'Aprovecha esta promoción por tiempo limitado.');
            $flashImage = $assetUrl($flashContent['image'] ?? $flashContent['image_url'] ?? $flashContent['background_image'] ?? null);
            $flashBg = $color($flashContent['background_color'] ?? $flashContent['background'] ?? null,$secondary);
            $flashButtonText = trim($flashContent['button_text'] ?? $flashContent['cta_text'] ?? 'Ver oferta');
            $flashButtonUrl = trim($flashContent['button_url'] ?? $flashContent['url'] ?? $shopUrl);
            $flashEndsRaw = $flashContent['ends_at'] ?? $flashContent['end_at'] ?? $flashContent['end'] ?? $flashContent['countdown_end'] ?? null;
            $flashExpiredAction = $flashContent['expired_action'] ?? $flashContent['on_end'] ?? 'hide';
            $flashExpiredMessage = trim($flashContent['expired_message'] ?? 'Esta oferta ha finalizado.');
            $flashEndsIso = null; $flashExpired = false;
            if ($flashEndsRaw) { try { $flashDate = \Illuminate\Support\Carbon::parse($flashEndsRaw); $flashEndsIso=$flashDate->toIso8601String(); $flashExpired=$flashDate->isPast(); } catch (\Throwable $exception) {} }
            $flashShouldRender = !$flashExpired || $flashExpiredAction !== 'hide';
        @endphp
        @if($isHomeSectionVisible('flash_sale') && $flashShouldRender)
        <section class="special-home-section flash-sale-section" data-store-native-section="daily_offer" style="order:{{ $sectionOrder('flash_sale') }};--flash-bg:{{ $flashBg }}" @if($flashEndsIso) x-data="{deadline:new Date(@js($flashEndsIso)).getTime(),now:Date.now(),timer:null,start(){this.timer=setInterval(()=>this.now=Date.now(),1000)},stop(){if(this.timer)clearInterval(this.timer)},pad(v){return String(Math.max(0,v)).padStart(2,'0')},get distance(){return Math.max(0,this.deadline-this.now)},get days(){return Math.floor(this.distance/86400000)},get hours(){return Math.floor((this.distance%86400000)/3600000)},get minutes(){return Math.floor((this.distance%3600000)/60000)},get seconds(){return Math.floor((this.distance%60000)/1000)}}" x-init="start()" @endif>
            <div class="container">
                <div class="flash-campaign {{ $flashImage ? 'has-image' : '' }}" @if($flashImage) style="background-image:linear-gradient(90deg,rgba(15,23,42,.88),rgba(15,23,42,.42)),url('{{ $flashImage }}')" @else style="background:linear-gradient(135deg,var(--flash-bg),color-mix(in srgb,var(--flash-bg) 72%,#000))" @endif>
                    <div class="flash-campaign-copy">
                        <span class="flash-label">Oferta limitada</span><h2>{{ $flashTitle }}</h2>
                        @if($flashExpired)<p>{{ $flashExpiredMessage }}</p>@elseif($flashSubtitle)<p>{{ $flashSubtitle }}</p>@endif
                        @if($flashEndsIso && !$flashExpired)<div class="flash-countdown"><div><strong x-text="pad(days)">00</strong><span>Días</span></div><div><strong x-text="pad(hours)">00</strong><span>Horas</span></div><div><strong x-text="pad(minutes)">00</strong><span>Min</span></div><div><strong x-text="pad(seconds)">00</strong><span>Seg</span></div></div>@endif
                        @if(!$flashExpired && $flashButtonText !== '' && $flashButtonUrl !== '')<a href="{{ $flashButtonUrl }}" class="button button-primary">{{ $flashButtonText }} <span>→</span></a>@endif
                    </div>
                </div>
                @if($flashProducts->isNotEmpty())
                <div class="special-product-grid flash-product-grid">
                    @foreach($flashProducts as $product)
                    @php $discount=(int)round((1-((float)$product->price/max(0.01,(float)$product->compare_price)))*100); $productUrl=route('public.product',[$project->slug,$product->id]); @endphp
                    <article class="special-product-card"><a href="{{ $productUrl }}" class="special-product-media">@if($product->main_image_url)<img src="{{ $product->main_image_url }}" alt="{{ $product->name }}" loading="lazy">@endif<span class="special-product-badge">-{{ $discount }}%</span></a><div class="special-product-body"><small>Oferta especial</small><a href="{{ $productUrl }}"><strong>{{ $product->name }}</strong></a><div class="special-product-prices"><b>{{ $currency }} {{ number_format((float)$product->price,2) }}</b><del>{{ $currency }} {{ number_format((float)$product->compare_price,2) }}</del></div></div></article>
                    @endforeach
                </div>
                @endif
            </div>
        </section>
        @endif

        {{-- ═══ PRODUCTOS CON DESCUENTO ═══ --}}
        @php
            $discountContent = $sectionContentFor('discount_products');
            $discountProducts = $selectSectionProducts($discountContent, $salePool, 8);
            $discountTitle = trim($discountContent['title'] ?? 'Productos con descuento');
            $discountSubtitle = trim($discountContent['subtitle'] ?? $discountContent['description'] ?? 'Una selección de oportunidades para comprar mejor.');
        @endphp
        @if($isHomeSectionVisible('discount_products') && $discountProducts->isNotEmpty())
        <section class="special-home-section discount-section" data-store-native-section="discounts" style="order:{{ $sectionOrder('discount_products') }}">
            <div class="container">
                <div class="special-section-head">
                    <div><h2>{{ $discountTitle }}</h2>@if($discountSubtitle)<p>{{ $discountSubtitle }}</p>@endif</div>
                    <a href="{{ $shopUrl }}" class="home-see-all">Ver todos <span>→</span></a>
                </div>
                <div class="special-product-grid">
                    @foreach($discountProducts as $product)
                    @php
                        $discount = (int) round((1 - ((float) $product->price / max(0.01, (float) $product->compare_price))) * 100);
                        $productUrl = route('public.product', [$project->slug, $product->id]);
                    @endphp
                    <article class="special-product-card">
                        <a href="{{ $productUrl }}" class="special-product-media">
                            @if($product->main_image_url)<img src="{{ $product->main_image_url }}" alt="{{ $product->name }}" loading="lazy">@endif
                            <span class="special-product-badge">-{{ $discount }}%</span>
                        </a>
                        <div class="special-product-body">
                            <small>Oferta</small>
                            <a href="{{ $productUrl }}"><strong>{{ $product->name }}</strong></a>
                            <div class="special-product-prices"><b>{{ $currency }} {{ number_format((float)$product->price,2) }}</b><del>{{ $currency }} {{ number_format((float)$product->compare_price,2) }}</del></div>
                        </div>
                    </article>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- ═══ BLOG INFORMATIVO ═══ --}}
        @php
            $blogContent = $sectionContentFor('blog');
            $blogItems = collect($blogContent['items'] ?? $blogContent['posts'] ?? []);
            if ($blogItems->isEmpty() && !empty($blogContent['body'])) {
                $blogItems = collect([[
                    'title' => $blogContent['article_title'] ?? $blogContent['title'] ?? 'Información para ti',
                    'excerpt' => $blogContent['body'],
                    'image' => $blogContent['image'] ?? null,
                    'url' => $blogContent['url'] ?? null,
                ]]);
            }
            $blogTitle = trim($blogContent['section_title'] ?? $blogContent['title'] ?? 'Blog informativo');
            $blogSubtitle = trim($blogContent['subtitle'] ?? $blogContent['description'] ?? '');
        @endphp
        @if($isHomeSectionVisible('blog') && $blogItems->isNotEmpty())
        <section class="special-home-section blog-section" data-store-native-section="blog" style="order:{{ $sectionOrder('blog') }}">
            <div class="container">
                <div class="special-section-head"><div><h2>{{ $blogTitle }}</h2>@if($blogSubtitle)<p>{{ $blogSubtitle }}</p>@endif</div></div>
                <div class="blog-grid">
                    @foreach($blogItems->take(6) as $item)
                    @php
                        $item = is_array($item) ? $item : (array) $item;
                        $itemImage = $assetUrl($item['image'] ?? null);
                        $itemUrl = trim((string) ($item['url'] ?? ''));
                    @endphp
                    <article class="blog-card">
                        @if($itemImage)<a @if($itemUrl)href="{{ $itemUrl }}"@endif class="blog-card-image"><img src="{{ $itemImage }}" alt="{{ $item['title'] ?? 'Artículo' }}" loading="lazy"></a>@endif
                        <div class="blog-card-body">
                            <small>Consejos y novedades</small>
                            <h3>{{ $item['title'] ?? 'Información para ti' }}</h3>
                            @if(!empty($item['excerpt']) || !empty($item['description']) || !empty($item['body']))
                            <p>{{ \Illuminate\Support\Str::limit(strip_tags($item['excerpt'] ?? $item['description'] ?? $item['body']), 180) }}</p>
                            @endif
                            @if($itemUrl)<a href="{{ $itemUrl }}" class="blog-card-link">Leer más →</a>@endif
                        </div>
                    </article>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- ═══ PRODUCTOS DESTACADOS (premium) ═══ --}}
        @php
            $homeFeatured = $featured->take(8); if($homeFeatured->isEmpty()) $homeFeatured = $categories->flatMap->products->take(8);
            // Título: usa el de la sección del constructor si existe.
            $pfSection = $homeSectionByNativeKey->get('featured_products');
            $pfContent = is_array($pfSection?->content) ? $pfSection->content : [];
            $pfTitle = $pfContent['title'] ?? ($catalogTitle ?: 'Productos destacados');
        @endphp
        @if($isHomeSectionVisible('featured_products') && $homeFeatured->count())
        <section class="pf-section" data-store-native-section="featured_products" style="order:{{ $sectionOrder('featured_products') }}"><div class="container">
            <div class="pf-head">
                <div>
                    <span class="pf-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2 15 8.5 22 9.5 17 14.5 18.5 21.5 12 18 5.5 21.5 7 14.5 2 9.5 9 8.5Z"></path></svg>LO MEJOR EN TECNOLOGÍA</span>
                    <h2 class="pf-title">{{ $pfTitle }}</h2>
                    <p class="pf-desc">Descubre nuestra selección de equipos y accesorios cuidadosamente seleccionados para ofrecer el mejor rendimiento y calidad.</p>
                </div>
                <a href="{{ $shopUrl }}" class="pf-seeall">Ver toda la tienda<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M14 6l6 6-6 6"></path></svg></a>
            </div>
            <div class="pf-grid">
                @foreach($homeFeatured as $product)
                @php
                    $pUrl = route('public.product', [$project->slug, $product->id]);
                    $onSale = filled($product->compare_price) && $product->compare_price > $product->price;
                    $descNorm = mb_strtolower(($product->name ?? '').' '.($product->description ?? ''));
                    // Specs rápidas detectadas del nombre/descripción (procesador, RAM, almacenamiento)
                    $specs = [];
                    if(preg_match('/(core\s?i[3579]|ryzen\s?[3579]|apple\s?m[123]|intel\s?n\d+|celeron)/i',$descNorm,$m)) $specs[]=['cpu',ucwords($m[1])];
                    if(preg_match('/(\d+)\s?gb\s?ram/i',$descNorm,$m)) $specs[]=['ram',$m[1].'GB RAM'];
                    if(preg_match('/(\d+)\s?(tb|gb)\s?(ssd|hdd)/i',$descNorm,$m)) $specs[]=['disk',strtoupper($m[1].$m[2].' '.$m[3])];
                @endphp
                <article class="pf-card">
                    <a class="pf-media" href="{{ $pUrl }}" aria-label="{{ $product->name }}">
                        @if($product->main_image_url)<img src="{{ $product->main_image_url }}" alt="{{ $product->name }}" loading="lazy">@else<svg class="pf-ph" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="m4 7 8-4 8 4-8 4-8-4Z"></path><path d="m4 7 8 4v10l-8-4V7Zm16 0-8 4v10l8-4V7Z"></path></svg>@endif
                        @if($onSale)<span class="pf-flag pf-flag-sale">Oferta</span>@elseif($loop->index < 2)<span class="pf-flag pf-flag-new">Nuevo</span>@else<span class="pf-flag pf-flag-hot">Destacado</span>@endif
                        <button type="button" class="pf-fav" aria-label="Agregar a favoritos" onclick="this.classList.toggle('on');event.preventDefault()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 21s-7-4.35-9.5-8.5C.5 8 3 4.5 6.5 4.5 9 4.5 12 7 12 7s3-2.5 5.5-2.5C21 4.5 23.5 8 21.5 12.5 19 16.65 12 21 12 21Z"></path></svg></button>
                    </a>
                    <div class="pf-body">
                        <span class="pf-cat">{{ $product->category->name ?? 'Producto' }}</span>
                        <a class="pf-name" href="{{ $pUrl }}">{{ $product->name }}</a>
                        @if(count($specs))
                        <div class="pf-specs">
                            @foreach($specs as $sp)
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">@switch($sp[0])@case('cpu')<rect x="6" y="6" width="12" height="12" rx="1.5"></rect><path d="M9 2v4M15 2v4M9 18v4M15 18v4M2 9h4M2 15h4M18 9h4M18 15h4"></path>@break @case('ram')<rect x="2" y="8" width="20" height="8" rx="1.5"></rect><path d="M6 16v2M10 16v2M14 16v2M18 16v2"></path>@break @default<path d="M4 6h16v12H4z"></path><path d="M8 10h8M8 14h5"></path>@endswitch</svg>{{ $sp[1] }}</span>
                            @endforeach
                        </div>
                        @endif
                        @unless($hidePrices)
                        <div class="pf-prices">
                            <span class="pf-price">{{ $currency }} {{ number_format($product->price, 2) }}</span>
                            @if($onSale)<span class="pf-compare">{{ $currency }} {{ number_format($product->compare_price, 2) }}</span>@endif
                        </div>
                        @endunless
                        <a class="pf-btn" href="{{ $pUrl }}">Ver detalles<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M14 6l6 6-6 6"></path></svg></a>
                    </div>
                </article>
                @endforeach
            </div>
            <div class="home-cta-row"><a href="{{ $shopUrl }}" class="button button-primary">Ver todos los productos<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14M14 6l6 6-6 6"></path></svg></a></div>
        </div></section>
        @endif
        @endif

        {{-- ═══ TIENDA: catálogo completo con filtros ═══ --}}
        @if($storeView === 'tienda')
        <section class="catalog" id="tienda-catalogo" style="order:{{ $sectionOrder('catalog') }}"><div class="container">
            <nav class="catalog-breadcrumb" aria-label="Ruta de navegación"><a href="{{ route('public.catalog', $project->slug) }}">Inicio</a><span>/</span><strong x-text="activeFilterLabel">Todos los productos</strong></nav>
            <div class="section-heading"><div><h2 x-text="activeFilterLabel">{{ $catalogTitle }}</h2><p>Filtra por categoría, precio y disponibilidad para encontrar la mejor opción.</p></div><span class="product-total">{{ $catalogProducts->count() }} productos disponibles</span></div>

            <div class="catalog-experience">
                <aside class="catalog-filter-panel" aria-label="Filtros del catálogo">
                    <div class="catalog-filter-header"><strong>Filtros</strong><button class="catalog-clear" type="button" @click="clearAllFilters()" :disabled="!hasActiveFilters">Limpiar todo</button></div>
                    <x-computienda.catalog-filters :categories="$categories" :catalog-products="$catalogProducts" filter-scope="desktop" />
                </aside>

                <div class="catalog-results">
                    <div class="catalog-chips" x-show="hasActiveFilters" x-cloak aria-label="Filtros activos">
                        <button class="catalog-chip" type="button" x-show="filterCat" @click="filterCat='';filterSubCat=''" x-cloak><span x-text="categoryName(filterCat)"></span><span aria-hidden="true">×</span></button>
                        <button class="catalog-chip" type="button" x-show="filterSubCat" @click="filterSubCat=''" x-cloak><span x-text="categoryName(filterSubCat)"></span><span aria-hidden="true">×</span></button>
                        <button class="catalog-chip" type="button" x-show="filterInStock" @click="filterInStock=false" x-cloak>En stock <span aria-hidden="true">×</span></button>
                        <button class="catalog-chip" type="button" x-show="filterOnSale" @click="filterOnSale=false" x-cloak>En oferta <span aria-hidden="true">×</span></button>
                        <button class="catalog-chip" type="button" x-show="priceMin>0 || priceMax<maxPrice" @click="priceMin=0;priceMax=maxPrice" x-cloak><span x-text="money(priceMin)+' – '+money(priceMax)"></span><span aria-hidden="true">×</span></button>
                        <button class="catalog-chip" type="button" x-show="query" @click="query=''" x-cloak><span x-text="'“'+query+'”'"></span><span aria-hidden="true">×</span></button>
                        <button class="catalog-chip catalog-chip-neutral" type="button" @click="clearAllFilters()">Limpiar todo</button>
                    </div>

                    <div class="catalog-results-head">
                        <div class="catalog-results-count" aria-live="polite"><strong x-text="catalogProducts.length">{{ $catalogProducts->count() }}</strong> de {{ $catalogProducts->count() }} productos</div>
                        <div class="catalog-results-tools">
                            <button class="catalog-mobile-filter" type="button" @click="filterDrawerOpen=true" aria-label="Abrir filtros"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"></path></svg>Filtros <span x-show="activeFilterCount" x-text="activeFilterCount" x-cloak></span></button>
                            <label><span class="sr-only">Ordenar productos</span><select class="catalog-sort" x-model="sortBy"><option value="default">Relevancia</option><option value="price-asc">Precio: menor a mayor</option><option value="price-desc">Precio: mayor a menor</option><option value="name">Nombre A-Z</option></select></label>
                        </div>
                    </div>

                    <div class="catalog-product-grid">
                        <template x-for="product in catalogProducts" :key="product.id">
                            <article class="catalog-card">
                                <a class="catalog-card-media" :href="product.url || '#catalogo'" @click="if(!product.url)$event.preventDefault()" :aria-label="'Ver '+product.name">
                                    <template x-if="product.image"><img :src="product.image" :alt="product.name" loading="lazy"></template>
                                    <template x-if="!product.image"><svg class="catalog-card-placeholder" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><path d="m4 7 8-4 8 4-8 4-8-4Z"></path><path d="m4 7 8 4v10l-8-4V7Zm16 0-8 4v10l8-4V7Z"></path></svg></template>
                                    <span class="catalog-discount" x-show="isDiscounted(product)" x-text="discountLabel(product)" x-cloak></span>
                                    <span class="catalog-sold-out" x-show="product.stock===0" x-cloak>{{ $settings['catalog_badge_sold_out'] ?? 'Agotado' }}</span>
                                </a>
                                <div class="catalog-card-body">
                                    <span class="catalog-card-category" x-text="{{ $showSku ? "product.sku ? 'SKU '+product.sku : product.category" : 'product.category' }}"></span>
                                    <a class="catalog-card-name" :href="product.url || '#catalogo'" @click="if(!product.url)$event.preventDefault()" x-text="product.name"></a>
                                    @if($hidePrices)<div class="catalog-card-prices"><span class="quote-price">Precio a solicitud</span></div>@else
                                        <div class="catalog-card-prices"><span class="catalog-card-price" x-text="money(product.price)"></span><span class="catalog-card-compare" x-show="isDiscounted(product)" x-text="money(product.comparePrice)" x-cloak></span><span class="catalog-card-percent" x-show="isDiscounted(product)" x-text="discountLabel(product)" x-cloak></span></div>
                                        @if($wholesale)<span class="catalog-card-wholesale" x-show="product.wholesalePrice" x-text="'Mayorista: '+money(product.wholesalePrice)+' desde '+product.wholesaleMinQty+' '+product.wholesaleUnit" x-cloak></span>@endif
                                    @endif
                                </div>
                                <button class="catalog-card-action" type="button" @click="add(product.id,product.name,product.price)" :disabled="product.stock===0" x-text="product.stock===0 ? soldOutText : cartButtonText"></button>
                            </article>
                        </template>

                        <div class="catalog-empty" x-show="catalogProducts.length===0" x-cloak>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><h3>Sin resultados</h3><p>{{ $txtNoResults }}</p><button class="button button-primary" type="button" @click="clearAllFilters()">{{ $txtViewMore }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div></section>
        @endif

        {{-- ═══ PÁGINA: Nosotros / Contacto (con el diseño de la tienda) ═══ --}}
        @if(in_array($storeView, ['nosotros','contacto'], true))
        @php $storePage = $storePage ?? null; $pc = is_array($storePage?->content) ? $storePage->content : []; @endphp
        <section class="store-page" style="order:{{ $sectionOrder('custom_page') }}"><div class="container">
            <nav class="catalog-breadcrumb" aria-label="Ruta de navegación"><a href="{{ \App\Support\StorefrontNavigation::resolveUrl($project, new \App\Models\StoreMenuItem(['destination_type'=>'home'])) }}">Inicio</a><span>/</span><strong>{{ $storePage?->title ?? ($storeView === 'nosotros' ? 'Nosotros' : 'Contacto') }}</strong></nav>
            <h1 class="store-page-title">{{ $storePage?->title ?? ($storeView === 'nosotros' ? 'Nosotros' : 'Contacto') }}</h1>

            @if($storeView === 'nosotros')
                @if(!empty($pc['image']))<img class="store-page-hero" src="{{ $assetUrl($pc['image']) }}" alt="{{ $storePage?->title }}">@endif
                @if(!empty($pc['body']))<div class="store-page-body">{{ $pc['body'] }}</div>@endif
                @foreach(['history'=>'Nuestra historia','mission'=>'Misión','vision'=>'Visión','values'=>'Valores','team'=>'Equipo'] as $k=>$lbl)
                    @if(!empty($pc[$k]))
                    <div class="store-page-block"><h2>{{ $lbl }}</h2><p>{{ $pc[$k] }}</p></div>
                    @endif
                @endforeach
            @else
                @if(!empty($pc['body']))<div class="store-page-body">{{ $pc['body'] }}</div>@endif
                <div class="store-page-contact">
                    <div class="store-contact-info">
                        @if(!empty($pc['email']))<p><strong>Correo:</strong> <a href="mailto:{{ $pc['email'] }}">{{ $pc['email'] }}</a></p>@endif
                        @if(!empty($pc['phone']))<p><strong>Teléfono:</strong> {{ $pc['phone'] }}</p>@endif
                        @if(!empty($pc['whatsapp']))<p><strong>WhatsApp:</strong> <a href="https://wa.me/{{ preg_replace('/\D/','',$pc['whatsapp']) }}" target="_blank" rel="noopener">{{ $pc['whatsapp'] }}</a></p>@endif
                        @if(!empty($pc['address']))<p><strong>Dirección:</strong> {{ $pc['address'] }}</p>@endif
                        @if(!empty($pc['hours']))<p><strong>Horario:</strong> {{ $pc['hours'] }}</p>@endif
                    </div>
                    <form class="store-contact-form" method="post" action="{{ url(($project->custom_domain && request()->getHost() === $project->custom_domain ? '' : '/'.$project->slug).'/contacto') }}">
                        @csrf
                        <input name="name" placeholder="Tu nombre" required>
                        <input name="email" type="email" placeholder="Tu correo" @if(!empty($pc['require_email'])) required @endif>
                        <input name="phone" placeholder="Tu teléfono" @if(!empty($pc['require_phone'])) required @endif>
                        <input name="subject" placeholder="Asunto">
                        <textarea name="message" rows="4" placeholder="Tu mensaje" required></textarea>
                        <label style="display:flex;gap:8px;align-items:flex-start;font-size:12px;color:#64748b"><input type="checkbox" name="privacy" value="1" required> Acepto la política de privacidad</label>
                        <button class="button button-primary" type="submit">Enviar mensaje</button>
                    </form>
                </div>
            @endif
        </div></section>
        @endif
    </main>

    {{-- ═══════════════ FOOTER PREMIUM ═══════════════ --}}
    @php
        // Íconos SVG del sistema reutilizados (mismos del hero/assurance)
        $svgIcons = [
            'shield'   => '<path d="M12 3 4 6v5c0 5 3.3 8.2 8 10 4.7-1.8 8-5 8-10V6l-8-3Z"></path><path d="m8.5 12 2.2 2.2 4.8-5"></path>',
            'truck'    => '<path d="M3 6h12v11H3zM15 10h4l2 3v4h-6z"></path><circle cx="7" cy="19" r="1.5"></circle><circle cx="18" cy="19" r="1.5"></circle>',
            'support'  => '<path d="M20 15a4 4 0 0 1-4 4H8a5 5 0 0 1-2-9.6A6 6 0 0 1 18 10a4 4 0 0 1 2 5Z"></path><path d="M9 14h6M12 11v6"></path>',
            'warranty' => '<path d="M4 5h16v14H4zM8 9h8M8 13h5"></path>',
            'phone'    => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"></path>',
            'pin'      => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle>',
            'mail'     => '<rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-10 6L2 7"></path>',
            'clock'    => '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path>',
            'chat'     => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>',
        ];
        $waFooter = preg_replace('/\D/', '', $settings['quote_whatsapp'] ?? $phone ?? '');
        if ($waFooter && !str_starts_with($waFooter, '51')) $waFooter = '51'.$waFooter;
        $shopBaseFooter = \App\Support\StorefrontNavigation::resolveUrl($project, new \App\Models\StoreMenuItem(['destination_type' => 'shop']));
        $aboutUrl   = \App\Support\StorefrontNavigation::resolveUrl($project, new \App\Models\StoreMenuItem(['destination_type' => 'about']));
        $contactUrl = \App\Support\StorefrontNavigation::resolveUrl($project, new \App\Models\StoreMenuItem(['destination_type' => 'contact']));
    @endphp

    <footer class="site-footer">
        <div class="container footer-main">
            {{-- Columna 1: Marca --}}
            <div class="footer-brand">
                <div class="footer-logo">
                    @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $storeName }}">@else<span class="footer-logo-mark">{{ mb_strtoupper(mb_substr($storeName,0,1)) }}</span>@endif
                    <strong>{{ $storeName }}</strong>
                </div>
                <p class="footer-desc">{{ Str::limit($tagline, 130) }}</p>

                @if(count($footerTrust))
                <div class="footer-trust">
                    @foreach($footerTrust as $t)
                    <div class="footer-trust-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">{!! $svgIcons[$t['k']] ?? $svgIcons['shield'] !!}</svg>
                        <span>{{ $t['t'] }}</span>
                    </div>
                    @endforeach
                </div>
                @endif

                @if($showSocial && count($social))
                <div class="footer-social">
                    @foreach($social as $red => $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $red }}" title="{{ $red }}">
                        @switch($red)
                            @case('Instagram')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="2" width="20" height="20" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" r="1" fill="currentColor"></circle></svg>@break
                            @case('Facebook')<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>@break
                            @case('TikTok')<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 3a5 5 0 0 0 5 5v3a8 8 0 0 1-5-1.7V15a6 6 0 1 1-6-6v3a3 3 0 1 0 3 3V3z"></path></svg>@break
                            @case('YouTube')<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23 12s0-3.5-.45-5.2a2.75 2.75 0 0 0-1.94-1.94C18.9 4.4 12 4.4 12 4.4s-6.9 0-8.61.46A2.75 2.75 0 0 0 1.45 6.8C1 8.5 1 12 1 12s0 3.5.45 5.2a2.75 2.75 0 0 0 1.94 1.94c1.71.46 8.61.46 8.61.46s6.9 0 8.61-.46a2.75 2.75 0 0 0 1.94-1.94C23 15.5 23 12 23 12zM10 15.5v-7l6 3.5z"></path></svg>@break
                            @default<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="18" rx="2"></rect><path d="M7 10v7M7 7v.01M11 17v-4a2 2 0 0 1 4 0v4M11 17v-3"></path></svg>
                        @endswitch
                    </a>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Columna 2: Categorías --}}
            @if($categories->count())
            <div class="footer-col">
                <h3 class="footer-title">Categorías</h3>
                <ul class="footer-links">
                    @foreach($categories->take(7) as $cat)
                    <li><a href="{{ $shopBaseFooter }}?category={{ $cat->id }}">
                        @if($cat->image_url)<img class="footer-cat-ico" src="{{ $assetUrl($cat->image_url) }}" alt="">@endif
                        <span>{{ $cat->name }}</span>
                    </a></li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Columna 3: Información --}}
            <div class="footer-col">
                <h3 class="footer-title">Información</h3>
                <ul class="footer-links">
                    <li><a href="{{ $aboutUrl }}"><span>Nosotros</span></a></li>
                    <li><a href="{{ $contactUrl }}"><span>Contacto</span></a></li>
                    <li><a href="{{ url(($project->custom_domain && request()->getHost() === $project->custom_domain ? '' : '/'.$project->slug).'/reclamaciones') }}"><span>Libro de Reclamaciones</span></a></li>
                    <li><a href="{{ url(($project->custom_domain && request()->getHost() === $project->custom_domain ? '' : '/'.$project->slug).'/privacidad') }}"><span>Políticas de privacidad</span></a></li>
                    <li><a href="{{ url(($project->custom_domain && request()->getHost() === $project->custom_domain ? '' : '/'.$project->slug).'/terminos') }}"><span>Términos y condiciones</span></a></li>
                </ul>
            </div>

            {{-- Columna 4: Contacto --}}
            <div class="footer-col">
                <h3 class="footer-title">Contacto</h3>
                <ul class="footer-contact">
                    @if($phone)<li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $svgIcons['phone'] !!}</svg><a href="tel:{{ preg_replace('/[^\d+]/','',$phone) }}">{{ $phone }}</a></li>@endif
                    @if($footerAddress)<li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $svgIcons['pin'] !!}</svg><span>{{ $footerAddress }}</span></li>@endif
                    @if($email)<li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $svgIcons['mail'] !!}</svg><a href="mailto:{{ $email }}">{{ $email }}</a></li>@endif
                    @if($footerHours)<li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $svgIcons['clock'] !!}</svg><span>{{ $footerHours }}</span></li>@endif
                </ul>

                @if($waFooter)
                <div class="footer-help">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $svgIcons['chat'] !!}</svg>
                    <div>
                        <strong>¿Necesitas ayuda?</strong>
                        <span>Nuestro equipo está listo para asesorarte.</span>
                    </div>
                    <a href="https://wa.me/{{ $waFooter }}" target="_blank" rel="noopener" class="footer-help-btn">Contáctanos</a>
                </div>
                @endif
            </div>
        </div>

        {{-- Barra inferior --}}
        <div class="footer-bottom">
            <div class="container footer-bottom-inner">
                {{-- Izquierda: sello de seguridad --}}
                @if($showFooterSsl)
                <div class="footer-secure">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                    <div><strong>Compra segura y protegida</strong><span>Sitio protegido con SSL</span></div>
                </div>
                @else<span></span>@endif

                {{-- Centro: copyright --}}
                <span class="footer-copy">{{ $footerCopyright }}</span>

                {{-- Derecha: métodos de pago (logos) --}}
                @if($showFooterPay)
                <div class="footer-pay">
                    @php
                        $payLogos = [
                            'visa'       => '<svg viewBox="0 0 48 30"><rect width="48" height="30" rx="4" fill="#fff"/><path d="M20.4 20.3h-2.8l1.8-11h2.8l-1.8 11zm10.2-10.7c-.6-.2-1.5-.5-2.6-.5-2.8 0-4.8 1.5-4.8 3.6 0 1.6 1.4 2.4 2.5 3 1.1.5 1.5.9 1.5 1.3 0 .7-.9 1-1.7 1-1.1 0-1.7-.2-2.7-.6l-.4-.2-.4 2.4c.7.3 1.9.6 3.2.6 3 0 4.9-1.5 4.9-3.7 0-1.2-.7-2.2-2.4-3-1-.5-1.6-.8-1.6-1.3 0-.4.5-.9 1.6-.9.9 0 1.6.2 2.1.4l.3.1.4-2.3zm7.2-.3h-2.2c-.7 0-1.2.2-1.5.9l-4.2 10.1h3l.6-1.7h3.6l.3 1.7h2.6l-2.3-11zm-3.5 7.1c.2-.6 1.1-3.1 1.1-3.1s.2-.6.4-1l.2.9.7 3.2h-2.4zM16.1 9.3l-2.8 7.5-.3-1.5c-.5-1.8-2.2-3.7-4-4.6l2.6 9.6h3l4.5-11h-3z" fill="#1a1f71"/></svg>',
                            'mastercard' => '<svg viewBox="0 0 48 30"><rect width="48" height="30" rx="4" fill="#fff"/><circle cx="19" cy="15" r="9" fill="#eb001b"/><circle cx="29" cy="15" r="9" fill="#f79e1b" fill-opacity=".9"/><path d="M24 8.5a9 9 0 0 1 0 13 9 9 0 0 1 0-13z" fill="#ff5f00"/></svg>',
                            'amex'       => '<svg viewBox="0 0 48 30"><rect width="48" height="30" rx="4" fill="#1e6cd6"/><text x="24" y="19" font-family="Arial" font-size="8" font-weight="bold" fill="#fff" text-anchor="middle">AMEX</text></svg>',
                            'diners'     => '<svg viewBox="0 0 48 30"><rect width="48" height="30" rx="4" fill="#fff"/><circle cx="24" cy="15" r="9" fill="none" stroke="#0079be" stroke-width="1.5"/><path d="M20 10a6 6 0 0 0 0 10V10zm8 0v10a6 6 0 0 0 0-10z" fill="#0079be"/></svg>',
                            'yape'       => '<svg viewBox="0 0 48 30"><rect width="48" height="30" rx="4" fill="#742384"/><text x="24" y="19" font-family="Arial" font-size="8" font-weight="bold" fill="#fff" text-anchor="middle">yape</text></svg>',
                            'plin'       => '<svg viewBox="0 0 48 30"><rect width="48" height="30" rx="4" fill="#00c3a5"/><text x="24" y="19" font-family="Arial" font-size="8" font-weight="bold" fill="#fff" text-anchor="middle">plin</text></svg>',
                        ];
                        $showPay = count($footerPayments) ? $footerPayments : ['visa','mastercard','amex','diners','yape'];
                    @endphp
                    @foreach($showPay as $pay)
                        @if(isset($payLogos[strtolower($pay)]))<span class="footer-pay-logo">{!! $payLogos[strtolower($pay)] !!}</span>@endif
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </footer>

    <div class="catalog-filter-layer" x-show="filterDrawerOpen" x-cloak @keydown.escape.window="filterDrawerOpen=false" role="dialog" aria-modal="true" aria-label="Filtros del catálogo">
        <div class="catalog-filter-overlay" @click="filterDrawerOpen=false"></div>
        <aside class="catalog-filter-drawer">
            <div class="catalog-filter-drawer-head"><strong>Filtrar productos</strong><button class="icon-button" type="button" @click="filterDrawerOpen=false" aria-label="Cerrar filtros"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"></path></svg></button></div>
            <div class="catalog-filter-drawer-body"><x-computienda.catalog-filters :categories="$categories" :catalog-products="$catalogProducts" filter-scope="mobile" /></div>
            <div class="catalog-filter-drawer-foot"><button type="button" @click="clearAllFilters()">Limpiar</button><button type="button" @click="filterDrawerOpen=false">Ver <span x-text="catalogProducts.length"></span> productos</button></div>
        </aside>
    </div>

    <div class="drawer-layer" x-show="cartOpen" x-cloak @keydown.escape.window="cartOpen=false" role="dialog" aria-modal="true" aria-label="Carrito">
        <div class="drawer-backdrop" @click="cartOpen=false"></div><aside class="cart-drawer">
            <div class="drawer-head"><h2>{{ $quoteMode ? 'Mi cotización' : $cartTitle }}</h2><button class="icon-button" type="button" @click="cartOpen=false" aria-label="Cerrar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 6 12 12M18 6 6 18"></path></svg></button></div>
            <div class="drawer-content"><template x-if="cart.length===0"><p class="cart-empty">{{ $cartEmpty }}</p></template><template x-for="item in cart" :key="item.id"><div class="cart-item"><div><strong x-text="item.nombre"></strong><small x-text="money(item.precio)"></small></div><div class="quantity"><button type="button" @click="decrease(item.id)">−</button><span x-text="item.cantidad"></span><button type="button" @click="increase(item.id)">+</button></div><button class="remove" type="button" @click="remove(item.id)" aria-label="Eliminar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13"></path></svg></button></div></template></div>
            <div class="drawer-footer" x-show="cart.length>0">@unless($hidePrices)<div class="cart-total"><span>Total</span><span x-text="money(total())"></span></div>@endunless<button class="button button-primary" type="button">{{ $checkoutText }}</button><p class="drawer-note">Confirmaremos disponibilidad, entrega y condiciones antes de procesar tu solicitud.</p></div>
        </aside>
    </div>

    <script>
        const COMPUTIENDA_PRODUCTS = @json($catalogProducts);
        const COMPUTIENDA_CATEGORIES = @json($catalogCategoryIndex);

        function professionalStore(){
            const key=@js('bixo_store_cart_'.$project->id);
            return {
                cartOpen:false,
                filterDrawerOpen:false,
                query:'',
                filterCat:'',
                filterSubCat:'',
                filterInStock:false,
                filterOnSale:false,
                priceMin:0,
                priceMax:1000,
                maxPrice:1000,
                sortBy:'default',
                catSearch:'',
                catOpen:{},
                soldOutText:@js($settings['catalog_badge_sold_out'] ?? 'Agotado'),
                cartButtonText:@js($quoteMode ? 'Agregar a cotización' : $cartText),
                cart:[],

                init(){
                    try {
                        const saved=JSON.parse(localStorage.getItem(key)||'[]');
                        this.cart=Array.isArray(saved)?saved:[];
                    } catch(e) {
                        this.cart=[];
                    }
                    this.maxPrice=Math.ceil((COMPUTIENDA_PRODUCTS.reduce((max,product)=>Math.max(max,Number(product.price)||0),0)||1000)/10)*10;
                    this.priceMax=this.maxPrice;
                    this.$watch('cart',value=>localStorage.setItem(key,JSON.stringify(value)),{deep:true});
                    // Aplicar filtro desde la URL (?category=ID) al llegar desde el mega-menú
                    try {
                        const cid = new URLSearchParams(location.search).get('category');
                        if (cid) this.applyCategoryFromUrl(String(cid));
                    } catch(e){}
                },
                // Detecta si el id es categoría raíz o subcategoría y aplica el filtro correcto
                applyCategoryFromUrl(id){
                    const cat = COMPUTIENDA_CATEGORIES.find(c=>String(c.id)===id);
                    if(!cat){ this.filterCat=id; return; }
                    if(cat.parentId){ // es subcategoría
                        this.filterCat=String(cat.parentId);
                        this.filterSubCat=id;
                        this.catOpen[cat.parentId]=true;
                    } else { // es categoría raíz
                        this.filterCat=id;
                        this.filterSubCat='';
                        this.catOpen[id]=true;
                    }
                },

                normalize(value){
                    return String(value||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
                },
                categoryMatches(name){
                    return !this.catSearch || this.normalize(name).includes(this.normalize(this.catSearch));
                },
                categoryName(id){
                    return COMPUTIENDA_CATEGORIES.find(category=>String(category.id)===String(id))?.name || 'Categoría';
                },
                selectCategory(id){
                    this.filterCat=String(id||'');
                    this.filterSubCat='';
                    if(this.filterCat)this.catOpen[this.filterCat]=true;
                    this.syncCategoryUrl(this.filterCat);
                },
                selectSubCategory(parentId,id){
                    this.filterCat=String(parentId||'');
                    this.filterSubCat=String(id||'');
                    if(this.filterCat)this.catOpen[this.filterCat]=true;
                    this.syncCategoryUrl(this.filterSubCat || this.filterCat);
                },
                syncCategoryUrl(id){
                    try {
                        const url=new URL(window.location.href);
                        if(id)url.searchParams.set('category',String(id));
                        else url.searchParams.delete('category');
                        history.replaceState({},'',url);
                    } catch(e){}
                },
                clampPrices(changed){
                    this.priceMin=Math.max(0,Math.min(Number(this.priceMin)||0,this.maxPrice));
                    this.priceMax=Math.max(0,Math.min(Number(this.priceMax)||0,this.maxPrice));
                    if(this.priceMin>=this.priceMax){
                        if(changed==='max')this.priceMin=Math.max(0,this.priceMax-1);
                        else this.priceMax=Math.min(this.maxPrice,this.priceMin+1);
                    }
                },
                clearAllFilters(){
                    this.query='';
                    this.filterCat='';
                    this.filterSubCat='';
                    this.filterInStock=false;
                    this.filterOnSale=false;
                    this.priceMin=0;
                    this.priceMax=this.maxPrice;
                    this.sortBy='default';
                    this.catSearch='';
                    this.syncCategoryUrl('');
                },
                isDiscounted(product){
                    return Number(product.comparePrice)>Number(product.price);
                },
                discountLabel(product){
                    return this.isDiscounted(product) ? '-'+Math.round((1-Number(product.price)/Number(product.comparePrice))*100)+'%' : '';
                },

                get activeFilterLabel(){
                    if(this.filterSubCat)return this.categoryName(this.filterSubCat);
                    if(this.filterCat)return this.categoryName(this.filterCat);
                    return 'Todos los productos';
                },
                get hasActiveFilters(){
                    return Boolean(this.query || this.filterCat || this.filterSubCat || this.filterInStock || this.filterOnSale || this.priceMin>0 || this.priceMax<this.maxPrice);
                },
                get activeFilterCount(){
                    return Number(Boolean(this.filterCat))+Number(Boolean(this.filterSubCat))+Number(this.filterInStock)+Number(this.filterOnSale)+Number(this.priceMin>0||this.priceMax<this.maxPrice)+Number(Boolean(this.query));
                },
                get catalogProducts(){
                    let products=COMPUTIENDA_PRODUCTS.slice();
                    if(this.filterSubCat)products=products.filter(product=>String(product.categoryId)===String(this.filterSubCat));
                    else if(this.filterCat)products=products.filter(product=>String(product.categoryId)===String(this.filterCat)||String(product.parentId)===String(this.filterCat));
                    if(this.query){
                        const query=this.normalize(this.query);
                        products=products.filter(product=>this.normalize(product.name).includes(query)||this.normalize(product.category).includes(query)||this.normalize(product.sku).includes(query));
                    }
                    if(this.priceMin>0)products=products.filter(product=>Number(product.price)>=this.priceMin);
                    if(this.priceMax<this.maxPrice)products=products.filter(product=>Number(product.price)<=this.priceMax);
                    if(this.filterInStock)products=products.filter(product=>product.stock===null||Number(product.stock)>0);
                    if(this.filterOnSale)products=products.filter(product=>this.isDiscounted(product));
                    if(this.sortBy==='price-asc')products.sort((a,b)=>Number(a.price)-Number(b.price));
                    if(this.sortBy==='price-desc')products.sort((a,b)=>Number(b.price)-Number(a.price));
                    if(this.sortBy==='name')products.sort((a,b)=>a.name.localeCompare(b.name,'es'));
                    return products;
                },

                add(id,nombre,precio){
                    const row=this.cart.find(item=>String(item.id)===String(id));
                    if(row)row.cantidad++;
                    else this.cart.push({id,nombre,precio:Number(precio),cantidad:1});
                    this.cartOpen=true;
                },
                increase(id){const row=this.cart.find(item=>String(item.id)===String(id));if(row)row.cantidad++},
                decrease(id){const row=this.cart.find(item=>String(item.id)===String(id));if(!row)return;row.cantidad--;if(row.cantidad<=0)this.remove(id)},
                remove(id){this.cart=this.cart.filter(item=>String(item.id)!==String(id))},
                itemCount(){return this.cart.reduce((sum,item)=>sum+Number(item.cantidad||0),0)},
                total(){return this.cart.reduce((sum,item)=>sum+Number(item.precio||0)*Number(item.cantidad||0),0)},
                money(value){return @js($currency)+' '+Number(value||0).toLocaleString('es-PE',{minimumFractionDigits:2,maximumFractionDigits:2})}
            }
        }
    </script>

    {{-- Botón flotante de WhatsApp con logotipo oficial --}}
    @if($waFloatShow && $whatsapp)
    <a href="https://wa.me/{{ $whatsapp }}?text={{ urlencode($waMsg) }}"
       target="_blank"
       rel="noopener"
       title="{{ $waTooltip }}"
       aria-label="Contactar por WhatsApp"
       class="official-whatsapp-float wa-pos-{{ $waFloatPos }}">
        <svg viewBox="0 0 32 32" width="31" height="31" aria-hidden="true" focusable="false">
            <path fill="#ffffff" d="M27.6 4.4A15.5 15.5 0 0 0 16.5 0C7.9 0 .9 7 .9 15.6c0 2.7.7 5.4 2.1 7.7L0 32l8.9-2.9a15.4 15.4 0 0 0 7.4 1.9h.1c8.6 0 15.6-7 15.6-15.6 0-4.1-1.6-8.1-4.4-11zM16.4 28.4h-.1c-2.3 0-4.5-.6-6.4-1.7l-.5-.3-5.3 1.7 1.7-5.2-.3-.5a12.7 12.7 0 1 1 10.9 6z"/>
            <path fill="#ffffff" d="M23.2 18.7c-.4-.2-2.2-1.1-2.6-1.2-.3-.1-.6-.2-.8.2-.2.3-1 1.2-1.2 1.4-.2.2-.4.3-.7.1-.4-.2-1.6-.6-3.1-2-1.2-1-2-2.2-2.3-2.6-.3-.4 0-.6.2-.8.1-.1.3-.4.5-.6.2-.2.2-.3.3-.5.1-.2 0-.4 0-.5 0-.1-.8-2-1.1-2.8-.3-.7-.6-.6-.8-.6h-.7c-.2 0-.6.1-.9.4-.3.4-1.2 1.2-1.2 2.9s1.2 3.4 1.3 3.7c.2.2 2.4 3.7 5.8 5.1.8.4 1.5.6 2 .7.8.2 1.6.2 2.2.1.7-.1 2.2-.9 2.5-1.8.3-.9.3-1.7.2-1.8-.1-.2-.4-.3-.8-.5z"/>
        </svg>
    </a>
    @endif

    <x-public-store-runtime :project="$project" :settings="$settings" :popup="$popup ?? null" :sections="$sections ?? collect()" :about-page="$aboutPage ?? null" :store-view="$storeView ?? 'home'" :own-footer="true" />
</body>
</html>
