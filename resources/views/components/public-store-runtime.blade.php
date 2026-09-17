@props(['project', 'settings' => [], 'popup' => null, 'sections' => collect(), 'aboutPage' => null, 'storeView' => 'home', 'ownFooter' => false, 'ownWhatsapp' => false, 'activeProfile' => null])

@php
    $safeColor = static fn ($value, $fallback) => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : $fallback;
    $enabled = static fn ($key, $default = '1') => (string) ($settings[$key] ?? $default) === '1';
    $assetUrl = static function ($path) {
        if (!$path) return null;
        return str_starts_with((string) $path, 'http://') || str_starts_with((string) $path, 'https://')
            ? $path
            : asset('storage/'.ltrim((string) $path, '/'));
    };

    $primary = $safeColor($settings['primary_color'] ?? null, '#4f46e5');
    $secondary = $safeColor($settings['secondary_color'] ?? null, '#6366f1');
    // Acento: token propio (detalles, CTA, subrayados). Sin configurar cae al primary.
    $accent = $safeColor($settings['accent_color'] ?? null, $primary);
    $headerBg = $safeColor($settings['header_bg_color'] ?? null, '#ffffff');
    $headerText = $safeColor($settings['header_text_color'] ?? null, '#111827');
    $footerBg = $safeColor($settings['footer_bg_color'] ?? null, '#0f172a');
    $footerText = $safeColor($settings['footer_text_color'] ?? null, '#ffffff');

    // ═══ Identidad del perfil de catálogo activo ═══
    // Este runtime pinta el encabezado y el pie con `!important` sobre
    // `--store-header-bg`, una variable paralela a `--header-bg` que usa la
    // plantilla. La plantilla SÍ aplicaba los colores del perfil, pero esta
    // capa los volvía a pisar porque no sabía que existían los perfiles: al
    // entrar en un perfil verde el encabezado seguía cian. Aquí se entera.
    $perfilActivo = $activeProfile;
    if ($perfilActivo) {
        if (!empty($perfilActivo->primary_color)) {
            $primary = $safeColor($perfilActivo->primary_color, $primary);
            $accent  = $primary;
        }
        if (!empty($perfilActivo->secondary_color))  $secondary = $safeColor($perfilActivo->secondary_color, $secondary);
        if (!empty($perfilActivo->header_bg_color))  $headerBg  = $safeColor($perfilActivo->header_bg_color, $headerBg);
        if (!empty($perfilActivo->header_text_color))$headerText= $safeColor($perfilActivo->header_text_color, $headerText);
        if (!empty($perfilActivo->footer_bg_color))  $footerBg  = $safeColor($perfilActivo->footer_bg_color, $footerBg);
    }
    $fontTitle = preg_replace('/[^a-zA-Z0-9 _-]/', '', $settings['font_title'] ?? $settings['font'] ?? 'Inter');
    $fontBody = preg_replace('/[^a-zA-Z0-9 _-]/', '', $settings['font_body'] ?? $settings['font'] ?? 'Inter');
    $radius = ['sharp' => '0px', 'soft' => '8px', 'rounded' => '16px', 'pill' => '9999px'][$settings['border_radius'] ?? 'rounded'] ?? '16px';
    $buttonRadius = ['sharp' => '0px', 'rounded' => '10px', 'pill' => '9999px'][$settings['btn_shape'] ?? 'rounded'] ?? $radius;
    $headerHeight = max(48, min(400, (int) ($settings['header_height'] ?? 72)));
    $logoHeight = max(20, min(300, (int) ($settings['logo_height'] ?? 40)));
    $footerLogoHeight = max(20, min(200, (int) ($settings['footer_logo_height'] ?? 40)));
    $desktopCols = max(1, min(6, (int) ($settings['catalog_cols_desktop'] ?? 4)));
    $mobileCols = max(1, min(3, (int) ($settings['catalog_cols_mobile'] ?? 2)));
    $logoUrl = $assetUrl($settings['logo_url'] ?? null);
    $faviconUrl = $assetUrl($settings['favicon_url'] ?? null);
    $heroImageUrl = $assetUrl($settings['hero_image'] ?? null);
    $heroOverlay = max(0, min(90, (int) ($settings['hero_overlay'] ?? 45))) / 100;
    // Alineacion del hero normalizada UNA vez, con el mismo criterio que
    // usan las plantillas del servidor.
    $alineacionHero = $settings['hero_align'] ?? 'center';
    if (! in_array($alineacionHero, ['left', 'center', 'right'], true)) {
        $alineacionHero = 'center';
    }
    $whatsappCountry = preg_replace('/\D/', '', $settings['quote_whatsapp_country'] ?? '51');
    $whatsapp = preg_replace('/\D/', '', $settings['quote_whatsapp'] ?? $project->whatsapp ?? '');
    if ($whatsapp && $whatsappCountry && !str_starts_with($whatsapp, $whatsappCountry)) $whatsapp = $whatsappCountry.$whatsapp;
    $whatsappMessage = $settings['whatsapp_msg'] ?? $settings['quote_wa_msg'] ?? 'Hola, deseo información sobre sus productos.';
    $acceptedPayments = json_decode($settings['accepted_payments'] ?? '[]', true);
    if (!is_array($acceptedPayments)) $acceptedPayments = [];
    $paymentLabels = [
        'efectivo' => 'Efectivo', 'yape' => 'Yape', 'plin' => 'Plin', 'transferencia' => 'Transferencia bancaria',
        'tarjeta' => 'Tarjeta', 'culqi' => 'Tarjeta en línea', 'mercadopago' => 'Mercado Pago',
    ];
    $checkoutFields = json_decode($settings['checkout_fields'] ?? 'null', true);
    if (!is_array($checkoutFields)) {
        $checkoutFields = [
            'fixed' => [
                'lname' => ['label' => 'Apellido', 'enabled' => true],
                'email' => ['label' => 'Email', 'enabled' => true],
                'dni' => ['label' => 'DNI / RUC', 'enabled' => true],
                'address' => ['label' => 'Dirección', 'enabled' => false],
                'notes' => ['label' => 'Notas', 'enabled' => true],
            ],
            'custom' => [],
        ];
    }
    $managedHero = $sections->firstWhere('component', 'hero');
    $managedHeroContent = $managedHero?->content ?? [];
    $managedHeroSlide = ($managedHeroContent['mode'] ?? 'single') === 'slider'
        ? collect($managedHeroContent['slides'] ?? [])->first(fn ($slide) => $slide['enabled'] ?? false)
        : ($managedHeroContent['single'] ?? []);
    $effectiveHeroTitle = data_get($managedHeroSlide, 'title') ?: ($settings['hero_title'] ?? $project->name);
    $effectiveHeroSubtitle = data_get($managedHeroSlide, 'body') ?: ($settings['hero_subtitle'] ?? null);
    $effectiveHeroImage = $assetUrl(data_get($managedHeroSlide, 'desktop_image') ?: ($settings['hero_image'] ?? null));
    $effectiveHeroCtaText = data_get($managedHeroSlide, 'primary_text') ?: ($settings['hero_cta1_text'] ?? 'Ver catálogo');
    $effectiveHeroCtaUrl = data_get($managedHeroSlide, 'primary_url') ?: '#catalogo';
    $needsFeaturedContent = $sections->contains('component', 'featured');
    $needsCategoryContent = $sections->contains('component', 'categories');
    $sectionProducts = $needsFeaturedContent ? $project->products()->where('is_available', true)->with('mainImage')->orderBy('sort_order')->take(8)->get() : collect();
    $sectionServices = $needsFeaturedContent && $sectionProducts->isEmpty() ? $project->services()->where('is_available', true)->orderBy('sort_order')->take(8)->get() : collect();
    $sectionCategories = $needsCategoryContent ? $project->categories()->where('is_active', true)->whereNull('parent_id')->orderBy('sort_order')->take(12)->get() : collect();
    $footerCategories = $enabled('footer_show_categories')
        ? $project->categories()->where('is_active', true)->whereNull('parent_id')->orderBy('sort_order')->take(8)->get()
        : collect();
    $parseFooterLinks = static fn ($value) => collect(preg_split('/\r\n|\r|\n/', (string) $value))
        ->map(fn ($line) => array_map('trim', explode('|', $line, 2)))
        ->filter(fn ($parts) => filled($parts[0] ?? null));
    $footerPages = $parseFooterLinks($settings['footer_pages'] ?? '');
    $footerStorePages = $parseFooterLinks($settings['footer_store_pages'] ?? '');
    $benefits = collect(range(1, 3))->map(fn ($i) => [
        'icon' => $settings["footer_benefit_{$i}_icon"] ?? null,
        'text' => $settings["footer_benefit_{$i}_text"] ?? null,
    ])->filter(fn ($item) => $item['icon'] || $item['text']);
    $hasPromoCards = filled($settings['banner1_title'] ?? null) || filled($settings['banner2_title'] ?? null);
    $hasSplitCards = filled($settings['split_left_title'] ?? null) || filled($settings['split_right_title'] ?? null);
    $hasTrustStrip = collect(range(1, 4))->contains(fn ($i) => filled($settings["trust_text_{$i}"] ?? null));
    $hasTabs = collect(range(1, 3))->contains(fn ($i) => filled($settings["tab{$i}_label"] ?? null));

    $runtimeConfig = [
        'projectName' => $project->name,
        'logo' => $logoUrl,
        'favicon' => $faviconUrl,
        'heroTitle' => $effectiveHeroTitle,
        'heroSubtitle' => $effectiveHeroSubtitle,
        'heroBadge' => $settings['hero_badge'] ?? null,
        'heroImage' => $effectiveHeroImage,
        'heroBackground' => $safeColor($settings['hero_bg_color'] ?? null, $primary),
        'heroOverlay' => $heroOverlay,
        // Mismo criterio que las plantillas del servidor: sin lista blanca, un
        // valor invalido viajaba crudo al cliente y acababa en
        // `hero.style.textAlign` (linea ~584), donde el navegador lo ignora. No
        // es inyectable —asignar a una propiedad CSS concreta no deja escapar—
        // pero servidor y cliente discrepaban: uno caia a su respaldo y el otro
        // se quedaba sin alineacion.
        // Se lee UNA vez: comprobar con `?? 'center'` y luego volver a acceder
        // a la clave revienta cuando no existe (el respaldo pasa la lista
        // blanca y el segundo acceso ya no encuentra nada).
        'heroAlign' => $alineacionHero,
        'heroHeight' => $settings['hero_height'] ?? 'medium',
        'cta1Show' => $enabled('hero_cta1_show'),
        'cta1Text' => $effectiveHeroCtaText,
        'cta2Show' => $enabled('hero_cta2_show', '0'),
        'cta2Text' => $settings['hero_cta2_text'] ?? null,
        'announcement' => $settings['announcement_text'] ?? null,
        'announcementBackground' => $safeColor($settings['announcement_bg'] ?? null, $secondary),
        'catalogTitle' => $settings['catalog_section_title'] ?? null,
        'currency' => $settings['currency_symbol'] ?? 'S/',
        'cardStyle' => $settings['card_style'] ?? 'minimal',
        'filters' => [
            'price' => $enabled('catalog_filter_price'),
            'categories' => $enabled('catalog_filter_cats'),
            'sale' => $enabled('catalog_filter_sale'),
            'search' => $enabled('catalog_filter_search'),
        ],
        'badges' => [
            'sale' => $settings['catalog_badge_sale'] ?? 'OFERTA',
            'new' => $settings['catalog_badge_new'] ?? 'NUEVO',
            'featured' => $settings['catalog_badge_featured'] ?? 'DESTACADO',
            'soldOut' => $settings['catalog_badge_sold_out'] ?? 'AGOTADO',
        ],
        'showRatings' => $enabled('catalog_show_ratings', '0'),
        'quickView' => $enabled('catalog_quick_view'),
        'showSku' => $enabled('catalog_show_sku', '0'),
        'showStock' => $enabled('catalog_show_stock'),
        'wholesale' => $enabled('wholesale_enabled', '0'),
        'cartText' => $settings['btn_cart_text'] ?? null,
        'quoteText' => $settings['btn_quote_text'] ?? null,
        'checkoutText' => $settings['btn_checkout_text'] ?? null,
        'sendQuoteText' => $settings['btn_send_quote_text'] ?? null,
        'showCartIcon' => $enabled('btn_show_icon'),
        'floatCart' => $enabled('float_cart_show'),
        'floatCartPosition' => $settings['float_cart_pos'] ?? 'bottom-right',
        'floatWhatsapp' => $enabled('float_wa_show'),
        'floatWhatsappPosition' => $settings['float_wa_pos'] ?? 'bottom-right',
        'floatWhatsappTooltip' => $settings['float_wa_tooltip'] ?? '¿Necesitas ayuda?',
        'searchPlaceholder' => $settings['txt_search_placeholder'] ?? null,
        'noResultsText' => $settings['txt_no_results'] ?? null,
        'viewMoreText' => $settings['txt_view_more'] ?? null,
        'allCategoriesText' => $settings['txt_all_cats'] ?? null,
        'storeMode' => $settings['store_mode'] ?? 'direct',
        'quotePrice' => $settings['quote_price_display'] ?? 'show',
        'whatsappNumber' => $whatsapp,
        'whatsappMessage' => $whatsappMessage,
        'cartTitle' => $settings['cart_title'] ?? null,
        'cartEmptyMessage' => $settings['cart_empty_msg'] ?? null,
        'shippingEnabled' => $enabled('shipping_enabled', '0'),
        'shippingCost' => (float) ($settings['shipping_cost'] ?? 0),
        'shippingFreeFrom' => (float) ($settings['shipping_free_from'] ?? 0),
        'requireAddress' => $enabled('require_address', '0'),
        'showFlashSale' => $enabled('show_flash_sale'),
        'showTestimonials' => $enabled('show_testimonials'),
        'showNewsletter' => $enabled('show_newsletter'),
        'acceptedPayments' => $acceptedPayments,
        'paymentDetails' => [
            'yapeNumber' => $settings['payment_yape_number'] ?? null,
            'yapeName' => $settings['payment_yape_name'] ?? null,
            'yapeQr' => $assetUrl($settings['payment_yape_qr'] ?? null),
            'plinNumber' => $settings['payment_plin_number'] ?? null,
            'bankDetails' => $settings['payment_bank_details'] ?? null,
            'manualInstructions' => $settings['payment_manual_instructions'] ?? null,
            'bcp' => $settings['payment_bank_bcp'] ?? null,
            'interbank' => $settings['payment_bank_interbank'] ?? null,
            'bbva' => $settings['payment_bank_bbva'] ?? null,
            'nacion' => $settings['payment_bank_nacion'] ?? null,
            'scotiabank' => $settings['payment_bank_scotiabank'] ?? null,
            'culqiPublicKey' => $settings['culqi_public_key'] ?? null,
            'culqiMode' => $settings['culqi_mode'] ?? 'test',
            'manualEnabled' => $enabled('payment_manual_enabled', '0'),
            'culqiEnabled' => $enabled('culqi_enabled', '0'),
            'mercadoPagoEnabled' => $enabled('mp_enabled', '0'),
        ],
        'login' => [
            'backgroundType' => $settings['login_bg_type'] ?? 'gradient',
            'color1' => $safeColor($settings['login_color1'] ?? null, $primary),
            'color2' => $safeColor($settings['login_color2'] ?? null, $secondary),
            'backgroundImage' => $assetUrl($settings['login_bg_image'] ?? null),
            'heading' => $settings['login_heading'] ?? null,
            'subtitle' => $settings['login_subtitle'] ?? null,
        ],
        'orderUrl' => route('public.order', $project->slug),
        'csrfToken' => csrf_token(),
        'ageGate' => $enabled('age_gate', '0'),
        'checkoutFields' => $checkoutFields,
        'seoTitle' => $settings['seo_title'] ?? null,
        'seoDescription' => $settings['seo_description'] ?? null,
        'seoKeywords' => $settings['seo_keywords'] ?? null,
    ];
@endphp

<style id="bixo-global-store-settings">
    /* Las fuentes ya las carga la plantilla con <link> en el <head>. Este
       @import pedia las mismas familias otra vez y, al ir dentro de <style>,
       bloqueaba el pintado hasta resolverse (Inter llegaba a pedirse 4 veces). */
    :root {
        --store-primary: {{ $primary }}; --store-secondary: {{ $secondary }};
        --primary: {{ $primary }}; --secondary: {{ $secondary }}; --accent: {{ $accent }};
        --brand: {{ $primary }}; --brand-primary: {{ $primary }}; --color-primary: {{ $primary }};
        --azul: {{ $primary }}; --rojo: {{ $secondary }};
        --color-primario: {{ $primary }}; --color-secundario: {{ $secondary }};
        --store-radius: {{ $radius }}; --radius: {{ $radius }}; --radius-md: {{ $radius }};
        --store-button-radius: {{ $buttonRadius }}; --store-header-height: {{ $headerHeight }}px;
        --store-logo-height: {{ $logoHeight }}px; --store-header-bg: {{ $headerBg }};
        --store-header-text: {{ $headerText }}; --store-footer-bg: {{ $footerBg }};
        --store-footer-text: {{ $footerText }};
    }
    body { font-family: "{{ $fontBody }}", system-ui, sans-serif !important; }
    h1,h2,h3,h4,h5,h6 { font-family: "{{ $fontTitle }}", system-ui, sans-serif !important; }
    header { min-height: var(--store-header-height); background-color: var(--store-header-bg) !important; color: var(--store-header-text) !important; }
    header a, header button, header svg { color: inherit; }
    header img { max-height: var(--store-logo-height); }
    button,.btn,.button,[class*="btn-"] { border-radius: var(--store-button-radius) !important; }
    .bg-primary,.bg-indigo-600,.bg-azul { background-color: var(--store-primary) !important; }
    .text-primary,.text-indigo-600,.text-indigo-700,.text-azul { color: var(--store-primary) !important; }
    .bixo-runtime-block { box-sizing: border-box; width: 100%; }
    .bixo-runtime-container { box-sizing: border-box; width: min(1180px, calc(100% - 32px)); margin: 0 auto; }
    .bixo-runtime-announcement { padding: 9px 16px; text-align: center; color: white; font-size: 14px; font-weight: 700; }
    #bixo-runtime-hero { box-sizing: border-box; min-height: 380px; padding: 70px 24px; color: white; background: var(--store-primary); background-size: cover; background-position: center; display: flex; align-items: center; }
    #bixo-runtime-hero[hidden] { display: none !important; }
    .bixo-runtime-hero-content { width: min(860px,100%); margin: 0 auto; }
    .bixo-runtime-hero-badge { display: inline-flex; margin-bottom: 12px; padding: 6px 11px; border-radius: 999px; background: rgba(255,255,255,.16); font-size: 12px; font-weight: 800; }
    #bixo-runtime-hero h1, #bixo-runtime-hero .bixo-runtime-hero-title { margin: 0; font-size: clamp(34px,6vw,68px); line-height: 1.05; }
    #bixo-runtime-hero p { max-width: 680px; margin: 16px auto 0; font-size: clamp(16px,2vw,21px); opacity: .9; }
    .bixo-runtime-hero-actions { display: flex; justify-content: inherit; gap: 10px; margin-top: 24px; }
    .bixo-runtime-hero-action { display: inline-flex; padding: 12px 20px; border-radius: var(--store-button-radius); background: var(--store-primary); color: white; font-weight: 800; text-decoration: none; }
    .bixo-runtime-hero-action.secondary { background: white; color: #111827; }
    .bixo-runtime-promos { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 16px; padding: 32px 0; }
    .bixo-runtime-promo { min-height: 150px; padding: 28px; display: flex; flex-direction: column; justify-content: center; border-radius: var(--store-radius); background: color-mix(in srgb, var(--store-primary) 10%, white); border: 1px solid color-mix(in srgb, var(--store-primary) 22%, transparent); }
    .bixo-runtime-promo:nth-child(even) { background: color-mix(in srgb, var(--store-secondary) 12%, white); }
    .bixo-runtime-promo h2 { margin: 0; font-size: clamp(21px,3vw,32px); }
    .bixo-runtime-promo p { margin: 8px 0 0; opacity: .72; }
    .bixo-runtime-trust { display: grid; grid-template-columns: repeat(4,minmax(0,1fr)); gap: 12px; padding: 20px 0; }
    .bixo-runtime-trust-item { padding: 14px; text-align: center; border: 1px solid #e5e7eb; border-radius: var(--store-radius); background: white; }
    .bixo-runtime-tabs { display: flex; gap: 8px; padding: 18px 0; overflow-x: auto; }
    .bixo-runtime-tab { flex: 0 0 auto; padding: 9px 16px; color: var(--store-primary); border: 1px solid currentColor; border-radius: var(--store-button-radius); font-weight: 700; text-decoration: none; }
    .store-global-section { padding: 48px 16px; text-align: center; border-top: 1px solid #eef2f7; background: white; }
    .store-global-section img { display: block; max-width: min(100%,760px); max-height: 420px; margin: 20px auto 0; object-fit: cover; border-radius: var(--store-radius); }
    .store-global-section h2 { margin: 8px 0; font-size: clamp(24px,3vw,38px); }
    .store-global-section p { max-width: 760px; margin: 8px auto; white-space: pre-line; color: #64748b; }
    .bixo-runtime-content-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 16px; width: min(1180px,100%); margin: 26px auto 0; text-align: left; }
    .bixo-runtime-content-card { display: block; padding: 14px; color: inherit; border: 1px solid #e5e7eb; border-radius: var(--store-radius); background: white; text-decoration: none; }
    .bixo-runtime-content-card img { width: 100%; height: 190px; margin: 0 0 12px; object-fit: contain; }
    .bixo-runtime-content-card strong { display: block; }
    .bixo-runtime-category-list { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 24px; }
    .bixo-runtime-category { padding: 10px 16px; color: var(--store-primary); border: 1px solid currentColor; border-radius: var(--store-button-radius); font-weight: 700; text-decoration: none; }
    .store-global-action { display: inline-flex; margin-top: 18px; padding: 12px 20px; color: white; background: var(--store-primary); border-radius: var(--store-button-radius); font-weight: 700; text-decoration: none; }
    .store-runtime-product-grid { grid-template-columns: repeat({{ $mobileCols }},minmax(0,1fr)) !important; }
    .store-runtime-product-card { border-radius: var(--store-radius) !important; }
    .store-runtime-product-card[data-card-style="sharp"] { border-radius: 0 !important; box-shadow: none !important; }
    .store-runtime-product-card[data-card-style="bold"] { border: 2px solid var(--store-primary) !important; }
    .store-runtime-product-card[data-card-style="dark"] { background: #111827 !important; color: white !important; }
    #bixo-runtime-footer { padding: 42px 16px 20px; background: var(--store-footer-bg); color: var(--store-footer-text); }
    #bixo-runtime-footer a { color: inherit; text-decoration: none; opacity: .86; }
    .bixo-runtime-footer-grid { display: grid; grid-template-columns: 1.4fr repeat(3,1fr); gap: 28px; }
    .bixo-runtime-footer-title { margin: 0 0 12px; font-weight: 800; }
    .bixo-runtime-footer-list { display: grid; gap: 8px; font-size: 14px; }
    .bixo-runtime-benefits { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 10px; margin-bottom: 30px; }
    .bixo-runtime-benefit { padding: 14px; text-align: center; background: rgba(255,255,255,.08); border-radius: var(--store-radius); }
    .bixo-runtime-footer-bottom { margin-top: 30px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,.14); text-align: center; font-size: 12px; opacity: .7; }
    [data-store-native-section][hidden] { display: none !important; }
    #bixo-store-popup .bg-indigo-600 { background: var(--store-primary) !important; }
    #bixo-runtime-checkout { position: fixed; inset: 0; z-index: 110; display: none; align-items: center; justify-content: center; padding: 18px; background: rgba(2,6,23,.68); }
    #bixo-runtime-checkout.is-open { display: flex; }
    .bixo-runtime-checkout-card { width: min(560px,100%); max-height: 92vh; overflow: auto; padding: 24px; border-radius: var(--store-radius); background: white; color: #111827; box-shadow: 0 24px 80px rgba(0,0,0,.32); }
    .bixo-runtime-checkout-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 12px; }
    .bixo-runtime-checkout-field { display: grid; gap: 5px; font-size: 13px; font-weight: 700; }
    .bixo-runtime-checkout-field.full { grid-column: 1/-1; }
    .bixo-runtime-checkout-field input,.bixo-runtime-checkout-field textarea,.bixo-runtime-checkout-field select { width: 100%; box-sizing: border-box; padding: 11px 12px; border: 1px solid #d1d5db; border-radius: 10px; background: white; color: #111827; font: inherit; }
    .bixo-runtime-payment-list { display: flex; flex-wrap: wrap; gap: 8px; }
    .bixo-runtime-payment-list label { padding: 9px 11px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 13px; cursor: pointer; }
    @media (min-width: 768px) { .store-runtime-product-grid { grid-template-columns: repeat({{ $desktopCols }},minmax(0,1fr)) !important; } }
    @media (max-width: 767px) {
        .bixo-runtime-promos,.bixo-runtime-trust,.bixo-runtime-benefits,.bixo-runtime-footer-grid { grid-template-columns: 1fr; }
        .bixo-hide-mobile { display: none !important; }
    }
    @media (min-width: 768px) and (max-width: 1023px) { .bixo-hide-tablet { display: none !important; } }
    @media (min-width: 1024px) { .bixo-hide-desktop { display: none !important; } }
    @media (min-width: 768px) { .bixo-runtime-content-grid { grid-template-columns: repeat(4,minmax(0,1fr)); } }
</style>

{{-- La barra de anuncio del runtime no se muestra si la plantilla trae la suya
     (computienda/ecommerce) para evitar la barra duplicada. --}}
@if(filled($settings['announcement_text'] ?? null) && ! $ownFooter && ! $ownWhatsapp)
    <div id="bixo-runtime-announcement" class="bixo-runtime-announcement" style="background:{{ $safeColor($settings['announcement_bg'] ?? null, $secondary) }}">{{ $settings['announcement_text'] }}</div>
@endif

<section id="bixo-runtime-hero" hidden>
    <div class="bixo-runtime-hero-content">
        @if(filled($settings['hero_badge'] ?? null))<span class="bixo-runtime-hero-badge">{{ $settings['hero_badge'] }}</span>@endif
        {{-- H2, no H1: esta seccion va `hidden` como respaldo del hero, pero
             Google la lee igual y la pagina terminaba con dos H1 compitiendo.
             El H1 real es el titulo del hero de la plantilla. --}}
        <h2 class="bixo-runtime-hero-title">{{ $effectiveHeroTitle }}</h2>
        @if(filled($effectiveHeroSubtitle))<p>{{ $effectiveHeroSubtitle }}</p>@endif
        <div class="bixo-runtime-hero-actions">
            @if($enabled('hero_cta1_show'))<a class="bixo-runtime-hero-action" href="{{ $effectiveHeroCtaUrl }}">{{ $effectiveHeroCtaText }}</a>@endif
            @if($enabled('hero_cta2_show', '0'))<a class="bixo-runtime-hero-action secondary" href="{{ $whatsapp ? 'https://wa.me/'.$whatsapp.'?text='.urlencode($whatsappMessage) : route('public.contact', $project->slug) }}">{{ $settings['hero_cta2_text'] ?? 'Contáctanos' }}</a>@endif
        </div>
    </div>
</section>

{{-- Secciones de contenido del runtime (banners/promos/countdown/hero extra).
     Las plantillas autónomas (ecommerce, computienda) manejan su propio
     contenido vía el Diseñador, así que aquí NO se inyecta nada para ellas. --}}
@unless($ownFooter || $ownWhatsapp)
<div id="bixo-runtime-home-extras" class="bixo-runtime-block">
    @if($hasPromoCards)
        <div class="bixo-runtime-container bixo-runtime-promos" data-store-native-section="announcements">
            @foreach([1,2] as $i)
                @if(filled($settings["banner{$i}_title"] ?? null) || filled($settings["banner{$i}_sub"] ?? null))
                    <article class="bixo-runtime-promo"><h2>{{ $settings["banner{$i}_title"] ?? '' }}</h2><p>{{ $settings["banner{$i}_sub"] ?? '' }}</p></article>
                @endif
            @endforeach
        </div>
    @endif
    @if(filled($settings['countdown_end'] ?? null))
        <div class="bixo-runtime-announcement" data-store-native-section="daily_offer" data-countdown-end="{{ $settings['countdown_end'] }}" style="background:var(--store-primary)"><span>{{ $settings['countdown_label'] ?? 'La promoción termina en' }}</span> <strong data-countdown-value></strong></div>
    @endif
    @if($hasSplitCards)
        <div class="bixo-runtime-container bixo-runtime-promos" data-store-native-section="announcements">
            @foreach(['left','right'] as $side)
                @if(filled($settings["split_{$side}_title"] ?? null) || filled($settings["split_{$side}_sub"] ?? null))
                    <article class="bixo-runtime-promo"><h2>{{ $settings["split_{$side}_title"] ?? '' }}</h2><p>{{ $settings["split_{$side}_sub"] ?? '' }}</p></article>
                @endif
            @endforeach
        </div>
    @endif
    @if($hasTrustStrip && $enabled('show_trust_strip'))
        <div class="bixo-runtime-container bixo-runtime-trust" data-store-native-section="benefits">
            @foreach(range(1,4) as $i)
                @if(filled($settings["trust_text_{$i}"] ?? null))
                    <div class="bixo-runtime-trust-item"><span>{{ $settings["trust_icon_{$i}"] ?? '✓' }}</span> <strong>{{ $settings["trust_text_{$i}"] }}</strong></div>
                @endif
            @endforeach
        </div>
    @endif
    @if($hasTabs)
        <nav class="bixo-runtime-container bixo-runtime-tabs" data-store-native-section="featured_categories" aria-label="Secciones destacadas">
            @foreach(range(1,3) as $i)
                @if(filled($settings["tab{$i}_label"] ?? null))<a class="bixo-runtime-tab" href="#catalogo">{{ $settings["tab{$i}_label"] }}</a>@endif
            @endforeach
        </nav>
    @endif
</div>
@endunless

{{-- Bloques del constructor (Beneficios, Promociones, etc.): SOLO en la home.
     Las plantillas autónomas (computienda/ecommerce) renderizan sus PROPIAS
     secciones, así que el runtime no debe volver a inyectarlas (evita el doble
     "Explora por categoría", "Promociones", etc.). --}}
@if($storeView === 'home' && ! $ownFooter && ! $ownWhatsapp)
<x-storefront-home-skins :settings="$settings" />
<x-storefront-home-sections :project="$project" :settings="$settings"
    :sections="\App\Storefront\HomePresets::ordenar($sections, $settings['home_template'] ?? null)" />
@endif

{{-- Compatibilidad del renderer anterior: no genera duplicados con el constructor visual. --}}
@foreach(collect() as $section)
    @continue($section->component === 'hero')
    @php
        $content = $section->content ?? [];
        $deviceClass = collect([
            !$section->show_mobile ? 'bixo-hide-mobile' : null,
            !$section->show_tablet ? 'bixo-hide-tablet' : null,
            !$section->show_desktop ? 'bixo-hide-desktop' : null,
        ])->filter()->implode(' ');
    @endphp
    <section class="store-global-section {{ $deviceClass }}" data-component="{{ $section->component }}">
        <small style="color:var(--store-primary);font-weight:800;text-transform:uppercase">{{ str_replace('_', ' ', $section->component) }}</small>
        @if(data_get($content, 'title'))<h2>{{ data_get($content, 'title') }}</h2>@endif
        @if(data_get($content, 'body'))<p>{{ data_get($content, 'body') }}</p>@endif
        @if($section->component === 'featured' && ($sectionProducts->isNotEmpty() || $sectionServices->isNotEmpty()))
            <div class="bixo-runtime-content-grid">
                @foreach($sectionProducts as $product)
                    <a class="bixo-runtime-content-card" href="{{ route('public.product', [$project->slug, $product->id]) }}">@if($product->main_image_url)<img src="{{ $product->main_image_url }}" alt="{{ $product->name }}">@endif<strong>{{ $product->name }}</strong><span style="color:var(--store-primary);font-weight:800">{{ $settings['currency_symbol'] ?? 'S/' }} {{ number_format((float) $product->price, 2) }}</span></a>
                @endforeach
                @foreach($sectionServices as $service)
                    <article class="bixo-runtime-content-card"><strong>{{ $service->name }}</strong>@if($service->description)<p>{{ $service->description }}</p>@endif<span style="color:var(--store-primary);font-weight:800">{{ $settings['currency_symbol'] ?? 'S/' }} {{ number_format((float) $service->price, 2) }}</span></article>
                @endforeach
            </div>
        @elseif($section->component === 'categories' && $sectionCategories->isNotEmpty())
            <div class="bixo-runtime-category-list">@foreach($sectionCategories as $category)<a class="bixo-runtime-category" href="{{ route('public.catalog', $project->slug) }}#cat-{{ $category->id }}">{{ $category->name }}</a>@endforeach</div>
        @endif
        @if(data_get($content, 'image'))<img src="{{ $assetUrl(data_get($content, 'image')) }}" alt="{{ data_get($content, 'title') }}">@endif
        @if(data_get($content, 'button_url'))<a href="{{ data_get($content, 'button_url') }}" class="store-global-action">{{ data_get($content, 'button_text') ?: 'Ver más' }}</a>@endif
    </section>
@endforeach

{{-- El pie del runtime SOLO cuando la plantilla no trae el suyo.
     Hasta hoy se pintaba siempre: computienda ya pasa `own-footer`, elegia su
     composicion en el Constructor... y debajo aparecia igualmente esta banda
     generica. Dos pies en cada tienda, y todas terminaban pareciendose. --}}
@if(! $ownFooter)
<footer id="bixo-runtime-footer">
    <div class="bixo-runtime-container">
        @if($enabled('footer_show_benefits') && $benefits->isNotEmpty())
            <div class="bixo-runtime-benefits">@foreach($benefits as $benefit)<div class="bixo-runtime-benefit"><span>{{ $benefit['icon'] ?: '✓' }}</span> <strong>{{ $benefit['text'] }}</strong></div>@endforeach</div>
        @endif
        <div class="bixo-runtime-footer-grid">
            <div>
                @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $project->name }}" style="max-height:{{ $footerLogoHeight }}px;max-width:220px">@else<h3 class="bixo-runtime-footer-title">{{ $project->name }}</h3>@endif
                @if(filled($settings['footer_tagline'] ?? null))<p style="max-width:380px;opacity:.76">{{ $settings['footer_tagline'] }}</p>@endif
            </div>
            @if($footerCategories->isNotEmpty())
                <div><h3 class="bixo-runtime-footer-title">Categorías</h3><div class="bixo-runtime-footer-list">@foreach($footerCategories as $category)<a href="{{ route('public.catalog', $project->slug) }}#cat-{{ $category->id }}">{{ $category->name }}</a>@endforeach</div></div>
            @endif
            <div><h3 class="bixo-runtime-footer-title">Información</h3><div class="bixo-runtime-footer-list">@if($aboutPage)<a href="{{ route('public.about', $project->slug) }}">Nosotros</a>@endif<a href="{{ route('public.contact', $project->slug) }}">Contacto</a>@foreach($footerPages->concat($footerStorePages) as $page)<a href="{{ $page[1] ?? '#' }}">{{ $page[0] }}</a>@endforeach<a href="{{ route('public.complaints', $project->slug) }}">Libro de Reclamaciones</a></div></div>
            <div>
                <h3 class="bixo-runtime-footer-title">Contacto</h3>
                <div class="bixo-runtime-footer-list">
                    @if(filled($settings['contact_phone'] ?? $project->phone))<span>{{ $settings['contact_phone'] ?? $project->phone }}</span>@endif
                    @if(filled($settings['contact_email'] ?? null))<a href="mailto:{{ $settings['contact_email'] }}">{{ $settings['contact_email'] }}</a>@endif
                    @if(filled($settings['business_hours'] ?? null))<span>{{ $settings['business_hours'] }}</span>@endif
                    @if($enabled('footer_show_address') && filled($project->address ?? null))<span>{{ $project->address }}</span>@endif
                </div>
                @if($enabled('footer_show_social'))
                    <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:14px">@foreach(['facebook_url'=>'Facebook','instagram_url'=>'Instagram','tiktok_url'=>'TikTok','youtube_url'=>'YouTube','twitter_url'=>'X','linkedin_url'=>'LinkedIn'] as $key=>$label)@if(filled($settings[$key] ?? null))<a href="{{ $settings[$key] }}" target="_blank" rel="noopener">{{ $label }}</a>@endif @endforeach</div>
                @endif
            </div>
        </div>
        @if($enabled('footer_show_newsletter') && filled($settings['footer_newsletter_title'] ?? null))
            <div style="margin-top:28px;text-align:center"><strong>{{ $settings['footer_newsletter_title'] }}</strong>@if(filled($settings['footer_newsletter_url'] ?? null)) <a class="store-global-action" style="margin-left:12px" href="{{ $settings['footer_newsletter_url'] }}">Suscribirme</a>@endif</div>
        @endif
        <div class="bixo-runtime-footer-bottom">{{ $settings['footer_copyright'] ?? ('© '.date('Y').' '.$project->name.'. Todos los derechos reservados.') }} @if(filled($settings['footer_dev_text'] ?? null)) · {{ $settings['footer_dev_text'] }} @endif · Desarrollado por <a href="https://eskalagroup.com/" target="_blank" rel="noopener" style="color:inherit;font-weight:700;text-decoration:underline">Eskala</a></div>
    </div>
</footer>
@endif

{{-- El WhatsApp flotante del runtime NO se renderiza cuando la plantilla trae
     el suyo propio (ownFooter/ownWhatsapp): evita el botón duplicado. --}}
@if(!$ownFooter && !$ownWhatsapp && $enabled('float_wa_show') && $whatsapp)
    <a id="bixo-runtime-whatsapp" href="https://wa.me/{{ $whatsapp }}?text={{ urlencode($whatsappMessage) }}" target="_blank" rel="noopener" aria-label="WhatsApp" title="{{ $settings['float_wa_tooltip'] ?? '¿Necesitas ayuda?' }}" style="position:fixed;z-index:70;bottom:22px;{{ ($settings['float_wa_pos'] ?? 'bottom-right') === 'bottom-left' ? 'left:22px' : 'right:22px' }};width:56px;height:56px;border-radius:999px;background:#25d366;color:white;display:grid;place-items:center;font-size:26px;text-decoration:none;box-shadow:0 10px 28px rgba(0,0,0,.22)">✆</a>
@endif

@if($enabled('age_gate', '0'))
    <div id="bixo-runtime-age-gate" role="dialog" aria-modal="true" aria-labelledby="bixo-age-title" style="position:fixed;inset:0;z-index:120;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(2,6,23,.82)">
        <div style="width:min(440px,100%);padding:32px;border-radius:var(--store-radius);background:white;color:#111827;text-align:center;box-shadow:0 24px 80px rgba(0,0,0,.4)">
            <div style="font-size:44px">🔞</div><h2 id="bixo-age-title" style="margin:10px 0">Verificación de edad</h2><p>Debes ser mayor de edad para ingresar a esta tienda.</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:22px"><button type="button" data-age-decline style="padding:12px;border:1px solid #d1d5db;background:white">Salir</button><button type="button" data-age-accept style="padding:12px;border:0;background:var(--store-primary);color:white;font-weight:800">Soy mayor de edad</button></div>
        </div>
    </div>
@endif

<div id="bixo-runtime-checkout" role="dialog" aria-modal="true" aria-labelledby="bixo-checkout-title">
    <form class="bixo-runtime-checkout-card" data-bixo-fallback-form>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px"><div><small style="color:var(--store-primary);font-weight:800">{{ (($settings['store_mode'] ?? 'direct') === 'quote') ? 'COTIZACIÓN' : 'FINALIZAR' }}</small><h2 id="bixo-checkout-title" style="margin:3px 0">{{ $settings['cart_title'] ?? ((($settings['store_mode'] ?? 'direct') === 'quote') ? 'Mi cotización' : 'Datos del pedido') }}</h2></div><button type="button" data-bixo-checkout-close aria-label="Cerrar" style="border:0;background:transparent;font-size:25px">×</button></div>
        <p style="margin:8px 0 18px;color:#64748b;font-size:14px">Completa tus datos para registrar el pedido.</p>
        <div class="bixo-runtime-checkout-grid">
            <label class="bixo-runtime-checkout-field"><span>Nombre *</span><input name="client_name" required maxlength="100"></label>
            <label class="bixo-runtime-checkout-field"><span>Celular *</span><input name="client_phone" type="tel" required maxlength="30"></label>
            @foreach($checkoutFields['fixed'] ?? [] as $key => $field)
                @continue(($field['enabled'] ?? true) === false)
                @php $fixedType = in_array($key, ['email'], true) ? 'email' : 'text'; @endphp
                @if($key === 'notes')
                    <label class="bixo-runtime-checkout-field full"><span>{{ $field['label'] ?? 'Notas' }}</span><textarea name="notes" rows="2"></textarea></label>
                @elseif($key === 'address')
                    <label class="bixo-runtime-checkout-field full"><span>{{ $field['label'] ?? 'Dirección' }}{{ $enabled('require_address', '0') ? ' *' : '' }}</span><input name="delivery_address" @required($enabled('require_address', '0'))></label>
                @else
                    <label class="bixo-runtime-checkout-field"><span>{{ $field['label'] ?? ucfirst($key) }}</span><input name="bixo_{{ $key }}" type="{{ $fixedType }}" data-bixo-checkout-field data-bixo-checkout-label="{{ $field['label'] ?? $key }}"></label>
                @endif
            @endforeach
            @if($enabled('require_address', '0') && (($checkoutFields['fixed']['address']['enabled'] ?? false) === false))
                <label class="bixo-runtime-checkout-field full"><span>Dirección de entrega *</span><input name="delivery_address" required></label>
            @endif
            @foreach($checkoutFields['custom'] ?? [] as $field)
                @continue(($field['enabled'] ?? true) === false)
                <label class="bixo-runtime-checkout-field {{ ($field['type'] ?? 'text') === 'textarea' ? 'full' : '' }}"><span>{{ $field['label'] ?? 'Dato adicional' }}{{ ($field['required'] ?? false) ? ' *' : '' }}</span>@if(($field['type'] ?? 'text') === 'textarea')<textarea data-bixo-checkout-field data-bixo-checkout-label="{{ $field['label'] ?? 'Dato adicional' }}" @required($field['required'] ?? false)></textarea>@else<input type="{{ in_array($field['type'] ?? 'text', ['text','number','date'], true) ? $field['type'] : 'text' }}" data-bixo-checkout-field data-bixo-checkout-label="{{ $field['label'] ?? 'Dato adicional' }}" @required($field['required'] ?? false)>@endif</label>
            @endforeach
        </div>
        @if($acceptedPayments)
            <fieldset style="margin:18px 0 0;border:0;padding:0"><legend style="margin-bottom:8px;font-size:13px;font-weight:800">Método de pago</legend><div class="bixo-runtime-payment-list">@foreach($acceptedPayments as $method)<label><input type="radio" name="payment_method" value="{{ $method }}" @checked($loop->first)> {{ $paymentLabels[$method] ?? ucfirst($method) }}</label>@endforeach</div></fieldset>
        @endif
        <div data-bixo-payment-details style="display:none;margin-top:14px;padding:12px;border-radius:10px;background:#f8fafc;font-size:13px;white-space:pre-line"></div>
        <div data-bixo-checkout-message style="display:none;margin-top:14px;padding:10px;border-radius:10px;font-size:13px"></div>
        <button type="submit" style="width:100%;margin-top:18px;padding:13px;border:0;border-radius:var(--store-button-radius);background:var(--store-primary);color:white;font-weight:800">{{ $settings['btn_checkout_text'] ?? 'Realizar pedido' }}</button>
    </form>
</div>

<x-public-popup :popup="$popup" :settings="$settings" />

<script>
(() => {
    const config = @json($runtimeConfig);
    config.ownFooter = @json((bool) $ownFooter);
    window.BixoStoreSettings = config;

    const normalize = value => (value || '').toString().trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const textMatches = (element, patterns) => patterns.some(pattern => normalize(element.textContent).includes(pattern));
    const nearestField = input => input.closest('label,.form-group,.field,.input-group,[class*="field"],[class*="form-"]') || input.parentElement;

    const originalFetch = window.fetch.bind(window);
    window.fetch = async (resource, options = {}) => {
        const url = typeof resource === 'string' ? resource : (resource?.url || '');
        if (/\/(order|quote)(?:\?|$)/.test(url) && typeof options.body === 'string') {
            try {
                const payload = JSON.parse(options.body);
                const extra = {};
                document.querySelectorAll('[data-bixo-checkout-field]').forEach(field => {
                    if (field.value !== '') extra[field.dataset.bixoCheckoutLabel || field.name] = field.value;
                });
                if (Object.keys(extra).length) {
                    payload.checkout_data = extra;
                    const lines = Object.entries(extra).map(([key,value]) => `${key}: ${value}`).join('\n');
                    payload.notes = [payload.notes, lines].filter(Boolean).join('\n');
                    options = {...options, body: JSON.stringify(payload)};
                }
            } catch (_) {}
        }
        return originalFetch(resource, options);
    };

    document.addEventListener('DOMContentLoaded', () => {
        if (config.favicon) {
            let icon = document.querySelector('link[rel~="icon"]');
            if (!icon) { icon = document.createElement('link'); icon.rel = 'icon'; document.head.appendChild(icon); }
            icon.href = config.favicon;
        }
        // La plantilla ya calcula titulo por vista (ficha, tienda, nosotros): si
        // lo marca como propio, no lo pisamos con el titulo global de la tienda.
        const tituloPropio = document.querySelector('title[data-seo="page"]');
        if (config.seoTitle && !tituloPropio) document.title = config.seoTitle;
        const metas = {description: config.seoDescription, keywords: config.seoKeywords};
        Object.entries(metas).forEach(([name,content]) => {
            if (!content) return;
            let meta = document.querySelector(`meta[name="${name}"]`);
            if (!meta) { meta = document.createElement('meta'); meta.name = name; document.head.appendChild(meta); }
            meta.content = content;
        });

        const header = document.querySelector('header');
        if (header && config.logo) {
            let logo = header.querySelector('[class*="logo" i] img, [class*="brand" i] img, a img, img[alt*="logo" i]');
            if (!logo) { logo = document.createElement('img'); logo.alt = config.projectName; logo.style.margin = '8px 16px'; header.prepend(logo); }
            logo.src = config.logo; logo.style.width = 'auto'; logo.style.maxHeight = 'var(--store-logo-height)';
        }

        const managedSections = [...document.querySelectorAll('[data-store-home-section]')];
        const managedComponents = new Set(managedSections.map(section => section.dataset.storeHomeSection).filter(Boolean));
        document.querySelectorAll('[data-store-native-section]').forEach(nativeSection => {
            if (nativeSection.closest('[data-store-home-section]')) return;
            const owners = (nativeSection.dataset.storeNativeSection || '').split(/\s+/).filter(Boolean);
            if (owners.some(component => managedComponents.has(component))) nativeSection.hidden = true;
        });

        const announcement = document.getElementById('bixo-runtime-announcement');
        if (announcement && header) header.before(announcement);

        // La plantilla premium trae su propio hero (carrusel). Si ownFooter, NO tocamos
        // el hero: no reescribir título, no mover botones, no ocultar nada.
        const fallbackHero = document.getElementById('bixo-runtime-hero');
        if (config.ownFooter) {
            if (fallbackHero) fallbackHero.remove();
        } else {
            let heroTitle = document.querySelector('[class*="hero" i] h1,[class*="banner" i] h1,section[data-hero] h1');
            const builderHero = managedSections.find(section => section.dataset.storeHomeSection === 'hero');
            if (builderHero) {
                const nativeHero = heroTitle?.closest('section') || heroTitle?.closest('[class*="hero" i],[class*="banner" i]');
                if (nativeHero && nativeHero !== builderHero) nativeHero.hidden = true;
                if (fallbackHero) fallbackHero.remove();
                const anchor = header || announcement;
                if (anchor) anchor.after(builderHero);
                heroTitle = null;
            } else if (!heroTitle && fallbackHero) {
                fallbackHero.hidden = false;
                const anchor = announcement || header;
                if (anchor) anchor.after(fallbackHero);
                heroTitle = fallbackHero.querySelector('h1');
            } else if (fallbackHero) {
                fallbackHero.remove();
            }
            if (heroTitle && config.heroTitle) {
                heroTitle.textContent = config.heroTitle;
                const hero = heroTitle.closest('section') || heroTitle.closest('[class*="hero" i],[class*="banner" i]') || heroTitle.parentElement;
                if (hero) {
                    hero.dataset.bixoRuntimeHero = '1';
                    hero.style.backgroundColor = config.heroBackground;
                    hero.style.textAlign = config.heroAlign;
                    hero.style.minHeight = ({small:'260px',medium:'380px',large:'520px'})[config.heroHeight] || '380px';
                    if (config.heroImage) {
                        hero.style.backgroundImage = `linear-gradient(rgba(0,0,0,${config.heroOverlay}),rgba(0,0,0,${config.heroOverlay})),url("${config.heroImage}")`;
                        hero.style.backgroundSize = 'cover'; hero.style.backgroundPosition = 'center';
                    }
                    const subtitle = heroTitle.parentElement?.querySelector('p');
                    if (subtitle && config.heroSubtitle) subtitle.textContent = config.heroSubtitle;
                    const badge = hero.querySelector('[class*="badge" i]');
                    if (badge && config.heroBadge) badge.textContent = config.heroBadge;
                    const actions = [...hero.querySelectorAll('a,button')].filter(el => !el.closest('nav'));
                    if (actions[0]) { actions[0].hidden = !config.cta1Show; if (config.cta1Text) actions[0].textContent = config.cta1Text; }
                    if (actions[1]) { actions[1].hidden = !config.cta2Show; if (config.cta2Text) actions[1].textContent = config.cta2Text; }
                }
            }
        }

        const nativeFooter = [...document.querySelectorAll('footer')].find(el => el.id !== 'bixo-runtime-footer');
        const runtimeFooter = document.getElementById('bixo-runtime-footer');
        const extras = document.getElementById('bixo-runtime-home-extras');
        const customSections = managedSections.filter(section => section.dataset.storeHomeSection !== 'hero');
        if (document.querySelector('[data-store-home-section="benefits"]')) {
            document.querySelectorAll('.assurance,.trust-strip,section[aria-label="Beneficios"]').forEach(section => {
                if (!section.closest('[data-store-home-section]')) section.hidden = true;
            });
        }
        const catalogProduct = document.querySelector('a[href*="/p/"]');
        const catalogArea = catalogProduct?.closest('main,section') || document.getElementById('catalogo')?.closest('main,section');
        customSections.filter(section => section.dataset.storePlacement === 'before-catalog').forEach(section => {
            if (catalogArea) catalogArea.before(section);
        });
        if (nativeFooter) {
            if (extras) nativeFooter.before(extras);
            customSections.filter(section => section.dataset.storePlacement !== 'before-catalog').forEach(section => nativeFooter.before(section));
            // Si la plantilla trae SU PROPIO footer (ownFooter), lo respetamos:
            // no lo ocultamos ni insertamos el footer del runtime.
            if (config.ownFooter) {
                if (runtimeFooter) runtimeFooter.remove();
            } else {
                if (runtimeFooter) nativeFooter.before(runtimeFooter);
                nativeFooter.hidden = true;
            }
        }

        const productLinks = [...document.querySelectorAll('a[href*="/p/"]')];
        const cards = new Set();
        productLinks.forEach(link => {
            const card = link.closest('article,li,[class*="product-card" i],[class*="card" i]') || link.parentElement;
            if (card) { card.classList.add('store-runtime-product-card'); card.dataset.cardStyle = config.cardStyle; cards.add(card); }
        });
        cards.forEach(card => {
            const grid = card.parentElement;
            if (grid && cards.size > 1) grid.classList.add('store-runtime-product-grid');
        });
        if (config.catalogTitle && cards.size) {
            const firstCard = [...cards][0];
            const area = firstCard.closest('section,main') || firstCard.parentElement;
            const title = area?.querySelector('h2,h1');
            // Solo se renombra el encabezado de una rejilla de catálogo. Antes se
            // tomaba el primer h1/h2 del área: en la ficha de producto ese título
            // es el NOMBRE del producto, y quedaba sustituido por "Productos
            // destacados" (los productos relacionados cuentan como tarjetas).
            const esFicha = !!document.querySelector('.pdp-wrap,.pdp-gallery,.product-detail,[data-product-detail]');
            const esTituloDeProducto = title && (
                title.closest('.pdp-wrap,.product-detail,[data-product-detail]') ||
                title.classList.contains('pdp-title') ||
                title.closest('.catalog-card')
            );
            // Un título con x-text lo gestiona la plantilla (por ejemplo, el del
            // catálogo cambia al nombre de la categoría filtrada): pisarlo dejaba
            // "Productos destacados" aunque el cliente estuviera viendo Monitores.
            const loGestionaAlpine = title && (title.hasAttribute('x-text') || title.hasAttribute('x-html'));
            if (title && !esFicha && !esTituloDeProducto && !loGestionaAlpine && !title.closest('[data-bixo-runtime-hero]')) {
                title.textContent = config.catalogTitle;
                title.id = 'catalogo';
            }
        }

        document.querySelectorAll('button,a').forEach(element => {
            if (element.hasAttribute('data-rotulo-fijo')) return; // texto propio, no se reescribe
            const value = normalize(element.textContent);
            if (config.cartText && /(agregar|anadir).*(carrito)?/.test(value)) element.textContent = config.cartText;
            if (config.quoteText && /(cotizar|solicitar cotizacion)/.test(value)) element.textContent = config.quoteText;
            if (config.checkoutText && /(finalizar|checkout|realizar pedido|continuar pedido)/.test(value)) element.textContent = config.checkoutText;
            if (config.sendQuoteText && /(enviar cotizacion|enviar solicitud)/.test(value)) element.textContent = config.sendQuoteText;
            if (!config.showCartIcon && /(agregar|anadir).*(carrito)?/.test(value)) element.querySelectorAll('svg,i').forEach(icon => icon.remove());
        });
        if (config.cartTitle) document.querySelectorAll('h1,h2,h3,h4,span').forEach(el => { if (textMatches(el,['tu carrito','carrito de compras','mi carrito'])) el.textContent = config.cartTitle; });
        if (config.cartEmptyMessage) document.querySelectorAll('p,div,span').forEach(el => { if (!el.children.length && textMatches(el,['carrito esta vacio','carrito vacío','sin productos en el carrito'])) el.textContent = config.cartEmptyMessage; });
        if (config.noResultsText) document.querySelectorAll('p,div,span').forEach(el => { if (!el.children.length && textMatches(el,['sin resultados','no se encontraron productos','no hay productos'])) el.textContent = config.noResultsText; });
        if (config.viewMoreText) document.querySelectorAll('a,button').forEach(el => { if (textMatches(el,['ver mas','ver todos','mostrar mas'])) el.textContent = config.viewMoreText; });
        if (config.allCategoriesText) document.querySelectorAll('a,button,option').forEach(el => { if (textMatches(el,['todas las categorias','ver categorias'])) el.textContent = config.allCategoriesText; });
        if (config.searchPlaceholder) document.querySelectorAll('input[type="search"],input[placeholder*="buscar" i]').forEach(input => input.placeholder = config.searchPlaceholder);
        if (!config.filters.search) document.querySelectorAll('input[type="search"],input[placeholder*="buscar" i]').forEach(input => nearestField(input).hidden = true);
        if (!config.filters.price) document.querySelectorAll('input[type="range"],input[name*="price" i]').forEach(input => nearestField(input).hidden = true);
        if (!config.filters.sale) document.querySelectorAll('label,button').forEach(el => { if (textMatches(el,['oferta','sale'])) el.hidden = true; });
        if (!config.filters.categories) document.querySelectorAll('[class*="filter" i]').forEach(el => { if (textMatches(el,['categoria'])) el.hidden = true; });
        if (!config.showRatings) document.querySelectorAll('[class*="rating" i],[class*="stars" i]').forEach(el => el.hidden = true);
        if (!config.quickView) document.querySelectorAll('button,a').forEach(el => { if (textMatches(el,['vista rapida','quick view'])) el.hidden = true; });
        if (!config.showSku) document.querySelectorAll('[class*="sku" i],[data-sku]').forEach(el => el.hidden = true);
        if (!config.showStock) document.querySelectorAll('[class*="stock" i],[data-stock]').forEach(el => el.hidden = true);
        // Flags legados del diseñador: NO tocan secciones del registro canónico (data-store-native-section),
        // cuya visibilidad ya la decidió el servidor con store_sections.
        const legacySectionHide = (sel) => document.querySelectorAll(sel).forEach(el => { if (!el.closest('[data-store-native-section]')) el.hidden = true; });
        if (!config.showFlashSale) legacySectionHide('[class*="flash-sale" i],[data-section="flash-sale"]');
        if (!config.showTestimonials) legacySectionHide('[class*="testimonial" i],[data-section="testimonials"]');
        if (!config.showNewsletter) legacySectionHide('[class*="newsletter" i],[data-section="newsletter"]');
        const loginPanel = document.querySelector('[data-customer-login],[class*="login-modal" i],[class*="login-panel" i]');
        if (loginPanel) {
            if (config.login.backgroundType === 'image' && config.login.backgroundImage) { loginPanel.style.backgroundImage = `url("${config.login.backgroundImage}")`; loginPanel.style.backgroundSize = 'cover'; }
            else if (config.login.backgroundType === 'solid') loginPanel.style.background = config.login.color1;
            else loginPanel.style.background = `linear-gradient(135deg,${config.login.color1},${config.login.color2})`;
            const heading = loginPanel.querySelector('h1,h2,h3'); if (heading && config.login.heading) heading.textContent = config.login.heading;
            const subtitle = heading?.parentElement?.querySelector('p'); if (subtitle && config.login.subtitle) subtitle.textContent = config.login.subtitle;
        }
        const sharedWhatsapp = document.getElementById('bixo-runtime-whatsapp');
        document.querySelectorAll('a[href*="wa.me"],a[href*="whatsapp" i]').forEach(el => {
            if (el !== sharedWhatsapp && (el.className?.toString().includes('fixed') || getComputedStyle(el).position === 'fixed')) el.hidden = true;
        });
        document.querySelectorAll('[class*="cart" i]').forEach(el => {
            if (getComputedStyle(el).position !== 'fixed') return;
            if (!config.floatCart) el.hidden = true;
            else { el.style.left = config.floatCartPosition === 'bottom-left' ? '20px' : 'auto'; el.style.right = config.floatCartPosition === 'bottom-left' ? 'auto' : '20px'; }
        });
        document.querySelectorAll('[class*="badge" i]').forEach(el => {
            const value = normalize(el.textContent);
            if (value.includes('oferta') || value === 'sale') el.textContent = config.badges.sale;
            else if (value.includes('nuevo') || value.includes('new')) el.textContent = config.badges.new;
            else if (value.includes('destacado') || value.includes('featured')) el.textContent = config.badges.featured;
            else if (value.includes('agotado') || value.includes('sold out')) el.textContent = config.badges.soldOut;
        });
        if (config.storeMode === 'quote_only') {
            document.querySelectorAll('button,a').forEach(el => { if (config.quoteText && !el.hasAttribute('data-rotulo-fijo') && textMatches(el,['agregar','anadir'])) el.textContent = config.quoteText; });
            if (config.quotePrice === 'hide') document.querySelectorAll('span,p,div').forEach(el => { if (!el.children.length && /^(S\/|\$|\u20ac)\s*[\d,.]+$/.test(el.textContent.trim())) el.hidden = true; });
        }

        const fixed = config.checkoutFields?.fixed || {};
        const selectors = {
            lname: 'input[name*="last" i],input[x-model*="last" i],input[placeholder*="apellido" i]',
            email: 'input[type="email"],input[name*="email" i],input[x-model*="email" i]',
            dni: 'input[name*="dni" i],input[name*="document" i],input[placeholder*="DNI" i],input[placeholder*="RUC" i]',
            // El comodin "address" tambien casaba con address2 (la referencia del
            // domicilio) y le sobreescribia la etiqueta: quedaban dos "Direccion".
            address: 'input[name*="address" i]:not([name*="address2" i]),textarea[name*="address" i]:not([name*="address2" i]),input[x-model*="address" i]:not([x-model*="address2" i]),textarea[x-model*="address" i]:not([x-model*="address2" i])',
            notes: 'textarea[name*="note" i],textarea[x-model*="note" i]'
        };
        Object.entries(fixed).forEach(([key,fieldConfig]) => {
            document.querySelectorAll(selectors[key] || '').forEach(input => {
                const wrapper = nearestField(input);
                wrapper.hidden = fieldConfig.enabled === false;
                if (fieldConfig.label) { input.placeholder = fieldConfig.label; const label = wrapper.querySelector('label'); if (label) label.textContent = fieldConfig.label; }
            });
        });
        const phone = [...document.querySelectorAll('input[type="tel"],input[name*="phone" i],input[x-model*="phone" i]')].find(input => !input.closest('#bixo-runtime-checkout'));
        const checkoutArea = phone?.closest('form,[class*="checkout" i],[class*="drawer" i],[class*="cart" i],[x-show]');
        const submit = checkoutArea ? [...checkoutArea.querySelectorAll('button')].find(button => textMatches(button,['pedido','cotizacion','finalizar','enviar'])) : null;
        if (checkoutArea && submit && Array.isArray(config.checkoutFields?.custom)) {
            config.checkoutFields.custom.filter(field => field.enabled !== false).forEach(field => {
                if (checkoutArea.querySelector(`[data-bixo-checkout-key="${CSS.escape(field.key)}"]`)) return;
                const wrapper = document.createElement('label'); wrapper.style.cssText = 'display:grid;gap:6px;margin:10px 0;font-size:13px;font-weight:600';
                wrapper.textContent = field.label + (field.required ? ' *' : '');
                const input = document.createElement(field.type === 'textarea' ? 'textarea' : 'input');
                if (field.type !== 'textarea') input.type = ['text','number','date'].includes(field.type) ? field.type : 'text';
                input.required = !!field.required; input.dataset.bixoCheckoutField = '1'; input.dataset.bixoCheckoutKey = field.key; input.dataset.bixoCheckoutLabel = field.label;
                input.style.cssText = 'width:100%;padding:11px 12px;border:1px solid #d1d5db;border-radius:10px;font:inherit';
                wrapper.appendChild(input); submit.before(wrapper);
            });
        }

        const fallbackCheckout = document.getElementById('bixo-runtime-checkout');
        if (!phone && fallbackCheckout) {
            fallbackCheckout.dataset.bixoFallbackReady = '1';
            const fallbackForm = fallbackCheckout.querySelector('[data-bixo-fallback-form]');
            const openFallback = event => { event?.preventDefault(); fallbackCheckout.classList.add('is-open'); document.body.style.overflow = 'hidden'; };
            const closeFallback = () => { fallbackCheckout.classList.remove('is-open'); document.body.style.overflow = ''; };
            document.querySelectorAll('a,button').forEach(element => {
                if (!element.closest('#bixo-runtime-checkout') && textMatches(element,['pedir por whatsapp','finalizar pedido','realizar pedido'])) element.addEventListener('click', openFallback, true);
            });
            fallbackCheckout.querySelector('[data-bixo-checkout-close]')?.addEventListener('click', closeFallback);
            fallbackCheckout.addEventListener('click', event => { if (event.target === fallbackCheckout) closeFallback(); });
            document.addEventListener('keydown', event => { if (event.key === 'Escape') closeFallback(); });

            const paymentOutput = fallbackCheckout.querySelector('[data-bixo-payment-details]');
            const showPaymentDetails = method => {
                const details = config.paymentDetails || {}; let value = '';
                if (method === 'yape') value = ['Yape: '+(details.yapeNumber||''), details.yapeName, details.manualInstructions].filter(Boolean).join('\n');
                else if (method === 'plin') value = ['Plin: '+(details.plinNumber||''), details.manualInstructions].filter(Boolean).join('\n');
                else if (method === 'transferencia') value = [details.bankDetails, details.bcp, details.interbank, details.bbva, details.nacion, details.scotiabank, details.manualInstructions].filter(Boolean).join('\n');
                if (paymentOutput) { paymentOutput.textContent = value; paymentOutput.style.display = value ? 'block' : 'none'; }
            };
            fallbackCheckout.querySelectorAll('input[name="payment_method"]').forEach(input => input.addEventListener('change', () => showPaymentDetails(input.value)));
            showPaymentDetails(fallbackCheckout.querySelector('input[name="payment_method"]:checked')?.value);

            fallbackForm?.addEventListener('submit', async event => {
                event.preventDefault();
                const root = document.querySelector('[x-data*="tienda"]') || document.body;
                const state = window.Alpine?.$data(root); const cart = Array.isArray(state?.cart) ? state.cart : [];
                const output = fallbackCheckout.querySelector('[data-bixo-checkout-message]');
                if (!cart.length) { if (output) { output.textContent = config.cartEmptyMessage || 'Tu carrito está vacío.'; output.style.cssText += ';display:block;background:#fff7ed;color:#9a3412'; } return; }
                const formData = new FormData(fallbackForm); const extras = {};
                fallbackForm.querySelectorAll('[data-bixo-checkout-field]').forEach(field => { if (field.value) extras[field.dataset.bixoCheckoutLabel || 'Dato'] = field.value; });
                const subtotal = cart.reduce((sum,item) => sum + Number(item.precio ?? item.price ?? 0) * Number(item.cantidad ?? item.qty ?? item.quantity ?? 1), 0);
                const shipping = config.shippingEnabled && !(config.shippingFreeFrom > 0 && subtotal >= config.shippingFreeFrom) ? config.shippingCost : 0;
                const payload = {
                    client_name: formData.get('client_name'), client_phone: formData.get('client_phone'),
                    client_email: formData.get('bixo_email') || null, notes: [formData.get('notes'), ...Object.entries(extras).map(([key,value]) => `${key}: ${value}`)].filter(Boolean).join('\n'),
                    delivery_address: formData.get('delivery_address') || null, shipping_cost: shipping,
                    payment_method: formData.get('payment_method') || null,
                    items: cart.map(item => ({product_id: /^\d+$/.test(String(item.id)) ? Number(item.id) : null,name:item.nombre ?? item.name ?? 'Producto',price:Number(item.precio ?? item.price ?? 0),quantity:Number(item.cantidad ?? item.qty ?? item.quantity ?? 1)})),
                };
                if (config.storeMode === 'quote_only' && config.whatsappNumber) {
                    const lines = payload.items.map(item => `- ${item.name} x${item.quantity}${config.quotePrice === 'hide' ? '' : `: ${config.currency} ${(item.price*item.quantity).toFixed(2)}`}`);
                    const message = [config.whatsappMessage, ...lines, payload.notes].filter(Boolean).join('\n');
                    window.open(`https://wa.me/${config.whatsappNumber}?text=${encodeURIComponent(message)}`,'_blank','noopener');
                    if (output) { output.textContent = 'Cotización preparada en WhatsApp.'; output.style.cssText += ';display:block;background:#ecfdf5;color:#047857'; }
                    return;
                }
                const button = fallbackForm.querySelector('button[type="submit"]'); button.disabled = true; button.textContent = 'Procesando…';
                try {
                    const response = await originalFetch(config.orderUrl,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':config.csrfToken},body:JSON.stringify(payload)});
                    const result = await response.json(); if (!response.ok || !result.ok) throw new Error(result.message || Object.values(result.errors || {})[0]?.[0] || 'No se pudo registrar el pedido.');
                    if (output) { output.textContent = `Pedido #${result.order_id} registrado correctamente.`; output.style.cssText += ';display:block;background:#ecfdf5;color:#047857'; }
                    if (state) state.cart = [];
                    button.textContent = 'Pedido registrado';
                } catch (error) {
                    if (output) { output.textContent = error.message; output.style.cssText += ';display:block;background:#fef2f2;color:#b91c1c'; }
                    button.disabled = false; button.textContent = config.checkoutText || 'Realizar pedido';
                }
            });
        } else if (fallbackCheckout) fallbackCheckout.remove();

        document.querySelectorAll('[data-countdown-end]').forEach(block => {
            const output = block.querySelector('[data-countdown-value]'); const end = new Date(block.dataset.countdownEnd).getTime();
            const refresh = () => { const diff = end - Date.now(); if (!output) return; if (!Number.isFinite(end) || diff <= 0) { output.textContent = 'Finalizada'; return; } const days=Math.floor(diff/86400000),hours=Math.floor(diff/3600000)%24,minutes=Math.floor(diff/60000)%60; output.textContent=`${days}d ${hours}h ${minutes}m`; };
            refresh(); setInterval(refresh, 60000);
        });

        const ageGate = document.getElementById('bixo-runtime-age-gate');
        if (ageGate && !sessionStorage.getItem('bixo-age-confirmed')) {
            ageGate.style.display = 'flex'; document.body.style.overflow = 'hidden';
            ageGate.querySelector('[data-age-accept]')?.addEventListener('click', () => { sessionStorage.setItem('bixo-age-confirmed','1'); ageGate.remove(); document.body.style.overflow = ''; });
            ageGate.querySelector('[data-age-decline]')?.addEventListener('click', () => { history.length > 1 ? history.back() : location.assign('https://www.google.com'); });
        } else if (ageGate) ageGate.remove();
        document.documentElement.dataset.bixoRuntimeReady = '1';
    });
})();
</script>
