<!DOCTYPE html>
@php
    // Fuente de verdad: los ajustes guardados desde el diseñador.
    // Esto evita que una plantilla personalizada activa sobrescriba cambios nuevos.
    $liveProjectSettings = $project->settings()->pluck('value', 'key')->toArray();
    $settings = array_merge((array) ($settings ?? []), $liveProjectSettings);
    // Perfil de catálogo activo: sus overrides visuales van ENCIMA de los settings
    // globales (lo que el perfil no define hereda la identidad de la tienda).
    if (!empty($activeProfile) && method_exists($activeProfile, 'settingOverrides')) {
        $profileOverrides = $activeProfile->settingOverrides();
        // Modo ACENTO (por defecto): el color del perfil distingue la seccion sin
        // repintar la tienda entera. Antes reemplazaba --primary y toda la pagina
        // cambiaba de color, perdiendo la identidad de marca. Con
        // profile_color_mode='full' se recupera el comportamiento anterior.
        if ((string) ($settings['profile_color_mode'] ?? 'accent') !== 'full') {
            $profileTint = $profileOverrides['primary_color'] ?? null;
            unset($profileOverrides['primary_color'], $profileOverrides['secondary_color'],
                  $profileOverrides['header_bg_color'], $profileOverrides['footer_bg_color']);
        }
        $settings = array_merge($settings, $profileOverrides);
    }
    // Preview del Constructor: los BORRADORES van encima de lo publicado.
    // Solo la ruta de preview pasa esta variable; el público nunca la recibe.
    if (!empty($builderSettingsOverlay) && is_array($builderSettingsOverlay)) {
        $settings = array_merge($settings, $builderSettingsOverlay);
    }
    $color = static fn ($v, $fallback) => is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : $fallback;
    // Encabezado personalizado de sección activo: REEMPLAZA al título nativo (un solo título).
    $introActive = static function (string $key) use ($settings): bool {
        $st = $settings["intro_{$key}_style"] ?? 'none';
        if (!in_array($st, ['simple', 'dot', 'band', 'strip'], true)) return false;
        return trim($settings["intro_{$key}_title"] ?? '') !== ''
            || trim($settings["intro_{$key}_subtitle"] ?? '') !== ''
            || filled($settings["intro_{$key}_image"] ?? null);
    };
    $assetUrl = static function ($value) {
        if (blank($value)) return null;
        $value = trim((string) $value);
        if (preg_match('#^(https?:)?//#i', $value) || str_starts_with($value, 'data:')) return $value;
        return asset('storage/' . ltrim(preg_replace('#^storage/#', '', $value), '/'));
    };
    $primary = $color($settings['primary_color'] ?? null, '#2563eb');
    // Colecciones (Niño / Niña...): cada una puede traer su propio color y hasta
    // ahora se ignoraba, asi que las dos versiones se veian identicas.
    if (!empty($activeProfile) && !empty($activeProfile->primary_color)) {
        $primary = $color($activeProfile->primary_color, $primary);
    }
    $secondary = $color($settings['secondary_color'] ?? null, '#0f172a');
    // Color de acento: detalles institucionales (iconos, líneas, etiquetas).
    // Configurable desde el Constructor; si no se define cae al primario.
    $accent = $color($settings['accent_color'] ?? null, $primary);
    // Color reservado a ofertas y precios rebajados. Separado del acento
    // porque el acento tambien pinta el encabezado: si se cambia por el de
    // ofertas, el buscador y el carrito se tinen del mismo color.
    $saleColor = $color($settings['sale_color'] ?? null, $accent);
    $headerBg = $color($settings['header_bg_color'] ?? null, '#ffffff');
    $headerText = $color($settings['header_text_color'] ?? null, '#0f172a');
    $heroBg = $color($settings['hero_bg_color'] ?? null, '#f1f5f9');

    // Sistema global de estilos para todas las secciones de la plantilla.
    $sectionPreset = in_array(($settings['section_style_preset'] ?? 'modern'), ['modern','minimal','commerce'], true)
        ? ($settings['section_style_preset'] ?? 'modern') : 'modern';

    // Tema visual completo (tokens): cambia superficies, sombras, radios y
    // personalidad sin tocar contenido. Vacío/classic = comportamiento actual.
    $themePresetDef = \App\Support\StorefrontThemePresets::get($settings['theme_preset'] ?? null);
    $themeBodyClass = $themePresetDef['body_class'] ?? '';
    $themeIsDark = (bool) ($themePresetDef['dark'] ?? false);

    // Variante estructural de las tarjetas de producto (no solo color).
    $productCardStyle = in_array(($settings['product_card_style'] ?? 'classic'), ['classic','tech','soft','elegant','contrast'], true)
        ? ($settings['product_card_style'] ?? 'classic') : 'classic';
    $sectionSpacing = in_array(($settings['section_spacing'] ?? 'comfortable'), ['compact','comfortable','dense'], true)
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
    $quoteBtnText = trim($settings['btn_quote_text'] ?? '') !== '' ? trim($settings['btn_quote_text']) : 'Cotizar';
    $checkoutText = $settings['btn_checkout_text'] ?? $settings['checkout_button_text'] ?? 'Finalizar pedido';
    $quoteMode = ($settings['store_mode'] ?? 'direct') === 'quote';
    $hidePrices = $quoteMode && ($settings['quote_price_display'] ?? 'show') === 'hide';

    /* ═══ Checkout / pago / envío (mismo esqueleto que la plantilla ecommerce) ═══ */
    $isQuoteOnly = $quoteMode;
    $payManualMethods = json_decode($settings['payment_manual_methods'] ?? '["yape"]', true) ?: [];
    $payManualEnabled = (string) ($settings['payment_manual_enabled'] ?? '1') !== '0';
    // Apagar un método en el Constructor lo oculta SIN borrar su configuración.
    $payMethodOn = static fn (string $m) => (string) ($settings["payment_{$m}_on"] ?? '1') !== '0';
    $payYapeNumber  = $payMethodOn('yape') ? preg_replace('/\D/', '', $settings['payment_yape_number'] ?? '') : '';
    $payYapeName    = $settings['payment_yape_name'] ?? ($project->name ?? '');
    $payYapeQr      = $settings['payment_yape_qr'] ?? '';
    $payYapeNote    = trim($settings['payment_yape_note'] ?? '');
    $payBanks = [
        'bcp'        => ['label'=>'BCP',               'color'=>'#003DA5'],
        'interbank'  => ['label'=>'Interbank',          'color'=>'#00873D'],
        'bbva'       => ['label'=>'BBVA',               'color'=>'#004481'],
        'nacion'     => ['label'=>'Banco de la Nación', 'color'=>'#D22630'],
        'scotiabank' => ['label'=>'Scotiabank',         'color'=>'#EC111A'],
    ];
    $payBankActive = [];
    foreach ($payBanks as $bk => $bv) {
        $details = $payMethodOn('bank') ? trim($settings['payment_bank_'.$bk] ?? '') : '';
        // CCI en campo propio (antes solo cabia dentro del texto libre de la cuenta).
        $cci = $payMethodOn('bank') ? trim($settings['payment_cci_'.$bk] ?? '') : '';
        if ($details) $payBankActive[$bk] = array_merge($bv, ['details' => $details, 'cci' => $cci]);
    }
    // Compatibilidad: campo genérico de transferencia si no hay bancos específicos.
    $payBankGeneric = trim($settings['payment_bank_details'] ?? '');
    if ($payBankGeneric !== '' && !count($payBankActive)) {
        $payBankActive['otros'] = ['label' => 'bancaria', 'color' => '#334155', 'details' => $payBankGeneric];
    }
    $shippingEnabled  = ($settings['shipping_enabled'] ?? '0') === '1';
    $shippingCost     = (float) ($settings['shipping_cost'] ?? 0);
    $shippingFreeFrom = (float) ($settings['shipping_free_from'] ?? 0);
    $requireAddress   = ($settings['require_address'] ?? '0') === '1';
    // Que se ve del sitio mientras el cliente paga:
    //   reduced = logo, "compra segura" y ayuda (recomendado: menos fugas)
    //   full    = el encabezado completo    none = sin encabezado
    $ckChrome = in_array($settings['checkout_chrome'] ?? 'reduced', ['reduced', 'full', 'none'], true)
        ? ($settings['checkout_chrome'] ?? 'reduced') : 'reduced';
    // Recojo en tienda: el Constructor lo ofrecia pero el checkout nunca lo mostraba.
    $pickupEnabled    = ($settings['pickup_enabled'] ?? '0') === '1';
    $pickupNote       = trim((string) ($settings['pickup_instructions'] ?? ''));
    $ckFields = json_decode($settings['checkout_fields'] ?? 'null', true) ?: [
        'fixed'  => [
            'lname'   => ['label'=>'Apellido',  'enabled'=>true],
            'email'   => ['label'=>'Email',     'enabled'=>true],
            'dni'     => ['label'=>'DNI / RUC', 'enabled'=>true],
            'address' => ['label'=>'Dirección', 'enabled'=>false],
            'notes'   => ['label'=>'Notas',     'enabled'=>true],
        ],
        'custom' => [],
    ];
    $showSku = (string) ($settings['catalog_show_sku'] ?? '0') === '1';
    $showStock = (string) ($settings['catalog_show_stock'] ?? '1') !== '0';
    $showRatings = (string) ($settings['catalog_show_ratings'] ?? '0') === '1';
    // Mayorista visible por defecto: solo aparece en productos que TIENEN precio mayorista cargado.
    $wholesale = (string) ($settings['wholesale_enabled'] ?? '1') === '1';
    // ── TARJETA DE PRODUCTO: modo comercial + opciones visuales ──────────────
    // purchase_mode: 'separate' (bloques Minorista/Mayorista, comportamiento
    // historico) o 'auto' (precio efectivo segun cantidad). Sin valor => 'separate',
    // por lo que las tiendas existentes no cambian.
    $pcMode = in_array($settings['purchase_mode'] ?? 'separate', ['separate','auto'], true)
        ? ($settings['purchase_mode'] ?? 'separate') : 'separate';
    $pcOpt = static fn (string $k, string $def = '1') => (string) ($settings[$k] ?? $def) !== '0';
    $pcShowWholesale = $pcOpt('card_show_wholesale_price');
    $pcShowCondition = $pcOpt('card_show_wholesale_condition');
    $pcShowQty       = $pcOpt('card_show_quantity');
    $pcShowSubtotal  = $pcOpt('card_show_subtotal', '0');
    $pcShowCart      = $pcOpt('card_show_cart');
    $pcShowWa        = $pcOpt('card_show_whatsapp');
    $pcShowSavings   = $pcOpt('card_show_savings', '0');
    $pcShowTierList  = $pcOpt('card_show_tier_list', '0');
    $pcCartStyle = in_array($settings['card_cart_style'] ?? 'full', ['full','compact','inline'], true)
        ? ($settings['card_cart_style'] ?? 'full') : 'full';
    $pcWaStyle = in_array($settings['card_whatsapp_style'] ?? 'outline', ['outline','solid','link','icon'], true)
        ? ($settings['card_whatsapp_style'] ?? 'outline') : 'outline';
    $pcQtyStyle = in_array($settings['card_qty_style'] ?? 'horizontal', ['horizontal','compact'], true)
        ? ($settings['card_qty_style'] ?? 'horizontal') : 'horizontal';
    $columns = max(2, min(5, (int) ($settings['catalog_cols_desktop'] ?? 4)));
    $mobileColumns = max(1, min(2, (int) ($settings['catalog_cols_mobile'] ?? 2)));
    $radiusSetting = $settings['border_radius'] ?? 8;
    $radius = match ((string) $radiusSetting) {
        'sharp' => 0,
        'rounded' => 12,
        'pill' => 24,
        default => max(0, min(24, (int) $radiusSetting)),
    };
    $logoUrl = \App\Support\ImageVariants::webp($assetUrl($settings['logo_url'] ?? $project->logo_url ?? null));
    $waSource = $settings['whatsapp_number']
        ?? $settings['quote_whatsapp']
        ?? $settings['contact_whatsapp']
        ?? $settings['store_whatsapp']
        ?? $project->whatsapp
        ?? $project->phone
        ?? '';
    $waRaw = preg_replace('/\D/', '', (string) $waSource);
    $whatsapp = $waRaw && !str_starts_with($waRaw, '51') ? '51' . $waRaw : $waRaw;
    // Botones de producto: solo compra, solo consulta (WhatsApp) o ambos; adaptado a modo cotización.
    $productButtonMode = in_array($settings['product_button_mode'] ?? '', ['cart', 'inquiry', 'both'], true) ? $settings['product_button_mode'] : 'cart';
    $inquiryText = trim($settings['btn_inquiry_text'] ?? '') !== '' ? trim($settings['btn_inquiry_text']) : 'Consultar';
    $showCartButton = $productButtonMode !== 'inquiry';
    $showInquiryButton = $productButtonMode !== 'cart' && $whatsapp;
    $inquiryMsgBase = $quoteMode ? 'Hola, quiero cotizar este producto: ' : 'Hola, quiero consultar por este producto: ';
    $phone = $settings['contact_phone'] ?? $project->phone ?? '';
    // Se respeta el host por el que entra el visitante: forzar el dominio propio
    // rompia la tienda cuando ese dominio no responde (MegaHogar entra por
    // arindg.com y megahogar.org esta suspendido) y ademas provocaba un salto
    // de dominio innecesario en el buscador.
    $webpUrl = static function (?string $url) {
        if (! $url) {
            return null;
        }
        $ruta = parse_url($url, PHP_URL_PATH);
        if (! $ruta || ! preg_match('/\.(png|jpe?g)$/i', $ruta)) {
            return null;
        }
        $enDisco = public_path(ltrim($ruta, '/'));
        $webp    = preg_replace('/\.(png|jpe?g)$/i', '.webp', $enDisco);
        return is_file($webp) ? preg_replace('/\.(png|jpe?g)$/i', '.webp', $url) : null;
    };
    $shopUrl = ($project->custom_domain && request()->getHost() === trim($project->custom_domain, '/'))
        ? 'https://' . trim($project->custom_domain, '/') . '/tienda'
        : url('/' . $project->slug . '/tienda');
    $email = $settings['contact_email'] ?? $project->email ?? '';
    // Personalización adicional (antes ignorada)
    $announcementText = trim($settings['announcement_text'] ?? '');
    $announcementBg = $color($settings['announcement_bg'] ?? null, $primary);
    // Personalización de la barra superior (topbar)
    $announcementColor = $color($settings['announcement_color'] ?? null, '#ffffff');
    $announcementFontSize = max(10, min(20, (int) ($settings['announcement_font_size'] ?? 12)));
    $announcementAlign = in_array(($settings['announcement_align'] ?? 'center'), ['left','center','right'], true)
        ? ($settings['announcement_align'] ?? 'center') : 'center';
    $announcementFull = (string) ($settings['announcement_full_width'] ?? '0') === '1'; // ancho completo o contenedor
    $announcementShow = (string) ($settings['announcement_show'] ?? '1') !== '0';
    // Encabezado sticky (acompaña al hacer scroll): 'all' (todo el header),
    // 'menu' (solo la barra de categorías/menú) o 'none'. Compatibilidad con
    // el booleano antiguo header_sticky ('0' = none) si nunca se migró.
    $headerStickyMode = $settings['header_sticky_mode'] ?? null;
    if (!in_array($headerStickyMode, ['all', 'header', 'menu', 'none'], true)) {
        $headerStickyMode = ((string) ($settings['header_sticky'] ?? '1') !== '0') ? 'all' : 'none';
    }
    $headerSticky = $headerStickyMode === 'all'; // se mantiene por compatibilidad con el resto del archivo
    // Diseño del menú de navegación: clásico, barra oscura, banda de color, píldora flotante, minimal subrayado.
    $headerStyle = in_array(($settings['header_style'] ?? 'classic'), ['classic', 'dark', 'accent', 'pill', 'line'], true)
        ? ($settings['header_style'] ?? 'classic') : 'classic';
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
    $footerCopyright  = trim($settings['footer_copyright'] ?? '') !== '' ? $settings['footer_copyright'] : ('© ' . date('Y') . ' ' . $storeName . '. Todos los derechos reservados.');
    $footerLogoHeight = (int) ($settings['footer_logo_height'] ?? 50) ?: 50;
    $showSocial       = (string) ($settings['footer_show_social'] ?? '1') !== '0';
    // Orden estandar de redes: Facebook, Instagram, TikTok, YouTube, LinkedIn.
    $social = array_filter([
        'Facebook'  => $settings['facebook_url'] ?? '',
        'Instagram' => $settings['instagram_url'] ?? '',
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
    // La ciudad se pedia en el Constructor pero no se mostraba en ningun sitio.
    $contactCity     = trim((string) ($settings['contact_city'] ?? ''));
    if ($contactCity !== '' && $footerAddress !== '' && !str_contains(mb_strtolower($footerAddress), mb_strtolower($contactCity))) {
        $footerAddress = rtrim($footerAddress, ' .,').' — '.$contactCity;
    }
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
    $homeSectionKeys = ['hero','media_banner','benefits','promotions','categories','collection_showcase','flash_sale','discount_products','featured_products','category_rows','brands','testimonials','gallery','faq','wa_advisory','cta_banner','locations','about_preview','info_strip','blog'];

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
        'daily_offer' => 'flash_sale', // nombre real del componente en store_sections

        'discount_products' => 'discount_products',
        'sale_products' => 'discount_products',
        'products_on_sale' => 'discount_products',
        'discounts' => 'discount_products', // nombre real del componente en store_sections

        'featured_products' => 'featured_products',

        'blog' => 'blog',
        'informative_blog' => 'blog',
        'blog_informativo' => 'blog',

        // Secciones nuevas del registro canónico (mapeo directo).
        'media_banner' => 'media_banner',
        'collection_showcase' => 'collection_showcase',
        'brands' => 'brands',
        'testimonials' => 'testimonials',
        'gallery' => 'gallery',
        'faq' => 'faq',
        'wa_advisory' => 'wa_advisory',
        'cta_banner' => 'cta_banner',
        'category_rows' => 'category_rows',
        'locations' => 'locations',
        'about_preview' => 'about_preview',
        'info_strip' => 'info_strip',
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

    // Si el proyecto tiene filas de secciones (aunque estén todas desactivadas),
    // se respeta lo publicado; el fallback "mostrar todo" es solo para tiendas sin configurar.
    $hasPublishedSectionRegistry = (bool) ($hasSectionRegistry ?? $homeSectionRecords->isNotEmpty());
    $isHomeSectionVisible = static fn (string $key): bool =>
        !$hasPublishedSectionRegistry || in_array($key, $publishedNativeSections, true);

    // Categorías destacadas configurables.
    // Inicio controla qué categorías aparecen; Portada controla su estilo visual.
    $categoriesSectionContent = $sectionContentFor('categories');
    $featuredCatsEnabled = (string) ($settings['featured_categories_enabled'] ?? '1') !== '0';
    // Con registro canónico publicado, la visibilidad la decide el registro (no el flag legado del diseñador).
    if ($hasPublishedSectionRegistry && in_array('categories', $publishedNativeSections, true)) $featuredCatsEnabled = true;
    $featuredCatsTitle = trim($settings['featured_categories_title'] ?? $categoriesSectionContent['title'] ?? 'Explora por categoría');
    $featuredCatsSubtitle = trim($settings['featured_categories_subtitle'] ?? '');
    $featuredCatsShowAll = (string) ($settings['featured_categories_show_all'] ?? '1') !== '0';
    $featuredCatsAllText = trim($settings['featured_categories_all_text'] ?? 'Ver todo');
    $featuredCatsVisual = in_array(($settings['featured_categories_visual'] ?? 'auto'), ['auto','image','icon','initial'], true)
        ? ($settings['featured_categories_visual'] ?? 'auto') : 'auto';
    $featuredCatsStyle = in_array(($settings['featured_categories_style'] ?? 'image-top'), ['image-top','overlay','minimal','horizontal','showcase','circles','carousel','editorial','ambientes','coleccion'], true)
        ? ($settings['featured_categories_style'] ?? 'image-top') : 'image-top';
    // Variante "showcase": banda de título destacada + círculos grandes.
    $featuredCatsBandBg = $color($settings['featured_categories_band_bg'] ?? null, $settings['primary_color'] ?? '#2563eb');
    $featuredCatsBandText = $color($settings['featured_categories_band_text'] ?? null, '#ffffff');
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
    // Texto libre que se suma a la linea de confianza (por ejemplo, "Hasta en 6
    // cuotas" o "14 anos en Huancavelica"). Vacio = no aparece nada.
    $trustExtraNote = trim((string) ($settings['trust_extra_note'] ?? ''));
    // Proporcion de la foto de producto. Un mueble es horizontal, una prenda
    // vertical y un teclado compacto: una sola proporcion no sirve a las tres
    // tiendas, y forzar el cuadrado encoge el producto que no lo es.
    $cardRatio = in_array($settings['card_image_ratio'] ?? '1/1', ['1/1', '4/3', '3/4'], true)
        ? ($settings['card_image_ratio'] ?? '1/1') : '1/1';
    // Fondo de la foto: el mueble claro sobre blanco desaparece.
    $cardImgBg = trim((string) ($settings['card_image_bg'] ?? '')) ?: '#fafaf9';
    // Alto del hero en pixeles, separado por dispositivo. Antes solo aceptaba
    // small/medium/large y un valor numerico se descartaba en silencio.
    $heroPxDesktop = (int) ($settings['hero_px_desktop'] ?? 0);
    $heroPxMobile  = (int) ($settings['hero_px_mobile'] ?? 0);
    // Categorias visibles en movil antes del boton "Ver todas".
    $catsMobileLimit = (int) ($settings['cats_mobile_limit'] ?? 0);
    $trustSectionStyle = in_array(($settings['trust_section_style'] ?? 'cards'), ['cards','compact','icons-top','tiles','band','outline','stripe','inline','linea'], true)
        ? ($settings['trust_section_style'] ?? 'cards') : 'cards';
    $trustSectionColumns = max(2, min(4, (int) ($settings['trust_section_columns'] ?? 4)));
    $trustSectionMobileColumns = max(1, min(2, (int) ($settings['trust_section_mobile_columns'] ?? 1)));
    $trustSectionRadius = max(0, min(28, (int) ($settings['trust_section_radius'] ?? 16)));
    $trustSectionBg = $color($settings['trust_section_bg'] ?? null, '#f8fafc');
    $trustCardBg = $color($settings['trust_card_bg'] ?? null, '#ffffff');
    $trustTextColor = $color($settings['trust_text_color'] ?? null, '#0f172a');
    $trustAccentColor = $color($settings['trust_accent_color'] ?? null, $primary);
    // Tinta legible sobre el acento (acentos claros → texto oscuro).
    $trustAccentIsLight = (static function (string $hex): bool {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) return false;
        return (0.299 * hexdec(substr($hex, 0, 2)) + 0.587 * hexdec(substr($hex, 2, 2)) + 0.114 * hexdec(substr($hex, 4, 2))) / 255 > 0.62;
    })($trustAccentColor);
    $trustOnAccent = $trustAccentIsLight ? '#0f172a' : '#ffffff';
    $trustOnAccentSoft = $trustAccentIsLight ? 'rgba(15,23,42,.72)' : 'rgba(255,255,255,.85)';
    $trustShowDescriptions = (string) ($settings['trust_show_descriptions'] ?? '1') !== '0';
    $trustMobileCarousel = (string) ($settings['trust_mobile_carousel'] ?? '0') === '1';

    // Banda de confianza: hasta 6 beneficios configurables (los 4 primeros con
    // textos por defecto; 5 y 6 solo aparecen si el negocio los escribe).
    $trustDefaults = [
        1 => ['t' => 'Retiro en tienda', 'd' => 'Coordina y recoge tu pedido.', 'i' => 'store'],
        2 => ['t' => 'Envíos a todo el Perú', 'd' => 'Cobertura según destino.', 'i' => 'truck'],
        3 => ['t' => 'Entrega express', 'd' => 'Consulta disponibilidad en tu zona.', 'i' => 'clock'],
        4 => ['t' => 'Diseños exclusivos', 'd' => 'Opciones seleccionadas para ti.', 'i' => 'sparkles'],
        5 => ['t' => '', 'd' => '', 'i' => 'check'],
        6 => ['t' => '', 'd' => '', 'i' => 'check'],
    ];
    $trustBenefits = [];
    foreach ($trustDefaults as $tn => $td) {
        $item = [
            'title' => trim($settings["trust_text_{$tn}"] ?? $td['t']),
            'description' => trim($settings["trust_description_{$tn}"] ?? $td['d']),
            'icon' => $settings["trust_icon_{$tn}"] ?? $td['i'],
            'url' => trim($settings["trust_url_{$tn}"] ?? ''),
            'enabled' => (string) ($settings["trust_item_{$tn}_enabled"] ?? '1') !== '0',
        ];
        if ($item['enabled'] && $item['title'] !== '') $trustBenefits[] = $item;
    }

    // Fase 1E-A: el catálogo completo solo se materializa/serializa en la Tienda.
    // En Inicio evitamos construir y enviar toda la colección al navegador.
    $isCatalogView = ($storeView ?? 'home') === 'tienda';
    // Pool de productos para las secciones del Inicio (ofertas, descuentos, hero).
    // Se construye SIEMPRE desde las relaciones ya cargadas (sin queries extra):
    // si quedara vacío en home, secciones activas como "Descuentos" nunca podrían verse.
    // La serialización del catálogo sigue protegida aparte por $catalogProducts.
    $allProducts = $categories->flatMap(fn ($cat) => $cat->products->concat($cat->children->flatMap(fn ($child) => $child->products)))->unique('id')->values();
    $catalogProducts = collect();
    if ($isCatalogView)
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
                'url' => is_numeric($product->id) ? \App\Support\ImageVariants::productUrl($project, $product->id, $product->name) : null,
                'wholesalePrice' => filled($product->wholesale_price) ? (float) $product->wholesale_price : null,
                'wholesaleMinQty' => (int) ($product->wholesale_min_qty ?? 1),
                'wholesaleUnit' => (filled($product->wholesale_unit) && !is_numeric($product->wholesale_unit)) ? $product->wholesale_unit : 'unidades',
                'sizes' => $product->sizes,
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
                    'url' => is_numeric($product->id) ? \App\Support\ImageVariants::productUrl($project, $product->id, $product->name) : null,
                    'wholesalePrice' => filled($product->wholesale_price) ? (float) $product->wholesale_price : null,
                    'wholesaleMinQty' => (int) ($product->wholesale_min_qty ?? 1),
                    'wholesaleUnit' => (filled($product->wholesale_unit) && !is_numeric($product->wholesale_unit)) ? $product->wholesale_unit : 'unidades',
                'sizes' => $product->sizes,
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
    // Alto del header y logo SIN TOPE: el vendedor controla el tamaño libremente.
    $headerHeight = (int) ($settings['header_height'] ?? 78) ?: 78;
    $logoHeight = (int) ($settings['header_logo_height'] ?? $settings['logo_height'] ?? 48) ?: 48;
    $menuAlign = in_array(($settings['menu_align'] ?? 'left'), ['left','center','right'], true) ? ($settings['menu_align'] ?? 'left') : 'left';
    // Colores personalizados del menú (opcionales; pisan cualquier diseño elegido).
    $menuCustomColor = static fn (string $k) => (is_string($settings[$k] ?? null) && preg_match('/^#[0-9a-fA-F]{6}$/', $settings[$k] ?? '')) ? $settings[$k] : null;
    $menuBgC = $menuCustomColor('menu_bg_color');
    $menuInkC = $menuCustomColor('menu_text_color');
    $menuActBgC = $menuCustomColor('menu_active_bg_color');
    $menuActInkC = $menuCustomColor('menu_active_text_color');
    // Fondo global de la página (pinta el lienzo y las secciones claras; respeta las oscuras).
    $pageBgC = $menuCustomColor('page_bg_color');
    // Hero: alto
    $heroHeightMap = ['small' => 360, 'medium' => 460, 'large' => 560];
    $rawHeroH   = $settings['hero_height'] ?? 'medium';
    $heroMinH   = is_numeric($rawHeroH)
        ? max(300, min(760, (int) $rawHeroH))
        : ($heroHeightMap[$rawHeroH] ?? 460);
    // Colores del footer
    $footerBg   = $color($settings['footer_bg_color'] ?? null, $secondary);
    // Diseño del pie de página: oscuro clásico, claro elegante, color de marca, compacto centrado.
    $footerStyle = in_array(($settings['footer_style'] ?? 'classic'), ['classic', 'light', 'accent', 'minimal'], true)
        ? ($settings['footer_style'] ?? 'classic') : 'classic';
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
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        // SEO por vista: antes todas las paginas compartian titulo y no habia
        // canonico, asi que buscadores veian la tienda entera como una sola URL.
        $seoBase = ($settings['seo_canonical'] ?? null)
            ?: ($project->custom_domain ? 'https://'.trim($project->custom_domain, '/') : url('/'.$project->slug));
        $seoProducto = ($storeView ?? 'home') === 'producto' && !empty($storeProduct) ? $storeProduct : null;
        $seoTitulo = $seoProducto
            ? $seoProducto->name.' | '.$storeName
            : match ($storeView ?? 'home') {
                'tienda'   => (!empty($activeProfile) ? $activeProfile->name : 'Tienda').' | '.$storeName,
                'nosotros' => 'Nosotros | '.$storeName,
                'contacto' => 'Contacto | '.$storeName,
                default    => $storeName.($heroSubtitle ? ' — '.\Illuminate\Support\Str::limit(strip_tags($heroSubtitle), 60, '') : ''),
            };
        $seoDesc = $seoProducto
            ? \Illuminate\Support\Str::limit(strip_tags((string) ($seoProducto->description ?: $seoProducto->name)), 155, '')
            : (string) ($settings['seo_description'] ?? $heroSubtitle);
        $seoCanonical = $seoProducto
            ? $seoBase.'/producto/'.\App\Support\ImageVariants::claveProducto($seoProducto->id, $seoProducto->name)
            : $seoBase.match ($storeView ?? 'home') {
                'tienda'   => '/tienda'.(!empty($activeProfile) ? '/'.$activeProfile->slug : ''),
                'nosotros' => '/nosotros',
                'contacto' => '/contacto',
                default    => '',
            };
        $seoImagen = $seoProducto && $seoProducto->main_image_url ? $assetUrl($seoProducto->main_image_url) : $assetUrl($settings['logo_url'] ?? null);
    @endphp
    <meta name="description" content="{{ $seoDesc }}">
    <title data-seo="page">{{ $seoTitulo }}</title>
    <link rel="canonical" href="{{ $seoCanonical }}">
    <meta name="robots" content="index, follow">
    <meta property="og:type" content="{{ $seoProducto ? 'product' : 'website' }}">
    <meta property="og:site_name" content="{{ $storeName }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:title" content="{{ $seoTitulo }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    @if($seoImagen)<meta property="og:image" content="{{ $seoImagen }}">@endif
    <meta property="og:locale" content="es_PE">
    <meta name="twitter:card" content="summary_large_image">
    @if($seoProducto)
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $seoProducto->name,
        'description' => $seoDesc,
        'sku' => $seoProducto->sku ?: (string) $seoProducto->id,
        'image' => array_filter([$seoImagen]),
        'url' => $seoCanonical,
        'brand' => ['@type' => 'Brand', 'name' => $storeName],
        'offers' => array_filter([
            '@type' => 'Offer',
            'url' => $seoCanonical,
            'priceCurrency' => 'PEN',
            'price' => (float) $seoProducto->price > 0 ? number_format((float) $seoProducto->price, 2, '.', '') : null,
            'availability' => ((int) $seoProducto->stock) > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        ]),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif
    @if(!empty($settings['favicon_url']))
    <link rel="icon" href="{{ \App\Support\ImageVariants::favicon($assetUrl($settings['favicon_url'])) }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ $googleFonts }}&display=swap" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root{
          --primary:{{ $primary }};
          --secondary:{{ $secondary }};
          --accent:{{ $accent }};
          --sale:{{ $saleColor }};
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
          --border:{{ $settings['border_color'] ?? '#e2e8f0' }};
          --border-warm:{{ $settings['border_warm_color'] ?? '#e5d8c2' }};
          --surface:{{ $settings['surface_color'] ?? '#ffffff' }};
          --surface-soft:{{ $settings['surface_soft_color'] ?? '#f8fafc' }};
          --text-strong:{{ $settings['text_strong_color'] ?? '#0f172a' }};
          --text:{{ $settings['text_color'] ?? '#334155' }};
          --muted:{{ $settings['text_muted_color'] ?? '#64748b' }};
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
        @if($themePresetDef)
        /* ═══ Tema visual ({{ $settings['theme_preset'] }}): tokens que sobrescriben :root ═══ */
        :root{
        @foreach($themePresetDef['tokens'] as $token => $value)
          {{ $token }}:{{ $value }};
        @endforeach
        }
        body{background:var(--surface);color:var(--text)}
        .{{ $themeBodyClass }} [data-store-native-section]{background:var(--surface)}
        .{{ $themeBodyClass }} h1,.{{ $themeBodyClass }} h2,.{{ $themeBodyClass }} h3{color:var(--text-strong)}
        @if($themeIsDark)
        .{{ $themeBodyClass }} .catalog-card,.{{ $themeBodyClass }} .pf-card{background:var(--surface-soft);border-color:var(--border)}
        .{{ $themeBodyClass }} .catalog-card-name,.{{ $themeBodyClass }} .pf-name{color:var(--text-strong)}
        .{{ $themeBodyClass }} .section-heading p,.{{ $themeBodyClass }} .pf-desc{color:var(--muted)}
        @endif

        @if($themeBodyClass === 'theme-tech-dark')
        /* ── Identidad TECH: retícula sutil, acentos con brillo, energía ── */
        .theme-tech-dark{background-image:linear-gradient(rgba(148,163,184,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(148,163,184,.045) 1px,transparent 1px);background-size:44px 44px}
        .theme-tech-dark [data-store-native-section]{background:transparent}
        .theme-tech-dark h2{letter-spacing:-.03em}
        .theme-tech-dark .section-heading h2,.theme-tech-dark .xs-head h2,.theme-tech-dark .home-section-copy h2{position:relative;padding-left:18px}
        .theme-tech-dark .section-heading h2::before,.theme-tech-dark .xs-head h2::before,.theme-tech-dark .home-section-copy h2::before{content:"";position:absolute;left:0;top:.18em;bottom:.18em;width:4px;border-radius:4px;background:var(--primary);box-shadow:0 0 16px color-mix(in srgb,var(--primary) 75%,transparent)}
        .theme-tech-dark .button-primary,.theme-tech-dark .product-action{box-shadow:0 0 0 rgba(0,0,0,0);transition:box-shadow .22s ease,transform .18s ease,filter .18s ease}
        .theme-tech-dark .button-primary:hover,.theme-tech-dark .product-action:hover{box-shadow:0 0 22px color-mix(in srgb,var(--primary) 55%,transparent)}
        .theme-tech-dark .catalog-card:hover,.theme-tech-dark .pf-card:hover{border-color:color-mix(in srgb,var(--primary) 60%,var(--border))}
        .theme-tech-dark .premium-hero{border-bottom:1px solid color-mix(in srgb,var(--primary) 35%,transparent)}
        .theme-tech-dark .site-footer,.theme-tech-dark footer{border-top:1px solid color-mix(in srgb,var(--primary) 40%,transparent)}
        .theme-tech-dark ::selection{background:color-mix(in srgb,var(--primary) 60%,transparent);color:#fff}
        @endif

        @if($themeBodyClass === 'theme-soft-kids')
        /* ── Identidad INFANTIL: lunares, subrayado de crayón, formas jugosas ── */
        .theme-soft-kids{background-image:radial-gradient(color-mix(in srgb,var(--primary) 9%,transparent) 2.2px,transparent 2.6px);background-size:26px 26px}
        .theme-soft-kids [data-store-native-section]{background:transparent}
        .theme-soft-kids .section-heading h2,.theme-soft-kids .xs-head h2,.theme-soft-kids .home-section-copy h2{display:inline-block;background:linear-gradient(transparent 62%,color-mix(in srgb,var(--primary) 28%,transparent) 62%,color-mix(in srgb,var(--primary) 28%,transparent) 92%,transparent 92%);padding:0 6px;border-radius:6px}
        .theme-soft-kids .button,.theme-soft-kids .product-action,.theme-soft-kids .catalog-card-action{border-radius:999px!important}
        .theme-soft-kids .button-primary:hover{transform:translateY(-2px) scale(1.03)}
        .theme-soft-kids .catalog-card:hover,.theme-soft-kids .pf-card:hover{transform:translateY(-5px) rotate(-.6deg)}
        .theme-soft-kids .premium-hero{border-radius:0 0 34px 34px;overflow:hidden}
        .theme-soft-kids .catalog-card-image img,.theme-soft-kids .pf-media img{border-radius:var(--radius-md)}
        .theme-soft-kids .hc-circle,.theme-soft-kids .xs-coll-card{border:3px solid #fff;outline:2px dashed color-mix(in srgb,var(--primary) 40%,transparent);outline-offset:4px}
        @endif

        @if($themeBodyClass === 'theme-warm-home')
        /* ── Identidad HOGAR: editorial serif, filetes finos, aire generoso ── */
        .theme-warm-home [data-store-native-section]{padding-top:clamp(56px,7vw,92px);padding-bottom:clamp(56px,7vw,92px)}
        .theme-warm-home .section-heading h2,.theme-warm-home .xs-head h2,.theme-warm-home .home-section-copy h2{font-weight:600;letter-spacing:.01em}
        .theme-warm-home .section-heading h2::after,.theme-warm-home .xs-head h2::after,.theme-warm-home .home-section-copy h2::after{content:"";display:block;width:54px;height:2px;margin-top:12px;background:var(--primary)}
        body.section-heading-center.theme-warm-home .section-heading h2::after,body.section-heading-center.theme-warm-home .xs-head h2::after{margin-inline:auto}
        .theme-warm-home .button,.theme-warm-home .product-action{border-radius:2px!important;letter-spacing:.06em;text-transform:uppercase;font-size:13px}
        .theme-warm-home .catalog-card,.theme-warm-home .pf-card{border:0;border-bottom:1px solid var(--border);background:transparent;box-shadow:none}
        .theme-warm-home .catalog-card:hover,.theme-warm-home .pf-card:hover{background:var(--surface-soft);box-shadow:none}
        .theme-warm-home .catalog-card-image,.theme-warm-home .pf-media{background:var(--surface-soft)}
        .theme-warm-home .premium-hero{margin:0 clamp(0px,2vw,28px);border-radius:0}
        .theme-warm-home .price,.theme-warm-home .catalog-card-price{font-family:var(--font-title)}
        @endif

        @if($themeBodyClass === 'theme-high-contrast')
        /* ── Identidad ELÉCTRICA: brutalista, franjas de seguridad, contundente ── */
        .theme-high-contrast .section-heading h2,.theme-high-contrast .xs-head h2,.theme-high-contrast .home-section-copy h2{text-transform:uppercase;letter-spacing:.02em;font-weight:800}
        .theme-high-contrast .section-heading h2::after,.theme-high-contrast .xs-head h2::after,.theme-high-contrast .home-section-copy h2::after{content:"";display:block;width:88px;height:8px;margin-top:10px;background:repeating-linear-gradient(-45deg,var(--text-strong) 0 8px,var(--primary) 8px 16px)}
        .theme-high-contrast .button,.theme-high-contrast .product-action,.theme-high-contrast .catalog-card-action{border:2px solid var(--text-strong)!important;border-radius:0!important;font-weight:800;text-transform:uppercase;letter-spacing:.03em}
        .theme-high-contrast .button-primary{box-shadow:4px 4px 0 var(--text-strong)}
        .theme-high-contrast .button-primary:hover{transform:translate(-2px,-2px);box-shadow:6px 6px 0 var(--text-strong)}
        .theme-high-contrast [data-store-native-section]{border-bottom:2px solid var(--text-strong)}
        .theme-high-contrast .premium-hero{border-bottom:6px solid var(--text-strong)}
        .theme-high-contrast .catalog-card-image,.theme-high-contrast .pf-media{border-bottom:2px solid var(--text-strong)}
        .theme-high-contrast .sf-sale,.theme-high-contrast .catalog-badge{border-radius:0;font-weight:900}
        .theme-high-contrast .site-footer,.theme-high-contrast footer{border-top:6px solid var(--primary)}
        @endif

        @if($themeBodyClass === 'theme-minimal')
        /* ── Identidad MINIMAL: nada sobra, tipografía y aire mandan ── */
        .theme-minimal .catalog-card,.theme-minimal .pf-card{border:0;background:transparent;box-shadow:none}
        .theme-minimal .catalog-card:hover img,.theme-minimal .pf-card:hover img{opacity:.88}
        .theme-minimal .button,.theme-minimal .product-action{border-radius:0!important}
        .theme-minimal .section-heading h2,.theme-minimal .xs-head h2{font-weight:500;letter-spacing:-.01em}
        .theme-minimal [data-store-native-section]{border:0}
        @endif

        /* ═══ Legibilidad garantizada: islas claras dentro del tema oscuro ═══
           Los paneles que siguen siendo blancos (checkout, filtros, modales,
           páginas internas) conservan texto oscuro aunque el tema fuerce
           títulos claros a nivel global. */
        .theme-tech-dark :is(.ck-modal-header,[class^=ck-],[class*=" ck-"]) :is(h1,h2,h3,h4,strong,label){color:#0f172a}
        .theme-tech-dark .catalog-filter-panel :is(h1,h2,h3,h4,strong,label,span){color:#334155}
        .theme-tech-dark .catalog-filter-panel strong{color:#0f172a}
        .theme-tech-dark .store-page :is(h1,h2,h3,p,li){color:var(--text)}
        .theme-tech-dark .store-page :is(h1,h2,h3){color:var(--text-strong)}
        .theme-tech-dark .quantity span{color:var(--text-strong)}
        .theme-tech-dark input,.theme-tech-dark select,.theme-tech-dark textarea{color:#0f172a}
        .theme-high-contrast .topbar a,.theme-high-contrast .topbar span{color:#fff!important}

        /* ═══ Correcciones de legibilidad detectadas en revisión visual ═══ */
        /* Tema oscuro: ninguna sección de portada puede quedar blanca con títulos claros */
        .theme-tech-dark [data-store-native-section]{background:transparent!important}
        .theme-tech-dark .catalog,.theme-tech-dark [data-store-native-section="catalog"]{background:var(--surface)!important}
        /* Botón secundario/fantasma legible en todos los temas */
        .theme-high-contrast .premium-hero .button-ghost{color:var(--text-strong)!important;border:2px solid var(--text-strong)!important;background:rgba(255,255,255,.85)}
        .theme-high-contrast .button-secondary{color:var(--text-strong)!important;border:2px solid var(--text-strong)!important}
        .theme-warm-home .button-secondary,.theme-soft-kids .button-secondary{color:#334155}
        /* Topbar hogar: el color inline del texto no puede ganar al tema claro */
        .theme-warm-home .topbar p,.theme-warm-home .topbar a{color:var(--text-strong)!important}
        /* Subtítulo del hero sobre fondos claros: contraste real */
        /* Solo cuando el hero NO tiene imagen de fondo: con foto el texto va sobre
           el degradado oscuro y el gris resultaba ilegible. */
        .theme-warm-home .premium-hero:not(.has-bg) .ph-sub,
        .theme-soft-kids .premium-hero:not(.has-bg) .ph-sub{color:#4b5563!important}
        .premium-hero.has-bg .ph-sub{color:rgba(255,255,255,.94)!important}
        /* Badge del hero sobre fondo claro: contraste real (se leia casi nada) */
        .premium-hero:not(.has-bg) .ph-eyebrow,.premium-hero:not(.has-bg) .ph-badge{color:var(--text-strong,#0f172a)!important;background:color-mix(in srgb,var(--primary) 12%,#fff)!important;border:1px solid color-mix(in srgb,var(--primary) 26%,transparent)!important}
        /* Título del hero alineado a la izquierda: que no quede bajo la flecha */
        .premium-hero:has(.ph-arrow) .premium-hero-copy:not(.is-center):not(.is-right){padding-left:64px}
        @media(max-width:760px){.premium-hero:has(.ph-arrow) .premium-hero-copy:not(.is-center):not(.is-right){padding-left:20px}}

        /* ═══ Cabecera, barra superior y footer con firma propia por tema ═══ */
        .theme-tech-dark .topbar{background:linear-gradient(90deg,var(--secondary),color-mix(in srgb,var(--primary) 45%,var(--secondary)))!important;letter-spacing:.14em;text-transform:uppercase;font-size:11px}
        .theme-tech-dark .header-zone{border-bottom:1px solid color-mix(in srgb,var(--primary) 35%,transparent);backdrop-filter:blur(8px)}
        .theme-tech-dark .site-footer{background:#070d1a!important;background-image:linear-gradient(rgba(148,163,184,.05) 1px,transparent 1px),linear-gradient(90deg,rgba(148,163,184,.05) 1px,transparent 1px);background-size:44px 44px}
        .theme-tech-dark .footer-col h4{text-transform:uppercase;letter-spacing:.12em;font-size:12px;color:var(--primary)}
        .theme-tech-dark .footer-links a:hover{color:var(--primary);text-shadow:0 0 12px color-mix(in srgb,var(--primary) 60%,transparent)}
        .theme-warm-home .topbar{background:var(--surface-soft)!important;color:var(--text-strong)!important;border-bottom:1px solid var(--border);letter-spacing:.22em;text-transform:uppercase;font-size:10.5px;font-weight:600}
        .theme-warm-home .header-zone{border-bottom:1px solid var(--border)}
        .theme-warm-home .site-footer{background:var(--surface-soft)!important;color:var(--text)!important;border-top:1px solid var(--border)}
        .theme-warm-home .site-footer a,.theme-warm-home .footer-contact li{color:var(--text)!important}
        .theme-warm-home .footer-col h4{font-family:var(--font-title);font-weight:600;font-size:17px;color:var(--text-strong);text-transform:none;letter-spacing:0}
        .theme-warm-home .footer-col h4::after{content:"";display:block;width:34px;height:1px;margin-top:8px;background:var(--primary)}
        .theme-warm-home .footer-bottom{border-top:1px solid var(--border)}
        .theme-soft-kids .topbar{border-radius:0 0 16px 16px;font-weight:700}
        .theme-soft-kids .header-zone{box-shadow:0 6px 24px color-mix(in srgb,var(--primary) 12%,transparent)}
        .theme-soft-kids .site-footer{border-radius:34px 34px 0 0;overflow:hidden}
        .theme-soft-kids .footer-col h4{font-family:var(--font-title);font-size:17px}
        .theme-soft-kids .footer-links a{border-radius:999px}
        .theme-high-contrast .topbar{background:repeating-linear-gradient(-45deg,var(--text-strong) 0 14px,color-mix(in srgb,var(--primary) 88%,black) 14px 28px)!important;color:#fff!important;font-weight:800;text-transform:uppercase;text-shadow:0 1px 2px rgba(0,0,0,.6)}
        .theme-high-contrast .header-zone{border-bottom:3px solid var(--text-strong)}
        .theme-high-contrast .site-footer{border-top:6px solid var(--primary)}
        .theme-high-contrast .footer-col h4{text-transform:uppercase;letter-spacing:.05em;font-weight:800;border-left:6px solid var(--primary);padding-left:10px}

        /* ═══ El tema también viste carrito, checkout y micro-interacciones ═══ */
        .theme-tech-dark .cart-drawer,.theme-tech-dark .cartpage{background:var(--surface-soft);color:var(--text)}
        .theme-tech-dark .drawer-head h2,.theme-tech-dark .cart-item strong,.theme-tech-dark .cart-total{color:var(--text-strong)}
        .theme-tech-dark .drawer-head,.theme-tech-dark .drawer-footer,.theme-tech-dark .cart-item{border-color:var(--border)}
        .theme-tech-dark .icon-button,.theme-tech-dark .quantity button{background:var(--surface);color:var(--text);border-color:var(--border)}
        .theme-tech-dark .ck-submit{box-shadow:0 0 18px color-mix(in srgb,var(--primary) 40%,transparent)}
        .theme-tech-dark .cart-trigger:hover{filter:drop-shadow(0 0 8px color-mix(in srgb,var(--primary) 70%,transparent))}
        .theme-soft-kids .cart-drawer{border-radius:26px 0 0 26px}
        .theme-soft-kids .ck-submit,.theme-soft-kids .quantity{border-radius:999px!important;overflow:hidden}
        .theme-soft-kids .cart-count{border-radius:999px;transform:scale(1.1)}
        .theme-warm-home .cart-drawer{box-shadow:-24px 0 60px rgba(120,90,60,.18)}
        .theme-warm-home .drawer-head h2{font-family:var(--font-title);font-weight:600}
        .theme-warm-home .ck-submit{border-radius:2px!important;letter-spacing:.08em;text-transform:uppercase}
        .theme-high-contrast .cart-drawer{border-left:3px solid var(--text-strong);box-shadow:none}
        .theme-high-contrast .drawer-head,.theme-high-contrast .drawer-footer{border-color:var(--text-strong)}
        .theme-high-contrast .ck-submit{border:2px solid var(--text-strong)!important;border-radius:0!important;box-shadow:4px 4px 0 var(--text-strong);text-transform:uppercase;font-weight:800}
        .theme-high-contrast .quantity{border:2px solid var(--text-strong);border-radius:0}
        .theme-high-contrast .cart-count{border-radius:0;font-weight:900}

        /* ═══ Animaciones de entrada por tema (solo si el JS las activa; respetan reduced-motion) ═══ */
        .sf-reveal-ready.{{ $themeBodyClass }} [data-store-native-section]{opacity:0;transform:translateY(22px);transition:opacity .6s ease,transform .6s ease}
        .sf-reveal-ready.theme-soft-kids [data-store-native-section]{transform:translateY(26px) scale(.985);transition:opacity .55s ease,transform .55s cubic-bezier(.34,1.56,.64,1)}
        .sf-reveal-ready.theme-warm-home [data-store-native-section]{transform:translateY(14px);transition:opacity .9s ease,transform .9s ease}
        .sf-reveal-ready.theme-high-contrast [data-store-native-section]{transform:translateX(-14px);transition:opacity .32s ease,transform .32s ease}
        /* Animación ELEGIDA por sección en el constructor: pisa el default del tema */
        .sf-reveal-ready [data-store-native-section][data-anim]{transition-duration:var(--anim-dur,.6s)!important;transition-delay:var(--anim-delay,0s)!important}
        .sf-reveal-ready [data-store-native-section][data-anim="none"]{opacity:1!important;transform:none!important;transition:none!important}
        .sf-reveal-ready [data-store-native-section][data-anim="fade"]{opacity:0;transform:none}
        .sf-reveal-ready [data-store-native-section][data-anim="up"]{opacity:0;transform:translateY(28px)}
        .sf-reveal-ready [data-store-native-section][data-anim="down"]{opacity:0;transform:translateY(-24px)}
        .sf-reveal-ready [data-store-native-section][data-anim="left"]{opacity:0;transform:translateX(-28px)}
        .sf-reveal-ready [data-store-native-section][data-anim="right"]{opacity:0;transform:translateX(28px)}
        .sf-reveal-ready [data-store-native-section][data-anim="zoom"]{opacity:0;transform:scale(.94)}
        .sf-reveal-ready [data-store-native-section].sf-in{opacity:1!important;transform:none!important}
        @media(prefers-reduced-motion:reduce){.sf-reveal-ready [data-store-native-section]{opacity:1!important;transform:none!important;transition:none!important}}
        @endif
        /* ═══ Variantes de tarjeta de producto (estructura, no solo color) ═══ */
        .cards-tech .catalog-card,.cards-tech .pf-card{border-radius:var(--radius-md);border-width:1px;position:relative}
        .cards-tech .catalog-card::before,.cards-tech .pf-card::before{content:"";position:absolute;inset:auto 0 0 0;height:3px;background:linear-gradient(90deg,var(--primary),color-mix(in srgb,var(--primary) 30%,transparent));opacity:0;transition:opacity .2s}
        .cards-tech .catalog-card:hover::before,.cards-tech .pf-card:hover::before{opacity:1}
        .cards-soft .catalog-card,.cards-soft .pf-card{border-radius:var(--radius-lg);border-color:transparent;box-shadow:var(--shadow-md)}
        .cards-soft .catalog-card:hover,.cards-soft .pf-card:hover{transform:translateY(-4px) rotate(-.4deg)}
        .cards-elegant .catalog-card,.cards-elegant .pf-card{border-radius:var(--radius-sm);border-color:var(--border);box-shadow:none}
        .cards-elegant .catalog-card:hover,.cards-elegant .pf-card:hover{box-shadow:var(--shadow-lg)}
        .cards-contrast .catalog-card,.cards-contrast .pf-card{border:2px solid var(--text-strong);border-radius:var(--radius-sm);box-shadow:var(--shadow-md)}
        .cards-contrast .catalog-card:hover,.cards-contrast .pf-card:hover{transform:translate(-2px,-2px);box-shadow:var(--shadow-lg)}
        .product-action,.catalog-card-action,.button{border-radius:var(--btn-radius)!important}
        /* Fase 2B: accesibilidad y consistencia (usa variables configurables) */
        .catalog-card-action:focus-visible,.button:focus-visible,.catalog-card-name:focus-visible,.catalog-sort:focus-visible,.catalog-clear:focus-visible,.catalog-chip:focus-visible,.catalog-mobile-filter:focus-visible{outline:2px solid var(--primary);outline-offset:2px}
        .catalog-card{transition:border-color .18s ease,box-shadow .18s ease,transform .18s ease}
        .catalog-loadmore .button{min-width:200px;transition:filter .15s ease,opacity .15s ease}
        .catalog-loadmore .button:disabled{opacity:.6;cursor:progress}
        .catalog-loading,.catalog-error{font-size:14px}
        @media(prefers-reduced-motion:reduce){.catalog-card,.catalog-loadmore .button{transition:none}}

        /* ═══ Sistema global de estilos de secciones ═══ */
        body.section-spacing-compact [data-store-native-section]{padding-top:42px!important;padding-bottom:42px!important}
        body.section-spacing-comfortable [data-store-native-section]{padding-top:68px!important;padding-bottom:68px!important}
        {{-- Densidad "ecommerce": aire proporcional a la pantalla y sin doble
             espacio entre bloques consecutivos (el de arriba ya lo aporta). --}}
        body.section-spacing-dense [data-store-native-section]{padding-top:clamp(26px,3.2vw,46px)!important;padding-bottom:clamp(26px,3.2vw,46px)!important}
        body.section-spacing-dense [data-store-native-section] + [data-store-native-section]{padding-top:0!important}
        body.section-spacing-dense .hero,body.section-spacing-dense [data-store-native-section="hero"]{padding-top:0!important;padding-bottom:0!important}
        {{-- Una sección sin contenido real nunca debe reservar alto. --}}
        [data-store-native-section]:empty{display:none!important;padding:0!important;min-height:0!important}
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
        body.section-bg-white [data-store-native-section]:not(.premium-hero):not(.flash-sale-section):not(.style-band){background:#fff!important}
        body.section-bg-soft [data-store-native-section]:not(.premium-hero):not(.flash-sale-section):not(.style-band){background:#f8fafc!important}
        body.section-bg-alternate [data-store-native-section]:nth-of-type(even):not(.premium-hero):not(.flash-sale-section):not(.style-band){background:#f8fafc}
        body.section-bg-alternate [data-store-native-section]:nth-of-type(odd):not(.premium-hero):not(.flash-sale-section):not(.style-band){background:#fff}
        body.section-no-dividers [data-store-native-section]{border-top:0!important;border-bottom:0!important}
        body.section-dividers [data-store-native-section]{border-bottom:1px solid var(--border)}
        body.section-no-shadows .home-cat-card,
        body.section-no-shadows .trust-card,
        body.section-no-shadows .pf-card,
        body.section-no-shadows .catalog-card,
        body.section-no-shadows .promo-slider-shell,
        body.section-no-shadows .promo-grid .promo-slide{box-shadow:none!important}

        /* ═══ PRESET "COMERCIAL": escala 8pt, densidad ecommerce y jerarquía
           tipográfica mayor. Solo aplica a tiendas que lo eligen. ═══ */
        body.section-preset-commerce{--sp-1:8px;--sp-2:16px;--sp-3:24px;--sp-4:32px;--sp-6:48px;--sp-8:64px;--sp-10:80px}
        /* Contenedor más ancho y con respiro lateral */
        body.section-preset-commerce{--header-layout-width:min(1680px,calc(100vw - 96px))}
        body.section-preset-commerce .container{width:min(1680px,calc(100% - 96px))}
        @media(max-width:1024px){body.section-preset-commerce .container{width:calc(100% - 48px)}}
        @media(max-width:640px){body.section-preset-commerce .container{width:calc(100% - 32px)}}
        /* Topbar delgada */
        body.section-preset-commerce .topbar{font-size:12.5px;font-weight:500}
        body.section-preset-commerce .topbar-inner{min-height:40px}
        /* Header con presencia: logo grande y buscador protagonista */
        body.section-preset-commerce .header-main{min-height:104px;grid-template-columns:minmax(200px,240px) minmax(420px,1.6fr) minmax(220px,auto)}
        body.section-preset-commerce .hpx-main{padding-top:16px;padding-bottom:16px;gap:28px}
        body.section-preset-commerce .hpx-search{max-width:none;flex:1 1 52%}
        body.section-preset-commerce .hpx-main .brand{flex:0 0 auto}
        {{-- §7: altura de la referencia, con el contenido centrado (sin aire muerto) --}}
        body.section-preset-commerce .store-header{min-height:112px!important;display:flex;align-items:center}
        body.section-preset-commerce .header-zone{min-height:0!important}
        body.section-preset-commerce .hpx-main{width:100%;padding-top:0!important;padding-bottom:0!important}
        body.section-preset-commerce .brand-logo{width:auto!important;max-width:210px!important;height:auto!important;max-height:96px!important}
        body.section-preset-commerce .brand{flex:0 0 auto;min-width:200px}
        body.section-preset-commerce .search input{height:48px;border-radius:7px;font-size:14.5px}
        body.section-preset-commerce .phone-copy{border-right:0;padding-right:12px}
        body.section-preset-commerce .phone-copy strong{color:var(--secondary);font-size:14px}
        /* Navegación: botón de categorías dorado y activo subrayado */
        body.section-preset-commerce .category-nav{background:var(--surface);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
        body.section-preset-commerce .category-bar,
        body.section-preset-commerce .category-nav>.container.category-bar{min-height:60px!important;padding-top:0!important;padding-bottom:0!important}
        body.section-preset-commerce .category-nav .container{padding-top:0;padding-bottom:0}
        body.section-preset-commerce .mega-btn{min-height:46px;padding:0 22px;border-radius:6px;background:var(--accent,var(--primary));color:#fff;font-weight:700}
        body.section-preset-commerce .category-list a{padding:18px 0;margin-right:30px;color:var(--text-strong);font-size:13.5px;font-weight:600;border-radius:0;background:none}
        body.section-preset-commerce .category-list a.is-active{color:var(--secondary);background:none;box-shadow:inset 0 -3px 0 var(--accent,var(--primary))}
        /* Hero: alto de referencia, esquinas suaves y overlay lateral */
        body.section-preset-commerce .premium-hero,body.section-preset-commerce .ph-hero,body.section-preset-commerce .hero{min-height:clamp(334px,26vw,380px)!important;border-radius:12px!important;overflow:hidden}
        /* ── §4/§28: el hero de fallback NO debe usar la decoración abstracta.
           En el preset comercial se comporta como la banda fotográfica de la
           referencia: navy a la izquierda, contenido legible, sin rejillas. ── */
        body.section-preset-commerce .premium-hero .ph-grid,
        body.section-preset-commerce .premium-hero .ph-line,
        body.section-preset-commerce .premium-hero .ph-glow{display:none!important}
        body.section-preset-commerce .premium-hero{min-height:clamp(334px,24vw,360px)!important;max-height:380px;display:flex;align-items:center;overflow:hidden}
        body.section-preset-commerce .premium-hero-inner{width:min(1400px,calc(100% - 96px));padding:0!important;min-height:0!important}
        body.section-preset-commerce .premium-hero-copy{max-width:520px;padding:26px 0}
        body.section-preset-commerce .premium-hero-visual{display:none}
        body.section-preset-commerce .ph-eyebrow{display:inline-block;background:transparent!important;border:0!important;padding:0!important;margin-bottom:18px;color:#CEB16D!important;font-size:13px!important;font-weight:600!important;letter-spacing:.04em!important;text-transform:uppercase}
        body.section-preset-commerce .ph-sub{color:rgba(255,255,255,.94)!important;font-size:16.5px!important;line-height:1.55!important;max-width:480px}
        /* Hero con foto: mas alto, CTAs en linea y degradado con transicion suave */
        body.section-preset-commerce .premium-hero.has-bg{min-height:480px!important;height:500px;max-height:560px!important;border-radius:12px;overflow:hidden}
        /* §11: contenido a la izquierda, ancho 500-560, padding 65 */
        body.section-preset-commerce .premium-hero.has-bg .ph-slide-copy{padding-left:65px}
        /* §11: etiqueta dorada y titulo 52-58 */
        body.section-preset-commerce .premium-hero.has-bg .ph-eyebrow{color:#E5B851!important;font-size:13px!important;font-weight:700!important;letter-spacing:.04em!important}
        /* La columna de texto medía 384px: los 2 CTA no cabían en una fila y se
           apilaban. Se amplía la columna y se fija la fila. */
        body.section-preset-commerce .premium-hero.has-bg .ph-slide-copy{max-width:600px!important;width:auto!important;flex:0 0 auto}
        body.section-preset-commerce .premium-hero.has-bg .ph-title,
        body.section-preset-commerce .premium-hero.has-bg .ph-sub{max-width:600px!important}
        .premium-hero .hero-actions{display:flex!important;flex-wrap:nowrap;align-items:center;gap:14px;margin-top:22px}
        .premium-hero .hero-actions .button{white-space:nowrap;flex:0 0 auto}
        @media(max-width:760px){
            /* El subtitulo del hero se salia de la caja en pantallas pequenas */
            .premium-hero .ph-sub,.premium-hero .ph-title,.premium-hero .ph-eyebrow{max-width:100%!important;overflow-wrap:break-word;hyphens:auto}
            .premium-hero .ph-slide-copy,.premium-hero .premium-hero-copy{max-width:100%!important;padding-left:18px!important;padding-right:18px!important;box-sizing:border-box}
            .premium-hero .hero-actions{flex-wrap:wrap;gap:10px;margin-top:16px}
            .premium-hero .hero-actions .button{flex:1 1 100%}
            /* Hero movil mas bajo y con velo para que el texto siempre se lea */
            body.section-preset-commerce .premium-hero.has-bg{min-height:460px!important;height:auto!important;max-height:520px!important;border-radius:0!important}
            body.section-preset-commerce .premium-hero.has-bg::before{background:linear-gradient(180deg,rgba(4,29,65,.86) 0%,rgba(4,29,65,.74) 45%,rgba(4,29,65,.88) 100%)!important}
            body.section-preset-commerce .premium-hero.has-bg .ph-slide-copy{max-width:none!important;padding:0 18px}
            body.section-preset-commerce .premium-hero.has-bg .ph-title{font-size:clamp(24px,7vw,32px)!important}
            body.section-preset-commerce .premium-hero.has-bg .ph-sub{font-size:14.5px!important}
            body.section-preset-commerce .premium-hero.has-bg::after{display:none}
        }
        .premium-hero .hero-actions>*{margin:0!important}
        .premium-hero.has-bg .button-ghost{border-color:rgba(255,255,255,.55);color:#fff;backdrop-filter:blur(2px)}
        .premium-hero.has-bg .button-ghost:hover{background:rgba(255,255,255,.12);border-color:#fff}
        body.section-preset-commerce .ph-title{font-size:clamp(32px,3.9vw,56px)!important;line-height:1.05!important;font-weight:750!important;letter-spacing:-.02em!important}
        /* §12: CTA principal dorado y secundario contorno blanco */
        body.section-preset-commerce .premium-hero.has-bg .hero-actions .button{min-height:50px;padding:0 24px;border-radius:5px;font-weight:700;font-size:14px}
        body.section-preset-commerce .premium-hero.has-bg .hero-actions .button-primary:hover{background:#B98A32!important;border-color:#B98A32!important}
        body.section-preset-commerce .premium-hero.has-bg .hero-actions .button-ghost{border:1px solid rgba(255,255,255,.8)!important;background:transparent!important;color:#fff!important}
        body.section-preset-commerce .premium-hero.has-bg .hero-actions .button-ghost:hover{background:#fff!important;color:var(--secondary)!important;border-color:#fff!important}
        body.section-preset-commerce .premium-hero .hero-actions .button-primary,
        body.section-preset-commerce .ph-slide-copy .hero-actions .button-primary{background:var(--accent,#C39C54)!important;border-color:var(--accent,#C39C54)!important;color:#fff!important}
        body.section-preset-commerce .premium-hero .hero-actions .button-ghost{border:1px solid rgba(255,255,255,.85)!important;color:#fff!important;background:transparent!important}
        /* Blindaje anti-FOUC: un SVG sin dimensiones se dibuja gigante hasta
           que aplica su regla; este tope lo impide en cualquier parte. */
        svg:not([width]){max-width:100%;max-height:64px}
        /* ── IMÁGENES DE MUESTRA (cualquier tienda): cuando no hay foto cargada,
           se dibuja una escena vectorial con los colores de la marca en vez de
           dejar el espacio vacío. Desaparece sola al subir la imagen real. ── */
        body.section-preset-commerce .premium-hero:not([style*="background-image"]) .premium-hero-visual{
            display:block!important;position:absolute;inset:0;width:100%;pointer-events:none;z-index:0;
            background-repeat:no-repeat;background-position:center right;background-size:cover;
            background-image:url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 800 560' preserveAspectRatio='xMidYMid slice'%3E%3Crect width='800' height='560' fill='%23142845'/%3E%3Crect x='0' y='430' width='800' height='130' fill='%231d3557'/%3E%3Crect x='90' y='250' width='330' height='120' rx='16' fill='%23c9b18a'/%3E%3Crect x='70' y='300' width='40' height='90' rx='12' fill='%23b89e75'/%3E%3Crect x='400' y='300' width='40' height='90' rx='12' fill='%23b89e75'/%3E%3Crect x='120' y='215' width='90' height='50' rx='10' fill='%23e0d3bb'/%3E%3Crect x='230' y='215' width='90' height='50' rx='10' fill='%23e0d3bb'/%3E%3Crect x='500' y='150' width='190' height='240' rx='12' fill='%23dfe6ef'/%3E%3Crect x='515' y='170' width='160' height='95' rx='6' fill='%23c3ccd9'/%3E%3Ccircle cx='595' cy='330' r='26' fill='%23aeb9c8'/%3E%3Crect x='150' y='95' width='210' height='95' rx='8' fill='none' stroke='%23c39c54' stroke-width='5'/%3E%3Ccircle cx='620' cy='95' r='34' fill='%23c39c54' opacity='.55'/%3E%3C/svg%3E")}
        /* §5: gradiente navy lateral sobre la imagen (foto real o de muestra) */
        /* ══ DESIGN SYSTEM §25-26: radios contenidos y sombras planas.
              Se aplica al preset commerce (no a otras plantillas). ══ */
        body.section-preset-commerce{--radius-sm:6px;--radius-md:9px;--radius-lg:12px;
            --shadow-sm:0 4px 16px rgba(15,23,42,.05);
            --shadow-md:0 10px 30px rgba(15,23,42,.09);
            --shadow-lg:0 16px 40px rgba(15,23,42,.10)}
        body.section-preset-commerce .catalog-card,
        body.section-preset-commerce .home-cat-card{border-radius:8px;box-shadow:0 4px 16px rgba(15,23,42,.05)}
        body.section-preset-commerce .catalog-card:hover,
        body.section-preset-commerce .home-cat-card:hover{box-shadow:0 10px 30px rgba(15,23,42,.09)}
        body.section-preset-commerce .button,
        body.section-preset-commerce .buy-add,
        body.section-preset-commerce .catalog-card-action{border-radius:6px}
        /* ══ §31 MICROINTERACCIONES: discretas y coherentes (180-350ms).
              Se respeta prefers-reduced-motion. ══ */
        body.section-preset-commerce .button,
        body.section-preset-commerce .buy-add,
        body.section-preset-commerce .catalog-card-action,
        body.section-preset-commerce .catalog-card-inquiry{transition:background-color .18s ease,color .18s ease,border-color .18s ease,transform .18s ease,box-shadow .18s ease}
        body.section-preset-commerce .button:hover,
        body.section-preset-commerce .buy-add:hover,
        body.section-preset-commerce .catalog-card-action:hover{transform:translateY(-1px)}
        body.section-preset-commerce .button:active,
        body.section-preset-commerce .buy-add:active{transform:translateY(0)}
        body.section-preset-commerce .catalog-card{transition:box-shadow .22s ease,border-color .22s ease,transform .22s ease}
        body.section-preset-commerce .catalog-card:hover{transform:translateY(-2px)}
        body.section-preset-commerce .catalog-card-media img{transition:transform .35s ease}
        body.section-preset-commerce .catalog-card:hover .catalog-card-media img{transform:scale(1.04)}
        body.section-preset-commerce .category-list a{transition:color .18s ease}
        body.section-preset-commerce a{transition:color .18s ease,opacity .18s ease}
        @media(prefers-reduced-motion:reduce){
            body.section-preset-commerce *,body.section-preset-commerce *::before,body.section-preset-commerce *::after{transition-duration:.01ms!important;animation-duration:.01ms!important}
        }
        /* §32 accesibilidad: foco visible coherente */
        body.section-preset-commerce a:focus-visible,
        body.section-preset-commerce button:focus-visible,
        body.section-preset-commerce input:focus-visible,
        body.section-preset-commerce select:focus-visible{outline:2px solid var(--accent,var(--primary));outline-offset:2px;border-radius:4px}
        /* ══ BARRA INFERIOR ANIMADA (marquee): mensaje comercial que se desplaza.
              Fija al fondo, se oculta al llegar al footer y respeta reduced-motion.
              Se configura desde el Constructor con ticker_text. ══ */
        /* Sticky: acompana el scroll pegada al fondo y al llegar al final queda
           justo encima del pie, sin hueco. */
        .bx-ticker{position:sticky;bottom:0;z-index:40;overflow:hidden;background:var(--primary);color:#fff;box-shadow:0 -4px 16px rgba(15,23,42,.12)}
        /* El pie traia 60px de margen: pegado a la franja no debe haber hueco */
        .bx-ticker+footer,.bx-ticker+.ftc,.bx-ticker+.site-footer-corporate{margin-top:0!important}
        .bx-ticker-track{display:flex;width:max-content;animation:bxTicker var(--bx-ticker-speed,28s) linear infinite}
        .bx-ticker-track span{display:inline-flex;align-items:center;gap:26px;padding:9px 26px;font-size:13px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;white-space:nowrap}
        .bx-ticker-track span:after{content:'•';opacity:.55}
        .bx-ticker:focus-within .bx-ticker-track{animation-play-state:paused}
        @keyframes bxTicker{from{transform:translateX(0)}to{transform:translateX(-50%)}}
        @media(prefers-reduced-motion:reduce){.bx-ticker:not(.bx-force-motion) .bx-ticker-track{animation:none}}
        @media(max-width:600px){.bx-ticker-track span{font-size:11.5px;padding:7px 18px;gap:18px}}
        /* §5: contenedor 1440 */
        body.section-preset-commerce .container{max-width:1440px}
        body.section-preset-commerce .premium-hero::before{
            content:'';position:absolute;inset:0;z-index:1;pointer-events:none;
            background:linear-gradient(90deg,rgba(4,30,59,.98) 0%,rgba(4,30,59,.94) 28%,rgba(4,30,59,.72) 43%,rgba(4,30,59,.25) 58%,rgba(4,30,59,0) 72%)}
        /* Vineta inferior: asienta la foto y da profundidad */
        body.section-preset-commerce .premium-hero.has-bg::after{
            content:'';position:absolute;inset:auto 0 0 0;height:38%;z-index:1;pointer-events:none;
            background:linear-gradient(to top,rgba(4,29,65,.34),rgba(4,29,65,0))}
        body.section-preset-commerce .premium-hero-inner{position:relative;z-index:2}
        /* ── PLACEHOLDERS: sin imagen, nunca un hueco blanco manchado ── */
        /* Hero sin foto: degradado navy de marca + patrón sutil, texto legible */
        body.section-preset-commerce .premium-hero:not([style*="background-image"]){
            background:linear-gradient(115deg,var(--secondary) 0%,color-mix(in srgb,var(--primary) 82%,#000) 46%,color-mix(in srgb,var(--accent,var(--primary)) 42%,#1b2b45) 100%)!important}
        body.section-preset-commerce .premium-hero:not([style*="background-image"])::after{
            content:'';position:absolute;inset:0;z-index:0;pointer-events:none;
            background-image:radial-gradient(circle at 78% 32%,rgba(255,255,255,.14),transparent 42%),radial-gradient(circle at 92% 78%,color-mix(in srgb,var(--accent,#C39C54) 30%,transparent),transparent 46%)}
        body.section-preset-commerce .premium-hero .ph-slide-copy,body.section-preset-commerce .premium-hero .premium-hero-copy{position:relative;z-index:2}
        /* Colecciones/ambientes sin foto: color de marca con la inicial, nunca gris vacío */
        body.section-preset-commerce .xs-coll-card:not([style*="background-image"]){
            background:linear-gradient(150deg,color-mix(in srgb,var(--primary) 88%,#000),color-mix(in srgb,var(--primary) 52%,#000))!important}
        /* El hero de la referencia es ancho y bajo: nada de 680px ni bordes de 34px */
        body.section-preset-commerce .premium-hero .ph-slide,body.section-preset-commerce .premium-hero .ph-stage{min-height:clamp(334px,26vw,380px)!important}
        /* §4/§23: sans, sin mayúsculas forzadas ni sombra, tamaño de la referencia */
        body.section-preset-commerce .ph-title{color:#fff!important;font-family:var(--font-body),system-ui,sans-serif!important;font-size:clamp(26px,2.8vw,40px)!important;font-weight:700!important;line-height:1.17!important;letter-spacing:-.01em!important;text-transform:none!important;text-shadow:none!important}
        body.section-preset-commerce .ph-badge{background:transparent!important;border:0!important;padding:0!important;color:#CEB16D!important;font-size:13px!important;font-weight:600!important;letter-spacing:.04em!important}
        body.section-preset-commerce .ph-slide-copy>p{font-family:var(--font-body),system-ui,sans-serif!important}
        body.section-preset-commerce .ph-slide::before,body.section-preset-commerce .hero::before{background:linear-gradient(90deg,rgba(4,29,65,.98) 0%,rgba(4,29,65,.93) 25%,rgba(4,29,65,.65) 40%,rgba(4,29,65,.15) 55%,rgba(4,29,65,0) 68%)!important}
        body.section-preset-commerce .ph-slide-copy,body.section-preset-commerce .hero-copy{max-width:470px;padding-left:clamp(24px,6vw,95px)}
        body.section-preset-commerce .hero-badge,body.section-preset-commerce .ph-badge{color:#CEB16D;font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;margin-bottom:18px}
        body.section-preset-commerce .ph-slide-copy h1,body.section-preset-commerce .hero-copy h1{font-size:clamp(26px,2.8vw,40px);line-height:1.17;font-weight:700;letter-spacing:-.01em}
        body.section-preset-commerce .ph-slide-copy>p,body.section-preset-commerce .hero-copy>p{font-size:15.5px;line-height:1.5;max-width:470px;color:rgba(255,255,255,.92);margin-top:16px}
        body.section-preset-commerce .ph-slide-copy .button,body.section-preset-commerce .hero-actions .button{min-height:45px;padding:0 23px;border-radius:6px;font-weight:600;font-size:14px;transition:background .18s ease,transform .18s ease,box-shadow .18s ease}
        body.section-preset-commerce .hero-actions,body.section-preset-commerce .ph-slide-copy .hero-actions{gap:14px;margin-top:24px}
        body.section-preset-commerce .button-primary:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(0,0,0,.18)}
        body.section-preset-commerce .button-ghost{background:transparent;border:1px solid rgba(255,255,255,.85);color:#fff}
        body.section-preset-commerce .button-ghost:hover{background:rgba(255,255,255,.1)}
        /* Flechas e indicadores del slider (§26-27) */
        body.section-preset-commerce .ph-arrow{width:42px;height:42px;border-radius:50%}
        body.section-preset-commerce .ph-dot{width:7px;height:7px;background:rgba(255,255,255,.55);transition:background .2s ease,width .2s ease}
        body.section-preset-commerce .ph-dot.is-active{background:var(--accent,#C39C54);width:20px;border-radius:4px}
        /* Banda de beneficios: UNA banda pegada al hero, con divisores */
        body.section-preset-commerce [data-store-native-section="benefits"]{padding-top:var(--sp-2)!important}
        body.section-preset-commerce .trust-grid{gap:0;border:1px solid #E7DFD4;border-radius:10px;background:#F5F4F2;overflow:hidden;box-shadow:none}
        body.section-preset-commerce .trust-section .trust-grid>.trust-card,
        body.section-preset-commerce .trust-card{border:0!important;border-radius:0!important;box-shadow:none!important;background:transparent!important;min-height:100px;padding:24px 26px;border-left:1px solid #DED8CF!important}
        body.section-preset-commerce .trust-section .trust-grid>.trust-card:first-child{border-left:0!important}
        /* §14: banda de beneficios pegada al hero, blanca, borde y sombra suave */
        body.section-preset-commerce .trust-section .trust-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:0!important;border:1px solid var(--border)!important;border-radius:0 0 10px 10px!important;background:var(--surface)!important;overflow:hidden;box-shadow:0 6px 24px rgba(15,23,42,.045)!important}
        body.section-preset-commerce .trust-section{padding-top:0!important;margin-top:-1px}
        /* §15: la siguiente seccion arranca a 40-50px, no a 100 */
        body.section-preset-commerce .trust-section+*{padding-top:46px!important}
        @media(max-width:980px){body.section-preset-commerce .trust-section .trust-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:560px){body.section-preset-commerce .trust-section .trust-grid{grid-template-columns:1fr}}
        body.section-preset-commerce .trust-grid>.trust-card:first-child{border-left:0}
        body.section-preset-commerce .trust-card strong{font-size:14px;font-weight:700;color:#142845}
        body.section-preset-commerce .trust-card span{font-size:12.5px;color:#5E6878;margin-top:4px}
        body.section-preset-commerce .trust-icon svg{width:34px;height:34px}
        @media(max-width:760px){body.section-preset-commerce .trust-grid{border:0;border-radius:0}body.section-preset-commerce .trust-card{border-left:0;border:1px solid var(--border);border-radius:10px}}
        /* Títulos de sección con jerarquía mayor */
        body.section-preset-commerce .section-heading h2,body.section-preset-commerce .pf-title,body.section-preset-commerce .xs-head h2{font-size:clamp(26px,2.6vw,34px);font-weight:700;letter-spacing:-.02em}
        /* Tarjetas de producto: borde suave, sin sombra pesada */
        body.section-preset-commerce .catalog-card,body.section-preset-commerce .pf-card{border:1px solid var(--border);border-radius:11px;box-shadow:none;transition:transform .16s ease,box-shadow .16s ease}
        body.section-preset-commerce .catalog-card:hover,body.section-preset-commerce .pf-card:hover{transform:translateY(-2px);box-shadow:0 10px 26px rgba(15,23,42,.07)}
        body.section-preset-commerce .catalog-card-media,body.section-preset-commerce .pf-media{background:var(--surface-soft)}
        /* Ambientes/colecciones 4:3 con zoom sutil */
        body.section-preset-commerce .xs-col-card{aspect-ratio:4/3;border-radius:12px}
        body.section-preset-commerce .xs-col-card img{transition:transform .3s ease}
        body.section-preset-commerce .xs-col-card:hover img{transform:scale(1.04)}
        /* Marcas: banda compacta con cards de logo */
        body.section-preset-commerce [data-store-native-section="brands"]{padding-top:36px!important;padding-bottom:45px!important;background:#FAFBFB}
        body.section-preset-commerce [data-store-native-section="brands"] .xs-head{text-align:center;margin-bottom:30px}
        body.section-preset-commerce [data-store-native-section="brands"] .xs-head h2{font-size:26px;font-weight:700;color:#142845}
        body.section-preset-commerce [data-store-native-section="brands"] .xs-head::after{content:'';display:block;width:46px;height:3px;margin:14px auto 0;background:var(--accent,#C39C54);border-radius:2px}
        body.section-preset-commerce .xs-brands{gap:14px}
        body.section-preset-commerce .xs-brand{width:160px;height:62px;min-height:0;min-width:0;box-sizing:border-box;padding:8px 14px;border:1px solid #E3E6EB;border-radius:8px;background:#fff}
        body.section-preset-commerce .xs-brand-name{border:0;background:transparent;padding:0;font-size:15px;color:var(--secondary)}
        /* Footer: navy profundo con jerarquía de la spec */
        body.section-preset-commerce .ftc-main{padding:60px 0 36px}
        body.section-preset-commerce .ftc h4{font-size:14px;font-weight:600}
        body.section-preset-commerce .ftc ul{font-size:12.5px;gap:11px}
        body.section-preset-commerce .ftc-bottom-inner{padding:18px 0}
        /* WhatsApp flotante según spec */
        body.section-preset-commerce .float-wa,body.section-preset-commerce .wa-float{width:56px;height:56px;right:24px;bottom:24px}
        @media(max-width:640px){body.section-preset-commerce .float-wa,body.section-preset-commerce .wa-float{right:18px;bottom:18px}}
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
        .footer-dev-link{color:#cbd5e1;font-weight:700;text-decoration:underline;text-underline-offset:2px}
        .footer-dev-link:hover{color:#fff}
        @media(max-width:640px){.footer-copy{white-space:normal}}
        .footer-pay{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}.footer-pay-logo{display:inline-flex}.footer-pay-logo svg{height:26px;width:auto;border-radius:4px;box-shadow:0 1px 4px rgba(0,0,0,.25)}
        @media(max-width:1000px){.footer-main{grid-template-columns:1fr 1fr;gap:36px 40px}.footer-brand{grid-column:1/-1}}
        @media(max-width:760px){.footer-bottom-inner{grid-template-columns:1fr;justify-items:center;text-align:center;gap:16px}.footer-pay{justify-content:center}.footer-secure{justify-content:center}}
        @media(max-width:600px){.footer-main{grid-template-columns:1fr;gap:32px;padding:40px 0 30px}.footer-trust{grid-template-columns:1fr 1fr}}
        /* ── Diseños del pie de página (footer_style) ── */
        /* Claro elegante */
        .ft-style-light.site-footer{color:#475569;background:#f8fafc;border-top:1px solid var(--border)}
        .ft-style-light .footer-logo strong,.ft-style-light .footer-title,.ft-style-light .footer-help strong,.ft-style-light .footer-secure strong{color:#0f172a}
        .ft-style-light .footer-desc,.ft-style-light .footer-copy,.ft-style-light .footer-secure span{color:#64748b}
        .ft-style-light .footer-trust-item{color:#475569}.ft-style-light .footer-trust-item svg,.ft-style-light .footer-contact li>svg,.ft-style-light .footer-help>svg{filter:none}
        .ft-style-light .footer-social a{color:#475569;background:#fff;border-color:#e2e8f0}
        .ft-style-light .footer-links li{border-color:#e2e8f0}
        .ft-style-light .footer-links a{color:#64748b}.ft-style-light .footer-links a:hover{color:var(--primary)}
        .ft-style-light .footer-contact li{color:#475569}.ft-style-light .footer-contact li>svg{background:#fff;border-color:#e2e8f0}
        .ft-style-light .footer-contact a{color:#475569}.ft-style-light .footer-contact a:hover{color:var(--primary)}
        .ft-style-light .footer-help{background:#fff;border-color:#e2e8f0}.ft-style-light .footer-help span{color:#64748b}
        .ft-style-light .footer-bottom{border-color:#e2e8f0}.ft-style-light .footer-secure>svg{color:#94a3b8}
        .ft-style-light .footer-dev-link{color:#334155}.ft-style-light .footer-dev-link:hover{color:var(--primary)}
        .ft-style-light .footer-logo img{background:transparent;padding:0}
        /* Color de marca */
        .ft-style-accent.site-footer{background:linear-gradient(180deg,var(--primary) 0%,color-mix(in srgb,var(--primary) 66%,#000) 100%);color:#f1f5f9}
        .ft-style-accent .footer-desc,.ft-style-accent .footer-copy,.ft-style-accent .footer-links a,.ft-style-accent .footer-help span,.ft-style-accent .footer-secure span{color:rgba(255,255,255,.72)}
        .ft-style-accent .footer-trust-item,.ft-style-accent .footer-contact li,.ft-style-accent .footer-contact a,.ft-style-accent .footer-secure strong{color:rgba(255,255,255,.9)}
        .ft-style-accent .footer-trust-item svg,.ft-style-accent .footer-contact li>svg,.ft-style-accent .footer-help>svg,.ft-style-accent .footer-secure>svg{color:#fff;filter:none}
        .ft-style-accent .footer-title:after{background:#fff}
        .ft-style-accent .footer-logo-mark{background:#fff;color:var(--primary)}
        .ft-style-accent .footer-social a:hover{color:var(--primary);background:#fff;border-color:#fff}
        .ft-style-accent .footer-links a:hover,.ft-style-accent .footer-contact a:hover{color:#fff}
        .ft-style-accent .footer-help-btn{color:var(--primary);background:#fff}
        /* Compacto centrado */
        .ft-style-minimal .footer-main{display:flex;flex-direction:column;align-items:center;gap:26px;padding:44px 0 30px;text-align:center}
        .ft-style-minimal .footer-brand{display:flex;flex-direction:column;align-items:center}
        .ft-style-minimal .footer-logo{justify-content:center}
        .ft-style-minimal .footer-desc{max-width:520px}
        .ft-style-minimal .footer-trust{display:flex;flex-wrap:wrap;justify-content:center;gap:10px 22px;margin-bottom:0}
        .ft-style-minimal .footer-social{justify-content:center}
        .ft-style-minimal .footer-title{display:none}
        .ft-style-minimal .footer-links{display:flex;flex-wrap:wrap;justify-content:center;gap:2px 24px}
        .ft-style-minimal .footer-links li{border:0}
        .ft-style-minimal .footer-links a:hover{padding-left:0}
        .ft-style-minimal .footer-contact{flex-direction:row;flex-wrap:wrap;justify-content:center;gap:14px 26px}
        .ft-style-minimal .footer-help{display:none}
        @media(max-width:600px){.ft-style-minimal .footer-trust{display:flex}}

        /* Secciones de la home (portada) */

        /* ═══ Secciones especiales del constructor de Inicio ═══ */
        .special-home-section{padding:64px 0;border-bottom:1px solid var(--border)}
        .special-section-head{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin-bottom:26px}
        .special-section-head h2{margin:0;color:var(--secondary);font-size:clamp(25px,3vw,36px);line-height:1.1;letter-spacing:-.04em}
        .special-section-head p{margin:9px 0 0;color:#64748b;font-size:14px;line-height:1.6}
        .flash-sale-section{position:relative;overflow:hidden;color:#fff;background:linear-gradient(120deg,var(--secondary),color-mix(in srgb,var(--primary) 60%,#020617))}
        .flash-sale-section::before{content:"";position:absolute;inset:-40% auto auto 62%;width:520px;height:520px;border-radius:50%;background:color-mix(in srgb,var(--primary) 30%,transparent);filter:blur(20px)}
        .flash-sale-section .container{position:relative;z-index:1}
        /* Variante banda compacta del contador */
        .flash-sale-section.style-band{background:transparent;color:inherit;padding:26px 0}
        .flash-sale-section.style-band::before{content:none}
        .flash-band{display:flex;align-items:center;justify-content:center;gap:clamp(14px,3vw,26px);flex-wrap:wrap;padding:20px clamp(18px,4vw,34px);background:var(--flash-bg);border-radius:18px;color:#fff;text-decoration:none;box-shadow:0 14px 34px color-mix(in srgb,var(--flash-bg) 35%,transparent);transition:transform .18s ease}
        .flash-band:hover{transform:translateY(-2px)}
        .flash-band-ico{width:40px;height:40px;display:grid;place-items:center;background:rgba(255,255,255,.16);border-radius:12px}
        .flash-band-ico svg{width:22px;height:22px;color:var(--flash-accent)}
        .flash-band-title{font-size:clamp(19px,2.6vw,27px);font-weight:800;letter-spacing:-.02em}
        .flash-band-count{display:flex;gap:9px}
        .flash-band-count>span{min-width:58px;padding:8px 6px 6px;background:var(--flash-accent);color:#111827;border-radius:10px;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,.18)}
        .flash-band-count b{display:block;font-size:21px;font-weight:800;font-variant-numeric:tabular-nums;line-height:1}
        .flash-band-count i{display:block;margin-top:3px;font-size:10px;font-style:normal;font-weight:700}
        .flash-band-msg{font-size:14px;font-weight:700;opacity:.92}
        @media(max-width:640px){.flash-band{padding:16px 14px}.flash-band-ico{display:none}.flash-band-count>span{min-width:50px}.flash-band-count b{font-size:18px}}
        /* Variante showcase de categorías: banda de título + círculos */
        .home-cats-band{display:flex;align-items:center;justify-content:center;gap:14px;margin-bottom:36px;padding:18px 26px;background:var(--band-bg);color:var(--band-ink);border-radius:16px;box-shadow:0 12px 30px color-mix(in srgb,var(--band-bg) 30%,transparent)}
        .home-cats-band h2{margin:0;font-size:clamp(20px,2.8vw,29px);font-weight:800;letter-spacing:-.02em}
        .home-cats-band-ico{width:38px;height:38px;display:grid;place-items:center}
        .home-cats-band-ico svg{width:27px;height:27px}
        .home-cats-showcase{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:clamp(18px,3vw,30px);justify-items:center}
        .hc-showcase-item{display:flex;flex-direction:column;align-items:center;gap:13px;min-width:0;text-align:center}
        .hc-circle{width:min(176px,100%);aspect-ratio:1;display:grid;place-items:center;overflow:hidden;background:#fff;border-radius:50%;box-shadow:0 10px 30px rgba(15,23,42,.10);transition:transform .2s ease,box-shadow .2s ease}
        .hc-circle img{width:100%;height:100%;object-fit:cover}
        .hc-circle svg{width:44%;height:44%;color:#94a3b8}
        .hc-showcase-item:hover .hc-circle{transform:translateY(-5px);box-shadow:0 16px 38px rgba(15,23,42,.16)}
        .hc-showcase-item strong{max-width:100%;overflow:hidden;color:#334155;font-size:12.5px;font-weight:800;letter-spacing:.06em;text-overflow:ellipsis;text-transform:uppercase;white-space:nowrap}
        .hc-showcase-item small{margin-top:-6px;color:#94a3b8;font-size:11px}
        @media(max-width:640px){.home-cats-showcase{grid-template-columns:repeat(2,1fr);gap:16px}.home-cats-band{margin-bottom:24px;padding:14px 16px}.hc-circle{width:min(136px,100%)}}
        /* Categorías: variantes circles / carousel / editorial */
        .home-section-head.is-centered{justify-content:center;text-align:center}
        .home-section-head.is-centered .home-section-copy{margin:0 auto}
        .hc-scroller-wrap{position:relative}
        .home-cats-showcase.is-scroll{display:flex;gap:22px;overflow-x:auto;padding:6px 4px 14px;scroll-snap-type:x mandatory;scrollbar-width:none}
        .home-cats-showcase.is-scroll::-webkit-scrollbar{display:none}
        .home-cats-showcase.is-scroll .hc-showcase-item{flex:0 0 150px;scroll-snap-align:start}
        .home-cats-showcase.is-scroll .hc-circle{width:126px}
        .hc-arrow{position:absolute;top:42%;z-index:3;width:40px;height:40px;display:grid;place-items:center;background:#fff;border:1px solid var(--border);border-radius:50%;box-shadow:0 8px 22px rgba(15,23,42,.14);color:#334155;font-size:22px;line-height:1;cursor:pointer;transition:.15s}
        .hc-arrow:hover{color:var(--primary);border-color:var(--primary)}
        .hc-arrow-prev{left:-8px}.hc-arrow-next{right:-8px}
        .hc-dot{color:var(--primary)}
        .hc-editorial-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px}
        .hc-photo{position:relative;display:block;aspect-ratio:3/4;overflow:hidden;border-radius:10px;background:#eef1f6}
        .hc-photo img{width:100%;height:100%;object-fit:cover;transition:transform .3s ease}
        .hc-photo:hover img{transform:scale(1.05)}
        .hc-photo-ph{position:absolute;inset:0;background:linear-gradient(135deg,#e2e8f0,#f8fafc)}
        .hc-photo-label{position:absolute;left:0;right:0;bottom:0;padding:34px 14px 14px;background:linear-gradient(180deg,transparent,rgba(0,0,0,.55));color:#fff;font-size:13.5px;font-weight:800;letter-spacing:.06em}
        @media(max-width:640px){.hc-editorial-grid{grid-template-columns:repeat(2,1fr)}.home-cats-showcase.is-scroll .hc-showcase-item{flex-basis:118px}.home-cats-showcase.is-scroll .hc-circle{width:100px}.hc-arrow{display:none}}
        /* Beneficios: variantes tiles (iconos acento) y band (fondo de color) */
        .trust-section.style-tiles .trust-card{flex-direction:column;align-items:flex-start;gap:14px;border:0;box-shadow:0 6px 24px rgba(15,23,42,.06)}
        .trust-section.style-tiles .trust-icon{width:52px;height:52px;flex-basis:52px;color:{{ $trustOnAccent }}!important;background:{{ $trustAccentColor }}!important;border:0;border-radius:14px;box-shadow:0 10px 22px color-mix(in srgb,{{ $trustAccentColor }} 35%,transparent)}
        /* El icono heredaba color blanco sobre chip claro y se veia vacio */
        .trust-section.style-tiles .trust-icon svg{color:{{ $trustOnAccent }}!important;stroke:currentColor!important;width:24px;height:24px}
        .trust-section.style-tiles .trust-card:hover .trust-icon{transform:scale(1.06)}
        .trust-section.style-band{background:{{ $trustAccentColor }};padding:46px 0}
        .trust-section.style-band .trust-section-head h2,.trust-section.style-band .trust-section-head p{color:{{ $trustOnAccent }}}
        .trust-section.style-band .trust-card{flex-direction:column;align-items:center;text-align:center;background:transparent;border:0;box-shadow:none;padding:10px}
        .trust-section.style-band .trust-icon{width:56px;height:56px;flex-basis:56px;color:{{ $trustOnAccent }};background:color-mix(in srgb,{{ $trustOnAccent }} 12%,transparent);border:1.5px solid color-mix(in srgb,{{ $trustOnAccent }} 40%,transparent);border-radius:50%}
        .trust-section.style-band .trust-icon svg{width:27px;height:27px}
        .trust-section.style-band .trust-card strong{color:{{ $trustOnAccent }}}
        .trust-section.style-band .trust-card span{color:{{ $trustOnAccentSoft }}}
        .trust-section.style-outline .trust-card{background:transparent;border:1.5px solid color-mix(in srgb,{{ $trustAccentColor }} 30%,#dbe3ef);box-shadow:none}
        .trust-section.style-outline .trust-card:hover{border-color:{{ $trustAccentColor }}}
        .trust-section.style-outline .trust-icon{color:{{ $trustAccentColor }};background:transparent;border:2px solid {{ $trustAccentColor }};border-radius:50%}
        .trust-section.style-stripe .trust-card{border:1px solid var(--border);border-left:5px solid {{ $trustAccentColor }};border-radius:12px}
        .trust-section.style-stripe .trust-icon{color:{{ $trustAccentColor }};background:color-mix(in srgb,{{ $trustAccentColor }} 10%,#fff);border:0;border-radius:11px}
        .trust-section.style-inline{padding:30px 0}
        /* VARIANTE "linea": una sola fila de texto con separadores, sin tarjetas
           ni iconos. Ocupa ~48px en lugar de 120 y evita el patron de 3-4
           columnas con icono, que es la señal mas reconocible de plantilla. */
        .trust-section.style-linea{padding:0;border-block:1px solid var(--border);
            background:var(--surface-soft,#f8fafc)}
        .trust-section.style-linea .trust-section-head{display:none}
        /* La especificidad sube a body...!important porque los presets de seccion
           (section-preset-commerce y compañia) fijan la rejilla de 5 columnas y
           el fondo de la banda con !important, y ganaban a la variante. */
        /* El relleno generico de <section> (68px arriba y abajo) sumaba 136px a una
           banda cuyo contenido mide 50: la linea es una franja, no una seccion. */
        /* #storefront-main > section fija el relleno con un ID, y un ID gana a
           cualquier numero de clases: hay que igualar la especificidad. */
        #storefront-main > section.trust-section.style-linea,
        body .trust-section.style-linea{padding:0!important;padding-block:0!important;
            margin-top:0!important;margin-bottom:0!important}
        body .trust-section.style-linea .trust-section-head{display:none!important}
        /* La linea usa todo el ancho util: con textos largos (MegaHogar) el
           contenedor de 1180px la partia en dos filas. */
        body .trust-section.style-linea > .container{width:min(1560px,calc(100% - 28px))!important;
            max-width:none!important}
        body .trust-section.style-linea .trust-grid{display:flex!important;
            grid-template-columns:none!important;flex-wrap:wrap;
            align-items:center;justify-content:center;gap:0!important;min-height:48px;flex-wrap:nowrap!important;
            border:0!important;border-radius:0!important;background:transparent!important;
            box-shadow:none!important;overflow:visible!important}
        /* El preset de seccion (section-preset-commerce) fija estas tarjetas con la
           misma especificidad, y ganaba por orden: en MegaHogar median 100px de
           alto frente a los 44 de las otras dos. Con el ID del contenedor se
           resuelve el empate. */
        #storefront-main .trust-section.style-linea .trust-card,
        body .trust-section.style-linea .trust-card{display:inline-flex!important;
            flex-direction:row!important;align-items:center!important;
            gap:0!important;padding:12px 0!important;margin:0!important;
            min-height:0!important;height:auto!important;border:0!important;
            background:transparent!important;box-shadow:none!important;flex:0 0 auto!important}
        body .trust-section.style-linea .trust-icon{display:none!important}
        .trust-section.style-linea .trust-card-copy{display:inline-flex;align-items:baseline;gap:6px}
        .trust-section.style-linea .trust-card-copy strong{color:var(--text-strong);
            font-size:13px;font-weight:700;letter-spacing:.01em;white-space:nowrap}
        /* Cada dato ocupa una sola linea: "Entrega e instalacion" partia en dos
           y duplicaba el alto de la franja. */
        body .trust-section.style-linea .trust-card,
        body .trust-section.style-linea .trust-card-copy{white-space:nowrap!important}
        body .trust-section.style-linea .trust-card-copy span{display:none!important}
        /* :last-child fallaba cuando existe el dato extra (que va despues), y se
           pintaban dos separadores seguidos. :last-of-type mira solo las tarjetas. */
        .trust-section.style-linea .trust-card:not(:last-of-type)::after{content:'·';
            margin:0 clamp(8px,1vw,14px);color:var(--muted);font-weight:700}
        /* Con 5 beneficios mas el dato extra la linea se partia en dos. El texto
           y el separador se ajustan al ancho disponible antes de romper. */
        @media(min-width:761px) and (max-width:1500px){
            .trust-section.style-linea .trust-card-copy strong{font-size:12.5px}
            .trust-section.style-linea .trust-extra{font-size:12.5px}
            .trust-section.style-linea .trust-extra::before{margin:0 10px}
        }
        .trust-section.style-linea .trust-extra{display:inline-flex;align-items:center;
            color:var(--primary);font-size:13px;font-weight:800}
        .trust-section.style-linea .trust-extra::before{content:'·';margin:0 14px;
            color:var(--muted);font-weight:700}
        @media(max-width:760px){
            /* En movil se convierte en carrusel horizontal: cabe entera sin
               apilar cuatro filas de texto. */
            .trust-section.style-linea .trust-grid{flex-wrap:nowrap;overflow-x:auto;
                justify-content:flex-start;padding-inline:16px;
                scrollbar-width:none;-webkit-overflow-scrolling:touch}
            .trust-section.style-linea .trust-grid::-webkit-scrollbar{display:none}
            .trust-section.style-linea .trust-card{white-space:nowrap}
        }
        .trust-section.style-inline .trust-section-head{margin-bottom:18px}
        .trust-section.style-inline .trust-section-head h2{font-size:clamp(19px,2vw,24px)}
        .trust-section.style-inline .trust-grid{display:flex;flex-wrap:wrap;justify-content:center;gap:0}
        .trust-section.style-inline .trust-card{flex:1 1 auto;justify-content:center;background:transparent;border:0;box-shadow:none;padding:8px 28px;border-right:1px solid var(--border);border-radius:0}
        .trust-section.style-inline .trust-card:last-child{border-right:0}
        .trust-section.style-inline .trust-icon{width:42px;height:42px;flex-basis:42px;color:{{ $trustAccentColor }};background:color-mix(in srgb,{{ $trustAccentColor }} 9%,#fff);border:0;border-radius:50%}
        @media(max-width:760px){.trust-section.style-inline .trust-grid{flex-direction:column;align-items:stretch}.trust-section.style-inline .trust-card{border-right:0;border-bottom:1px solid var(--border);justify-content:flex-start}.trust-section.style-inline .trust-card:last-child{border-bottom:0}}
        .trust-section.style-band .trust-card:hover{transform:translateY(-3px)}
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
        /* Si hay menos categorias que columnas, la fila se ajusta: antes quedaba un hueco a la derecha. */
        .home-cats-grid{display:grid;grid-template-columns:repeat(var(--cats-cols,{{ $featuredCatsColumns }}),minmax(0,1fr));gap:18px}
        .home-cat-card{position:relative;min-width:0;overflow:hidden;background:{{ $featuredCatsCardBg }};border:1px solid color-mix(in srgb,{{ $featuredCatsAccent }} 16%,#dbe3ef);border-radius:{{ $featuredCatsRadius }}px;transition:transform .22s ease,border-color .22s ease,box-shadow .22s ease}
        .home-cat-card:hover{transform:translateY(-5px);border-color:color-mix(in srgb,{{ $featuredCatsAccent }} 60%,#fff);box-shadow:0 18px 44px color-mix(in srgb,{{ $featuredCatsAccent }} 16%,transparent)}
        .home-cat-media{position:relative;display:grid;place-items:center;height:142px;overflow:hidden;background:linear-gradient(145deg,color-mix(in srgb,{{ $featuredCatsAccent }} 9%,#fff),color-mix(in srgb,{{ $featuredCatsAccent }} 3%,#fff))}
        .home-cat-media img{width:100%;height:100%;object-fit:{{ $featuredCatsImageFit }};transition:transform .35s ease}.home-cat-card:hover .home-cat-media img{transform:scale(1.055)}
        .home-cats.shape-square .home-cat-card,.home-cats.shape-square .home-cat-icon{border-radius:0}
        /* Con pocas categorias (2-3) el grid las separaba a los extremos y dejaba
           un hueco enorme al centro: se centran y se limita el ancho util. */
        .home-cats.shape-circle .home-cats-grid{justify-content:center;max-width:min(100%,760px);margin-inline:auto}
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
        /* ══ §17-19 COLECCIONES VISUALES: card blanca, foto 1.15/1 arriba e info
              abajo (icono + nombre + "Ver mas"). Generica: cualquier rubro puede
              usarla desde el constructor (estilo "coleccion"). ══ */
        .home-cats.style-coleccion .home-cats-grid{gap:18px}
        .home-cats.style-coleccion .home-cat-card{background:var(--surface);border:1px solid var(--border);border-radius:8px;overflow:hidden;box-shadow:0 4px 14px rgba(15,23,42,.05);transition:transform .25s ease,box-shadow .25s ease}
        .home-cats.style-coleccion .home-cat-card:hover{transform:none;box-shadow:0 10px 30px rgba(15,23,42,.09)}
        .home-cats.style-coleccion .home-cat-media{height:auto;aspect-ratio:1.15/1;background:var(--surface-soft);overflow:hidden}
        .home-cats.style-coleccion .home-cat-media img{width:100%;height:100%;object-fit:cover;transition:transform .35s ease}
        .home-cats.style-coleccion .home-cat-card:hover .home-cat-media img{transform:scale(1.035)}
        .home-cats.style-coleccion .home-cat-media::after{display:none}
        .home-cats.style-coleccion .home-cat-icon{width:auto!important;height:auto!important;color:var(--primary)!important;background:none!important;border:0!important;box-shadow:none!important;opacity:.30}
        .home-cats.style-coleccion .home-cat-icon svg{width:56px!important;height:56px!important;max-width:56px!important;max-height:56px!important;stroke-width:1.3}
        .home-cats.style-coleccion .home-cat-content{position:static;display:block;padding:15px 16px 14px;background:var(--surface)}
        .home-cats.style-coleccion .home-cat-card strong{display:block;color:var(--text-strong);font-family:var(--font-title);font-size:14px;font-weight:700;letter-spacing:.02em;text-transform:uppercase;white-space:normal}
        .home-cats.style-coleccion .home-cat-card small{display:block;margin-top:3px;color:var(--muted);font-size:12.5px}
        .home-cats.style-coleccion .home-cat-arrow{display:inline-flex;align-items:center;gap:6px;width:auto;height:auto;margin-top:10px;padding:0;color:var(--text);background:none;border-radius:0;font-size:13px;font-weight:600;transition:color .18s ease}
        .home-cats.style-coleccion .home-cat-arrow svg{width:15px;height:15px}
        .home-cats.style-coleccion .home-cat-cta{font-style:normal}
        .home-cats.style-coleccion .home-cat-card:hover .home-cat-arrow{color:var(--accent,var(--primary))}
        @media(max-width:900px){.home-cats.style-coleccion .home-cats-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
        @media(max-width:600px){.home-cats.style-coleccion .home-cats-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
            .home-cats.style-coleccion .home-cat-card strong{font-size:12.5px}
            .home-cats.style-coleccion .home-cat-content{padding:11px 12px 12px}}
        /* ── Variante AMBIENTES: cards altas editoriales. Funciona CON y SIN fotografía
           (sin foto compone un lienzo de marca con el icono de la categoría), por lo que
           sirve a cualquier rubro que aún no tenga banco de imágenes. */
        .home-cats.style-ambientes .home-cats-grid{gap:20px}
        .home-cats.style-ambientes .home-cat-card{border:0;border-radius:14px;box-shadow:0 10px 30px rgba(15,23,42,.09)}
        .home-cats.style-ambientes .home-cat-card:hover{transform:translateY(-4px);box-shadow:0 20px 46px rgba(15,23,42,.16)}
        .home-cats.style-ambientes .home-cat-media{height:268px;background:linear-gradient(155deg,color-mix(in srgb,{{ $featuredCatsAccent }} 82%,#fff) 0%,{{ $featuredCatsAccent }} 55%,color-mix(in srgb,{{ $featuredCatsAccent }} 74%,#000) 100%)}
        /* Cada ambiente recibe un matiz propio derivado de la marca: evita el muro
           monocromo cuando ninguna categoria tiene fotografia todavia. */
        .home-cats.style-ambientes .home-cat-card:nth-child(4n+2) .home-cat-media{background:linear-gradient(155deg,color-mix(in srgb,var(--accent) 46%,{{ $featuredCatsAccent }}) 0%,{{ $featuredCatsAccent }} 62%,color-mix(in srgb,{{ $featuredCatsAccent }} 72%,#000) 100%)}
        .home-cats.style-ambientes .home-cat-card:nth-child(4n+3) .home-cat-media{background:linear-gradient(155deg,color-mix(in srgb,{{ $featuredCatsAccent }} 62%,#fff) 0%,color-mix(in srgb,{{ $featuredCatsAccent }} 94%,#000) 100%)}
        .home-cats.style-ambientes .home-cat-card:nth-child(4n+4) .home-cat-media{background:linear-gradient(155deg,color-mix(in srgb,var(--accent) 30%,{{ $featuredCatsAccent }}) 0%,color-mix(in srgb,{{ $featuredCatsAccent }} 88%,#000) 100%)}
        .home-cats.style-ambientes .home-cat-media::after{content:'';position:absolute;inset:0;background:linear-gradient(to top,rgba(8,17,33,.80) 0%,rgba(8,17,33,.26) 52%,rgba(8,17,33,0) 100%)}
        .home-cats.style-ambientes .home-cat-icon{width:auto!important;height:auto!important;color:#fff!important;background:none!important;border:0!important;border-radius:0!important;box-shadow:none!important;opacity:.92;transition:transform .3s ease,opacity .3s ease}
        .home-cats.style-ambientes .home-cat-card:hover .home-cat-icon{color:#fff!important;background:none!important;border-color:transparent!important}
        .home-cats.style-ambientes .home-cat-card:hover .home-cat-icon{opacity:1;transform:scale(1.04)}
        .home-cats.style-ambientes .home-cat-icon svg{width:88px!important;height:88px!important;max-height:88px!important;max-width:88px!important;stroke-width:1.5;filter:drop-shadow(0 2px 10px rgba(0,0,0,.45))}
        .home-cats.style-ambientes .home-cat-content{position:absolute;inset:auto 0 0 0;z-index:2;align-items:flex-end;padding:20px}
        .home-cats.style-ambientes .home-cat-card strong{color:#fff;font-family:var(--font-title);font-size:19px;letter-spacing:.01em;white-space:normal}
        .home-cats.style-ambientes .home-cat-card small{color:rgba(255,255,255,.82)}
        .home-cats.style-ambientes .home-cat-arrow{width:34px;height:34px;color:#fff;background:color-mix(in srgb,{{ $featuredCatsAccent }} 88%,#000);border-radius:50%;display:grid;place-items:center;flex:none;transition:.2s}
        .home-cats.style-ambientes .home-cat-card:hover .home-cat-arrow{transform:translateX(3px)}
        @media(max-width:900px){.home-cats.style-ambientes .home-cat-media{height:216px}}
        @media(max-width:600px){.home-cats.style-ambientes .home-cat-media{height:178px}.home-cats.style-ambientes .home-cat-card strong{font-size:16px}}
        .home-cat-content{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:17px 18px 19px}
        .home-cat-copy{min-width:0}.home-cat-card strong{display:block;color:{{ $featuredCatsTextColor }};font-size:15px;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        /* En ambientes el nombre puede ocupar dos lineas: categorias como
           "Organizacion y Melamine" se truncaban con puntos suspensivos. */
        .home-cats.style-ambientes .home-cat-card strong{white-space:normal;overflow:visible;text-overflow:clip}
        .home-cat-card small{display:block;margin-top:5px;color:#64748b;font-size:11.5px}.home-cat-arrow{width:32px;height:32px;display:grid;place-items:center;flex:0 0 32px;color:{{ $featuredCatsAccent }};background:color-mix(in srgb,{{ $featuredCatsAccent }} 9%,#fff);border-radius:999px;transition:.2s}
        .home-cat-arrow svg{width:15px;height:15px}.home-cat-card:hover .home-cat-arrow{color:#fff;background:{{ $featuredCatsAccent }};transform:translateX(2px)}
        .home-cats.style-minimal .home-cat-card{text-align:center}.home-cats.style-minimal .home-cat-media{height:auto;padding:25px 16px 4px;background:transparent}.home-cats.style-minimal .home-cat-content{display:block;padding:12px 16px 22px}.home-cats.style-minimal .home-cat-arrow{display:none}
        .home-cats.style-horizontal .home-cat-card{display:flex;align-items:center;gap:14px;padding:14px}.home-cats.style-horizontal .home-cat-media{width:104px;height:104px;flex:0 0 104px;overflow:hidden;border-radius:10px;background:var(--surface-soft,#f8fafc)}.home-cats.style-horizontal .home-cat-media img{width:100%;height:100%;object-fit:contain;padding:6px}.home-cats.style-horizontal .home-cat-content{flex:1;min-width:0}
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
        /* ══ MOVIL (todas las plantillas): 2 columnas de producto. A 1 columna el
              catalogo generaba scroll interminable (12000px+). ══ */
        /* Beneficios en 2 columnas en movil: en una sola columna gastaban
           4-5 pantallazos de alto. */
        @media(max-width:600px){
            .trust-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:8px!important}
            .trust-grid>*{padding:12px 10px!important}
            .trust-grid strong,.trust-item strong{font-size:12.5px!important}
            .trust-grid small,.trust-item small,.trust-item p{font-size:11px!important;line-height:1.4!important}
        }
        @media(max-width:560px){
            .catalog-product-grid,.special-product-grid,.discount-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:11px!important}
            .special-product-grid .catalog-card-body{min-height:0;padding:10px}
            .product-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:11px!important}
            .catalog-product-grid .catalog-card-body,.product-grid .product-body{min-height:0;padding:10px}
            .catalog-product-grid .catalog-card-name,.product-grid .product-name{min-height:34px;font-size:12px;line-height:1.35}
            .catalog-product-grid .catalog-card-price,.product-grid .price{font-size:14px}
            .catalog-product-grid .catalog-card-action,.product-grid .product-action{min-height:38px;font-size:10.5px}
            .catalog-product-grid .catalog-quickview{display:none}
        }
        /* Proporcion 4:5 en destacados: la media cuadrada producia cards de ~590px
           en desktop (mucho vacio cuando el producto aun no tiene foto). */
        .pf-grid .catalog-card-media{aspect-ratio:4/3.5}
        /* Movil a 2 columnas (estandar ecommerce): a 1 columna, 8 productos generaban
           miles de pixeles de scroll. La card se compacta para que quepa comoda. */
        @media(max-width:560px){.pf-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:11px}.pf-section{padding:48px 0}}
        @media(max-width:560px){
            .pf-grid .catalog-card-body{min-height:0;padding:10px}
            .pf-grid .catalog-card-name{min-height:34px;margin-top:5px;font-size:12px;line-height:1.35}
            .pf-grid .catalog-card-category{font-size:8px}
            .pf-grid .catalog-card-price{font-size:14px}
            .pf-grid .catalog-card-prices{padding-top:8px}
            .pf-grid .catalog-card-action{min-height:38px;margin:0 8px 8px;font-size:10px}
            .pf-grid .catalog-quickview{display:none}
        }
        .home-cta-row{display:flex;justify-content:center;margin-top:34px}
        /* Páginas Nosotros / Contacto */
        .store-page{padding:44px 0 72px}.store-page-title{margin:14px 0 24px;color:var(--secondary);font-size:clamp(26px,3.4vw,40px);letter-spacing:-.03em}.store-page-hero{width:100%;max-height:360px;object-fit:cover;border-radius:var(--radius);margin-bottom:28px}.store-page-body{color:#475569;font-size:16px;line-height:1.8;white-space:pre-line}.store-page-block{margin-top:30px;padding-top:24px;border-top:1px solid var(--border)}.store-page-block h2{margin:0 0 10px;color:var(--secondary);font-size:20px}.store-page-block p{margin:0;color:#475569;line-height:1.8;white-space:pre-line}
        /* Páginas legales + Libro de Reclamaciones */
        .legal-container{max-width:860px}
        .legal-updated{margin:-14px 0 22px;color:#94a3b8;font-size:12px}
        .lr-head{display:flex;align-items:center;gap:16px;margin:14px 0 24px}
        .lr-badge{width:56px;height:56px;flex:0 0 56px;display:grid;place-items:center;color:#fff;background:var(--primary);border-radius:14px}
        .lr-sub{margin:0;color:#64748b;font-size:13px}
        .lr-flash{padding:16px 18px;color:#065f46;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:12px;font-size:14px;font-weight:600}
        .lr-errors{margin-bottom:18px;padding:14px 16px;color:#991b1b;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;font-size:13px}
        .lr-errors ul{margin:6px 0 0;padding-left:18px}
        .lr-form{padding:26px;background:#fff;border:1px solid var(--border);border-radius:16px}
        .lr-form h2{margin:26px 0 14px;color:var(--secondary);font-size:15px;font-weight:800;letter-spacing:.02em;text-transform:uppercase}
        .lr-form h2:first-of-type{margin-top:0}
        .lr-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .lr-grid label,.lr-area{display:flex;flex-direction:column;gap:6px;color:#475569;font-size:12.5px;font-weight:700}
        .lr-full{grid-column:1/-1}
        .lr-form input,.lr-form select,.lr-form textarea{min-height:44px;padding:10px 12px;color:#172033;background:#fff;border:1px solid #cbd5e1;border-radius:9px;font:inherit;font-weight:400}
        .lr-form textarea{min-height:0;resize:vertical}
        .lr-form input:focus,.lr-form select:focus,.lr-form textarea:focus{outline:0;border-color:var(--primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 14%,transparent)}
        .lr-type{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px}
        .lr-type-opt{display:flex;gap:10px;align-items:flex-start;padding:13px 14px;border:1.5px solid #cbd5e1;border-radius:11px;cursor:pointer;transition:border-color .15s}
        .lr-type-opt:has(input:checked){border-color:var(--primary);background:color-mix(in srgb,var(--primary) 5%,#fff)}
        .lr-type-opt input{margin-top:3px;accent-color:var(--primary)}
        .lr-type-opt strong{display:block;color:#172033;font-size:13.5px}
        .lr-type-opt small{color:#64748b;font-size:11.5px;font-weight:400}
        .lr-area{margin-bottom:14px}
        .lr-terms{display:flex;gap:9px;align-items:flex-start;margin:4px 0 12px;color:#475569;font-size:12.5px}
        .lr-terms input{margin-top:2px;accent-color:var(--primary)}
        .lr-terms a{color:var(--primary);font-weight:700;text-decoration:underline}
        .lr-note{margin:0 0 18px;color:#94a3b8;font-size:11.5px;line-height:1.6}
        @media(max-width:640px){.lr-grid,.lr-type{grid-template-columns:1fr}.lr-form{padding:18px}}
        /* ═══ Nosotros institucional ═══ */
        .about-hero{position:relative;overflow:hidden;border-radius:var(--radius-lg);margin:6px 0 44px;height:var(--ab-hero-h,380px)}
        .about-hero picture,.about-hero img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
        .about-hero:before{content:'';position:absolute;inset:0;z-index:1;background:linear-gradient(90deg,rgba(10,16,28,calc(var(--ab-overlay,.45) + .25)) 0%,rgba(10,16,28,var(--ab-overlay,.45)) 55%,rgba(10,16,28,calc(var(--ab-overlay,.45)*.55)) 100%)}
        /* Hero institucional SIN fotografia: lienzo de marca con textura sutil.
           Permite publicar la pagina antes de tener banco de imagenes. */
        .about-hero--brand{background:linear-gradient(120deg,var(--primary) 0%,color-mix(in srgb,var(--primary) 78%,#000) 58%,color-mix(in srgb,var(--accent) 42%,var(--primary)) 100%)}
        .about-hero--brand:after{content:'';position:absolute;inset:0;z-index:1;opacity:.5;background:radial-gradient(circle at 82% 22%,color-mix(in srgb,var(--accent) 34%,transparent) 0%,transparent 46%)}
        .about-hero-content{position:relative;z-index:2;display:flex;flex-direction:column;justify-content:center;height:100%;max-width:640px;padding:0 clamp(24px,5vw,64px)}
        .about-hero--center .about-hero-content{max-width:none;align-items:center;text-align:center}
        .about-hero-title{margin:0;color:#fff!important;-webkit-text-fill-color:#fff;font-family:var(--font-title);font-size:clamp(30px,4.4vw,52px);letter-spacing:-.02em;line-height:1.08;text-shadow:0 2px 18px rgba(0,0,0,.35)}
        .about-hero-subtitle{margin:14px 0 0;color:var(--accent);font-size:clamp(16px,2vw,22px);font-weight:600;line-height:1.35}
        .about-hero-desc{margin:12px 0 0;color:rgba(255,255,255,.82);font-size:15px;line-height:1.7;max-width:520px}
        .about-grid{display:grid;grid-template-columns:minmax(0,2fr) minmax(0,3fr);gap:clamp(28px,4vw,56px);align-items:start;margin-top:10px}
        .about-grid--single{grid-template-columns:minmax(0,1fr);max-width:780px}
        .about-label{display:inline-block;color:var(--accent);font-size:12px;font-weight:800;letter-spacing:.18em;text-transform:uppercase}
        .about-label:after{content:'';display:block;width:44px;height:2px;margin-top:8px;background:var(--accent)}
        .about-heading{margin:16px 0 14px;color:var(--secondary);font-family:var(--font-title);font-size:clamp(24px,2.8vw,36px);letter-spacing:-.02em;line-height:1.18}
        .about-intro .store-page-body{font-size:15px}
        .about-cta{display:inline-block;margin-top:22px}
        .about-cards{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}
        .about-card{padding:30px 26px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);transition:transform .18s ease,box-shadow .18s ease}
        .about-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-md)}
        .about-card-icon{display:grid;place-items:center;width:52px;height:52px;color:var(--accent);background:color-mix(in srgb,var(--accent) 12%,transparent);border-radius:14px}
        .about-card-icon svg{width:26px;height:26px}
        .about-card h3{margin:16px 0 0;color:var(--secondary);font-family:var(--font-title);font-size:19px}
        .about-card-line{display:block;width:34px;height:3px;margin:10px 0 12px;background:var(--accent);border-radius:2px}
        .about-card p{margin:0;color:var(--text);font-size:14px;line-height:1.75;white-space:pre-line}
        .about-diffs{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:18px;margin-top:52px;padding:34px clamp(20px,3vw,40px);background:var(--surface-soft);border:1px solid var(--border);border-radius:var(--radius-lg)}
        .about-diff{text-align:center;padding:6px 4px}
        .about-diff-icon{display:grid;place-items:center;width:56px;height:56px;margin:0 auto;color:var(--accent);background:var(--surface);border:1px solid var(--border);border-radius:50%;box-shadow:var(--shadow-sm)}
        .about-diff-icon svg{width:26px;height:26px}
        .about-diff strong{display:block;margin-top:14px;color:var(--secondary);font-size:15px;font-weight:700}
        .about-diff p{margin:6px 0 0;color:var(--muted);font-size:13px;line-height:1.6}
        @media(max-width:980px){.about-grid{grid-template-columns:1fr}.about-cards{margin-top:6px}}
        @media(max-width:640px){.about-cards{grid-template-columns:1fr}.about-hero{height:min(var(--ab-hero-h,380px),330px);border-radius:var(--radius-md)}.about-diffs{grid-template-columns:repeat(2,minmax(0,1fr));padding:24px 16px}}
        @media(max-width:420px){.about-diffs{grid-template-columns:1fr}}
        .store-page-contact{display:grid;grid-template-columns:1fr 1.2fr;gap:36px;margin-top:28px;align-items:start}.store-contact-info p{margin:0 0 12px;color:#475569;font-size:15px}.store-contact-info a{color:var(--primary)}.store-contact-form{display:flex;flex-direction:column;gap:12px;background:var(--surface);padding:24px;border:1px solid var(--border);border-radius:var(--radius)}.store-contact-form input,.store-contact-form textarea{width:100%;padding:11px 13px;border:1px solid #cbd5e1;border-radius:calc(var(--radius)*.75);font:inherit;font-size:14px;outline:0}.store-contact-form input:focus,.store-contact-form textarea:focus{border-color:var(--primary)}.store-contact-form .button{margin-top:4px}
        @media(max-width:800px){.store-page-contact{grid-template-columns:1fr}}
        @media(max-width:900px){.home-cats-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.home-prod-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:560px){
        .home-cats{padding:42px 0}.home-section-head{align-items:flex-start;margin-bottom:20px}.home-section-head p{font-size:13px}
        .home-cats-grid{grid-template-columns:repeat({{ $featuredCatsMobileColumns }},minmax(0,1fr));gap:11px}
@if($catsMobileLimit > 0)
        /* Limite de categorias visibles en movil: el resto queda tras "Ver todas". */
        .home-cats:not(.mobile-carousel) .home-cats-grid > .home-cat-card:nth-child(n+{{ $catsMobileLimit + 1 }}){display:none}
@endif
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

        *{box-sizing:border-box}html{scroll-behavior:smooth;overflow-x:clip}body{max-width:100%;margin:0;overflow-x:clip;color:#172033;background:#fff;font-family:'Inter',system-ui,sans-serif;-webkit-font-smoothing:antialiased}button,input,select{font:inherit}a{color:inherit;text-decoration:none}img{display:block;max-width:100%}[x-cloak]{display:none!important}.container{width:min(1180px,calc(100% - 40px));margin:auto}
        .topbar{color:#cbd5e1;background:var(--secondary);font-size:12px}.topbar-inner{min-height:34px;display:flex;align-items:center;justify-content:space-between;gap:24px}.topbar p{margin:0}.contact-links{display:flex;gap:22px}.contact-links a:hover{opacity:.85}
        .topbar--center .topbar-inner{justify-content:center;text-align:center}.topbar--left .topbar-inner{justify-content:flex-start}.topbar--right .topbar-inner{justify-content:flex-end}
        .topbar-full{width:100%;padding-inline:clamp(14px,4vw,32px);margin:0 auto;display:flex;align-items:center;justify-content:inherit}
        .store-header{position:relative;z-index:40;color:var(--header-text);background:var(--header-bg);border-bottom:1px solid var(--border)}
        #storefront-main{display:flex;flex-direction:column}
        /* Encabezados opcionales de sección (texto/imagen antes del bloque) */
        .sec-intro{margin:0 0 30px;text-align:center}
        .sec-intro-img{max-width:min(760px,100%);margin:0 auto 14px;border-radius:14px}
        .sec-intro-row{display:flex;align-items:center;justify-content:center;gap:12px}
        .sec-intro-title{margin:0;color:#1f2937;font-weight:800;letter-spacing:-.02em}
        .sec-intro-sub{margin:8px 0 0;color:#6b7280;font-size:15px}
        .sec-intro--simple .sec-intro-title{font-size:clamp(18px,2.2vw,24px);font-weight:500;color:#374151}
        .sec-intro--dot .sec-intro-title{font-size:clamp(26px,3.4vw,38px)}
        .sec-intro-dot{color:var(--primary);font-size:1.15em;line-height:0}
        .sec-intro--band .sec-intro-row{padding:16px clamp(18px,4vw,30px);background:var(--si-bg);color:var(--si-ink);border-radius:16px;box-shadow:0 10px 26px color-mix(in srgb,var(--si-bg) 35%,transparent)}
        .sec-intro--band .sec-intro-title{color:var(--si-ink);font-size:clamp(19px,2.5vw,26px)}
        .sec-intro-ico{width:34px;height:34px;display:grid;place-items:center}
        .sec-intro-ico svg{width:26px;height:26px}
        .sec-intro--band .sec-intro-sub{color:#6b7280}
        .sec-intro--strip{margin-left:calc(50% - 50vw);margin-right:calc(50% - 50vw);text-align:left}
        .sec-intro--strip .sec-intro-row{justify-content:flex-start;padding:10px clamp(16px,5vw,60px);background:var(--si-bg)}
        .sec-intro--strip .sec-intro-title{color:var(--si-ink);font-size:14.5px;font-weight:700}
        @media(max-width:640px){.sec-intro{margin-bottom:20px}.sec-intro--band .sec-intro-row{padding:12px 14px}}
        .header-zone.is-sticky{position:sticky;top:0;z-index:60;box-shadow:0 2px 12px rgba(15,23,42,.06)}.header-zone.is-sticky-menu .category-nav{position:sticky;top:0;z-index:60;box-shadow:0 2px 12px rgba(15,23,42,.06)}.store-header>.container.header-main{width:min(1360px,calc(100% - 48px))}.header-main{min-height:78px;display:grid;grid-template-columns:minmax(220px,.9fr) minmax(320px,1.7fr) minmax(200px,.7fr);align-items:center;gap:28px}.brand{min-width:0;display:flex;align-items:center;gap:13px}.brand-logo{height:var(--logo-h);width:auto;max-width:min(300px,32vw);object-fit:contain;flex:0 0 auto}.brand-mark{width:44px;height:44px;display:grid;place-items:center;flex:0 0 44px;color:#fff;background:var(--primary);border-radius:var(--radius);font-weight:700}.brand-copy{min-width:0}.brand-name,.brand-tagline{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.brand-name{color:var(--header-text);font-size:17px;font-weight:700;letter-spacing:-.02em}.brand-tagline{margin-top:3px;color:#64748b;font-size:11px}
        .qv-sizes,.pdp-sizes{margin:14px 0 4px}
        .qv-sizes-label{display:block;margin-bottom:8px;color:#334155;font-size:12px;font-weight:800;letter-spacing:.04em;text-transform:uppercase}
        .qv-size-list{display:flex;flex-wrap:wrap;gap:8px}
        .qv-size{min-width:46px;min-height:40px;padding:0 12px;color:#334155;background:#fff;border:1.5px solid #cbd5e1;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;transition:.14s}
        .qv-size:hover{border-color:var(--primary);color:var(--primary)}
        .qv-size.on{color:#fff;background:var(--primary);border-color:var(--primary)}
        .qv-size-req{margin:8px 0 0;color:#b42318;font-size:12px;font-weight:700}
        .search{position:relative}
        /* Búsqueda predictiva */
        .search-suggest{position:absolute;top:calc(100% + 8px);left:0;right:0;z-index:90;overflow:hidden;background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:0 18px 44px rgba(15,23,42,.16)}
        .search-suggest-item{display:flex;align-items:center;gap:11px;padding:9px 12px;color:#172033;transition:background .12s ease}
        .search-suggest-item:hover,.search-suggest-item:focus-visible{background:var(--surface,#f5f7fb)}
        .search-suggest-thumb{width:42px;height:42px;flex:0 0 42px;display:grid;place-items:center;overflow:hidden;background:#fafaf9;border:1px solid #f1f5f9;border-radius:8px}
        .search-suggest-thumb img{width:100%;height:100%;object-fit:contain;padding:4px}
        .search-suggest-info{flex:1;min-width:0}
        .search-suggest-info strong{display:block;overflow:hidden;font-size:12.5px;font-weight:700;line-height:1.35;text-overflow:ellipsis;white-space:nowrap}
        .search-suggest-info small{display:block;overflow:hidden;color:#7c899b;font-size:10px;font-weight:700;letter-spacing:.05em;text-overflow:ellipsis;text-transform:uppercase;white-space:nowrap}
        .search-suggest-price{flex:0 0 auto;color:var(--secondary);font-size:13px;font-weight:800}
        .search-suggest-all{width:100%;padding:10px 12px;color:var(--primary);background:var(--surface,#f5f7fb);border:0;border-top:1px solid var(--border);font-size:12px;font-weight:800;cursor:pointer;transition:filter .12s ease}
        .search-suggest-all:hover{filter:brightness(.97)}.search input{width:100%;height:44px;padding:0 47px 0 15px;color:#172033;background:#fff;border:1px solid #cbd5e1;border-radius:var(--radius);outline:0}.search input:focus,.toolbar input:focus,.toolbar select:focus{border-color:var(--primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 13%,transparent)}.search svg{position:absolute;top:12px;right:15px;width:20px;color:#64748b}.header-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px}.phone-copy{display:flex;flex-direction:column;padding-right:16px;border-right:1px solid var(--border)}.phone-copy small{color:#64748b;font-size:10px}.phone-copy strong{margin-top:3px;font-size:12px}.cart-trigger{min-width:52px;height:44px;display:flex;align-items:center;justify-content:center;gap:8px;color:var(--header-text);background:#fff;border:1px solid #cbd5e1;border-radius:var(--radius);cursor:pointer}.cart-trigger svg{width:20px}.cart-count{min-width:20px;height:20px;display:grid;place-items:center;padding:0 5px;color:#fff;background:var(--primary);border-radius:20px;font-size:10px;font-weight:700}
        .category-nav{background:#fff;border-bottom:1px solid var(--border)}.category-list{min-height:48px;display:flex;align-items:center;gap:4px;overflow-x:auto;scrollbar-width:none}.category-list::-webkit-scrollbar{display:none}.category-list a{flex:0 0 auto;padding:9px 13px;color:#475569;border-radius:5px;font-size:13px;font-weight:500}.category-list a.is-active{color:#fff;background:var(--secondary)}.category-list a:hover{color:var(--primary);background:#f1f5f9}
        /* Mega-menú de categorías */
        .category-nav{position:relative}.category-bar{display:flex;align-items:center;gap:10px;min-height:52px}
        .category-bar .category-list{flex:1;justify-content:flex-start}
        .category-bar.menu-center .category-list{justify-content:center}
        /* Centrado REAL respecto a la barra completa (no al espacio sobrante) en escritorio ancho */
        @media(min-width:1200px){
        .category-bar.menu-center{position:relative}
        {{-- Centrado FLEX (no absoluto): con chips de perfiles o botón de categorías
             en la fila, el centrado absoluto se montaba encima de ellos. --}}
        .category-bar.menu-center .category-list{justify-content:center;margin-inline:auto;flex:0 1 auto;width:auto;overflow:visible}
        }
        .category-bar.menu-right .category-list{justify-content:flex-end}
        /* ── Diseños del menú (header_style) ── */
        .hz-style-dark .category-nav{background:var(--secondary);border-bottom:0}
        .hz-style-dark .category-list a{color:#e2e8f0}
        .hz-style-dark .category-list a:hover{color:#fff;background:rgba(255,255,255,.12)}
        .hz-style-dark .category-list a.is-active{color:var(--secondary);background:#fff}
        .hz-style-dark .mega-btn{color:var(--secondary);background:#fff}
        .hz-style-accent .category-nav{background:var(--primary);border-bottom:0}
        .hz-style-accent .category-list a{color:#fff;font-weight:600}
        .hz-style-accent .category-list a:hover{color:#fff;background:rgba(255,255,255,.16)}
        .hz-style-accent .category-list a.is-active{color:var(--primary);background:#fff}
        .hz-style-accent .mega-btn{color:var(--primary);background:#fff}
        .hz-style-pill .category-nav{padding:10px 0 6px;background:transparent;border-bottom:0}
        .hz-style-pill .category-bar{min-height:0;padding:6px 10px;background:#fff;border:1px solid var(--border);border-radius:999px;box-shadow:0 10px 26px rgba(15,23,42,.09)}
        .hz-style-pill .category-list{min-height:44px}
        .hz-style-pill .category-list a{border-radius:999px}
        .hz-style-pill .category-list a.is-active{color:#fff;background:var(--primary)}
        .hz-style-pill .mega-btn{border-radius:999px}
        .hz-style-line .category-list{gap:20px}
        .hz-style-line .category-list a{padding:9px 2px;color:#334155;border-radius:0;border-bottom:2px solid transparent;font-size:12.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase}
        .hz-style-line .category-list a:hover{color:var(--primary);background:transparent;border-bottom-color:color-mix(in srgb,var(--primary) 45%,transparent)}
        .hz-style-line .category-list a.is-active{color:var(--primary);background:transparent;border-bottom-color:var(--primary)}
        .hz-style-line .mega-btn{color:var(--secondary);background:transparent;border:1.5px solid var(--border)}
        .hz-style-line .mega-btn:hover{color:var(--primary);border-color:var(--primary);filter:none}
        /* Colores personalizados del menú (si están definidos, mandan sobre el diseño) */
        @if($menuBgC).header-zone:not(.hz-style-pill) .category-nav{background:{{ $menuBgC }}!important;border-bottom-color:color-mix(in srgb,{{ $menuBgC }} 82%,#64748b)!important}
        .hz-style-pill .category-bar{background:{{ $menuBgC }}!important}@endif
        @if($menuInkC).category-list a{color:{{ $menuInkC }}!important}
        .category-list a:hover{color:{{ $menuInkC }}!important;background:color-mix(in srgb,{{ $menuInkC }} 14%,transparent)!important;border-bottom-color:{{ $menuInkC }}!important}@endif
        @if($menuActBgC).category-list a.is-active{background:{{ $menuActBgC }}!important}@endif
        @if($menuActInkC).category-list a.is-active{color:{{ $menuActInkC }}!important;border-bottom-color:{{ $menuActInkC }}!important}@endif
        @if($pageBgC)
        /* Fondo global de la página */
        body{background:{{ $pageBgC }}!important}
        [data-store-native-section]:not(.flash-sale-section):not(.style-band),.catalog,.store-page{background:{{ $pageBgC }}!important}
        @endif
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
        .premium-hero{min-height:var(--hero-desktop-h,560px)}.premium-hero-inner{min-height:0;box-sizing:border-box}
        .premium-hero-copy.is-center{margin-inline:auto;text-align:center}.premium-hero-copy.is-center .hero-actions{justify-content:center}.premium-hero-copy.is-center .ph-eyebrow{margin-inline:auto}
        .premium-hero-copy.is-right{margin-left:auto;text-align:right}.premium-hero-copy.is-right .hero-actions{justify-content:flex-end}.premium-hero-copy.is-right .ph-eyebrow{margin-left:auto}
        .ph-picture img.pos-left{object-position:left center}.ph-picture img.pos-right{object-position:right center}.ph-picture img.pos-top{object-position:center top}.ph-picture img.pos-bottom{object-position:center bottom}
        .ph-slide-copy .hero-actions{margin-top:28px}.ph-slide-copy .button{min-width:148px}.ph-slide-copy .button-ghost{background:rgba(15,23,42,.34)}
        @media(max-width:760px){.premium-hero-copy.is-right{text-align:center}.premium-hero-copy.is-right .hero-actions{justify-content:center}.premium-hero-copy.is-right .ph-eyebrow{margin-inline:auto}.ph-slide-copy .button{width:100%;max-width:320px}.ph-slide-copy .hero-actions{flex-direction:column}}
        @media(max-width:760px){.premium-hero{min-height:var(--hero-mobile-h)}.premium-hero-inner{min-height:0;padding:48px 22px 96px}.ph-shade{background:linear-gradient(180deg,rgba(7,15,38,.32),rgba(7,15,38,.86) 72%,rgba(7,15,38,.94))}.ph-picture img{object-position:center}.ph-arrow{width:40px;height:40px}.ph-arrow-prev{left:10px}.ph-arrow-next{right:10px}.ph-dots{bottom:14px}.ph-title{font-size:clamp(31px,10vw,46px)}.ph-sub{font-size:14px}.premium-hero .hero-actions{justify-content:center}.premium-hero-copy{text-align:center;margin:auto}.ph-eyebrow{margin-inline:auto}}
        .premium-hero.has-bg .premium-hero-inner{grid-template-columns:1fr}
        .premium-hero.has-bg .premium-hero-copy{max-width:560px}
        @media(max-width:960px){.premium-hero.has-bg{background-image:linear-gradient(180deg,rgba(11,23,54,.72),rgba(11,23,54,.92)),var(--hero-bg-img)!important;background-position:center}}
        .premium-hero-bg{position:absolute;inset:0;overflow:hidden;pointer-events:none}
        .ph-glow{position:absolute;border-radius:50%;filter:blur(90px);opacity:.55}
        .ph-glow-1{width:520px;height:520px;top:-140px;right:-80px;background:radial-gradient(circle,var(--primary) 0%,transparent 70%)}
        .ph-glow-2{width:440px;height:440px;bottom:-160px;left:8%;background:radial-gradient(circle,{{ $secondary }} 0%,transparent 70%);opacity:.4}
        .ph-grid{position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.04) 1px,transparent 1px);background-size:52px 52px;mask-image:radial-gradient(120% 80% at 60% 30%,#000 30%,transparent 75%)}
        .ph-line{position:absolute;height:1px;width:60%;background:linear-gradient(90deg,transparent,color-mix(in srgb,{{ $primary }} 70%,transparent),transparent);opacity:.6}
        .ph-line-1{top:32%;left:-10%;transform:rotate(-8deg)}
        .ph-line-2{bottom:26%;right:-10%;width:50%;background:linear-gradient(90deg,transparent,color-mix(in srgb,{{ $secondary }} 60%,transparent),transparent);transform:rotate(6deg)}
        .premium-hero-inner{position:relative;z-index:2;display:grid;grid-template-columns:40% 60%;align-items:center;gap:40px;padding:clamp(26px,5vh,70px) 0;width:min(1180px,calc(100% - 40px))}
        /* Al cambiar de diapositiva conviven dos textos durante la transicion:
           como el contenedor es una rejilla, el segundo abria una fila nueva y la
           seccion daba un salto de alto. Todos comparten celda y se superponen. */
        /* Solo se ocultan los textos cuando hay carrusel (varios copys apilados).
           Con hero simple hay uno solo y debe verse siempre: al no existir el
           carrusel nadie le ponia la clase y el hero quedaba mudo. */
        .premium-hero-inner>.premium-hero-copy{grid-area:1/1}
        .premium-hero-inner:has(> .premium-hero-copy ~ .premium-hero-copy)>.premium-hero-copy{
            opacity:0;visibility:hidden;pointer-events:none;
            transition:opacity .45s ease,visibility .45s ease}
        .premium-hero-inner:has(> .premium-hero-copy ~ .premium-hero-copy)>.premium-hero-copy.is-on{
            opacity:1;visibility:visible;pointer-events:auto}
        /* Pantallas de poca altura (portatiles al 100% de zoom): el hero ocupaba
           casi todo el alto util y no se intuia que hubiera contenido debajo.
           Se compacta el texto en vez de recortar la imagen. */
        @media(min-width:961px) and (max-height:950px){
            .premium-hero-inner{padding:clamp(20px,3.4vh,44px) 0;gap:28px}
            .premium-hero .ph-title{font-size:clamp(30px,3.1vw,44px);line-height:1.08}
            .premium-hero .ph-sub{font-size:15px;margin-top:10px}
            .premium-hero .hero-actions{margin-top:18px}
            .premium-hero .ph-eyebrow{margin-bottom:8px}
            /* El bloque de texto llevaba 78px fijos arriba y abajo (156px en total):
               era el grueso del alto sobrante en pantallas de portatil. */
            .premium-hero .premium-hero-copy,.premium-hero .ph-slide-copy{padding:clamp(14px,2.4vh,40px) 0!important}
        }
        .premium-hero-copy{min-width:0}
        .ph-eyebrow{display:inline-flex;align-items:center;gap:10px;margin-bottom:22px;padding:7px 14px;color:color-mix(in srgb,{{ $primary }} 55%,#fff);background:color-mix(in srgb,{{ $primary }} 12%,transparent);border:1px solid color-mix(in srgb,{{ $primary }} 30%,transparent);border-radius:999px;font-size:11.5px;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
        .ph-title{margin:0;color:#fff;font-family:var(--font-title);font-size:clamp(38px,4.6vw,62px);font-weight:800;line-height:1.04;letter-spacing:-.03em;text-transform:uppercase;text-shadow:0 4px 30px color-mix(in srgb,{{ $primary }} 25%,transparent)}
        .ph-sub{max-width:440px;margin:22px 0 0;color:#aab6d4;font-size:16.5px;line-height:1.7}
        .premium-hero .hero-actions{margin-top:32px}
        .premium-hero .button-primary{background:linear-gradient(135deg,var(--primary),color-mix(in srgb,var(--primary) 72%,#000));box-shadow:0 10px 30px color-mix(in srgb,var(--primary) 40%,transparent);min-height:50px;padding:0 26px;font-size:14px}
        .premium-hero .button-primary:hover{filter:brightness(1.08);transform:translateY(-2px)}
        .premium-hero .button-ghost{min-height:50px;padding:0 24px;color:#dbe4ff;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.16);border-radius:var(--btn-radius);font-size:14px;font-weight:700;display:inline-flex;align-items:center;gap:8px;transition:.18s}
        .premium-hero .button-ghost:hover{background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.3)}
        .premium-hero-visual{position:relative;min-height:420px;display:flex;align-items:center;justify-content:center}
        .ph-stage{position:relative;width:100%;height:100%;min-height:420px;display:flex;align-items:center;justify-content:center}
        .ph-main{position:relative;z-index:3;width:min(560px,92%);filter:drop-shadow(0 40px 70px rgba(0,0,0,.55)) drop-shadow(0 0 40px color-mix(in srgb,{{ $primary }} 25%,transparent))}
        .ph-stage-clean .ph-main{width:min(680px,100%)}
        .ph-main img{width:100%;height:auto;object-fit:contain;animation:phFloat 6s ease-in-out infinite}
        .ph-float{position:absolute;z-index:4;border-radius:16px;overflow:hidden;background:rgba(255,255,255,.03);backdrop-filter:blur(2px);filter:drop-shadow(0 20px 40px rgba(0,0,0,.5))}
        .ph-float img{width:100%;height:100%;object-fit:contain}
        .ph-float-a{width:130px;height:130px;top:6%;right:4%;animation:phFloat 5s ease-in-out .3s infinite}
        .ph-float-b{width:150px;height:110px;bottom:8%;left:0;animation:phFloat 5.5s ease-in-out .6s infinite}
        .ph-float-c{width:100px;height:100px;bottom:20%;right:10%;animation:phFloat 4.5s ease-in-out .9s infinite}
        .ph-reflection{position:absolute;z-index:2;bottom:6%;left:50%;transform:translateX(-50%);width:60%;height:60px;background:radial-gradient(ellipse at center,color-mix(in srgb,{{ $primary }} 35%,transparent),transparent 70%);filter:blur(20px)}
        @keyframes phFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-14px)}}
        @media(prefers-reduced-motion:reduce){.ph-main img,.ph-float{animation:none}}
        @media(max-width:960px){.premium-hero{min-height:0}.premium-hero-inner{grid-template-columns:1fr;gap:36px;padding:clamp(24px,4.5vh,52px) 0;text-align:center}.ph-eyebrow{margin-inline:auto}.premium-hero .hero-actions{justify-content:center}.ph-sub{margin-inline:auto}.premium-hero-visual{min-height:320px}.ph-float-a{right:12%}.ph-float-c{right:16%}}
        @media(max-width:560px){.ph-float{display:none}.ph-main{width:82%}}
        .hero-visual{min-height:330px;display:flex;align-items:center}.hero-frame{width:100%;aspect-ratio:4/3;overflow:hidden;background:#fff;border:1px solid #dbe3ed;border-radius:calc(var(--radius)*1.25);box-shadow:0 20px 45px rgba(15,23,42,.1)}.hero-frame>img{width:100%;height:100%;padding:28px;object-fit:contain}.hero-fallback{width:100%;height:100%;display:flex;flex-direction:column;justify-content:center;padding:42px;color:#475569}.hero-fallback>svg{width:42px;color:var(--primary)}.hero-fallback>strong{margin-top:20px;color:#0f172a;font-size:21px}.hero-fallback>span{margin-top:8px;font-size:14px;line-height:1.6}.hero-metrics{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1px;margin-top:28px;overflow:hidden;background:var(--border);border:1px solid var(--border);border-radius:calc(var(--radius)*.75)}.hero-metric{padding:16px;background:#fff}.hero-metric b{display:block;color:#0f172a;font-size:21px}.hero-metric small{display:block;margin-top:4px;color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.05em}

        .trust-section{padding:64px 0;background:{{ $trustSectionBg }};border-bottom:1px solid var(--border)}
        .trust-section-head{margin:0 auto 30px;max-width:720px;text-align:center}.trust-section-head h2{margin:0;color:{{ $trustTextColor }};font-size:clamp(25px,2.8vw,36px);line-height:1.1;letter-spacing:-.045em}.trust-section-head p{margin:10px 0 0;color:#64748b;font-size:15px;line-height:1.6}
        .trust-grid{display:grid;grid-template-columns:repeat({{ $trustSectionColumns }},minmax(0,1fr));gap:16px}
        .trust-card{display:flex;align-items:center;gap:15px;min-width:0;padding:22px;background:{{ $trustCardBg }};border:1px solid color-mix(in srgb,{{ $trustAccentColor }} 14%,#dbe3ef);border-radius:{{ $trustSectionRadius }}px;box-shadow:0 10px 30px rgba(15,23,42,.045);transition:transform .22s ease,border-color .22s ease,box-shadow .22s ease}
        .trust-card:hover{transform:translateY(-4px);border-color:color-mix(in srgb,{{ $trustAccentColor }} 55%,#fff);box-shadow:0 18px 42px color-mix(in srgb,{{ $trustAccentColor }} 13%,transparent)}
        .trust-icon{width:48px;height:48px;display:grid;place-items:center;flex:0 0 48px;color:{{ $trustAccentColor }};background:color-mix(in srgb,{{ $trustAccentColor }} 10%,#fff);border:1px solid color-mix(in srgb,{{ $trustAccentColor }} 16%,#fff);border-radius:14px;transition:.22s}
        .trust-card:hover .trust-icon{transform:scale(1.05);background:color-mix(in srgb,{{ $trustAccentColor }} 16%,#fff);border-color:color-mix(in srgb,{{ $trustAccentColor }} 40%,#fff)}.trust-icon svg{width:23px;height:23px}
        {{-- Solo el texto (strong/span del copy): el chip del icono también es <span> y no debe heredar esto. --}}
        .trust-card-copy{min-width:0}.trust-card-copy strong{display:block;color:{{ $trustTextColor }};font-size:15px;line-height:1.35}.trust-card-copy span{display:block;margin-top:6px;color:#64748b;font-size:13px;line-height:1.55}
        .trust-section.style-compact .trust-section-head{text-align:center;margin-left:auto;margin-right:auto}.trust-section.style-compact .trust-card{align-items:center;padding:17px 18px;box-shadow:none}.trust-section.style-compact .trust-icon{width:42px;height:42px;flex-basis:42px}
        .trust-section.style-icons-top .trust-section-head{text-align:center;margin-left:auto;margin-right:auto}.trust-section.style-icons-top .trust-card{display:block;text-align:center;padding:26px 20px}.trust-section.style-icons-top .trust-icon{margin:0 auto 15px}

        .catalog{padding:72px 0 90px}.section-heading{display:flex;align-items:end;justify-content:space-between;gap:32px;margin-bottom:26px}.section-heading h2{margin:0;color:var(--secondary);font-size:clamp(26px,3vw,36px);letter-spacing:-.035em}.section-heading p{max-width:550px;margin:10px 0 0;color:#64748b;font-size:14px;line-height:1.65}.product-total{flex:0 0 auto;color:#64748b;font-size:12px}.catalog-toolbar{display:grid;grid-template-columns:minmax(260px,1fr) minmax(240px,.75fr);gap:14px;margin-bottom:30px;padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius)}.toolbar{position:relative}.toolbar input,.toolbar select{width:100%;height:44px;padding:0 14px;color:#1e293b;background:#fff;border:1px solid #cbd5e1;border-radius:calc(var(--radius)*.75);outline:0}.toolbar input{padding-left:42px}.toolbar svg{position:absolute;top:12px;left:13px;width:19px;color:#64748b}
        .category-section{scroll-margin-top:145px;margin-top:54px}.category-section:first-of-type{margin-top:0}.category-title{display:flex;align-items:center;gap:14px;margin:0 0 18px;color:var(--secondary);font-size:18px}.category-title:after{height:1px;flex:1;content:'';background:var(--border)}.product-grid{display:grid;grid-template-columns:repeat(var(--columns),minmax(0,1fr));gap:18px}.product-card{min-width:0;display:flex;flex-direction:column;overflow:hidden;background:#fff;border:1px solid var(--border);border-radius:var(--radius);transition:border-color .15s ease,box-shadow .15s ease}.product-card:hover{border-color:#cbd5e1;box-shadow:0 10px 28px rgba(15,23,42,.08)}.product-image{position:relative;aspect-ratio:1;display:grid;place-items:center;overflow:hidden;background:#fff;border-bottom:1px solid #edf1f5}.product-image img{width:100%;height:100%;object-fit:cover}.image-empty{width:52px;color:#cbd5e1}.product-badge{position:absolute;top:12px;left:12px;z-index:2;padding:5px 8px;color:#fff;background:var(--primary);border-radius:4px;font-size:9px;font-weight:700;letter-spacing:.05em}.product-body{min-height:183px;display:flex;flex:1;flex-direction:column;padding:16px}.product-meta{min-height:17px;display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:7px;color:#7c899b;font-size:10px}.available{color:#18794e}.unavailable{color:#b42318}.product-name{color:#243044;font-size:13px;font-weight:600;line-height:1.5}.product-name:hover{color:var(--primary)}.rating{margin-top:8px;color:#a16207;font-size:11px}.price-row{display:flex;flex-wrap:wrap;align-items:baseline;gap:7px;margin-top:auto;padding-top:15px}.price{color:var(--secondary);font-size:18px;font-weight:700}.compare-price{color:#94a3b8;font-size:11px;text-decoration:line-through}.quote-price{color:var(--primary);font-size:14px;font-weight:700}.wholesale{margin-top:5px;color:#64748b;font-size:10px}.product-action{width:100%;min-height:40px;margin-top:14px;color:#fff;background:var(--secondary);border:0;border-radius:calc(var(--radius)*.75);font-size:11px;font-weight:700;cursor:pointer}.product-action:hover{background:var(--primary)}.product-action:disabled{color:#94a3b8;background:#e2e8f0;cursor:not-allowed}.empty{padding:50px 20px;color:#64748b;text-align:center;border:1px dashed #cbd5e1;border-radius:var(--radius)}
        /* Catálogo avanzado: experiencia de filtros de Ecommerce con identidad CompuTienda. */
        .catalog-breadcrumb{display:flex;align-items:center;gap:8px;margin-bottom:10px;color:#64748b;font-size:12px}.catalog-breadcrumb a:hover{color:var(--primary)}
        .catalog-experience{display:grid;grid-template-columns:280px minmax(0,1fr);align-items:start;gap:30px;margin-top:28px}.catalog-filter-panel{position:sticky;top:96px;max-height:calc(100vh - 116px);overflow-y:auto;background:#fff;border:1px solid var(--border);border-radius:12px;scrollbar-width:thin}.catalog-filter-header{position:sticky;top:0;z-index:2;min-height:52px;display:flex;align-items:center;justify-content:space-between;padding:0 16px;background:#fff;border-bottom:1px solid var(--border)}.catalog-filter-header strong{font-size:14px}.catalog-clear{min-height:44px;padding:0;color:var(--primary);background:transparent;border:0;font-size:12px;font-weight:700;cursor:pointer}.catalog-clear[disabled]{opacity:0;pointer-events:none}
        .catalog-filter-group{border-top:1px solid var(--border)}.catalog-filter-group:first-of-type{border-top:0}.catalog-filter-summary{min-height:48px;display:flex;align-items:center;gap:8px;padding:0 16px;list-style:none;font-size:13px;font-weight:700;cursor:pointer;user-select:none}.catalog-filter-summary::-webkit-details-marker{display:none}.catalog-filter-summary svg{width:17px;margin-left:auto;color:#64748b;transition:transform .18s ease}.catalog-filter-group[open]>.catalog-filter-summary svg{transform:rotate(180deg)}.catalog-filter-badge{min-width:19px;height:19px;display:grid;place-items:center;padding:0 5px;color:#fff;background:var(--primary);border-radius:999px;font-size:10px}.catalog-filter-body{display:flex;flex-direction:column;gap:3px;padding:0 16px 14px}.catalog-filter-search input{width:100%;height:38px;margin-bottom:7px;padding:0 11px;color:#172033;background:var(--surface);border:1px solid #cbd5e1;border-radius:6px;font-size:12px;outline:0}.catalog-filter-search input:focus{border-color:var(--primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 12%,transparent)}
        .catalog-filter-option{min-height:36px;display:flex;align-items:center;gap:9px;padding:5px 6px;border-radius:6px;font-size:12px;cursor:pointer}.catalog-filter-option:hover{background:var(--surface)}.catalog-filter-option input{appearance:none;width:17px;height:17px;display:grid;place-items:center;flex:0 0 17px;margin:0;background:#fff;border:1.5px solid #cbd5e1;border-radius:50%;cursor:pointer}.catalog-filter-option input:checked{background:var(--primary);border-color:var(--primary);box-shadow:inset 0 0 0 4px #fff}.catalog-filter-option span{min-width:0;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.catalog-filter-option small{padding:2px 6px;color:#64748b;background:var(--surface);border-radius:999px;font-size:10px}.catalog-subcategories{margin:1px 0 5px 4px;padding-left:8px;border-left:2px solid var(--border)}.catalog-filter-option-sub{color:#475569}.catalog-filter-checkbox input{border-radius:4px}.catalog-filter-checkbox input:checked{box-shadow:none}.catalog-filter-checkbox input:checked:after{content:'✓';color:#fff;font-size:11px;font-weight:800}
        .catalog-price-inputs{display:grid;grid-template-columns:1fr 1fr;gap:8px}.catalog-price-inputs label{display:flex;flex-direction:column;gap:5px;color:#64748b;font-size:10px}.catalog-price-inputs input{width:100%;height:38px;padding:0 9px;color:#172033;background:var(--surface);border:1px solid #cbd5e1;border-radius:6px;font-size:12px;outline:0}.catalog-price-inputs input:focus{border-color:var(--primary)}.catalog-range{position:relative;height:4px;margin:18px 5px 10px;background:#e2e8f0;border-radius:999px}.catalog-range span{position:absolute;height:4px;background:var(--primary);border-radius:999px}.catalog-range input{position:absolute;top:0;width:100%;height:4px;margin:0;appearance:none;background:transparent;pointer-events:none}.catalog-range input::-webkit-slider-thumb{width:18px;height:18px;appearance:none;background:#fff;border:2px solid var(--primary);border-radius:50%;box-shadow:0 1px 5px rgba(15,23,42,.2);pointer-events:auto}.catalog-range input::-moz-range-thumb{width:18px;height:18px;background:#fff;border:2px solid var(--primary);border-radius:50%;pointer-events:auto}
        .catalog-results{min-width:0}.catalog-chips{min-height:32px;display:flex;flex-wrap:wrap;gap:7px;margin-bottom:12px}.catalog-chip{min-height:32px;display:inline-flex;align-items:center;gap:7px;padding:0 11px;color:var(--primary);background:color-mix(in srgb,var(--primary) 9%,white);border:1px solid color-mix(in srgb,var(--primary) 18%,white);border-radius:999px;font-size:11px;font-weight:600;cursor:pointer}.catalog-chip:hover{background:color-mix(in srgb,var(--primary) 15%,white)}.catalog-chip-neutral{color:#475569;background:var(--surface);border-color:var(--border)}.catalog-results-head{min-height:46px;display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:14px}.catalog-results-count{color:#64748b;font-size:12px}.catalog-results-count strong{color:#172033}.catalog-results-tools{display:flex;align-items:center;gap:8px}.catalog-sort{height:42px;min-width:205px;padding:0 36px 0 13px;color:#172033;background:#fff;border:1px solid #cbd5e1;border-radius:6px;font-size:12px;outline:0}.catalog-sort:focus{border-color:var(--primary)}.catalog-mobile-filter{display:none;min-height:44px;align-items:center;gap:8px;padding:0 14px;color:#fff;background:var(--secondary);border:0;border-radius:6px;font-size:12px;font-weight:700}.catalog-mobile-filter svg{width:17px}.catalog-mobile-filter span{min-width:18px;height:18px;display:grid;place-items:center;background:var(--primary);border-radius:999px;font-size:9px}
        .catalog-product-grid{display:grid;grid-template-columns:repeat({{ $catalogColumns }},minmax(0,1fr));gap:18px}.catalog-card{min-width:0;display:flex;flex-direction:column;overflow:hidden;background:#fff;border:1px solid var(--border);border-radius:12px;transition:border-color .18s ease,box-shadow .18s ease,transform .18s ease}.catalog-card:hover{transform:translateY(-2px);border-color:#cbd5e1;box-shadow:0 12px 30px rgba(15,23,42,.08)}.catalog-card-media{position:relative;aspect-ratio:1;display:grid;place-items:center;overflow:hidden;background:#fafaf9;border-bottom:1px solid #f1f5f9}.catalog-card-media img{width:100%;height:100%;object-fit:cover;transition:transform .25s ease}.catalog-card:hover .catalog-card-media img{transform:scale(1.035)}.catalog-card-media.is-noimg,.catalog-card-media:has(.catalog-card-placeholder){background:linear-gradient(140deg,color-mix(in srgb,var(--primary) 9%,#fff),color-mix(in srgb,var(--primary) 3%,#fff))}.catalog-card-placeholder{width:74px;height:74px;color:color-mix(in srgb,var(--primary) 38%,#cbd5e1);opacity:.9}.catalog-card-media .ph-note{position:absolute;bottom:10px;left:0;right:0;text-align:center;color:color-mix(in srgb,var(--primary) 55%,#94a3b8);font-size:9.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.catalog-discount{position:absolute;top:12px;left:12px;z-index:1;padding:6px 9px;color:#fff;background:var(--primary);border-radius:6px;font-size:10px;font-weight:800}.catalog-sold-out{position:absolute;top:12px;right:12px;padding:6px 8px;color:#fff;background:#475569;border-radius:6px;font-size:9px;font-weight:700}.catalog-card-body{min-height:128px;display:flex;flex:1;flex-direction:column;padding:14px}.catalog-card-category{overflow:hidden;color:#7c899b;font-size:9px;font-weight:700;letter-spacing:.07em;text-overflow:ellipsis;text-transform:uppercase;white-space:nowrap}.catalog-card-name{min-height:40px;margin-top:7px;color:#172033;font-size:13px;font-weight:700;line-height:1.45}.catalog-card-name:hover{color:var(--primary)}.catalog-card-prices{display:flex;flex-wrap:wrap;align-items:baseline;gap:7px;margin-top:auto;padding-top:14px}.catalog-card-price{color:var(--secondary);font-size:17px;font-weight:800}.catalog-card-compare{color:#94a3b8;font-size:11px;text-decoration:line-through}.catalog-card-percent{padding:3px 6px;color:#fff;background:var(--primary);border-radius:4px;font-size:9px;font-weight:800}.catalog-card-wholesale{display:block;margin-top:5px;color:#64748b;font-size:9px}.catalog-card-action{min-height:44px;margin:0 10px 10px;color:#fff;background:var(--primary);border:0;border-radius:6px;font-size:11px;font-weight:800;cursor:pointer}.catalog-card-action:hover{filter:brightness(.92)}.catalog-card-action:disabled{color:#94a3b8;background:#e2e8f0;cursor:not-allowed}.catalog-empty{grid-column:1/-1;padding:64px 22px;text-align:center;background:#fff;border:1px dashed #cbd5e1;border-radius:12px}.catalog-empty svg{width:42px;margin:0 auto;color:#94a3b8}.catalog-empty h3{margin:15px 0 6px;color:#172033;font-size:18px}.catalog-empty p{margin:0 0 18px;color:#64748b;font-size:13px}
        .catalog-card-media-link{position:absolute;inset:0;display:grid;place-items:center}.catalog-card-media-link img{width:100%;height:100%;padding:18px;object-fit:contain;transition:transform .25s ease}.catalog-card:hover .catalog-card-media-link img{transform:scale(1.035)}
        /* El wrapper SSR no debe romper el grid: sus cards participan directamente */
        .catalog-ssr{display:contents}
        /* Vista rápida (quick view) — estilo computienda */
        .catalog-quickview{position:absolute;left:0;right:0;bottom:0;z-index:2;display:flex;justify-content:center;padding:10px;opacity:0;transform:translateY(6px);transition:opacity .18s ease,transform .18s ease;pointer-events:none}.catalog-card:hover .catalog-quickview{opacity:1;transform:none;pointer-events:auto}.catalog-quickview button{min-height:38px;display:inline-flex;align-items:center;gap:6px;padding:0 16px;color:#172033;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 6px 18px rgba(15,23,42,.16);font-size:12px;font-weight:800;cursor:pointer}.catalog-quickview button:hover{color:#fff;background:var(--primary);border-color:var(--primary)}.catalog-quickview svg{width:15px;height:15px}
        @media(hover:none){.catalog-quickview{position:static;padding:0 10px 10px;opacity:1;transform:none;pointer-events:auto}.catalog-quickview button{width:100%;justify-content:center;box-shadow:none}}
        .qv-layer{position:fixed;inset:0;z-index:110;display:flex;align-items:center;justify-content:center;padding:16px}.qv-overlay{position:absolute;inset:0;background:rgba(15,23,42,.56)}.qv-box{position:relative;width:100%;max-width:420px;overflow:hidden;background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:0 24px 64px rgba(15,23,42,.24);animation:qv-in .18s ease}@keyframes qv-in{from{opacity:0;transform:scale(.96) translateY(8px)}to{opacity:1;transform:none}}.qv-head{display:flex;gap:14px;align-items:flex-start;padding:18px 18px 14px}.qv-media{flex:0 0 118px;width:118px;height:118px;display:grid;place-items:center;overflow:hidden;background:#fafaf9;border:1px solid #f1f5f9;border-radius:10px}.qv-media img{width:100%;height:100%;object-fit:cover}.qv-media svg{width:44px;color:#cbd5e1}.qv-info{flex:1;min-width:0}.qv-cat{color:#7c899b;font-size:9px;font-weight:800;letter-spacing:.07em;text-transform:uppercase}.qv-name{display:block;margin-top:5px;color:#172033;font-size:16px;font-weight:800;line-height:1.35;text-decoration:none}.qv-name:hover,.qv-name:active{color:var(--primary)}.qv-prices{display:flex;flex-wrap:wrap;align-items:baseline;gap:8px;margin-top:9px}.qv-price{color:var(--secondary);font-size:20px;font-weight:800}.qv-compare{color:#94a3b8;font-size:12px;text-decoration:line-through}.qv-percent{padding:3px 6px;color:#fff;background:var(--primary);border-radius:4px;font-size:9px;font-weight:800}.qv-low{margin-top:8px;color:#92400e;font-size:12px;font-weight:700}.qv-close{position:absolute;top:12px;right:12px;width:30px;height:30px;display:grid;place-items:center;color:#475569;background:var(--surface);border:0;border-radius:50%;font-size:15px;cursor:pointer}.qv-foot{display:flex;flex-direction:column;gap:8px;padding:0 18px 18px}.qv-out{padding:9px 12px;color:#b91c1c;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:12px;font-weight:700;text-align:center}.qv-add{min-height:46px;display:flex;align-items:center;justify-content:center;gap:8px;color:#fff;background:var(--primary);border:0;border-radius:9px;font-size:14px;font-weight:800;cursor:pointer}.qv-add:hover{filter:brightness(.93)}.qv-view{min-height:44px;display:flex;align-items:center;justify-content:center;color:#172033;background:#fff;border:1px solid #cbd5e1;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;cursor:pointer}
        .catalog-filter-layer{position:fixed;inset:0;z-index:95}.catalog-filter-overlay{position:absolute;inset:0;background:rgba(15,23,42,.56)}.catalog-filter-drawer{position:absolute;right:0;bottom:0;left:0;max-height:88vh;overflow-y:auto;background:#fff;border-radius:18px 18px 0 0;box-shadow:0 -18px 50px rgba(15,23,42,.2)}.catalog-filter-drawer-head{position:sticky;top:0;z-index:2;min-height:58px;display:flex;align-items:center;justify-content:space-between;padding:0 16px;background:#fff;border-bottom:1px solid var(--border)}.catalog-filter-drawer-head strong{font-size:16px}.catalog-filter-drawer-body{padding-bottom:76px}.catalog-filter-drawer-foot{position:sticky;right:0;bottom:0;left:0;display:grid;grid-template-columns:1fr 2fr;gap:8px;padding:12px 16px;background:#fff;border-top:1px solid var(--border)}.catalog-filter-drawer-foot button{min-height:46px;border-radius:7px;font-size:12px;font-weight:800;cursor:pointer}.catalog-filter-drawer-foot button:first-child{color:#475569;background:#fff;border:1px solid #cbd5e1}.catalog-filter-drawer-foot button:last-child{color:#fff;background:var(--primary);border:1px solid var(--primary)}
        .native-footer{padding:26px 0;color:#94a3b8;background:var(--secondary);font-size:12px;text-align:center}.drawer-layer{position:fixed;inset:0;z-index:80}.drawer-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.52)}.cart-drawer{position:absolute;top:0;right:0;width:min(430px,100%);height:100%;display:flex;flex-direction:column;background:#fff;box-shadow:-20px 0 50px rgba(15,23,42,.18)}.drawer-head{min-height:72px;display:flex;align-items:center;justify-content:space-between;padding:0 24px;border-bottom:1px solid var(--border)}.drawer-head h2{margin:0;color:var(--secondary);font-size:18px}.icon-button{width:44px;height:44px;display:grid;place-items:center;color:#475569;background:#fff;border:1px solid var(--border);border-radius:var(--radius);cursor:pointer}.icon-button svg{width:18px}.drawer-content{flex:1;overflow:auto;padding:22px 24px}.cart-empty{margin:80px 0 0;color:#64748b;font-size:14px;text-align:center}.cart-item{display:grid;grid-template-columns:1fr auto auto;align-items:center;gap:12px;padding:16px 0;border-bottom:1px solid var(--border)}.cart-item strong{display:block;color:var(--secondary);font-size:13px;line-height:1.45}.cart-item small{display:block;margin-top:4px;color:#64748b}.quantity{display:flex;align-items:center;border:1px solid #cbd5e1;border-radius:6px}.quantity button{width:40px;height:40px;color:#475569;background:#fff;border:0;cursor:pointer}.quantity span{width:28px;font-size:12px;text-align:center}.remove{width:44px;height:44px;display:grid;place-items:center;padding:0;color:#94a3b8;background:transparent;border:0;cursor:pointer}.remove svg{width:17px}.drawer-footer{padding:20px 24px 24px;border-top:1px solid var(--border)}.cart-total{display:flex;justify-content:space-between;margin-bottom:16px;color:var(--secondary);font-size:16px;font-weight:700}.drawer-footer .button{width:100%}.drawer-note{margin:11px 0 0;color:#64748b;font-size:10px;line-height:1.5;text-align:center}
        /* MOVIL: el encabezado del catalogo ocupaba media pantalla (titulo +
           descripcion + dos contadores). Se compacta y se evita el hueco muerto. */
        @media(max-width:760px){
            .catalog{padding-top:10px!important}
            .catalog>.container>:first-child{margin-top:0}
            .store-header+*{margin-top:0}
            .catalog .section-heading{margin-bottom:12px}
            .catalog .section-heading h2{font-size:24px!important;letter-spacing:-.02em!important}
            .catalog .section-heading p{display:none}
            .catalog .product-total{display:none}
            .breadcrumbs,.breadcrumb{margin-bottom:6px;font-size:11.5px}
        }
        /* Carrito v2: miniatura, subtotal por linea, progreso de envio gratis y
           salida a seguir comprando. Todo opcional desde el Constructor. */
        .cart-head-count{color:#94a3b8;font-size:13px;font-weight:700}
        .cart-item{grid-template-columns:auto 1fr auto auto}
        .cart-thumb{width:52px;height:52px;display:grid;place-items:center;overflow:hidden;background:var(--surface-soft,#f8fafc);border:1px solid var(--border);border-radius:8px;color:#cbd5e1}
        .cart-thumb img{width:100%;height:100%;object-fit:cover}
        .cart-item-copy{min-width:0}
        .cart-line-total{display:block;margin-top:3px;color:var(--secondary);font-size:13px;font-weight:800}
        .cart-empty-box{padding:56px 10px 0;text-align:center;color:#94a3b8}
        .cart-empty-ico{color:#cbd5e1}
        .cart-keep{margin-top:14px;padding:10px 18px;color:var(--primary);background:none;border:1px solid var(--border);border-radius:8px;font-size:12.5px;font-weight:800;cursor:pointer}
        .cart-keep:hover{border-color:var(--primary)}
        .cart-keep--foot{display:block;width:100%;margin-top:8px}
        .cart-ship{margin:0 0 12px;padding:9px 12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;color:#166534;font-size:11.5px}
        .cart-ship b{font-weight:800}
        .cart-ship.is-ok{background:#ecfdf5;border-color:#a7f3d0;font-weight:800}
        .cart-ship-bar{display:block;height:5px;margin-top:7px;background:#dcfce7;border-radius:99px;overflow:hidden}
        .cart-ship-bar i{display:block;height:100%;background:#22c55e;border-radius:99px;transition:width .3s ease}
        /* El boton flotante de WhatsApp tapaba el CTA del carrito */
        body:has(.drawer-layer:not([style*="display: none"])) .official-whatsapp-float,
        body:has(.cartpage:not([style*="display: none"])) .official-whatsapp-float{opacity:0;pointer-events:none;transition:opacity .2s}
        a:focus-visible,button:focus-visible,input:focus-visible,select:focus-visible{outline:3px solid color-mix(in srgb,var(--primary) 45%,white);outline-offset:2px}.product-action{min-height:44px}.category-list a{min-height:44px;display:inline-flex;align-items:center}
        .sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
        @media(max-width:900px){.header-main{grid-template-columns:1fr auto;gap:18px}.search{grid-column:1/-1;grid-row:2;padding-bottom:14px}.phone-copy{display:none}.hero-inner{min-height:0;grid-template-columns:1fr;gap:40px;padding:54px 0}.hero-visual{min-height:0}.hero-frame{max-width:620px;aspect-ratio:16/10}.trust-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.product-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
        @media(max-width:640px){.container{width:calc(100% - 28px)}#bixo-runtime-announcement{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.topbar-inner{min-height:32px;justify-content:center}.contact-links{display:none}.topbar p{max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.header-main{min-height:66px;gap:12px}.brand-logo{width:auto;height:min(calc(var(--logo-h) * .8),56px);max-width:150px;flex-basis:auto}.brand-mark{width:38px;height:38px;flex-basis:38px}.brand-tagline{display:none}.brand-name{max-width:190px;font-size:15px}.category-list{min-height:44px}.hero-inner{width:calc(100% - 28px);gap:32px;padding:42px 0}.hero-copy,.hero-visual{width:100%;max-width:calc(100vw - 28px)}.hero-copy h1{width:100%;max-width:calc(100vw - 28px);white-space:normal!important;font-size:30px;line-height:1.14}.hero-copy>p{width:100%;max-width:calc(100vw - 28px);white-space:normal!important;margin-top:18px;font-size:15px;line-height:1.65}.hero-actions{width:100%;max-width:calc(100vw - 28px);margin-top:25px}.hero-actions .button{width:100%;max-width:100%}.hero-frame{width:100%;max-width:calc(100vw - 28px);aspect-ratio:auto;min-height:340px}.hero-frame>img{padding:18px}.hero-fallback{padding:24px}.hero-metrics{margin-top:22px}.trust-section{padding:44px 0}.trust-section-head{margin-bottom:20px}.trust-grid{grid-template-columns:repeat({{ $trustSectionMobileColumns }},minmax(0,1fr));gap:11px}.trust-card{padding:17px}.trust-section.mobile-carousel .trust-grid{display:flex;overflow-x:auto;scroll-snap-type:x mandatory;padding:2px 2px 14px;scrollbar-width:none}.trust-section.mobile-carousel .trust-grid::-webkit-scrollbar{display:none}.trust-section.mobile-carousel .trust-card{flex:0 0 84%;scroll-snap-align:start}.catalog{padding:52px 0 70px}.section-heading{align-items:flex-start;flex-direction:column;gap:8px}.catalog-toolbar{grid-template-columns:1fr;padding:12px}.category-section{margin-top:44px}.product-grid{grid-template-columns:repeat(var(--mobile-columns),minmax(0,1fr));gap:10px}.product-body{min-height:174px;padding:12px}.product-image img{padding:0}.product-name{font-size:12px}.price{font-size:16px}.product-action{min-height:44px;padding:0 5px}.quantity button{width:44px;height:44px}
        /* Tarjetas del catálogo en móvil: sin "Vista rápida", botones apilados a lo ancho */
        .catalog-quickview{display:none!important}
        /* Vista previa como hoja inferior (igual que ecommerce): tocar la tarjeta la abre */
        .qv-layer{align-items:flex-end;padding:0}
        .qv-box{max-width:100%;border-radius:18px 18px 0 0;animation:qv-up .22s ease}
        @keyframes qv-up{from{opacity:.5;transform:translateY(48px)}to{opacity:1;transform:none}}
        .catalog-card-actions{flex-direction:column;gap:6px;margin:0 8px 10px}
        .catalog-card-actions .catalog-card-action{width:100%;min-height:40px;font-size:10.5px}
        .catalog-card-inquiry{width:100%;min-height:40px;font-size:10.5px}
        .catalog-card-body{min-height:0;padding:11px}
        .catalog-card-name{min-height:0;margin-top:5px;font-size:12px}
        .catalog-card-prices{padding-top:10px}
        .catalog-card-price{font-size:15px}
        .catalog-card-media img{padding:0}}
        @media(max-width:1100px){.catalog-product-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.catalog-experience{grid-template-columns:250px minmax(0,1fr);gap:20px}}
        @media(max-width:900px){.catalog-experience{grid-template-columns:1fr}.catalog-filter-panel{display:none}.catalog-mobile-filter{display:inline-flex}.catalog-results-head{align-items:flex-start}.catalog-results-tools{flex-wrap:wrap;justify-content:flex-end}}
        @media(max-width:640px){.catalog-experience{margin-top:20px}.catalog-breadcrumb{margin-bottom:7px}.catalog-chips{flex-wrap:nowrap;overflow-x:auto;padding-bottom:3px;scrollbar-width:none}.catalog-chip{flex:0 0 auto}.catalog-results-head{align-items:stretch;flex-direction:column}.catalog-results-tools{display:grid;grid-template-columns:auto 1fr;width:100%}.catalog-sort{width:100%;min-width:0}.catalog-product-grid{grid-template-columns:repeat(var(--mobile-columns),minmax(0,1fr));gap:10px}.catalog-card-body{min-height:120px;padding:11px}.catalog-card-media img{padding:0}.catalog-card-name{min-height:36px;font-size:12px}.catalog-card-price{font-size:15px}.catalog-card-percent{display:none}.catalog-card-action{margin:0 7px 7px;padding:0 5px;font-size:10px}.catalog-filter-option{min-height:44px}.catalog-filter-summary{min-height:52px}}
        @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}*,*:before,*:after{transition-duration:.01ms!important;animation-duration:.01ms!important;animation-iteration-count:1!important}.catalog-card:hover{transform:none}}
        /* Excepcion: la franja del pie puede forzarse desde el constructor. La
           regla global de arriba usa !important y la dejaba congelada. */
        @media(prefers-reduced-motion:reduce){.bx-ticker.bx-force-motion .bx-ticker-track{
            animation-duration:var(--bx-ticker-speed,28s)!important;animation-iteration-count:infinite!important}}

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
        .category-toggle{padding:0 22px!important;border-radius:16px!important;background:linear-gradient(135deg,var(--primary),color-mix(in srgb,var(--primary) 82%,#fff))!important;font-weight:800!important;box-shadow:0 14px 28px color-mix(in srgb,var(--primary) 24%,transparent)!important}
        .hero-overlay{background:linear-gradient(90deg,rgba(15,23,42,.78),rgba(15,23,42,.34) 42%,rgba(15,23,42,.10))!important}
        .premium-hero-copy{padding:78px 0!important}
        .ph-title{letter-spacing:-.045em!important}
        .ph-sub{font-size:17px!important;line-height:1.68!important;color:rgba(255,255,255,.90)!important}
        .button-primary{background:linear-gradient(135deg,var(--primary),color-mix(in srgb,var(--primary) 82%,#fff))!important;color:#fff!important;box-shadow:0 12px 28px color-mix(in srgb,var(--primary) 26%,transparent)!important}
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
        /* El boton de categorias quedaba pegado al primer enlace del menu (10px):
           se separa con aire y un divisor sutil que marca que son dos zonas. */
        .category-bar .mega-trigger{margin-right:22px!important;padding-right:22px!important;position:relative}
        .category-bar .mega-trigger:after{content:'';position:absolute;right:0;top:50%;transform:translateY(-50%);width:1px;height:24px;background:var(--border,rgba(15,23,42,.14))}
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
        .flash-countdown{display:inline-flex;flex-wrap:wrap;gap:8px;margin:0 0 26px}
        .flash-countdown>div{position:relative;min-width:66px;padding:12px 10px 9px;border-radius:12px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);text-align:center;backdrop-filter:blur(8px)}
        .flash-countdown strong{display:block;font-size:30px;font-weight:800;line-height:1;color:#fff;font-variant-numeric:tabular-nums;letter-spacing:-.02em}
        .flash-countdown span{display:block;margin-top:6px;color:rgba(255,255,255,.7);font-size:9px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}
        .flash-product-grid{margin-top:24px}
        @media(max-width:640px){.flash-campaign{min-height:360px;padding:28px 22px;border-radius:18px}.flash-countdown>div{min-width:62px}}

    /* ═══════════ Fase 2B-B: rediseño tecnológico premium (usa variables configurables) ═══════════ */
    :root{--rd-space:clamp(48px,6vw,88px);--rd-radius:14px;--rd-shadow:0 6px 24px -8px rgba(15,23,42,.18);--rd-shadow-hover:0 18px 40px -12px rgba(15,23,42,.28);--rd-ring:color-mix(in srgb,var(--primary) 40%,transparent);}
    body{background:#f5f7fb}
    /* Ancho unificado: el contenido se alinea EXACTO con el header y el menú. */
    .container,
    .store-header>.container.header-main,
    .category-nav>.container.category-bar,
    .topbar>.container.topbar-inner{
        width:min(var(--header-layout-width,1360px),calc(100% - 48px))!important;
        max-width:none!important;
        margin-inline:auto!important;
        padding-inline:0!important;
    }
    @media(max-width:768px){
        .container,
        .store-header>.container.header-main,
        .category-nav>.container.category-bar,
        .topbar>.container.topbar-inner{width:min(var(--header-layout-width,1360px),calc(100% - 28px))!important}
    }
    /* Secciones con ritmo vertical uniforme */
    section{padding-block:var(--rd-space)!important}
    .section-heading h2{font-size:clamp(24px,3vw,34px)!important;font-weight:800!important;letter-spacing:-.02em;color:var(--secondary)}
    .section-heading p{color:#64748b;font-size:15px}
    /* Hero tecnológico: más alto, overlay controlado, título contundente */
    @if($heroPxDesktop > 0)
    #storefront-main .premium-hero{min-height:{{ max(220, min(900, $heroPxDesktop)) }}px!important}
    {{-- El alto pedido no servia de nada si la foto del hero era mas alta que
         el banner: la imagen manda y el banner crecia. Aqui la foto se acota. --}}
    #storefront-main .premium-hero .ph-main{max-height:{{ max(140, min(820, $heroPxDesktop - 90)) }}px}
    #storefront-main .premium-hero .ph-main img{max-height:{{ max(140, min(820, $heroPxDesktop - 90)) }}px;width:auto;max-width:100%;margin-inline:auto;display:block}
    @endif
    @if($heroPxMobile > 0)
    @media(max-width:760px){
        {{-- En movil pasaba lo mismo que en escritorio: la foto era mas alta que
             el banner y el alto pedido no se cumplia. Se acota tambien aqui. --}}
        {{-- Sin max-height: recortar el banner cortaba el boton de la portada.
             Se acota la foto, y el texto decide el alto minimo real. --}}
        #storefront-main .premium-hero{min-height:{{ max(180, min(900, $heroPxMobile)) }}px!important}
        {{-- En movil el texto y la foto se apilan: si la foto ocupa 420px, el
             banner mide el doble de lo pedido. Se acota el bloque visual. --}}
        {{-- El interior traia 48+96px de relleno en movil: con un alto pedido
             esos 144px son casi la mitad del banner. --}}
        #storefront-main .premium-hero .premium-hero-inner{padding-top:26px!important;padding-bottom:34px!important;gap:18px}
        #storefront-main .premium-hero .premium-hero-copy,
        #storefront-main .premium-hero .ph-slide-copy{padding-block:0!important}
        #storefront-main .premium-hero .premium-hero-visual{max-height:{{ max(100, min(420, (int) ($heroPxMobile * 0.45))) }}px;overflow:hidden}
        #storefront-main .premium-hero .ph-main,
        #storefront-main .premium-hero .ph-main img{max-height:{{ max(100, min(420, (int) ($heroPxMobile * 0.45))) }}px;width:auto;max-width:100%;margin-inline:auto;display:block}
    }
    @endif
    .premium-hero{min-height:clamp(360px,46vh,var(--hero-desktop-h,520px))!important;border-radius:0 0 24px 24px;overflow:hidden}
    /* El hero es una seccion a sangre completa: el relleno generico de <section>
       le sumaba ~137px que no aportan nada (su interior ya trae el suyo). */
    body section.premium-hero,body .premium-hero{padding-block:0!important}
    /* Controles de accion del carrusel y de las secciones: se amplia el area de
       toque sin cambiar el tamano visible. Los puntos siguen midiendo 9px a la
       vista, pero se tocan en 44. El nombre del producto NO se toca: es texto
       de tarjeta y estirarlo a 44px romperia la rejilla. */
    .ph-arrow{min-width:44px;min-height:44px}
    .ph-dot{position:relative}
    .ph-dot::after{content:'';position:absolute;top:50%;left:50%;
        width:44px;height:44px;transform:translate(-50%,-50%)}
    .promo-cta,.home-see-all,.pf-head a,.section-head a,
    .catalog-card-action,.catalog-card .buy-add{min-height:44px;
        display:inline-flex;align-items:center;justify-content:center}
    @media(max-width:960px){
        /* Estas tres reglas fijaban 38-40px en movil y ganaban por especificidad.
           El boton de compra es el control que mas importa: va a 44px. */
        .catalog-card .buy-add,.catalog-card .button,.catalog-card a.button,
        .catalog-product-grid .catalog-card-action,
        .pf-grid .catalog-card-action,
        .catalog-card-actions .catalog-card-action,
        .special-product-grid .catalog-card-action,
        .discount-grid .catalog-card-action{min-height:44px}
    }
    /* ── MISMA ESCALA EN LAS TRES TIENDAS ────────────────────────────────────
       Medido: el hero iba de 500 a 945 px segun la tienda, el titulo del hero
       de 40 a 58 px, y el preset "dense" dejaba el relleno asimetrico (0 arriba
       y 46 abajo). Comparten plantilla, asi que deben compartir proporciones. */
    #storefront-main > section{padding-block:var(--sec-pad,68px)!important}
    body.section-spacing-dense #storefront-main > section{--sec-pad:46px}
    body.section-spacing-compact #storefront-main > section{--sec-pad:52px}
    /* El hero manda su propio alto y no lleva relleno de seccion. */
    body #storefront-main > .premium-hero,
    body .premium-hero{padding-block:0!important}
    /* Nada de max-height aqui: recortaba el subtitulo y el boton del hero.
       El alto lo gobierna min-height, que se adapta sin cortar contenido. */
    /* El interlineado apretado (1.08) hacia que la segunda linea del titulo
       invadiera el subtitulo: con tildes y mayusculas el texto necesita mas
       aire del que reservaba la caja. */
    .premium-hero .ph-title,
    .premium-hero h1{font-size:clamp(30px,4vw,54px)!important;line-height:1.18}
    .premium-hero .ph-sub{margin-top:14px}
    @media(max-width:760px){
        #storefront-main > section{--sec-pad:40px}
    }
    /* ── COHERENCIA ENTRE SECCIONES ─────────────────────────────────────────
       La pagina mezclaba seis tamanos de titulo (29 a 58px), alineaciones
       distintas sin criterio y tarjetas de 259 a 328px de ancho segun la
       seccion. Eso es lo que hace que un inicio parezca armado con piezas
       sueltas. Se unifica en una sola escala. */
    /* ── ESCALA, RADIOS Y SOMBRAS ───────────────────────────────────────────
       Escala tipografica con razon 1,33 (cuarta justa). Antes el salto del
       titular de seccion al cuerpo era de 1,54 y dejaba un hueco visible.
       Radios jerarquizados: un radio unico en todo (16px) hace que un chip y
       una seccion entera parezcan la misma pieza.
       Dos niveles de sombra: reposo y elevado. Una sola sombra plana no
       comunica que una tarjeta se puede tocar. */
    :root{
        --card-ratio:{{ $cardRatio }};
        --card-img-bg:{{ $cardImgBg }};
        --t-xl:clamp(30px,3.4vw,44px);
        --t-lg:clamp(24px,2.6vw,33px);
        --t-md:25px; --t-sm:19px; --t-xs:16px;
        --r-chip:4px; --r-btn:8px; --r-card:12px; --r-block:20px;
        --sh-1:0 1px 3px rgba(15,23,42,.07);
        --sh-2:0 8px 24px rgba(15,23,42,.13);
    }
    /* La proporcion y el fondo de la foto salen del constructor. */
    .catalog-card-media,.catalog-card-media-link{aspect-ratio:var(--card-ratio)!important}
    .catalog-card-media{background:var(--card-img-bg)!important}
    .catalog-card-media img{object-fit:contain!important;padding:6px}
    #storefront-main .catalog-card,
    #storefront-main .trust-card,
    #storefront-main .home-cat-card{border-radius:var(--r-card)!important;
        box-shadow:var(--sh-1)!important}
    #storefront-main .catalog-card:hover{box-shadow:var(--sh-2)!important;
        transform:translateY(-2px)}
    #storefront-main .button,
    #storefront-main .catalog-card-action,
    #storefront-main .buy-add{border-radius:var(--r-btn)!important}
    #storefront-main .catalog-discount,
    #storefront-main .catalog-new,
    #storefront-main .catalog-card-percent{border-radius:999px!important}
    #storefront-main .premium-hero,
    #storefront-main .promo-section .promo-card{border-radius:var(--r-block)}
    #storefront-main section h2,
    #storefront-main .pf-title,
    #storefront-main .xs-title,
    #storefront-main .special-title{
        font-size:var(--t-lg)!important;
        line-height:1.15;
        letter-spacing:-.02em;
        font-weight:800}
    /* La alineacion la decide el ajuste del constructor, no cada seccion. */
    body.section-heading-left #storefront-main section > .container > h2,
    body.section-heading-left #storefront-main .section-head,
    body.section-heading-left #storefront-main .pf-head,
    body.section-heading-left #storefront-main .xs-head,
    body.section-heading-left #storefront-main .home-cats-head,
    body.section-heading-left #storefront-main .trust-head{text-align:left!important;margin-inline:0!important}
    body.section-heading-left #storefront-main .home-cats h2,
    body.section-heading-left #storefront-main .trust-section h2,
    body.section-heading-left #storefront-main .xs-section h2,
    body.section-heading-left #storefront-main .promo-section h2{text-align:left!important}
    body.section-heading-left #storefront-main .home-cats p,
    body.section-heading-left #storefront-main .trust-section > .container > p{text-align:left!important;margin-inline:0!important}
    /* Tarjetas de producto: mismo ancho y misma proporcion de imagen en todas
       las rejillas, para que el ojo lea una sola tienda. */
    #storefront-main .catalog-product-grid,
    #storefront-main .special-product-grid,
    #storefront-main .discount-grid,
    #storefront-main .pf-grid,
    #storefront-main .xs-crows .catalog-product-grid{
        grid-template-columns:repeat(auto-fill,minmax(240px,1fr))!important;gap:20px!important}
    #storefront-main .catalog-card .catalog-card-media,
    #storefront-main .catalog-card .catalog-card-media-link{aspect-ratio:var(--card-ratio)!important;height:auto!important}
    #storefront-main .catalog-card .catalog-card-media img{width:100%;height:100%;object-fit:contain}
    @media(max-width:760px){
        #storefront-main .catalog-product-grid,
        #storefront-main .special-product-grid,
        #storefront-main .discount-grid,
        #storefront-main .pf-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:12px!important}
    }
    /* Banda de categoria del catalogo: se mantiene alineada a la izquierda
       (asi se escanea un catalogo) pero con fondo propio y una barra de acento
       que la separa del resto de la pagina. */
    .cat-banner{margin:0 0 26px;padding:26px 0 24px;border-bottom:1px solid var(--border);
        background:linear-gradient(90deg,color-mix(in srgb,var(--primary) 7%,transparent),transparent 62%)}
    .cat-banner-inner{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;flex-wrap:wrap}
    .cat-banner-copy{position:relative;padding-left:16px;min-width:0}
    .cat-banner-copy::before{content:'';position:absolute;left:0;top:4px;bottom:4px;width:4px;
        border-radius:3px;background:var(--primary)}
    .cat-banner h2{margin:0;font-family:var(--font-title);font-size:clamp(26px,3vw,38px);
        font-weight:800;letter-spacing:-.02em;color:var(--text-strong)}
    .cat-banner p{margin:7px 0 0;color:var(--muted);font-size:14.5px}
    .cat-banner .product-total{flex:0 0 auto;display:inline-flex;align-items:baseline;gap:6px;
        padding:8px 16px;border-radius:999px;background:var(--surface-soft,#f1f5f9);
        border:1px solid var(--border);color:var(--muted);font-size:13px;font-weight:600}
    .cat-banner .product-total b{color:var(--text-strong);font-size:17px;font-weight:800}
    @media(max-width:700px){.cat-banner{padding:20px 0 18px;margin-bottom:20px}
        .cat-banner-inner{align-items:flex-start;gap:14px}}
    /* Distintivo de novedad: mismo lugar que el de descuento, en verde para no
       competir con la oferta (que siempre manda). */
    .catalog-new{position:absolute;top:10px;left:10px;z-index:2;padding:4px 10px;border-radius:999px;
        background:#10b981;color:#fff;font-size:11px;font-weight:800;letter-spacing:.02em;
        box-shadow:0 4px 12px rgba(16,185,129,.32)}
    /* CHECKOUT ENFOCADO: al pagar se retiran las salidas (menu, buscador,
       carrito y franja) y queda la marca con el sello de compra segura. El
       cliente ya decidio comprar; cada enlace extra es una fuga. */
    body.ck-focus .category-nav,
    body.ck-focus .hpx-searchbox,
    body.ck-focus .hpx-search,
    body.ck-focus .hpx-cart,
    body.ck-focus .hpx-sep,
    body.ck-focus .bx-ticker,
    body.ck-focus .float-wa,
    body.ck-focus .hpx-topbar-right{display:none!important}
    body.ck-focus .header-zone{position:static!important;box-shadow:none}
    body.ck-focus .hpx-main{justify-content:space-between}
    body.ck-focus .ck-safe{display:inline-flex;align-items:center;gap:8px;color:var(--header-text);
        font-size:13px;font-weight:700;opacity:.9}
    body.ck-focus .ck-safe svg{width:17px;height:17px}
    body:not(.ck-focus) .ck-safe{display:none}
    @media(max-width:760px){body.ck-focus .ck-safe span{display:none}}
    /* El encabezado fijo tapaba el titulo de la seccion al saltar por un ancla
       o al volver de una busqueda. Se reserva su alto. */
    :root{scroll-padding-top:170px}
    section[id],[data-store-native-section]{scroll-margin-top:170px}
    @media(max-width:960px){:root{scroll-padding-top:130px}section[id],[data-store-native-section]{scroll-margin-top:130px}}
    /* Cuadriculas de producto: con menos productos que columnas quedaban huecos
       a la derecha. Las tarjetas se reparten el ancho disponible. */
    .catalog-product-grid,.special-product-grid,.discount-grid,.pf-grid{justify-content:center}
    .special-product-grid:has(> :last-child:nth-child(-n+3)),
    .discount-grid:has(> :last-child:nth-child(-n+3)){grid-template-columns:repeat(auto-fit,minmax(240px,320px))}
    .premium-hero h1,.ph-title{font-size:clamp(30px,5vw,58px)!important;font-weight:900!important;letter-spacing:-.03em;line-height:1.04}
    /* Categorías uniformes con hover elegante */
    .footer-cat-ico,.cat-card,.category-card{border-radius:var(--rd-radius)!important;transition:transform .2s ease,box-shadow .2s ease}
    .cat-card:hover,.category-card:hover{transform:translateY(-4px);box-shadow:var(--rd-shadow-hover)}
    /* Tarjetas de producto: nítidas, imagen consistente, hover elevado */
    .catalog-card,.pf-card,.product-card{border-radius:var(--rd-radius)!important;border:1px solid #e6ebf3!important;box-shadow:var(--rd-shadow);background:#fff;overflow:hidden;transition:transform .22s cubic-bezier(.2,.7,.3,1),box-shadow .22s ease,border-color .22s ease}
    .catalog-card:hover,.pf-card:hover,.product-card:hover{transform:translateY(-6px);box-shadow:var(--rd-shadow-hover);border-color:color-mix(in srgb,var(--primary) 35%,#e6ebf3)!important}
    .catalog-card-media,.pf-card-media{aspect-ratio:1/1;background:#f8fafc;overflow:hidden}
    .catalog-card-media img,.pf-card-media img{width:100%;height:100%;object-fit:cover;transition:transform .3s ease}
    .catalog-card:hover .catalog-card-media img{transform:scale(1.04)}
    .catalog-card-name,.pf-card-name{font-weight:700!important;color:var(--secondary);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.6em;padding-left:2px;margin-left:-2px}
    .catalog-card-price,.pf-price-now{font-size:19px!important;font-weight:800!important;color:var(--secondary)}
    .catalog-card-compare{color:#94a3b8;text-decoration:line-through;font-size:12px}
    /* El descuento iba en el color primario: en una tienda monocromatica no
   destacaba nada. Va en el acento, que existe justo para esto. */
    .catalog-discount,.catalog-card-percent,.badge-sale{background:var(--sale)!important;color:#fff;font-weight:800;border-radius:var(--r-btn,8px);letter-spacing:.02em}
    #storefront-main .catalog-card-price.is-sale,#storefront-main .pdp-price.is-sale{color:var(--sale)}
    .catalog-sold-out{background:#334155!important;border-radius:8px}
    .catalog-card-action,.pf-card-action{font-weight:700!important;letter-spacing:.01em;border-radius:10px!important}
    /* Catálogo: grid aireado + panel de filtros con tarjeta */
    .catalog-product-grid{gap:clamp(14px,1.6vw,22px)!important}
    .catalog-filter-panel{border-radius:var(--rd-radius)!important;box-shadow:var(--rd-shadow);border:1px solid #e6ebf3!important}
    .catalog-results-count strong{color:var(--primary)}
    .catalog-loadmore .button{border-radius:12px!important;padding:14px 34px!important;font-weight:800;letter-spacing:.02em}
    /* Footer premium: contraste y separación */
    .footer-col strong,.footer-brand{letter-spacing:.01em}
    .footer-bottom{border-top:1px solid rgba(255,255,255,.12);padding-top:22px}
    /* WhatsApp flotante — definición única y limpia (un solo círculo, sin doble contorno) */
    .official-whatsapp-float{width:58px!important;height:58px!important;display:grid!important;place-items:center!important;border:0!important;border-radius:50%!important;background:#25D366!important;box-shadow:0 8px 22px rgba(37,211,102,.45)!important;overflow:visible!important}
    .official-whatsapp-float::before,.official-whatsapp-float::after{content:none!important;display:none!important}
    .official-whatsapp-float svg{width:30px!important;height:30px!important}
    /* Focus accesible unificado */
    a:focus-visible,button:focus-visible,select:focus-visible,input:focus-visible{outline:2px solid var(--rd-ring);outline-offset:2px;border-radius:6px}
    @media(max-width:640px){section{padding-block:clamp(32px,8vw,48px)!important}.premium-hero{min-height:clamp(340px,60vh,460px)!important;border-radius:0 0 16px 16px}}
    @media(prefers-reduced-motion:reduce){*{transition:none!important}}

    /* ═══════════ Encabezado móvil: logo + buscador + hamburguesa (fila única) ═══════════ */
    .header-hamburger{display:none;width:44px;height:44px;flex:0 0 44px;place-items:center;color:var(--header-text);background:transparent;border:1px solid var(--border);border-radius:var(--radius);cursor:pointer}
    .header-hamburger svg{width:22px;height:22px}
    .header-search-toggle{display:none;width:44px;height:44px;flex:0 0 44px;place-items:center;color:var(--header-text);background:#fff;border:1px solid #cbd5e1;border-radius:var(--radius);cursor:pointer}
    .header-search-toggle svg{width:20px;height:20px}
    @media(max-width:900px){
      /* Fila única: [☰] [logo] [🔍] [🛒] */
      .store-header>.container.header-main,.header-main{display:flex!important;align-items:center!important;gap:10px!important;min-height:60px!important;padding:8px 0!important;grid-template-columns:none!important}
      .header-hamburger{display:grid}
      .header-search-toggle{display:grid}
      .brand{flex:1 1 auto;min-width:0;justify-content:center;gap:8px!important}
      .brand--logo-only{justify-content:center!important}
      /* Logo: respeta el tamaño configurado pero con tope móvil para no romper la fila */
      .brand-logo{width:auto!important;height:min(var(--logo-h),40px)!important;max-width:150px!important;object-fit:contain!important;flex:0 0 auto!important}
      .brand-mark{width:36px!important;height:36px!important;flex-basis:36px!important}
      .brand-name{max-width:150px!important;font-size:14px!important}
      .brand-tagline{display:none!important}
      /* Buscador colapsado: oculto por defecto, se despliega como barra bajo el header */
      .header-main .search{display:none!important;position:absolute;left:0;right:0;top:100%;z-index:39;width:100%!important;padding:10px 14px;background:var(--header-bg);border-bottom:1px solid var(--border);box-shadow:0 12px 24px rgba(15,23,42,.10)}
      .header-main .search.search-open{display:block!important}
      .header-zone.is-sticky{position:sticky;top:0}
      .header-zone.is-sticky .store-header{position:relative}
      .header-actions{flex:0 0 auto;gap:8px!important;padding:0!important}
      .header-actions .phone-copy{display:none!important}
      /* La barra de categorías de escritorio se reemplaza por el drawer */
      .category-nav{display:none!important}
    }
    /* Drawer del menú móvil */
    .mobile-nav-layer{position:fixed;inset:0;z-index:120}
    .mobile-nav-overlay{position:absolute;inset:0;background:rgba(15,23,42,.55)}
    .mobile-nav-panel{position:absolute;top:0;left:0;bottom:0;width:min(82vw,340px);display:flex;flex-direction:column;background:#fff;box-shadow:20px 0 50px rgba(15,23,42,.20);overflow-y:auto}
    .mobile-nav-head{display:flex;align-items:center;justify-content:space-between;min-height:60px;padding:0 16px;border-bottom:1px solid var(--border)}
    .mobile-nav-head strong{font-size:15px;color:var(--secondary)}
    .mobile-nav-links{display:flex;flex-direction:column;padding:8px 0}
    .mobile-nav-links a{padding:14px 18px;color:#172033;font-size:15px;font-weight:600;border-bottom:1px solid #f1f5f9;text-decoration:none}
    .mobile-nav-links a.is-active{color:var(--primary)}
    .mobile-nav-links a:active{background:#f8fafc}
    .mobile-nav-section{padding:14px 18px 6px;color:#94a3b8;font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase}
    /* ═══ Checkout (esqueleto ecommerce, estilo computienda) ═══ */
    .ck-modal{background:var(--surface,#f5f7fb);padding-bottom:8px}
    /* La cabecera propia del checkout ya no lleva logo (el encabezado real lo
       muestra) y deja de ser sticky para no competir con el del sitio. */
    .ck-modal .ck-brand{display:none}
    .ck-modal .ck-foot{display:none}
    .ck-modal-header{display:flex;align-items:center;gap:14px;min-height:70px;padding:0 clamp(14px,4vw,32px);background:#fff;border-bottom:1px solid var(--border)}
    /* El checkout tapaba header y footer: se conserva la identidad de la tienda */
    .ck-brand{display:inline-flex;align-items:center;flex:0 0 auto}
    .ck-brand img{max-height:40px;max-width:150px;width:auto;object-fit:contain}
    /* El boton flotante de WhatsApp tapaba el CTA de compra en movil */
    body:has(.ck-modal:not([style*="display: none"])) .official-whatsapp-float{opacity:0!important;pointer-events:none!important}
    /* El modal debe quedar por encima del contenido de la pagina */
    .ck-modal{background:var(--surface-soft,#f5f7fb)}
    @media(max-width:760px){
        .ck-modal-header h2{font-size:16px}
        .ck-modal .ck-submit{position:sticky;bottom:10px;z-index:3;box-shadow:0 8px 26px rgba(15,23,42,.22)}
    }
    .ck-foot{padding:22px clamp(14px,4vw,32px) 30px;border-top:1px solid var(--border);background:var(--surface-soft,#f8fafc)}
    .ck-foot-inner{max-width:1180px;margin:0 auto;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px 22px;color:var(--muted,#64748b);font-size:12.5px}
    .ck-foot a{color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
    .ck-foot a:hover{color:var(--primary)}
    .ck-foot-links{display:flex;flex-wrap:wrap;gap:14px}
    @media(max-width:600px){.ck-foot-inner{flex-direction:column;align-items:flex-start;gap:12px}.ck-brand img{max-height:32px;max-width:120px}}
    .ck-modal-header h2{flex:1;margin:0;color:var(--secondary);font-size:18px;font-weight:800}
    .ck-back{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;color:#475569;background:#fff;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer}
    .ck-back:hover{border-color:var(--primary);color:var(--primary)}
    .ck-grid{display:grid;grid-template-columns:1.6fr 1fr;gap:28px;max-width:1100px;margin:0 auto;padding:clamp(18px,4vw,32px) clamp(14px,4vw,32px)}
    .ck-section{margin-bottom:16px;padding:22px;background:#fff;border:1px solid var(--border);border-radius:14px}
    .ck-section h3{margin:0 0 16px;color:var(--secondary);font-size:16px;font-weight:800}
    .ck-paynote{margin:0;color:var(--muted,#64748b);font-size:13.5px;line-height:1.6}
    .ck-field{display:flex;flex-direction:column;gap:6px;margin-bottom:12px}
    .ck-field label{color:#475569;font-size:12px;font-weight:700}
    .ck-field input,.ck-field select,.ck-field textarea{width:100%;min-width:0;min-height:44px;padding:0 14px;color:#172033;background:#fff;border:1px solid #cbd5e1;border-radius:9px;font-size:14px;outline:0}
    .ck-field textarea{min-height:82px;padding:12px 14px;resize:vertical}
    .ck-field input:focus,.ck-field select:focus,.ck-field textarea:focus{border-color:var(--primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 14%,transparent)}
    .ck-field-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}.ck-field-row>*{min-width:0}.ck-field,.ck-section{min-width:0}
    .ck-pay{display:flex;gap:12px;align-items:center;margin-bottom:8px;padding:13px 14px;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;transition:border-color .15s,background .15s}
    .ck-pay:hover{border-color:#cbd5e1}
    .ck-pay.selected{border-color:var(--primary);background:color-mix(in srgb,var(--primary) 6%,transparent)}
    .ck-radio{width:18px;height:18px;flex-shrink:0;display:grid;place-items:center;border:2px solid #cbd5e1;border-radius:50%}
    .ck-pay.selected .ck-radio{border-color:var(--primary)}
    .ck-radio span{width:9px;height:9px;border-radius:50%;background:var(--primary)}
    .ck-summary{position:sticky;top:78px;padding:22px;background:#fff;border:1px solid var(--border);border-radius:14px}
    .ck-summary h4{margin:0 0 14px;color:var(--secondary);font-size:16px;font-weight:800}
    .ck-order-item{display:flex;gap:12px;align-items:center;padding:10px 0;border-bottom:1px solid var(--border)}
    .ck-order-item:last-of-type{border-bottom:0}
    .ck-order-thumb{width:48px;height:48px;flex-shrink:0;overflow:hidden;background:#f1f5f9;border-radius:8px}
    .ck-order-thumb img{width:100%;height:100%;object-fit:contain;padding:4px}
    .ck-qtybtn{width:26px;height:26px;color:#475569;background:#fff;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;cursor:pointer}
    .ck-sum-row{display:flex;justify-content:space-between;margin-top:10px;color:#475569;font-size:13px}
    .ck-sum-row.total{margin-top:14px;padding-top:14px;border-top:1px solid var(--border);color:var(--secondary);font-size:17px;font-weight:800}
    .ck-submit{width:100%;min-height:52px;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:16px;color:#fff;background:var(--primary);border:0;border-radius:10px;font-size:15px;font-weight:800;cursor:pointer}
    .ck-submit:hover{filter:brightness(.93)}.ck-submit:disabled{background:#94a3b8;cursor:not-allowed}
    .ck-submit.wa{background:#25d366}
    .ck-error{margin-bottom:14px;padding:12px 14px;color:#b91c1c;background:#fef2f2;border:1px solid #fecaca;border-radius:9px;font-size:13px}
    .ck-success{padding:26px;text-align:center;background:#fff;border:1px solid #86efac;border-radius:14px}
    .ck-success h3{margin:8px 0;color:#16a34a;font-size:20px}
    /* En movil el resumen iba DESPUES del formulario: el comprador llenaba sus
       datos sin ver que compra. Se sube al primer lugar. */
    @media(max-width:860px){.ck-grid{grid-template-columns:1fr;display:flex;flex-direction:column}
        .ck-grid>*:last-child{order:-1}
        .ck-summary{position:static;margin-bottom:4px}}
    @media(max-width:480px){.ck-field-row{grid-template-columns:1fr}.ck-grid{padding-left:12px;padding-right:12px}.ck-section{padding:16px}}
    /* ═══ Página de carrito (esqueleto ecommerce, estilo computienda) ═══ */
    .cartpage{position:relative;z-index:1;min-height:62vh;background:var(--surface,#f5f7fb)}
    .cartpage-inner{width:min(var(--header-layout-width,1360px),calc(100% - 48px));margin:0 auto;padding:clamp(20px,4vw,40px) 0}
    .cartpage h1,.cartpage h2{margin:0 0 6px;color:var(--secondary);font-size:clamp(26px,4vw,34px);font-weight:800}
    .cartpage-back{display:inline-flex;align-items:center;gap:6px;margin-bottom:18px;color:#475569;background:none;border:0;font-size:13px;font-weight:700;cursor:pointer}
    .cartpage-back:hover{color:var(--primary)}
    .cartpage-grid{display:grid;grid-template-columns:1fr 360px;gap:26px;align-items:start}
    .cartpage-lines{display:flex;flex-direction:column;gap:12px}
    .cart-line{display:grid;grid-template-columns:88px 1fr auto;gap:16px;align-items:center;padding:16px;background:#fff;border:1px solid var(--border);border-radius:14px}
    .cart-line-thumb{width:88px;height:88px;display:grid;place-items:center;overflow:hidden;background:#fafaf9;border:1px solid #f1f5f9;border-radius:10px}
    .cart-line-thumb img{width:100%;height:100%;padding:8px;object-fit:contain}
    .cart-line-cat{color:#7c899b;font-size:9px;font-weight:800;letter-spacing:.07em;text-transform:uppercase}
    .cart-line-name{margin-top:3px;color:var(--secondary);font-size:14px;font-weight:700}
    .cart-line-ctl{display:flex;align-items:center;gap:14px;margin-top:10px}
    .cart-line-price{color:var(--secondary);font-size:16px;font-weight:800;white-space:nowrap}
    .cart-line-remove{color:#94a3b8;font-size:12px;font-weight:600;background:none;border:0;cursor:pointer}
    .cart-line-remove:hover{color:#dc2626}
    .cartpage-summary{position:sticky;top:24px;padding:22px;background:#fff;border:1px solid var(--border);border-radius:14px}
    .cartpage-summary h4{margin:0 0 14px;color:var(--secondary);font-size:16px;font-weight:800}
    .cartpage-empty{padding:64px 22px;text-align:center;background:#fff;border:1px solid var(--border);border-radius:14px}
    @media(max-width:860px){.cartpage-grid{grid-template-columns:1fr}.cartpage-summary{position:static}.cartpage-inner{width:calc(100% - 28px)}}
    /* ═══ Página de producto (esqueleto ecommerce, estilo computienda) ═══ */
    .pdp-wrap{display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:24px}
    .pdp-gallery{position:sticky;top:96px;align-self:start}
    .pdp-main-img{position:relative;aspect-ratio:1;display:grid;place-items:center;overflow:hidden;background:#fff;border:1px solid var(--border);border-radius:16px;cursor:zoom-in}
    .pdp-zoom-hint{position:absolute;bottom:12px;right:12px;width:38px;height:38px;display:grid;place-items:center;color:#fff;background:rgba(15,23,42,.55);border-radius:50%;opacity:.85;pointer-events:none;transition:opacity .2s}
    .pdp-main-img:hover .pdp-zoom-hint{opacity:1}
    .cpt-lb{position:fixed;inset:0;z-index:100000;display:none;background:rgba(2,6,23,.93);touch-action:none}
    body.cpt-lb-open .official-whatsapp-float,body.cpt-lb-open #bixo-runtime-whatsapp{display:none!important}
    @media(max-width:640px){
    .cpt-lb-zooms{left:50%;right:auto;transform:translateX(-50%);bottom:calc(14px + env(safe-area-inset-bottom,0px))}
    .cpt-lb-counter{bottom:auto;top:22px;left:50%;transform:translateX(-50%)}
    .cpt-lb-prev{left:6px}.cpt-lb-next{right:6px}
    .cpt-lb-stage img{max-width:100vw;max-height:80vh}
    }
    .cpt-lb.open{display:block}
    .cpt-lb-stage{width:100%;height:100%;display:flex;align-items:center;justify-content:center;overflow:hidden}
    .cpt-lb-stage img{max-width:90vw;max-height:88vh;object-fit:contain;border-radius:6px;transform-origin:center center;transition:transform .15s;user-select:none;cursor:grab}
    .cpt-lb-btn{position:absolute;z-index:2;width:46px;height:46px;display:grid;place-items:center;color:#fff;background:rgba(15,23,42,.62);border:1px solid rgba(255,255,255,.35);border-radius:50%;font-size:22px;cursor:pointer;backdrop-filter:blur(4px);box-shadow:0 4px 14px rgba(0,0,0,.35)}
    .cpt-lb-btn:hover{background:rgba(15,23,42,.85);border-color:rgba(255,255,255,.6)}
    .cpt-lb-close{top:14px;right:14px}
    .cpt-lb-prev{left:12px;top:50%;transform:translateY(-50%)}
    .cpt-lb-next{right:12px;top:50%;transform:translateY(-50%)}
    .cpt-lb-zooms{position:absolute;bottom:16px;right:16px;z-index:2;display:flex;gap:8px}
    .cpt-lb-zooms .cpt-lb-btn{position:static;width:40px;height:40px;font-size:19px}
    .cpt-lb-counter{position:absolute;bottom:18px;left:50%;transform:translateX(-50%);z-index:2;color:#fff;font-size:13px;font-weight:700;background:rgba(15,23,42,.62);padding:5px 12px;border-radius:999px}
    .pdp-main-img img{width:100%;height:100%;padding:28px;object-fit:contain;transition:transform .3s}
    .pdp-main-img:hover img{transform:scale(1.04)}
    .pdp-thumbs{display:flex;gap:10px;margin-top:12px;flex-wrap:wrap}
    .pdp-thumb{width:66px;height:66px;display:grid;place-items:center;overflow:hidden;background:#fff;border:1px solid var(--border);border-radius:10px;cursor:pointer}
    .pdp-thumb.active{border-color:var(--primary)}
    .pdp-thumb img{width:100%;height:100%;padding:6px;object-fit:contain}
    .pdp-cat{color:var(--primary);font-size:11px;font-weight:800;letter-spacing:.07em;text-transform:uppercase}
    .pdp-title{margin:8px 0 0;color:var(--secondary);font-size:clamp(22px,3vw,30px);font-weight:800;line-height:1.2}
    .pdp-price-row{display:flex;align-items:baseline;gap:12px;flex-wrap:wrap;margin:14px 0 4px}
    .pdp-wholesale{margin:6px 0 0;color:#475569;font-size:13px}
    /* Bloque de compra dual (minorista / mayorista) al estilo de la plantilla
       direct, adaptado al formato ecommerce. El bloque mayorista SOLO se pinta
       cuando el producto tiene precio mayorista cargado en el catalogo. */
    .buy-block{display:block;margin-top:8px;border:1px solid var(--border,#e2e8f0);border-radius:10px;overflow:hidden;background:#fff}
    .buy-block+.buy-block{margin-top:6px}
    .buy-head{display:flex;align-items:baseline;justify-content:space-between;gap:8px;padding:6px 10px;background:#f8fafc;border-bottom:1px solid #eef2f7}
    .buy-tag{color:#94a3b8;font-size:9px;font-weight:800;letter-spacing:.1em;text-transform:uppercase}
    .buy-price{color:var(--secondary);font-size:15px;font-weight:800;line-height:1}
    .buy-min{display:block;padding:0 10px;margin-top:4px;color:#a16207;font-size:10px}
    .buy-row{display:flex;align-items:center;gap:7px;padding:7px 8px}
    .buy-qty{display:flex;align-items:center;flex:0 0 auto;border:1px solid var(--border,#e2e8f0);border-radius:8px;overflow:hidden;background:#fff}
    .buy-qty button{width:24px;height:28px;display:grid;place-items:center;color:#64748b;background:none;border:0;font-size:15px;font-weight:800;cursor:pointer;line-height:1}
    .buy-qty button:hover{background:#f1f5f9}
    .buy-qty input{width:34px;height:28px;text-align:center;border:0;border-inline:1px solid var(--border,#e2e8f0);font-size:12px;font-weight:800;color:#172033;background:#fff;-moz-appearance:textfield}
    .buy-qty input::-webkit-outer-spin-button,.buy-qty input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
    .buy-add{flex:1 1 auto;min-height:30px;padding:0 10px;color:#fff;background:var(--primary);border:0;border-radius:8px;font-size:11px;font-weight:800;cursor:pointer;white-space:nowrap}
    .buy-add:hover{filter:brightness(.93)}
    /* MODALIDAD B (precio automatico por cantidad). Misma logica de precios que
       la modalidad separada; solo cambia la presentacion. */
    .buy-auto{margin-top:8px}
    .buy-auto-prices{display:flex;flex-direction:column;gap:2px;margin-bottom:8px}
    .buy-auto-price{color:var(--secondary);font-size:19px;font-weight:800;line-height:1.1}
    .buy-auto-hint{color:#b45309;font-size:11px;font-weight:700}
    .buy-auto-ok{color:#15803d;font-size:11px;font-weight:800}
    .buy-auto-sub{margin:6px 0 8px;color:#475569;font-size:12px}
    .buy-auto-sub b{color:var(--secondary);font-size:13.5px}
    .buy-auto .buy-row{padding:0;gap:8px}
    .buy-auto .buy-qty.is-compact{transform:scale(.92);transform-origin:left center}
    .buy-add--full{display:block;width:100%;margin-top:8px;min-height:38px}
    .buy-add--compact{display:inline-block;margin-top:8px;padding:0 18px;min-height:34px}
    @media(max-width:760px){.buy-auto .buy-row{flex-wrap:wrap}.buy-auto .buy-qty{flex:1 1 auto}.buy-auto .buy-add{flex:1 1 100%}}
    /* Variante minorista: acento de marca */
    .buy-block--retail .buy-tag{color:#64748b}
    .buy-block--retail .buy-price{color:var(--secondary)}
    .buy-block--retail .buy-add{background:var(--primary)}
    /* Con bloque dual, la botonera inferior sobra: se oculta para no repetir CTA */
    .catalog-card:has(.buy-block--wholesale) .catalog-card-actions .catalog-card-action{display:none}
    /* Modalidad B ya trae su boton: la botonera inferior solo conserva WhatsApp */
    .catalog-card:has(.buy-auto) .catalog-card-actions .catalog-card-action{display:none}
    /* Variante mayorista: mismo formato, acento ambar para diferenciarla */
    .buy-block--wholesale{border-color:#f0c471;background:#fffdf7}
    .buy-block--wholesale .buy-head{background:#fffbeb;border-bottom-color:#f6e3b8}
    .buy-block--wholesale .buy-tag{color:#b45309}
    .buy-block--wholesale .buy-price{color:#b45309}
    .buy-block--wholesale .buy-add{background:#f59e0b}
    .buy-block--wholesale .buy-add:hover{background:#d97706}
    /* Movil: el par selector+boton no cabe en una card de ~220px. El bloque pasa
       a dos filas, el selector ocupa el ancho util y el boton queda a lo ancho. */
    @media(max-width:760px){
        .buy-row{flex-wrap:wrap;gap:6px;padding:6px}
        .buy-qty{flex:1 1 auto}
        .buy-qty input{width:100%}
        .buy-add{flex:1 1 100%;min-height:32px;padding:0 8px;font-size:10.5px}
        .buy-head{padding:5px 8px}
        .buy-price{font-size:14px}
        .buy-tag{font-size:8.5px}
        .buy-min{padding:0 8px;font-size:9.5px}
    }
    /* Todas las tarjetas de una fila miden lo mismo aunque una traiga bloque
       mayorista y la otra no: la botonera queda alineada abajo. */
    #storefront-main .catalog-card{height:100%!important;display:flex;flex-direction:column}
    /* Y todas las filas del mismo grid miden igual: sin escalones entre fila 1 y 2. */
    #storefront-main .pf-grid,#storefront-main .home-prod-grid,#storefront-main .catalog-grid,
    #storefront-main .catalog-product-grid,#storefront-main .special-product-grid{grid-auto-rows:1fr}
    #storefront-main .catalog-card > .catalog-card-body{flex:1 1 auto}
    #storefront-main .catalog-card > .catalog-card-actions{margin-top:auto}
    /* Zonas tactiles de 44 px en los controles reales (2.5.5). Los puntos del
       carrusel conservan su tamano visual y amplian el area con un pseudo. */
    #storefront-main .ph-dot{position:relative}
    #storefront-main .ph-dot::after{content:'';position:absolute;left:50%;top:50%;width:44px;height:44px;transform:translate(-50%,-50%)}
    #storefront-main .catalog-quickview{min-height:44px}
    .hpx-uni-chip,.profile-chip,.hpx-corp-inner a{min-height:44px;display:inline-flex;align-items:center}
    .catalog-h1{margin:6px 0 0;color:var(--secondary);font-size:var(--t-lg);font-weight:800;line-height:1.15}
    /* La flecha del carrusel se montaba encima del titulo: el bloque de texto
       deja sitio a las flechas en vez de compartirlo. */
    #storefront-main .premium-hero:has(.ph-arrow) .premium-hero-copy.is-left{padding-left:56px!important}
    @media(max-width:760px){#storefront-main .premium-hero:has(.ph-arrow) .premium-hero-copy.is-left{padding-left:0!important}
        #storefront-main .premium-hero .ph-arrow{top:auto;bottom:12px}}
    /* El navegador pinta los textarea en monospace: rompe la tipografia de la tienda. */
    textarea,input,select,button{font-family:inherit}
    /* Mayorista plegable: la cabecera es el disparador y el detalle se despliega. */
    .buy-block--wholesale.is-foldable .buy-head{cursor:pointer;min-height:44px;align-items:center}
    .buy-block--wholesale.is-foldable .buy-head:hover{background:#fef6e0}
    .buy-fold{width:15px;height:15px;flex:0 0 auto;color:#b45309;transition:transform .2s ease}
    [x-cloak]{display:none!important}
    /* Compatibilidad con el markup antiguo (.wh-buy) mientras convive */
    .wh-buy{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:8px;padding:8px 10px;background:#fffbeb;border:1.5px solid #f59e0b;border-radius:10px}
    .wh-buy-info{min-width:0}
    .wh-buy-info b{display:block;color:#b45309;font-size:14px}
    .wh-buy-info small{color:#a16207;font-size:10.5px}
    .wh-buy-btn{flex:0 0 auto;padding:8px 14px;color:#fff;background:#f59e0b;border:0;border-radius:8px;font-size:11.5px;font-weight:800;cursor:pointer}
    .wh-buy-btn:hover{background:#d97706}
    .wh-buy--card{margin-top:6px;padding:6px 8px}.wh-buy--card .wh-buy-info b{font-size:12.5px}.wh-buy--card .wh-buy-btn{padding:6px 10px;font-size:10.5px}
    .qv-wholesale{margin:4px 0 0;color:#64748b;font-size:12px}
    .pdp-price{color:var(--secondary);font-size:30px;font-weight:800}
    .pdp-compare{color:#94a3b8;font-size:16px;text-decoration:line-through}
    .pdp-save{margin:2px 0 14px;color:#16a34a;font-size:13px;font-weight:700}
    .pdp-stock-out{padding:10px 14px;margin-bottom:14px;color:#b91c1c;background:#fef2f2;border:1px solid #fecaca;border-radius:9px;font-size:13px;font-weight:700}
    .pdp-stock-low{padding:8px 14px;margin-bottom:12px;color:#92400e;background:#fffbeb;border:1px solid #fde68a;border-radius:9px;font-size:12px;font-weight:700}
    .pdp-desc{margin:16px 0;color:#475569;font-size:14px;line-height:1.7}
    .pdp-actions{display:flex;flex-direction:column;gap:10px;margin-top:16px}
    .pdp-qty{display:inline-flex;align-items:center;border:1px solid #cbd5e1;border-radius:9px;overflow:hidden}
    .pdp-qty button{width:44px;height:46px;color:#475569;background:#fff;border:0;font-size:18px;cursor:pointer}
    .pdp-qty span{min-width:40px;text-align:center;font-size:15px;font-weight:700}
    .pdp-add{flex:1;min-height:52px;display:flex;align-items:center;justify-content:center;gap:8px;color:#fff;background:var(--primary);border:0;border-radius:10px;font-size:15px;font-weight:800;cursor:pointer}
    .pdp-add:hover{filter:brightness(.93)}.pdp-add:disabled{background:#e2e8f0;color:#94a3b8;cursor:not-allowed}
    /* Botón "Consultar" (WhatsApp) configurable por diseño */
    .pdp-consult{background:#25D366!important;color:#fff!important;text-decoration:none}
    .pdp-consult:hover{filter:brightness(.95)}
    .catalog-card-actions{display:flex;gap:8px;margin:0 10px 10px}
    .catalog-card-actions .catalog-card-action{flex:1;margin:0}
    .catalog-card-inquiry{flex:1;min-height:44px;display:inline-flex;align-items:center;justify-content:center;gap:6px;color:#128C7E;background:#fff;border:1.5px solid #25D366;border-radius:6px;font-size:11px;font-weight:800;text-decoration:none;transition:background .15s ease}
    .catalog-card-inquiry:hover{background:#f0fdf4}
    .catalog-card-inquiry svg{width:15px;height:15px;flex:none}
    body.catalog-view-compact .catalog-card-actions{margin:0 16px 0 0;align-self:center}
    @media(max-width:640px){body.catalog-view-compact .catalog-card-actions{grid-column:1/-1;margin:0 10px 10px}}
    .pdp-share{display:flex;align-items:center;gap:8px;margin-top:16px}
    .pdp-share span{color:#94a3b8;font-size:12px}
    .pdp-share a,.pdp-share button{width:34px;height:34px;display:grid;place-items:center;border-radius:50%;border:0;cursor:pointer}
    .pdp-related{margin-top:56px}
    .pdp-related h2{margin:0 0 18px;color:var(--secondary);font-size:20px;font-weight:800}
    @media(max-width:860px){.pdp-wrap{grid-template-columns:1fr;gap:22px}.pdp-gallery{position:static}.pdp-add-row{display:flex;gap:10px}}
    /* Selector de perfiles de catálogo */
    .profile-switch{display:flex;flex-wrap:wrap;gap:6px;margin-right:12px}
    .profile-switch{display:flex;align-items:center;gap:6px;flex:0 0 auto}
    .profile-switch-label{margin-right:3px;color:#94a3b8;font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;white-space:nowrap}
    /* ACENTO DE PERFIL: el color del perfil (Nino/Nina) distingue la seccion
       activa sin repintar la tienda. Solo se aplica si hay --profile-tint. */
    body[style*="--profile-tint"] .profile-chip.is-active,
    body[style*="--profile-tint"] .hp-universe.is-active,
    body[style*="--profile-tint"] .hpx-universe.is-active{background:var(--profile-tint)!important;border-color:var(--profile-tint)!important;color:#fff!important}
    body[style*="--profile-tint"] .catalog-card-category{color:color-mix(in srgb,var(--profile-tint) 78%,#334155)}
    body[style*="--profile-tint"] .section-heading h2:after,
    body[style*="--profile-tint"] .home-section-head h2:after{background:var(--profile-tint)}
    .profile-chip{display:inline-flex;align-items:center;gap:7px;min-height:34px;padding:7px 14px;color:#475569;background:#f1f5f9;border:1px solid transparent;border-radius:999px;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap;transition:.15s}
    .profile-chip .profile-dot{width:9px;height:9px;flex:0 0 auto;border-radius:50%;background:var(--chip-color,var(--primary))}
    .profile-chip:hover{color:var(--chip-color,var(--primary));border-color:var(--chip-color,var(--primary))}
    .profile-chip.is-active{color:#fff;background:var(--chip-color,var(--primary));border-color:var(--chip-color,var(--primary))}
    .profile-chip.is-active .profile-dot{background:#fff}
    @media(max-width:1080px){.profile-switch-label{display:none}}
    .mobile-nav-profiles{display:flex;flex-wrap:wrap;gap:8px;padding:10px 18px 4px}
    .mobile-nav-profiles a{padding:8px 13px;color:#475569;background:#f1f5f9;border-radius:999px;font-size:13px;font-weight:700;text-decoration:none}
    .mobile-nav-profiles a.is-active{color:#fff;background:var(--primary)}
    @media(max-width:900px){.profile-switch{display:none}}
    </style>
</head>
@php
    // Clases del preset de encabezado (tamaño del buscador y ajustes por modelo)
    $hpBodyKey = \App\Support\HeaderPresets::active($settings);
    $hpBodyClass = $hpBodyKey
        ? 'hp-preset hp-' . str_replace('_', '-', $hpBodyKey) . ' hp-search-' . (\App\Support\HeaderPresets::get($hpBodyKey)['search'] ?? 'normal')
        : '';
@endphp
<body x-data="professionalStore()" x-init="init()" :class="{'ck-focus': checkoutOpen}"
      @if(!empty($profileTint)) style="--profile-tint:{{ $profileTint }}" @endif
      class="section-preset-{{ $sectionPreset }} section-spacing-{{ $sectionSpacing }} section-heading-{{ $sectionHeadingAlign }} section-bg-{{ $sectionBackgroundMode }} {{ $sectionShowDividers ? 'section-dividers' : 'section-no-dividers' }} {{ $sectionCardShadow ? 'section-card-shadows' : 'section-no-shadows' }} featured-view-{{ $featuredProductsView }} catalog-view-{{ $catalogProductsView }} {{ $themeBodyClass }} cards-{{ $productCardStyle }} {{ $hpBodyClass }}">
    {{-- CABECERA: variante estructural (F1). classic = markup original extraído --}}
    {{-- Variables de navegación compartidas (header + menú móvil) --}}
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
        // Con un perfil activo (Niño/Niña…), las categorías del menú/filtros se acotan a SU
        // colección; si la colección es un solo árbol, sus subcategorías suben al primer
        // nivel para no repetir el nombre que ya está en los chips de Colecciones.
        $navCategories = $categories;
        $profCatIds = !empty($activeProfile)
            ? $activeProfile->categories()->pluck('categories.id')->map(fn ($v) => (int) $v)->all()
            : [];
        if (count($profCatIds)) {
            $scopedRoots = $categories->filter(fn ($c) => in_array((int) $c->id, $profCatIds, true))->values();
            if ($scopedRoots->count() === 1 && $scopedRoots->first()->children->count()) {
                $navCategories = $scopedRoots->first()->children->values();
            } elseif ($scopedRoots->count()) {
                $navCategories = $scopedRoots;
            }
        }
        // Las categorias sin ningun producto (ni en sus subcategorias) se ocultan
        // del menu: llevaban al cliente a una pagina vacia. Si por algun motivo
        // ninguna tuviera productos, se deja el listado original.
        $catConProductos = static function ($c) use (&$catConProductos) {
            if (($c->products_count ?? $c->products?->count() ?? 0) > 0) {
                return true;
            }
            foreach ($c->children ?? [] as $hijo) {
                if ($catConProductos($hijo)) {
                    return true;
                }
            }
            return false;
        };
        $navConStock = $navCategories->filter($catConProductos)->values();
        if ($navConStock->count()) {
            $navCategories = $navConStock;
        }
    @endphp
    @php
        // ── ENCABEZADO Y NAVEGACIÓN: preset activo (composición de módulos) ──
        // Sin preset elegido → comportamiento actual intacto (header_layout).
        $hpKey = \App\Support\HeaderPresets::active($settings);
        $hpPreset = \App\Support\HeaderPresets::get($hpKey);
        $hp = static fn (string $k, $d = null) => $settings["hp_{$k}"] ?? $d;
        $hpHeaderView = null;
        if ($hpKey) {
            // Con composición shell declarada, el shell componible arma la estructura;
            // si no, cae al layout base del preset (partials existentes).
            $hpCandidate = !empty($hpPreset['shell'])
                ? 'storefront.partials.headers.preset-shell'
                : 'storefront.partials.headers.' . (\App\Support\HeaderPresets::baseLayout($hpKey) ?? 'classic');
            $hpHeaderView = view()->exists($hpCandidate) ? $hpCandidate : null;
        }
    @endphp
    @include($hpHeaderView ?? \App\Support\StorefrontLayoutPacks::view($settings, 'headers') ?? 'storefront.partials.headers.classic')
    @if($hpKey)
        @includeIf('storefront.partials.nav.preset-modules')
    @endif

    <main id="storefront-main" x-show="!cartPageOpen && !checkoutOpen">
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
                @php $slWebp = $webpUrl($sl['img']); $slmWebp = $webpUrl($sl['mobileImg'] ?? null); @endphp
                <picture class="ph-picture">
                    @if($sl['mobileImg'])
                        @if($slmWebp)<source media="(max-width: 767px)" srcset="{{ $slmWebp }}" type="image/webp">@endif
                        <source media="(max-width: 767px)" srcset="{{ $sl['mobileImg'] }}">
                    @endif
                    @if($slWebp)<source srcset="{{ $slWebp }}" type="image/webp">@endif
                    <img class="pos-{{ $sl['position'] }}" src="{{ $sl['img'] }}" alt="{{ $sl['title'] ?: $storeName }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" fetchpriority="{{ $i === 0 ? 'high' : 'auto' }}" decoding="{{ $i === 0 ? 'sync' : 'async' }}">
                </picture>
                @if($sl['showContent'])<span class="ph-shade" style="opacity:{{ $sl['overlay'] / 100 }}" aria-hidden="true"></span>@endif
            </div>
            @endforeach
            <div class="container premium-hero-inner">
                @foreach($heroSlides as $i => $sl)
                <div class="premium-hero-copy is-{{ $sl['align'] }} {{ $i === 0 ? 'is-on' : '' }}" :class="{'is-on': s==={{ $i }}}" :aria-hidden="s!=={{ $i }}">
                    @if($sl['showContent'])
                    <div class="ph-slide-copy">
                        @if($sl['badge'])<span class="ph-eyebrow">{{ $sl['badge'] }}</span>@endif
                        {{-- Solo la primera diapositiva declara el h1: las demas son titulos visuales.
                             Con un h1 por diapositiva la pagina anunciaba 6 titulos principales. --}}
                        @if($sl['title'] ?: $heroTitle)
                            @if($i === 0)<h1 class="ph-title">{{ $sl['title'] ?: $heroTitle }}</h1>
                            @else<p class="ph-title" role="heading" aria-level="2">{{ $sl['title'] ?: $heroTitle }}</p>@endif
                        @endif
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
                    @if(trim($heroBadge) !== '')<span class="ph-eyebrow">{{ $heroBadge }}</span>@endif
                    <h1 class="ph-title">{{ $heroTitle }}</h1>
                    <p class="ph-sub">{{ $heroSubtitle }}</p>
                    <div class="hero-actions">
                        @if($heroCtaVisible)<a class="button button-primary" href="{{ trim($settings['hero_slide_1_cta1_url'] ?? '') ?: '#catalogo' }}">{{ $heroCta }}<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14M14 6l6 6-6 6"></path></svg></a>@endif
                        {{-- CTA secundario: enlace propio si está configurado; si no, WhatsApp --}}
                        @php $heroCta2Url = trim($settings['hero_slide_1_cta2_url'] ?? ''); @endphp
                        @if($contactCtaVisible && ($heroCta2Url !== '' || $whatsapp))
                        <a class="button button-ghost" href="{{ $heroCta2Url ?: 'https://wa.me/'.$whatsapp }}" @if($heroCta2Url === '') target="_blank" rel="noopener" @endif>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.6 13.4 12 22l-9-9V4h9l8.6 8.6a1.4 1.4 0 0 1 0 2Z"/><circle cx="7.5" cy="7.5" r="1.4"/></svg>{{ $contactCta }}
                        </a>
                        @endif
                    </div>
                </div>
                @endif
                @if(count($heroSlides) > 1)
                {{-- Controles del slider (§15/§26): solo con más de una imagen --}}
                <button type="button" class="ph-arrow ph-arrow-prev" aria-label="Anterior" @click="heroPrev && heroPrev()"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 6-6 6 6 6"/></svg></button>
                <button type="button" class="ph-arrow ph-arrow-next" aria-label="Siguiente" @click="heroNext && heroNext()"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"/></svg></button>
                @endif
                <div class="premium-hero-visual" aria-hidden="true">
                    @if($heroMain)
                    <div class="ph-stage">
                        <div class="ph-main"><img src="{{ \App\Support\ImageVariants::webp($heroMain) }}" alt="{{ $heroTitle }}" loading="eager" fetchpriority="high"></div>
                        @if($heroHead)<div class="ph-float ph-float-a"><img src="{{ \App\Support\ImageVariants::webp($heroHead->main_image_url) }}" alt="{{ $heroHead->name }}" loading="lazy" decoding="async"></div>@endif
                        @if($heroKb)<div class="ph-float ph-float-b"><img src="{{ \App\Support\ImageVariants::webp($heroKb->main_image_url) }}" alt="{{ $heroKb->name }}" loading="lazy" decoding="async"></div>@endif
                        @if($heroMouse)<div class="ph-float ph-float-c"><img src="{{ \App\Support\ImageVariants::webp($heroMouse->main_image_url) }}" alt="{{ $heroMouse->name }}" loading="lazy" decoding="async"></div>@endif
                        <div class="ph-reflection"></div>
                    </div>
                    @endif
                </div>
            </div>
        </section>
        @endif

        @if($isHomeSectionVisible('promotions') && $promoEnabled && count($promoItems))
        <section class="promo-section" aria-label="Promociones" data-store-native-section="promotions" style="order:{{ $sectionOrder('promotions') }}"
                 x-data="{ active:0, total:{{ count($promoItems) }}, timer:null,
                           start(){ if({{ $promoAutoplay ? 'true' : 'false' }} && this.total>1){ this.timer=setInterval(()=>this.active=(this.active+1)%this.total,{{ $promoDuration }}); } },
                           stop(){ if(this.timer){clearInterval(this.timer);this.timer=null;} } }"
                 x-init="start()" @mouseenter="stop()" @mouseleave="start()">
            <div class="container">
                @include('public.templates.partials.computienda-intro', ['introKey' => 'promotions'])
                @if(!$introActive('promotions') && ($promoSectionTitle !== '' || $promoSectionSubtitle !== ''))
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
                /* Hogar y muebles — el mapa original solo cubría informática. */
                'sofa' => '<path d="M4 12V9a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3"></path><path d="M2 13a2 2 0 0 1 2-2 2 2 0 0 1 2 2v4H2z"></path><path d="M22 13a2 2 0 0 0-2-2 2 2 0 0 0-2 2v4h4z"></path><path d="M6 17h12M5 20v-3M19 20v-3"></path>',
                'comedor' => '<path d="M3 8h18M4 8l1 12M20 8l-1 12"></path><path d="M8 12v8M16 12v8"></path>',
                'cama' => '<path d="M3 18v-8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8"></path><path d="M3 14h18M7 8V6h4v2"></path><path d="M3 18v2M21 18v2"></path>',
                'colchon' => '<rect x="2" y="7" width="20" height="10" rx="3"></rect><path d="M6 10v4M10 10v4M14 10v4M18 10v4"></path>',
                'refrigeradora' => '<rect x="6" y="2" width="12" height="20" rx="2"></rect><path d="M6 10h12M9 6v2M9 13v3"></path>',
                'lavadora' => '<rect x="4" y="3" width="16" height="18" rx="2"></rect><circle cx="12" cy="14" r="4"></circle><path d="M7 6h.01M10 6h.01"></path>',
                'cocina-electro' => '<rect x="3" y="7" width="18" height="14" rx="2"></rect><circle cx="8" cy="13" r="2.2"></circle><circle cx="16" cy="13" r="2.2"></circle><path d="M3 11h18M6 3v4M18 3v4"></path>',
                'armario' => '<rect x="4" y="2" width="16" height="20" rx="1.5"></rect><path d="M12 2v20M9 11h.5M15 11h-.5"></path>',
                'silla' => '<path d="M6 3h12v9H6z"></path><path d="M5 12h14M7 12v9M17 12v9M7 17h10"></path>',
                'lampara' => '<path d="M8 3h8l3 7H5z"></path><path d="M12 10v8M9 21h6"></path>',
                'decoracion' => '<path d="M12 3l2.6 5.6L20 9.4l-4 4.1.9 5.9-4.9-2.8-4.9 2.8.9-5.9-4-4.1 5.4-.8z"></path>',
                'bano' => '<path d="M4 12h16v3a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4z"></path><path d="M7 12V6a2 2 0 0 1 4 0"></path><path d="M7 19l-1 2M17 19l1 2"></path>',
                'organizacion' => '<rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M3 9h18M3 15h18M9 3v18"></path>',
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
                /* Hogar y muebles */
                if (str_contains($name, 'sala') || str_contains($name, 'sofá') || str_contains($name, 'sofa') || str_contains($name, 'mueble')) return 'sofa';
                if (str_contains($name, 'comedor') || str_contains($name, 'mesa')) return 'comedor';
                if (str_contains($name, 'colch')) return 'colchon';
                if (str_contains($name, 'dormitorio') || str_contains($name, 'cama') || str_contains($name, 'velador')) return 'cama';
                if (str_contains($name, 'refriger') || str_contains($name, 'congel') || str_contains($name, 'frigo')) return 'refrigeradora';
                if (str_contains($name, 'lavad') || str_contains($name, 'secad')) return 'lavadora';
                if (str_contains($name, 'cocina') || str_contains($name, 'electrohogar') || str_contains($name, 'electrodom')) return 'cocina-electro';
                if (str_contains($name, 'ropero') || str_contains($name, 'armario') || str_contains($name, 'closet') || str_contains($name, 'melamine')) return 'armario';
                if (str_contains($name, 'silla') || str_contains($name, 'oficina')) return 'silla';
                if (str_contains($name, 'lámpara') || str_contains($name, 'lampara') || str_contains($name, 'ilumin')) return 'lampara';
                if (str_contains($name, 'decor') || str_contains($name, 'adorno')) return 'decoracion';
                if (str_contains($name, 'baño') || str_contains($name, 'bano')) return 'bano';
                if (str_contains($name, 'organiz') || str_contains($name, 'almacenamiento')) return 'organizacion';
                return 'default';
            };

            $featuredCategoryItems = $categories->map(function ($cat) use ($featuredCatsItems) {
                $custom = $featuredCatsItems[(string) $cat->id] ?? [];
                $count = $cat->products->count() + $cat->children->sum(fn ($child) => $child->products->count());
                // El clásico guarda image:'' e icon:'default' como "sin elegir": deben caer a la foto real / icono automático.
                $image = filled($custom['image'] ?? null)
                    ? $custom['image']
                    : (data_get($cat, 'image_url') ?? data_get($cat, 'image') ?? data_get($cat, 'cover_url'));
                // Sin foto propia: usa la del primer producto con imagen de la
                // categoría (o de sus subcategorías) — nunca un bloque gris vacío.
                if (blank($image)) {
                    $image = $cat->products->firstWhere('main_image_url')?->main_image_url
                        ?? $cat->children->flatMap->products->firstWhere('main_image_url')?->main_image_url;
                }
                return [
                    'model' => $cat,
                    'count' => $count,
                    'image' => $image,
                    'visual' => $custom['visual'] ?? 'inherit',
                    'icon' => (filled($custom['icon'] ?? null) && ($custom['icon'] ?? '') !== 'default') ? $custom['icon'] : null,
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
        <section class="home-cats style-{{ $featuredCatsStyle }} shape-{{ $featuredCatsShape }} {{ $featuredCatsMobileCarousel ? 'mobile-carousel' : '' }}" data-store-native-section="categories" style="order:{{ $sectionOrder('categories') }};--cats-cols:{{ max(1, min($featuredCatsColumns, $featuredCategoryItems->count())) }}">
            <div class="container">
                @include('public.templates.partials.computienda-intro', ['introKey' => 'categories'])
                @if($featuredCatsStyle === 'showcase' && !$introActive('categories'))
                {{-- Banda de título destacada --}}
                <div class="home-cats-band" style="--band-bg:{{ $featuredCatsBandBg }};--band-ink:{{ $featuredCatsBandText }}">
                    <span class="home-cats-band-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3.5 4.5h2l2 10.5h10l2-8H7"/><circle cx="9.5" cy="19" r="1.4"/><circle cx="16.5" cy="19" r="1.4"/></svg></span>
                    <h2>{{ $featuredCatsTitle }}</h2>
                </div>
                @endif

                @if(in_array($featuredCatsStyle, ['showcase','circles','carousel'], true))
                @if($featuredCatsStyle !== 'showcase' && !$introActive('categories'))
                <div class="home-section-head is-centered">
                    <div class="home-section-copy"><h2>{{ $featuredCatsTitle }}</h2>@if($featuredCatsSubtitle)<p>{{ $featuredCatsSubtitle }}</p>@endif</div>
                </div>
                @endif
                <div class="hc-scroller-wrap {{ $featuredCatsStyle === 'carousel' ? 'is-carousel' : '' }}">
                    @if($featuredCatsStyle === 'carousel')
                    <button type="button" class="hc-arrow hc-arrow-prev" aria-label="Anterior" onclick="this.parentElement.querySelector('.home-cats-showcase').scrollBy({left:-280,behavior:'smooth'})">&lsaquo;</button>
                    @endif
                    <div class="home-cats-showcase {{ $featuredCatsStyle === 'carousel' ? 'is-scroll' : '' }}">
                        @foreach($featuredCategoryItems as $item)
                        @php
                            $cat = $item['model'];
                            $catImage = $assetUrl($item['image']);
                            $scIconKey = $item['icon'] && isset($categoryIconPaths[$item['icon']]) ? $item['icon'] : $autoCategoryIcon($cat->name);
                        @endphp
                        <a href="{{ $shopUrl }}?category={{ $cat->id }}" class="hc-showcase-item">
                            <span class="hc-circle">
                                @if($catImage)
                                    <img src="{{ $catImage }}" alt="{{ $cat->name }}" loading="lazy">
                                @elseif(filled($settings['caticon_'.$cat->id] ?? null))
                                    {!! $settings['caticon_'.$cat->id] !!}
                                @else
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">{!! $categoryIconPaths[$scIconKey] !!}</svg>
                                @endif
                            </span>
                            <strong>{{ $cat->name }}</strong>
                            @if($featuredCatsShowCount)<small>{{ $item['count'] }} {{ $item['count'] === 1 ? 'producto' : 'productos' }}</small>@endif
                        </a>
                        @endforeach
                    </div>
                    @if($featuredCatsStyle === 'carousel')
                    <button type="button" class="hc-arrow hc-arrow-next" aria-label="Siguiente" onclick="this.parentElement.querySelector('.home-cats-showcase').scrollBy({left:280,behavior:'smooth'})">&rsaquo;</button>
                    @endif
                </div>

                @elseif($featuredCatsStyle === 'editorial')
                {{-- Tarjetas fotográficas altas con etiqueta y punto de acento --}}
@unless($introActive('categories'))                <div class="home-section-head"><div class="home-section-copy"><h2>{{ $featuredCatsTitle }}<span class="hc-dot">.</span></h2></div></div>@endunless
                <div class="hc-editorial-grid">
                    @foreach($featuredCategoryItems as $item)
                    @php $cat = $item['model']; $catImage = $assetUrl($item['image']); @endphp
                    <a href="{{ $shopUrl }}?category={{ $cat->id }}" class="hc-photo">
                        @if($catImage)<img src="{{ $catImage }}" alt="{{ $cat->name }}" loading="lazy">@else<span class="hc-photo-ph"></span>@endif
                        <span class="hc-photo-label">{{ mb_strtoupper($cat->name) }}<span class="hc-dot">.</span></span>
                    </a>
                    @endforeach
                </div>
                @endif

                @if(!in_array($featuredCatsStyle, ['showcase','circles','carousel','editorial'], true))
                @unless($introActive('categories'))
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
                @endunless

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
                            @elseif(filled($settings['caticon_'.$cat->id] ?? null))
                                <span class="home-cat-icon">{!! $settings['caticon_'.$cat->id] !!}</span>
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
                            <span class="home-cat-arrow">@if($featuredCatsStyle==='coleccion')<i class="home-cat-cta">{{ $featuredCatsAllText ?: 'Ver más' }}</i>@endif<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M14 6l6 6-6 6"></path></svg></span>
                        </div>
                    </a>
                    @endforeach
                </div>
                @endif
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
                'card' => '<rect x="3" y="6" width="18" height="13" rx="2"></rect><path d="M3 10h18"></path><path d="M7 15h4"></path>',
                'check' => '<circle cx="12" cy="12" r="9"></circle><path d="m8.5 12.5 2.3 2.3 4.7-5"></path>',
                'gift' => '<rect x="4" y="9" width="16" height="11" rx="1.5"></rect><path d="M12 9v11M4 13h16"></path><path d="M12 9c-4 0-4.8-4-2.5-4.6C11.6 3.9 12 7 12 9c0-2 .4-5.1 2.5-4.6C16.8 5 16 9 12 9Z"></path>',
                'box' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"></path><path d="M12 12 4 7.5M12 12l8-4.5M12 12v9"></path>',
                'percent' => '<path d="m6 18 12-12"></path><circle cx="8" cy="8" r="2.4"></circle><circle cx="16" cy="16" r="2.4"></circle>',
                'pin' => '<path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"></path><circle cx="12" cy="10" r="2.6"></circle>',
                'default' => '<circle cx="12" cy="12" r="9"></circle><path d="M12 8v4M12 16h.01"></path>',
            ];
            // Resolución inteligente: nombre exacto → alias comunes → palabras del título.
            $trustIconResolve = static function (?string $icon, string $title) use ($trustIconPaths): string {
                $icon = mb_strtolower(trim((string) $icon));
                if (isset($trustIconPaths[$icon])) return $icon;
                $alias = [
                    'envio' => 'truck', 'envío' => 'truck', 'delivery' => 'truck', 'camion' => 'truck', 'camión' => 'truck', 'moto' => 'truck',
                    'garantia' => 'warranty', 'garantía' => 'warranty', 'medalla' => 'warranty', 'calidad' => 'warranty', 'sello' => 'warranty',
                    'pago' => 'card', 'tarjeta' => 'card', 'pagos' => 'card', 'visa' => 'card',
                    'seguro' => 'shield', 'seguridad' => 'shield', 'candado' => 'shield', 'proteccion' => 'shield', 'protección' => 'shield',
                    'soporte' => 'support', 'atencion' => 'support', 'atención' => 'support', 'headset' => 'support', 'ayuda' => 'support', 'servicio' => 'support',
                    'tienda' => 'store', 'retiro' => 'store', 'local' => 'store',
                    'reloj' => 'clock', 'express' => 'clock', 'rapido' => 'clock', 'rápido' => 'clock', '24h' => 'clock', 'horario' => 'clock',
                    'estrella' => 'sparkles', 'star' => 'sparkles', 'nuevo' => 'sparkles', 'exclusivo' => 'sparkles',
                    'regalo' => 'gift', 'caja' => 'box', 'paquete' => 'box', 'descuento' => 'percent', 'oferta' => 'percent', 'ubicacion' => 'pin', 'ubicación' => 'pin', 'mapa' => 'pin',
                ];
                if (isset($alias[$icon])) return $alias[$icon];
                $t = mb_strtolower($title);
                foreach ([
                    'envío' => 'truck', 'envio' => 'truck', 'entrega' => 'truck', 'delivery' => 'truck',
                    'garant' => 'warranty', 'original' => 'warranty', 'calidad' => 'warranty',
                    'pago' => 'card', 'seguro' => 'shield', 'protec' => 'shield',
                    'soporte' => 'support', 'técnico' => 'support', 'tecnico' => 'support', 'atención' => 'support', 'atencion' => 'support', 'asesor' => 'support', 'ayuda' => 'support',
                    'tienda' => 'store', 'retiro' => 'store', 'recoge' => 'store',
                    'express' => 'clock', 'rápid' => 'clock', 'rapid' => 'clock', 'hora' => 'clock', 'inmediat' => 'clock',
                    'descuento' => 'percent', 'oferta' => 'percent', 'precio' => 'percent',
                    'regalo' => 'gift', 'stock' => 'box', 'exclusiv' => 'sparkles', 'peru' => 'truck', 'perú' => 'truck',
                ] as $kw => $ic) {
                    if (str_contains($t, $kw)) return $ic;
                }
                return 'default';
            };
        @endphp
        <section class="trust-section style-{{ $trustSectionStyle }} {{ $trustMobileCarousel ? 'mobile-carousel' : '' }}" aria-label="Beneficios" data-store-native-section="benefits" style="order:{{ $sectionOrder('benefits') }}">
            <div class="container">
                @include('public.templates.partials.computienda-intro', ['introKey' => 'benefits'])
@unless($introActive('benefits'))
                {{-- Sin título ni subtítulo la cabecera NO se renderiza (0 espacio). --}}
                @if($trustSectionTitle !== '' || $trustSectionSubtitle !== '')
                <div class="trust-section-head">
                    @if($trustSectionTitle !== '')<h2>{{ $trustSectionTitle }}</h2>@endif
                    @if($trustSectionSubtitle !== '')<p>{{ $trustSectionSubtitle }}</p>@endif
                </div>
                @endif
                @endunless
                <div class="trust-grid">
                    @foreach($trustBenefits as $benefit)
                    <article class="trust-card" @if(($benefit['url'] ?? '') !== '') style="position:relative" @endif>
                        @if(($benefit['url'] ?? '') !== '')
                        <a href="{{ $benefit['url'] }}" aria-label="{{ $benefit['title'] }}" style="position:absolute;inset:0;z-index:1"></a>
                        @endif
                        <span class="trust-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                {!! $trustIconPaths[$trustIconResolve($benefit['icon'], $benefit['title'])] !!}
                            </svg>
                        </span>
                        <div class="trust-card-copy">
                            <strong>{{ $benefit['title'] }}</strong>
                            @if($trustShowDescriptions && $benefit['description'] !== '')<span>{{ $benefit['description'] }}</span>@endif
                        </div>
                    </article>
                    @endforeach
                    @if($trustExtraNote !== '')<span class="trust-extra">{{ $trustExtraNote }}</span>@endif
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
            // Regla: una sección ACTIVA siempre se muestra. Si la oferta venció, se
            // oculta sólo el contador — no la sección entera.
            $flashExpiredAction = $flashContent['expired_action'] ?? $flashContent['on_end'] ?? 'keep';
            $flashExpiredMessage = trim($flashContent['expired_message'] ?? 'Esta oferta ha finalizado.');
            $flashEndsIso = null; $flashExpired = false;
            if ($flashEndsRaw) { try { $flashDate = \Illuminate\Support\Carbon::parse($flashEndsRaw); $flashEndsIso=$flashDate->toIso8601String(); $flashExpired=$flashDate->isPast(); } catch (\Throwable $exception) {} }
            // Sólo se oculta si el usuario eligió explícitamente 'hide' al expirar.
            $flashShouldRender = !$flashExpired || $flashExpiredAction !== 'hide';
            // Variantes del contador: bloque completo o banda compacta.
            $flashStyle = in_array(($settings['flash_sale_style'] ?? 'full'), ['full','band'], true) ? ($settings['flash_sale_style'] ?? 'full') : 'full';
            $flashAccent = $color($settings['flash_sale_accent'] ?? null, '#facc15');
            // Si venció, no pasamos la fecha para que no muestre un contador en cero.
            if ($flashExpired) { $flashEndsIso = null; }
        @endphp
        @if($isHomeSectionVisible('flash_sale') && $flashShouldRender)
        <section class="special-home-section flash-sale-section style-{{ $flashStyle }}" data-store-native-section="flash_sale" style="order:{{ $sectionOrder('flash_sale') }};--flash-bg:{{ $flashBg }}" @if($flashEndsIso) x-data="{deadline:new Date(@js($flashEndsIso)).getTime(),now:Date.now(),timer:null,start(){this.timer=setInterval(()=>this.now=Date.now(),1000)},stop(){if(this.timer)clearInterval(this.timer)},pad(v){return String(Math.max(0,v)).padStart(2,'0')},get distance(){return Math.max(0,this.deadline-this.now)},get days(){return Math.floor(this.distance/86400000)},get hours(){return Math.floor((this.distance%86400000)/3600000)},get minutes(){return Math.floor((this.distance%3600000)/60000)},get seconds(){return Math.floor((this.distance%60000)/1000)}}" x-init="start()" @endif>
            <div class="container">
                @include('public.templates.partials.computienda-intro', ['introKey' => 'flash_sale'])
                @if($flashStyle === 'band')
                {{-- Variante banda compacta: título + contador en cajas --}}
                <a href="{{ $flashButtonUrl ?: $shopUrl }}" class="flash-band" style="--flash-accent:{{ $flashAccent }}">
                    <span class="flash-band-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M13 3L5.5 13.5H11L10 21l7.5-10.5H12L13 3Z"/></svg></span>
                    <strong class="flash-band-title">{{ $flashTitle }}</strong>
                    @if($flashEndsIso && !$flashExpired)
                    <span class="flash-band-count">
                        <span><b x-text="pad(days)">00</b><i>Días</i></span>
                        <span><b x-text="pad(hours)">00</b><i>Horas</i></span>
                        <span><b x-text="pad(minutes)">00</b><i>Minutos</i></span>
                        <span><b x-text="pad(seconds)">00</b><i>Segundos</i></span>
                    </span>
                    @elseif($flashExpired)<span class="flash-band-msg">{{ $flashExpiredMessage }}</span>@endif
                </a>
                @else
                <div class="flash-campaign {{ $flashImage ? 'has-image' : '' }}" @if($flashImage) style="background-image:linear-gradient(90deg,rgba(15,23,42,.88),rgba(15,23,42,.42)),url('{{ $flashImage }}')" @else style="background:linear-gradient(135deg,var(--flash-bg),color-mix(in srgb,var(--flash-bg) 72%,#000))" @endif>
                    <div class="flash-campaign-copy">
                        <span class="flash-label">Oferta limitada</span><h2>{{ $flashTitle }}</h2>
                        @if($flashExpired)<p>{{ $flashExpiredMessage }}</p>@elseif($flashSubtitle)<p>{{ $flashSubtitle }}</p>@endif
                        @if($flashEndsIso && !$flashExpired)<div class="flash-countdown"><div><strong x-text="pad(days)">00</strong><span>Días</span></div><div><strong x-text="pad(hours)">00</strong><span>Horas</span></div><div><strong x-text="pad(minutes)">00</strong><span>Min</span></div><div><strong x-text="pad(seconds)">00</strong><span>Seg</span></div></div>@endif
                        @if(!$flashExpired && $flashButtonText !== '' && $flashButtonUrl !== '')<a href="{{ $flashButtonUrl }}" class="button button-primary">{{ $flashButtonText }} <span>→</span></a>@endif
                    </div>
                </div>
                @endif
                @if($flashProducts->isNotEmpty())
                <div class="special-product-grid flash-product-grid">
                    @foreach($flashProducts as $product)
                    @include('public.templates.partials.computienda-card', ['p' => $product])
                    @endforeach
                </div>
                @endif
            </div>
        </section>
        @endif

        {{-- ═══ PRODUCTOS CON DESCUENTO ═══ --}}
        @php
            $discountContent = $sectionContentFor('discount_products');
            $discountProducts = $selectSectionProducts($discountContent, $salePool, max(1, min(24, (int) ($discountContent['limit'] ?? 8))));
            $discountTitle = trim($discountContent['title'] ?? 'Productos con descuento');
            $discountSubtitle = trim($discountContent['subtitle'] ?? $discountContent['description'] ?? 'Una selección de oportunidades para comprar mejor.');
        @endphp
        @if($isHomeSectionVisible('discount_products') && $discountProducts->isNotEmpty())
        <section class="special-home-section discount-section" data-store-native-section="discount_products" style="order:{{ $sectionOrder('discount_products') }}">
            <div class="container">
                @include('public.templates.partials.computienda-intro', ['introKey' => 'discount_products'])
@unless($introActive('discount_products'))                <div class="special-section-head">
                    <div><h2>{{ $discountTitle }}</h2>@if($discountSubtitle)<p>{{ $discountSubtitle }}</p>@endif</div>
                    <a href="{{ $shopUrl }}" class="home-see-all">Ver todos <span>→</span></a>
                </div>@endunless
                <div class="special-product-grid">
                    @foreach($discountProducts as $product)
                    @include('public.templates.partials.computienda-card', ['p' => $product])
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
            // Fuente configurable del componente de productos (ProductSection genérico):
            // automatic (destacados) | manual (ids) | newest (novedades) | discounted (ofertas) | category.
            $pfRawContent = $homeSectionByNativeKey->get('featured_products')?->content ?? [];
            $pfSelIds = collect($pfRawContent['product_ids'] ?? [])
                ->map(fn ($v) => (string) $v)->filter()->values();
            $pfLimit = max(1, min(24, (int) ($pfRawContent['limit'] ?? 8)));
            $pfSource = in_array(($pfRawContent['selection'] ?? 'automatic'), ['automatic','manual','newest','discounted','category'], true)
                ? ($pfRawContent['selection'] ?? 'automatic') : 'automatic';
            $homeFeatured = match ($pfSource) {
                'manual' => $pfSelIds->isNotEmpty()
                    ? $allProducts->filter(fn ($pp) => $pfSelIds->contains((string) $pp->id))->take($pfLimit)->values()
                    : collect(),
                'newest' => ($newArrivals ?? collect())->take($pfLimit)->values(),
                'discounted' => ($onSale ?? collect())->take($pfLimit)->values(),
                'category' => $allProducts->filter(fn ($pp) => (string) $pp->category_id === (string) ($pfRawContent['category_id'] ?? ''))->take($pfLimit)->values(),
                default => $featured->take($pfLimit),
            };
            if($homeFeatured->isEmpty()) $homeFeatured = $featured->take($pfLimit);
            if($homeFeatured->isEmpty()) $homeFeatured = $categories->flatMap->products->take(8);
            // En la portada mandan primero los productos que SÍ tienen foto: un
            // escaparate lleno de "foto en camino" resta más de lo que suma.
            // Si la tienda aún no subió ninguna imagen, no se altera nada.
            if ($homeFeatured->contains(fn ($pp) => filled($pp->main_image_url))) {
                $homeFeatured = $homeFeatured
                    ->sortByDesc(fn ($pp) => filled($pp->main_image_url) ? 1 : 0)
                    ->values();
            }
            // Título: usa el de la sección del constructor si existe.
            $pfSection = $homeSectionByNativeKey->get('featured_products');
            $pfContent = is_array($pfSection?->content) ? $pfSection->content : [];
            $pfTitle = $pfContent['title'] ?? ($catalogTitle ?: 'Productos destacados');
            // Al filtrar por una categoria, el encabezado toma su nombre: antes
            // decia "Productos destacados" aunque el cliente estuviera viendo
            // Computadoras, y no daba pista de donde estaba.
            $pfCatId = \App\Support\StorefrontNavigation::currentCategoryId();
            $pfCat = $pfCatId ? $categories->flatMap(fn ($c) => collect([$c])->merge($c->children ?? []))
                ->first(fn ($c) => (string) $c->id === (string) $pfCatId) : null;
            if ($pfCat) {
                $pfTitle = $pfCat->name;
                $pfPadre = $pfCat->parent_id
                    ? $categories->first(fn ($c) => (string) $c->id === (string) $pfCat->parent_id)
                    : null;
                $pfBadgeCat = $pfPadre?->name;
                $pfDescCat = trim((string) ($pfCat->description ?? ''));
            }
            // Defaults neutros de rubro: los textos anteriores hablaban de tecnologia
            // y se filtraban a tiendas de otros sectores. Editables desde el constructor.
            $pfBadge = trim($pfBadgeCat ?? ($pfContent['badge'] ?? 'Destacados'));
            $pfDesc = trim($pfDescCat ?? '') !== ''
                ? $pfDescCat
                : ($pfCat
                    ? 'Todo lo que tenemos en '.$pfCat->name.'.'
                    : trim($pfContent['description'] ?? 'Una selección de nuestro catálogo, elegida para ti.'));
        @endphp
        @if($isHomeSectionVisible('featured_products') && $homeFeatured->count())
        <section class="pf-section" data-store-native-section="featured_products" style="order:{{ $sectionOrder('featured_products') }}"><div class="container">
                @include('public.templates.partials.computienda-intro', ['introKey' => 'featured_products'])
            @unless($introActive('featured_products'))<div class="pf-head">
                <div>
                    @if($pfBadge !== '')<span class="pf-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2 15 8.5 22 9.5 17 14.5 18.5 21.5 12 18 5.5 21.5 7 14.5 2 9.5 9 8.5Z"></path></svg>{{ mb_strtoupper($pfBadge) }}</span>@endif
                    <h2 class="pf-title">{{ $pfTitle }}</h2>
                    @if($pfDesc !== '')<p class="pf-desc">{{ $pfDesc }}</p>@endif
                </div>
                <a href="{{ $shopUrl }}" class="pf-seeall">Ver toda la tienda<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M14 6l6 6-6 6"></path></svg></a>
            </div>@endunless
            <div class="pf-grid">
                @foreach($homeFeatured as $product)
                @include('public.templates.partials.computienda-card', ['p' => $product])
                @endforeach
            </div>
            <div class="home-cta-row"><a href="{{ $shopUrl }}" class="button button-primary">Ver todos los productos<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14M14 6l6 6-6 6"></path></svg></a></div>
        </div></section>
        @endif

        {{-- ═══ Secciones extra del registro canónico (multimedia, colecciones,
             marcas, testimonios, galería, FAQ, asesoría WA, CTA) ═══ --}}
        @include('public.templates.partials.computienda-home-extras')
        @endif

        {{-- ═══ TIENDA: catálogo completo con filtros ═══ --}}
        @if($storeView === 'tienda')
        <section class="catalog" id="tienda-catalogo" data-store-native-section="catalog" style="order:{{ $sectionOrder('catalog') }}"><div class="container">
            <nav class="catalog-breadcrumb" aria-label="Ruta de navegación"><a href="{{ \App\Support\StorefrontNavigation::homeUrl($project) }}">Inicio</a><span>/</span><strong x-text="activeFilterLabel">Todos los productos</strong></nav>
            {{-- La vista tienda no declaraba h1: buscadores y lectores no sabian de que iba. --}}
            <h1 class="catalog-h1" @if(empty($activeProfile)) x-text="activeFilterLabel" @endif>{{ !empty($activeProfile) ? $activeProfile->name : 'Todos los productos' }}</h1>
            {{-- Banda de categoria: da contexto de donde esta el cliente y cuantos
                 productos hay AQUI. El contador anterior mostraba el total de la
                 tienda (46) aunque la categoria tuviera 4, y se contradecia con
                 el conteo de abajo. --}}
            <div class="cat-banner">
                <div class="cat-banner-inner">
                    <div class="cat-banner-copy">
                        <h2 x-text="activeFilterLabel">{{ $catalogTitle }}</h2>
                        <p x-text="catalogSubtitle">Filtra por categoría, precio y disponibilidad para encontrar la mejor opción.</p>
                    </div>
                    <span class="product-total"><b x-text="catalogCount">{{ $catalogProducts->count() }}</b> <span x-text="catalogCount===1 ? 'producto' : 'productos'">productos</span></span>
                </div>
            </div>

            <div class="catalog-experience">
                <aside class="catalog-filter-panel" aria-label="Filtros del catálogo">
                    <div class="catalog-filter-header"><strong>Filtros</strong><button class="catalog-clear" type="button" @click="clearAllFilters()" :disabled="!hasActiveFilters">Limpiar todo</button></div>
                    <x-computienda.catalog-filters :categories="$navCategories" :catalog-products="$catalogProducts" filter-scope="desktop" />
                </aside>

                <div class="catalog-results" x-data="catalogBrowser({ endpoint: {{ Js::from(\App\Support\StorefrontNavigation::shopUrl($project)) }}, total: {{ $catalogPage->total() }}, hasMore: {{ $catalogPage->hasMorePages() ? 'true' : 'false' }} })" x-init="init()">
                    <div class="catalog-chips" x-show="hasActiveFilters" x-cloak aria-label="Filtros activos">
                        <template x-for="cid in filterCats" :key="'c'+cid">
                            <button class="catalog-chip" type="button" @click="filterCats=filterCats.filter(x=>x!==cid)" x-cloak><span x-text="categoryName(cid)"></span><span aria-hidden="true">×</span></button>
                        </template>
                        <template x-for="sid in filterSubCats" :key="'s'+sid">
                            <button class="catalog-chip" type="button" @click="filterSubCats=filterSubCats.filter(x=>x!==sid)" x-cloak><span x-text="categoryName(sid)"></span><span aria-hidden="true">×</span></button>
                        </template>
                        <button class="catalog-chip" type="button" x-show="filterInStock" @click="filterInStock=false" x-cloak>En stock <span aria-hidden="true">×</span></button>
                        <button class="catalog-chip" type="button" x-show="filterOnSale" @click="filterOnSale=false" x-cloak>En oferta <span aria-hidden="true">×</span></button>
                        <button class="catalog-chip" type="button" x-show="priceMin>0 || priceMax<maxPrice" @click="priceMin=0;priceMax=maxPrice" x-cloak><span x-text="money(priceMin)+' – '+money(priceMax)"></span><span aria-hidden="true">×</span></button>
                        <button class="catalog-chip" type="button" x-show="query" @click="query=''" x-cloak><span x-text="'“'+query+'”'"></span><span aria-hidden="true">×</span></button>
                        <button class="catalog-chip catalog-chip-neutral" type="button" @click="clearAllFilters()">Limpiar todo</button>
                    </div>

                    <div class="catalog-results-head">
                        <div class="catalog-results-count" aria-live="polite"><strong x-text="total">{{ $catalogPage->total() }}</strong> productos</div>
                        <div class="catalog-results-tools">
                            <button class="catalog-mobile-filter" type="button" @click="filterDrawerOpen=true" aria-label="Abrir filtros"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"></path></svg>Filtros <span x-show="activeFilterCount" x-text="activeFilterCount" x-cloak></span></button>
                            <label><span class="sr-only">Ordenar productos</span><select class="catalog-sort" x-model="sort" @change="applyFilters()"><option value="recommended">Relevancia</option><option value="price_asc">Precio: menor a mayor</option><option value="price_desc">Precio: mayor a menor</option><option value="name">Nombre A-Z</option><option value="newest">Novedades</option></select></label>
                        </div>
                    </div>

                    {{-- Grid: render inicial server-side (SEO / sin JS) + append AJAX --}}
                    <div class="catalog-product-grid">
                            <template x-if="!serverRendered">
                                <template x-for="product in products" :key="product.id">
                                    <article class="catalog-card">
                                        <div class="catalog-card-media" :class="!product.image && 'is-noimg'">
                                            <a class="catalog-card-media-link" :href="product.url" @click="qvMobile($event, product)" :aria-label="'Ver '+product.name">
                                                <template x-if="product.image"><img :src="product.image" :alt="product.name" loading="lazy"></template>
                                                <template x-if="!product.image"><svg class="catalog-card-placeholder" width="74" height="74" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linejoin="round" aria-hidden="true"><path d="m4 7 8-4 8 4-8 4-8-4Z"/><path d="M4 7v10l8 4 8-4V7"/><path d="M12 11v10"/></svg><span class="ph-note">Foto en camino</span></template>
                                            </a>
                                            <span class="catalog-sold-out" x-show="product.stock===0" x-cloak>{{ $settings['catalog_badge_sold_out'] ?? 'Agotado' }}</span>
                                            <div class="catalog-quickview">
                                                <button type="button" @click.prevent.stop="openQuickView(product)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>{{ $settings['catalog_quickview_text'] ?? 'Vista rápida' }}</button>
                                            </div>
                                        </div>
                                        <div class="catalog-card-body">
                                            <span class="catalog-card-category" x-text="product.category"></span>
                                            <a class="catalog-card-name" :href="product.url" @click="qvMobile($event, product)" x-text="product.name"></a>
                                            @if($hidePrices)<div class="catalog-card-prices"><span class="quote-price">Precio a solicitud</span></div>@else
                                            @if($pcMode==='auto')
                                            {{-- MODALIDAD B: precio automatico por cantidad (una sola logica de precios) --}}
                                            <div class="buy-auto" x-data="{ q:1 }">
                                                <div class="buy-auto-prices">
                                                    <span class="buy-auto-price" x-text="money(effectivePrice(product,q))"></span>
                                                    @if($pcShowWholesale)
                                                    <template x-if="!isWholesaleQty(product,q) && nextTier(product,q)">
                                                        <small class="buy-auto-hint" x-text="money(nextTier(product,q).price)+' c/u desde '+nextTier(product,q).min+' unid.'"></small>
                                                    </template>
                                                    @endif
                                                    <template x-if="isWholesaleQty(product,q)">
                                                        <small class="buy-auto-ok">✓ Precio mayorista aplicado</small>
                                                    </template>
                                                </div>
                                                @if($pcShowQty)
                                                <div class="buy-row buy-row--{{ $pcCartStyle }}">
                                                    <span class="buy-qty {{ $pcQtyStyle==='compact' ? 'is-compact' : '' }}">
                                                        <button type="button" @click.prevent.stop="q=Math.max(1,q-1)" aria-label="Quitar">−</button>
                                                        <input type="number" x-model.number="q" min="1" @click.stop>
                                                        <button type="button" @click.prevent.stop="q=q+1" aria-label="Agregar">+</button>
                                                    </span>
                                                    @if($pcShowCart && $pcCartStyle==='inline')
                                                    <button type="button" class="buy-add" @click.prevent.stop="addSmart(product,q)">{{ $cartText }}</button>
                                                    @endif
                                                </div>
                                                @endif
                                                @if($pcShowSubtotal)
                                                <div class="buy-auto-sub" x-show="q>1" x-cloak>Subtotal: <b x-text="money(lineSubtotal(product,q))"></b></div>
                                                @endif
                                                @if($pcShowCart && $pcCartStyle!=='inline')
                                                <button type="button" class="buy-add buy-add--{{ $pcCartStyle }}" @click.prevent.stop="addSmart(product,q)">{{ $cartText }}</button>
                                                @endif
                                            </div>
                                            @else
                                            <div class="catalog-card-prices" x-show="!product.wholesalePrice"><span class="catalog-card-price" x-text="money(product.price)"></span><span class="catalog-card-compare" x-show="product.comparePrice" x-text="money(product.comparePrice)" x-cloak></span></div>
                                            @if($wholesale)<div class="buy-block buy-block--retail" x-data="{ q:1 }" x-show="product.wholesalePrice" x-cloak>
                                                <div class="buy-head"><span class="buy-tag">Minorista</span><span class="buy-price" x-text="money(product.price)"></span></div>
                                                <small class="buy-min">Por unidad</small>
                                                <div class="buy-row">
                                                    <span class="buy-qty">
                                                        <button type="button" @click.prevent.stop="q=Math.max(1,q-1)" aria-label="Quitar">−</button>
                                                        <input type="number" x-model.number="q" min="1" @click.stop>
                                                        <button type="button" @click.prevent.stop="q=q+1" aria-label="Agregar">+</button>
                                                    </span>
                                                    <button type="button" class="buy-add" @click.prevent.stop="for(let i=0;i<q;i++)add(product.id,product.name,product.price,product.image,product.category)">+ Agregar</button>
                                                </div>
                                            </div>@endif
                                            @if($wholesale)<div class="buy-block buy-block--wholesale" x-data="{ q: Math.max(1, Number(product.wholesaleMinQty)||1) }" x-show="product.wholesalePrice" x-cloak>
                                                <div class="buy-head"><span class="buy-tag">Mayorista</span><span class="buy-price" x-text="money(product.wholesalePrice)"></span></div>
                                                <small class="buy-min" x-text="(()=>{const u=product.wholesaleUnit||'',n=Number(product.wholesaleMinQty)||1;return u&&u!=='unidad'?('1 '+u+' = '+n+' unid.'):('Desde '+n+' unid.')})()"></small>
                                                <div class="buy-row">
                                                    <span class="buy-qty">
                                                        <button type="button" @click.prevent.stop="const p=Number(product.wholesaleMinQty)||1;q=Math.max(p,q-p)" aria-label="Quitar">−</button>
                                                        <input type="number" x-model.number="q" :min="Number(product.wholesaleMinQty)||1" :step="Number(product.wholesaleMinQty)||1" @click.stop>
                                                        <button type="button" @click.prevent.stop="q=q+(Number(product.wholesaleMinQty)||1)" aria-label="Agregar">+</button>
                                                    </span>
                                                    <button type="button" class="buy-add" @click.prevent.stop="addWholesale(product.id,product.name,product.wholesalePrice,q,product.image,product.category)">+ Agregar</button>
                                                </div>
                                            </div>@endif @endif
                                            @endif
                                        </div>
                                        <div class="catalog-card-actions">
                                            @if($showCartButton)<button class="catalog-card-action" type="button" @click="(product.sizes&&product.sizes.length)?openQuickView(product):add(product.id,product.name,product.price,product.image,product.category)" :disabled="product.stock===0" x-text="product.stock===0 ? soldOutText : cartButtonText"></button>@endif
                                            @if($showInquiryButton)<a class="catalog-card-inquiry" :href="'https://wa.me/{{ $whatsapp }}?text='+encodeURIComponent({{ Js::from($inquiryMsgBase) }}+product.name+' '+(product.url&&product.url.indexOf('http')===0?product.url:location.origin+(product.url||'')))" target="_blank" rel="noopener"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.4 14.1c-.2.7-1.3 1.3-1.9 1.4-.5.1-1.1.1-1.8-.1-.4-.1-1-.3-1.7-.6-2.9-1.3-4.8-4.2-5-4.4-.1-.2-1.2-1.6-1.2-3s.7-2.1 1-2.4c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.4.2.5.7 1.8.8 1.9.1.1.1.3 0 .5l-.4.6c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1.1 2.2 1.4 2.5 1.5.3.1.5.1.6-.1.2-.2.7-.8.9-1.1.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.2 1.4Z"/></svg>{{ $inquiryText }}</a>@endif
                                        </div>
                                    </article>
                                </template>
                            </template>

                            {{-- SSR inicial (visible sin JS y para SEO) --}}
                            <div x-show="serverRendered" class="catalog-ssr">
                                @foreach($catalogCards as $card)
                                <article class="catalog-card">
                                    <div class="catalog-card-media{{ $card['image'] ? '' : ' is-noimg' }}">
                                        <a class="catalog-card-media-link" href="{{ $card['url'] }}" @click="qvMobile($event, {{ Js::from($card) }})" aria-label="Ver {{ $card['name'] }}">
                                            @if($card['image'])<img src="{{ $card['image'] }}" alt="{{ $card['name'] }}" loading="lazy">@else<svg class="catalog-card-placeholder" width="74" height="74" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linejoin="round" aria-hidden="true"><path d="m4 7 8-4 8 4-8 4-8-4Z"/><path d="M4 7v10l8 4 8-4V7"/><path d="M12 11v10"/></svg><span class="ph-note">Foto en camino</span>@endif
                                        </a>
                                        <div class="catalog-quickview">
                                            <button type="button" @click.prevent.stop="openQuickView({{ Js::from($card) }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>{{ $settings['catalog_quickview_text'] ?? 'Vista rápida' }}</button>
                                        </div>
                                    </div>
                                    <div class="catalog-card-body">
                                        <span class="catalog-card-category">{{ $card['category'] }}</span>
                                        <a class="catalog-card-name" href="{{ $card['url'] }}" @click="qvMobile($event, {{ Js::from($card) }})">{{ $card['name'] }}</a>
                                        @if($hidePrices)<div class="catalog-card-prices"><span class="quote-price">Precio a solicitud</span></div>@else
                                        @if($pcMode==='auto')
                                        {{-- MODALIDAD B (server): misma logica de precios via Alpine --}}
                                        @php $pcData = ['id'=>$card['id'],'name'=>$card['name'],'price'=>(float) $card['price'],'wholesalePrice'=>(float) ($card['wholesalePrice'] ?? 0),'wholesaleMinQty'=>max(1,(int) ($card['wholesaleMinQty'] ?? 1)),'image'=>$card['image'] ?? '','category'=>$card['category'] ?? '']; @endphp
                                        <div class="buy-auto" x-data="{ q:1, p:{{ Js::from($pcData) }} }">
                                            <div class="buy-auto-prices">
                                                <span class="buy-auto-price" x-text="money(effectivePrice(p,q))"></span>
                                                @if($pcShowWholesale)
                                                <template x-if="!isWholesaleQty(p,q) && nextTier(p,q)">
                                                    <small class="buy-auto-hint" x-text="money(nextTier(p,q).price)+' c/u desde '+nextTier(p,q).min+' unid.'"></small>
                                                </template>
                                                @endif
                                                <template x-if="isWholesaleQty(p,q)"><small class="buy-auto-ok">✓ Precio mayorista aplicado</small></template>
                                            </div>
                                            @if($pcShowQty)
                                            <div class="buy-row buy-row--{{ $pcCartStyle }}">
                                                <span class="buy-qty {{ $pcQtyStyle==='compact' ? 'is-compact' : '' }}">
                                                    <button type="button" @click.prevent.stop="q=Math.max(1,q-1)" aria-label="Quitar">−</button>
                                                    <input type="number" x-model.number="q" min="1" @click.stop>
                                                    <button type="button" @click.prevent.stop="q=q+1" aria-label="Agregar">+</button>
                                                </span>
                                                @if($pcShowCart && $pcCartStyle==='inline')<button type="button" class="buy-add" @click.prevent.stop="addSmart(p,q)">{{ $cartText }}</button>@endif
                                            </div>
                                            @endif
                                            @if($pcShowSubtotal)<div class="buy-auto-sub" x-show="q>1" x-cloak>Subtotal: <b x-text="money(lineSubtotal(p,q))"></b></div>@endif
                                            @if($pcShowCart && $pcCartStyle!=='inline')<button type="button" class="buy-add buy-add--{{ $pcCartStyle }}" @click.prevent.stop="addSmart(p,q)">{{ $cartText }}</button>@endif
                                        </div>
                                        @else
                                        @if(!($wholesale && !empty($card['wholesalePrice'])))<div class="catalog-card-prices"><span class="catalog-card-price">{{ $currencySymbol ?? 'S/' }} {{ number_format($card['price'],2) }}</span>@if($card['comparePrice'])<span class="catalog-card-compare">{{ $currencySymbol ?? 'S/' }} {{ number_format($card['comparePrice'],2) }}</span>@endif</div>@endif
                                        @if($wholesale && !empty($card['wholesalePrice']))<div class="buy-block buy-block--retail" x-data="{ q:1 }">
                                            <div class="buy-head"><span class="buy-tag">Minorista</span><span class="buy-price">{{ $currencySymbol ?? 'S/' }} {{ number_format($card['price'],2) }}</span></div>
                                            <small class="buy-min">Por unidad</small>
                                            <div class="buy-row">
                                                <span class="buy-qty">
                                                    <button type="button" @click.prevent.stop="q=Math.max(1,q-1)" aria-label="Quitar">−</button>
                                                    <input type="number" x-model.number="q" min="1" @click.stop>
                                                    <button type="button" @click.prevent.stop="q=q+1" aria-label="Agregar">+</button>
                                                </span>
                                                <button type="button" class="buy-add" @click.prevent.stop="for(let i=0;i<q;i++)add({{ $card['id'] }},{{ Js::from($card['name']) }},{{ (float) $card['price'] }},{{ Js::from($card['image'] ?? '') }},{{ Js::from($card['category'] ?? '') }})">+ Agregar</button>
                                            </div>
                                        </div>@endif
                                        @if($wholesale && !empty($card['wholesalePrice']))<div class="buy-block buy-block--wholesale" x-data="{ q: {{ max(1,(int) $card['wholesaleMinQty']) }} }">
                                            <div class="buy-head"><span class="buy-tag">Mayorista</span><span class="buy-price">{{ $currencySymbol ?? 'S/' }} {{ number_format($card['wholesalePrice'],2) }}</span></div>
                                            <small class="buy-min">{{ (trim((string) $card['wholesaleUnit'])!=='' && !in_array(trim((string) $card['wholesaleUnit']),['unidad','unidades'],true)) ? '1 '.trim((string) $card['wholesaleUnit']).' = '.max(1,(int) $card['wholesaleMinQty']).' unid.' : 'Desde '.max(1,(int) $card['wholesaleMinQty']).' unid.' }}</small>
                                            <div class="buy-row">
                                                <span class="buy-qty">
                                                    <button type="button" @click.prevent.stop="q=Math.max({{ max(1,(int) $card['wholesaleMinQty']) }},q-{{ max(1,(int) $card['wholesaleMinQty']) }})" aria-label="Quitar">−</button>
                                                    <input type="number" x-model.number="q" min="{{ max(1,(int) $card['wholesaleMinQty']) }}" step="{{ max(1,(int) $card['wholesaleMinQty']) }}" @click.stop>
                                                    <button type="button" @click.prevent.stop="q=q+{{ max(1,(int) $card['wholesaleMinQty']) }}" aria-label="Agregar">+</button>
                                                </span>
                                                <button type="button" class="buy-add" @click.prevent.stop="addWholesale({{ $card['id'] }},{{ Js::from($card['name']) }},{{ (float) $card['wholesalePrice'] }},q,{{ Js::from($card['image'] ?? '') }},{{ Js::from($card['category'] ?? '') }})">+ Agregar</button>
                                            </div>
                                        </div>@endif @endif
                                        @endif
                                    </div>
                                    <div class="catalog-card-actions">
                                        @if($showCartButton)<button class="catalog-card-action" type="button" @click="@if(!empty($card['sizes']))openQuickView({{ Js::from($card) }})@else add({{ $card['id'] }},{{ Js::from($card['name']) }},{{ $card['price'] }},{{ Js::from($card['image'] ?? '') }},{{ Js::from($card['category'] ?? '') }})@endif">{{ $quoteMode ? $quoteBtnText : ($settings['btn_cart_text'] ?? 'Agregar') }}</button>@endif
                                        @if($showInquiryButton)<a class="catalog-card-inquiry" href="https://wa.me/{{ $whatsapp }}?text={{ urlencode($inquiryMsgBase.$card['name'].' '.$card['url']) }}" target="_blank" rel="noopener"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.4 14.1c-.2.7-1.3 1.3-1.9 1.4-.5.1-1.1.1-1.8-.1-.4-.1-1-.3-1.7-.6-2.9-1.3-4.8-4.2-5-4.4-.1-.2-1.2-1.6-1.2-3s.7-2.1 1-2.4c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.4.2.5.7 1.8.8 1.9.1.1.1.3 0 .5l-.4.6c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1.1 2.2 1.4 2.5 1.5.3.1.5.1.6-.1.2-.2.7-.8.9-1.1.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.2 1.4Z"/></svg>{{ $inquiryText }}</a>@endif
                                    </div>
                                </article>
                                @endforeach
                            </div>

                            <div class="catalog-empty" x-show="!loading && total===0" x-cloak>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><h3>Sin resultados</h3><p>{{ $txtNoResults }}</p><button class="button button-primary" type="button" @click="clearAllFilters()">{{ $txtViewMore }}</button>
                            </div>
                        </div>

                        <div class="catalog-loading" x-show="loading" x-cloak aria-live="polite" style="grid-column:1/-1;text-align:center;padding:24px;color:#64748b">Cargando…</div>
                        <div class="catalog-error" x-show="error" x-cloak style="grid-column:1/-1;text-align:center;padding:16px;color:#dc2626">No pudimos cargar más productos. <button type="button" @click="loadMore()" style="text-decoration:underline">Reintentar</button></div>
                        <div class="catalog-loadmore" x-show="hasMore && !loading" x-cloak style="grid-column:1/-1;text-align:center;padding:20px">
                            <button class="button button-primary" type="button" @click="loadMore()" :disabled="loading">{{ $txtViewMore ?? 'Cargar más' }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div></section>
        @endif

        {{-- ═══ PÁGINA DE PRODUCTO (mismo header/menú/carrito/footer de la tienda) ═══ --}}
        @if($storeView === 'producto' && !empty($storeProduct))
        @php
            $sp = $storeProduct;
            $spImages = $sp->images && $sp->images->count() ? $sp->images : collect();
            $spMain = $sp->mainImage?->url ?? ($spImages->first()->url ?? null);
            $spMainUrl = $spMain ? $assetUrl($spMain) : null;
            $spCompare = $sp->compare_price && $sp->compare_price > $sp->price ? (float) $sp->compare_price : null;
            $spStock = $sp->stock;
        @endphp
        <section class="catalog" data-store-native-section="product" style="order:1" x-data="{ pdpImg: @js($spMainUrl), pdpQty: 1, pdpSize: null, pdpSizeErr: false }"><div class="container">
            <nav class="catalog-breadcrumb" aria-label="Ruta de navegación" style="margin-top:22px">
                <a href="{{ \App\Support\StorefrontNavigation::homeUrl($project) }}">Inicio</a><span>/</span>
                <a href="{{ \App\Support\StorefrontNavigation::shopUrl($project) }}">Tienda</a><span>/</span>
                <strong>{{ $sp->name }}</strong>
            </nav>

            <div class="pdp-wrap">
                {{-- Galería --}}
                <div class="pdp-gallery">
                    <div class="pdp-main-img" @click="pdpImg && window.__lightbox && window.__lightbox(pdpImg)">
                        <template x-if="pdpImg"><img :src="pdpImg" alt="{{ $sp->name }}"></template>
                        <template x-if="!pdpImg"><svg class="catalog-card-placeholder" width="96" height="96" style="width:96px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true"><path d="m4 7 8-4 8 4-8 4-8-4Z"></path></svg></template>
                        <span class="pdp-zoom-hint" x-show="pdpImg" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3M11 8v6M8 11h6"/></svg></span>
                        @if($spCompare)<span class="catalog-discount" style="top:14px;left:14px">-{{ round((1 - $sp->price / $spCompare) * 100) }}%</span>@endif
                    </div>
                    @if($spImages->count() > 1)
                    <div class="pdp-thumbs">
                        @foreach($spImages as $img)
                        @php $iu = $assetUrl($img->url); @endphp
                        <div class="pdp-thumb" :class="pdpImg==='{{ $iu }}' && 'active'" @click="pdpImg='{{ $iu }}'"><img src="{{ $iu }}" alt="{{ $sp->name }}"></div>
                        @endforeach
                    </div>
                    @endif
                    <div class="pdp-share">
                        <span>Compartir:</span>
                        <a href="https://wa.me/?text={{ urlencode($sp->name.' - ') }}{{ urlencode(\App\Support\ImageVariants::productUrl($project, $sp->id, $sp->name)) }}" target="_blank" rel="noopener" style="background:#25d366" title="WhatsApp"><svg width="16" height="16" viewBox="0 0 24 24" fill="#fff"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2z"/></svg></a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(\App\Support\ImageVariants::productUrl($project, $sp->id, $sp->name)) }}" target="_blank" rel="noopener" style="background:#1877f2" title="Facebook"><svg width="16" height="16" viewBox="0 0 24 24" fill="#fff"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg></a>
                    </div>
                </div>

                {{-- Info --}}
                <div>
                    @if($sp->category)<a class="pdp-cat" href="{{ \App\Support\StorefrontNavigation::shopUrl($project) }}?category={{ $sp->category_id }}">{{ $sp->category->name }}</a>@endif
                    <h1 class="pdp-title">{{ $sp->name }}</h1>
                    @unless($hidePrices)
                    <div class="pdp-price-row">
                        <span class="pdp-price">{{ $currency }} {{ number_format($sp->price, 2) }}</span>
                        @if($spCompare)<span class="pdp-compare">{{ $currency }} {{ number_format($spCompare, 2) }}</span>@endif
                    </div>
                    @if($spCompare)<p class="pdp-save">Ahorras {{ $currency }} {{ number_format($spCompare - $sp->price, 2) }}</p>@endif
                    @if($wholesale && filled($sp->wholesale_price))
                    <div class="wh-buy"><span class="wh-buy-info"><b>MAYORISTA {{ $currency }} {{ number_format($sp->wholesale_price, 2) }}</b><small>Mín. {{ (int) ($sp->wholesale_min_qty ?? 1) }} {{ (filled($sp->wholesale_unit) && !is_numeric($sp->wholesale_unit)) ? $sp->wholesale_unit : 'unidades' }}</small></span><button type="button" class="wh-buy-btn" @click="addWholesale({{ $sp->id }},{{ Js::from($sp->name) }},{{ (float) $sp->wholesale_price }},{{ (int) ($sp->wholesale_min_qty ?? 1) }},{{ Js::from($sp->main_image_url ?? '') }},{{ Js::from($sp->category?->name ?? '') }})">+ Agregar por mayor</button></div>
                    @endif
                    @endunless

                    @if(!is_null($spStock) && $spStock === 0)
                        <div class="pdp-stock-out">⚠️ Producto agotado temporalmente</div>
                    @elseif(!is_null($spStock) && $spStock > 0 && $spStock <= 10)
                        <div class="pdp-stock-low">⚡ ¡Solo quedan {{ $spStock }} unidades!</div>
                    @endif

                    @if($sp->sku && $showSku)<p style="color:#94a3b8;font-size:12px;margin:4px 0">SKU: {{ $sp->sku }}</p>@endif
                    @if($sp->description)<div class="pdp-desc">{!! nl2br(e($sp->description)) !!}</div>@endif

                    @php $spSizes = $sp->sizes; @endphp
                    @if(count($spSizes))
                    <div class="pdp-sizes">
                        <span class="qv-sizes-label">Talla:</span>
                        <div class="qv-size-list">
                            @foreach($spSizes as $sz)
                            <button type="button" class="qv-size" :class="pdpSize==={{ Js::from($sz) }}&&'on'" @click="pdpSize={{ Js::from($sz) }};pdpSizeErr=false">{{ $sz }}</button>
                            @endforeach
                        </div>
                        <p class="qv-size-req" x-show="pdpSizeErr" x-cloak role="alert">Elige una talla para continuar</p>
                    </div>
                    @endif
                    <div class="pdp-actions">
                        <div class="pdp-add-row" style="display:flex;gap:10px;align-items:stretch">
                            @if($showCartButton)
                            <div class="pdp-qty">
                                <button type="button" @click="pdpQty=Math.max(1,pdpQty-1)" aria-label="Menos">−</button>
                                <span x-text="pdpQty"></span>
                                <button type="button" @click="pdpQty++" aria-label="Más">+</button>
                            </div>
                            <button class="pdp-add" type="button" @if(!is_null($spStock) && $spStock === 0) disabled @else @click="if({{ count($sp->sizes) }}&&!pdpSize){pdpSizeErr=true}else{for(let i=0;i<pdpQty;i++)add({{ $sp->id }},{{ Js::from($sp->name) }},{{ $sp->price }},'','',pdpSize||'')}" @endif>
                                @if(!is_null($spStock) && $spStock === 0){{ $soldOutText ?? 'Agotado' }}@else🛒 {{ $quoteMode ? $quoteBtnText : $cartText }}@endif
                            </button>
                            @endif
                            @if($showInquiryButton)
                            <a class="pdp-add pdp-consult" href="https://wa.me/{{ $whatsapp }}?text={{ urlencode($inquiryMsgBase.$sp->name.' '.request()->fullUrl()) }}" target="_blank" rel="noopener"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.4 14.1c-.2.7-1.3 1.3-1.9 1.4-.5.1-1.1.1-1.8-.1-.4-.1-1-.3-1.7-.6-2.9-1.3-4.8-4.2-5-4.4-.1-.2-1.2-1.6-1.2-3s.7-2.1 1-2.4c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.4.2.5.7 1.8.8 1.9.1.1.1.3 0 .5l-.4.6c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1.1 2.2 1.4 2.5 1.5.3.1.5.1.6-.1.2-.2.7-.8.9-1.1.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.2 1.4Z"/></svg>{{ $inquiryText }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Relacionados --}}
            @if(!empty($relatedProducts) && $relatedProducts->count())
            <div class="pdp-related">
                <h2>Productos relacionados</h2>
                <div class="catalog-product-grid">
                    @foreach($relatedProducts as $rp)
                    @php $rpImg = $rp->mainImage?->url ? $assetUrl($rp->mainImage->url) : null; $rpUrl = \App\Support\ImageVariants::productUrl($project, $rp->id, $rp->name); @endphp
                    <article class="catalog-card">
                        <div class="catalog-card-media">
                            <a class="catalog-card-media-link" href="{{ $rpUrl }}" aria-label="Ver {{ $rp->name }}">
                                @if($rpImg)<img src="{{ $rpImg }}" alt="{{ $rp->name }}" loading="lazy">@else<svg class="catalog-card-placeholder" width="74" height="74" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linejoin="round" aria-hidden="true"><path d="m4 7 8-4 8 4-8 4-8-4Z"/><path d="M4 7v10l8 4 8-4V7"/><path d="M12 11v10"/></svg><span class="ph-note">Foto en camino</span>@endif
                            </a>
                        </div>
                        <div class="catalog-card-body">
                            <span class="catalog-card-category">{{ $rp->category->name ?? '' }}</span>
                            <a class="catalog-card-name" href="{{ $rpUrl }}">{{ $rp->name }}</a>
                            @unless($hidePrices)<div class="catalog-card-prices"><span class="catalog-card-price">{{ $currency }} {{ number_format($rp->price, 2) }}</span></div>@endunless
                        </div>
                        <div class="catalog-card-actions">
                            @if($showCartButton)<button class="catalog-card-action" type="button" @click="add({{ $rp->id }},{{ Js::from($rp->name) }},{{ $rp->price }})">{{ $quoteMode ? $quoteBtnText : $cartText }}</button>@endif
                            @if($showInquiryButton)<a class="catalog-card-inquiry" href="https://wa.me/{{ $whatsapp }}?text={{ urlencode($inquiryMsgBase.$rp->name.' '.$rpUrl) }}" target="_blank" rel="noopener"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.4 14.1c-.2.7-1.3 1.3-1.9 1.4-.5.1-1.1.1-1.8-.1-.4-.1-1-.3-1.7-.6-2.9-1.3-4.8-4.2-5-4.4-.1-.2-1.2-1.6-1.2-3s.7-2.1 1-2.4c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.4.2.5.7 1.8.8 1.9.1.1.1.3 0 .5l-.4.6c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1.1 2.2 1.4 2.5 1.5.3.1.5.1.6-.1.2-.2.7-.8.9-1.1.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.2 1.4Z"/></svg>{{ $inquiryText }}</a>@endif
                        </div>
                    </article>
                    @endforeach
                </div>
            </div>
            @endif
        </div></section>
        @endif

        {{-- Lightbox de imagenes (lupa): global — producto y vista rapida --}}
        <div id="cpt-lb" class="cpt-lb" role="dialog" aria-modal="true" aria-label="Imagen ampliada">
            <button type="button" class="cpt-lb-btn cpt-lb-close" aria-label="Cerrar">✕</button>
            <button type="button" class="cpt-lb-btn cpt-lb-prev" aria-label="Anterior" style="display:none">‹</button>
            <button type="button" class="cpt-lb-btn cpt-lb-next" aria-label="Siguiente" style="display:none">›</button>
            <div class="cpt-lb-counter" style="display:none"></div>
            <div class="cpt-lb-zooms">
                <button type="button" class="cpt-lb-btn" data-lbz="in" aria-label="Acercar">+</button>
                <button type="button" class="cpt-lb-btn" data-lbz="out" aria-label="Alejar">−</button>
                <button type="button" class="cpt-lb-btn" data-lbz="rst" aria-label="Restablecer" style="font-size:12px">1:1</button>
            </div>
            <div class="cpt-lb-stage"><img src="" alt="" draggable="false"></div>
        </div>
        <script>
        (function(){
            var imgs = @js(isset($spImages) && $spImages->count() ? $spImages->map(fn ($i) => $assetUrl($i->url))->values()->all() : (isset($spMainUrl) && $spMainUrl ? [$spMainUrl] : []));
            var lb = document.getElementById('cpt-lb');
            if (!lb) return;
            var img = lb.querySelector('.cpt-lb-stage img'),
                prev = lb.querySelector('.cpt-lb-prev'),
                next = lb.querySelector('.cpt-lb-next'),
                counter = lb.querySelector('.cpt-lb-counter'),
                idx = 0, scale = 1, panX = 0, panY = 0, dragging = null;

            function apply(){ img.style.transform = 'scale('+scale+') translate('+(panX/scale)+'px,'+(panY/scale)+'px)'; img.style.cursor = scale>1 ? 'grab' : 'zoom-in'; }
            function reset(){ scale = 1; panX = 0; panY = 0; apply(); }
            function show(){
                img.src = imgs[idx];
                var multi = imgs.length > 1;
                prev.style.display = next.style.display = multi ? 'grid' : 'none';
                counter.style.display = multi ? 'block' : 'none';
                if (multi) counter.textContent = (idx+1)+' / '+imgs.length;
            }
            function open(i){ idx = i; reset(); show(); lb.classList.add('open'); document.body.classList.add('cpt-lb-open'); document.body.style.overflow = 'hidden'; }
            function close(){ lb.classList.remove('open'); document.body.classList.remove('cpt-lb-open'); img.src=''; document.body.style.overflow=''; reset(); }
            function zoomBy(f){ scale = Math.min(5, Math.max(1, scale*f)); if (scale===1){ panX=panY=0; } apply(); }

            var gallery = imgs.slice();
            window.__lightbox = function(src, list){
                if (Array.isArray(list) && list.length) imgs = list.slice();
                else if (gallery.length && (!src || gallery.indexOf(src) >= 0)) imgs = gallery.slice();
                else if (src) imgs = [src];
                if (!imgs.length) return;
                open(Math.max(0, imgs.indexOf(src)));
            };
            lb.querySelector('.cpt-lb-close').addEventListener('click', close);
            lb.addEventListener('click', function(e){ if (moved) return; if (e.target === lb || e.target.classList.contains('cpt-lb-stage')) close(); });
            prev.addEventListener('click', function(){ idx = (idx-1+imgs.length)%imgs.length; reset(); show(); });
            next.addEventListener('click', function(){ idx = (idx+1)%imgs.length; reset(); show(); });
            lb.querySelector('[data-lbz=in]').addEventListener('click', function(){ zoomBy(1.4); });
            lb.querySelector('[data-lbz=out]').addEventListener('click', function(){ zoomBy(0.7); });
            lb.querySelector('[data-lbz=rst]').addEventListener('click', reset);
            img.addEventListener('click', function(){ if (moved) return; if (scale === 1) zoomBy(1.8); else reset(); });
            lb.addEventListener('wheel', function(e){ e.preventDefault(); zoomBy(e.deltaY < 0 ? 1.15 : 0.87); }, { passive: false });
            // Pellizco con dos dedos + arrastre con uno (pointer events)
            var stage = lb.querySelector('.cpt-lb-stage'), pts = new Map(), pinchStart = 0, pinchBase = 1, moved = false;
            function ptDist(){ var a = Array.from(pts.values()); return Math.hypot(a[0].x - a[1].x, a[0].y - a[1].y); }
            stage.addEventListener('pointerdown', function(e){
                stage.setPointerCapture(e.pointerId);
                pts.set(e.pointerId, { x: e.clientX, y: e.clientY });
                moved = false;
                if (pts.size === 2) { pinchStart = ptDist(); pinchBase = scale; dragging = null; }
                else if (pts.size === 1 && scale > 1) { dragging = { x: e.clientX - panX, y: e.clientY - panY }; img.style.cursor = 'grabbing'; }
            });
            stage.addEventListener('pointermove', function(e){
                if (!pts.has(e.pointerId)) return;
                pts.set(e.pointerId, { x: e.clientX, y: e.clientY });
                moved = true;
                if (pts.size === 2 && pinchStart > 0) {
                    scale = Math.min(5, Math.max(1, pinchBase * ptDist() / pinchStart));
                    if (scale === 1) { panX = panY = 0; }
                    apply();
                } else if (pts.size === 1 && dragging) {
                    panX = e.clientX - dragging.x; panY = e.clientY - dragging.y; apply();
                }
            });
            function endPt(e){
                pts.delete(e.pointerId);
                if (pts.size < 2) pinchStart = 0;
                if (!pts.size) { dragging = null; img.style.cursor = scale > 1 ? 'grab' : 'zoom-in'; }
            }
            stage.addEventListener('pointerup', endPt);
            stage.addEventListener('pointercancel', endPt);
            document.addEventListener('keydown', function(e){
                if (!lb.classList.contains('open')) return;
                if (e.key === 'Escape') close();
                if (e.key === 'ArrowLeft' && imgs.length>1) prev.click();
                if (e.key === 'ArrowRight' && imgs.length>1) next.click();
            });
        })();
        </script>

        {{-- ═══ PÁGINA: Nosotros / Contacto (con el diseño de la tienda) ═══ --}}
        @if(in_array($storeView, ['nosotros','contacto'], true))
        @php $storePage = $storePage ?? null; $pc = is_array($storePage?->content) ? $storePage->content : []; @endphp
        <section class="store-page" data-store-native-section="custom_page" style="order:{{ $sectionOrder('custom_page') }}"><div class="container">
            <nav class="catalog-breadcrumb" aria-label="Ruta de navegación"><a href="{{ \App\Support\StorefrontNavigation::resolveUrl($project, new \App\Models\StoreMenuItem(['destination_type'=>'home'])) }}">Inicio</a><span>/</span><strong>{{ $storePage?->title ?? ($storeView === 'nosotros' ? 'Nosotros' : 'Contacto') }}</strong></nav>
            @php $abHeroActive = $storeView === 'nosotros' && !empty($pc['hero_enabled']) && (!empty($pc['hero_image']) || !empty($pc['image'])); @endphp
            @unless($abHeroActive)<h1 class="store-page-title">{{ $storePage?->title ?? ($storeView === 'nosotros' ? 'Nosotros' : 'Contacto') }}</h1>@endunless

            @if($storeView === 'nosotros')
                @php
                    // ── Nosotros institucional: todo configurable desde el Constructor (Páginas → Nosotros) ──
                    // El hero institucional ya no exige fotografia: sin imagen se pinta un
                    // fondo de marca (degradado navy/acento) para que la pagina conserve
                    // jerarquia. Al subir la foto, esta la reemplaza automaticamente.
                    $abHeroOn   = !empty($pc['hero_enabled']);
                    $abHeroHasImg = !empty($pc['hero_image']) || !empty($pc['image']);
                    $abHeroImg  = $assetUrl($pc['hero_image'] ?? $pc['image'] ?? null);
                    $abHeroImgM = !empty($pc['hero_image_mobile']) ? $assetUrl($pc['hero_image_mobile']) : $abHeroImg;
                    $abHeroAlign = in_array($pc['hero_align'] ?? 'left', ['left','center'], true) ? ($pc['hero_align'] ?? 'left') : 'left';
                    $abHeroOverlay = max(0, min(80, (int) ($pc['hero_overlay'] ?? 45))) / 100;
                    $abHeroH = ['compact' => '300px', 'standard' => '380px', 'tall' => '470px'][$pc['hero_height'] ?? 'standard'] ?? '380px';
                    $abIcons = [
                        'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/>',
                        'eye'    => '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/>',
                        'star'   => '<path d="m12 2 3 6.6 7 .9-5.2 4.9 1.3 7-6.1-3.5L5.9 21l1.3-7L2 9.5l7-.9L12 2Z"/>',
                        'heart'  => '<path d="M12 21C7 16.6 3 13.3 3 9.3 3 6.4 5.2 4 8 4c1.6 0 3.1.8 4 2 1-1.2 2.4-2 4-2 2.8 0 5 2.4 5 5.3 0 4-4 7.3-9 11.7Z"/>',
                        'shield' => '<path d="M12 2 4 5v6c0 5 3.4 9.4 8 11 4.6-1.6 8-6 8-11V5l-8-3Z"/><path d="m9 12 2 2 4-4"/>',
                        'award'  => '<circle cx="12" cy="9" r="6"/><path d="m8.5 14.5-2 7 5.5-3 5.5 3-2-7"/>',
                        'users'  => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.8-3.2 3.4-5 6.5-5s5.7 1.8 6.5 5"/><circle cx="17" cy="9" r="2.6"/><path d="M16.5 14.6c2.4.3 4.3 1.8 5 4.4"/>',
                        'home'   => '<path d="m3 11 9-8 9 8"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/>',
                        'truck'  => '<path d="M1 4h14v12H1z"/><path d="M15 9h4l4 4v3h-8"/><circle cx="6" cy="18.5" r="1.8"/><circle cx="18.5" cy="18.5" r="1.8"/>',
                        'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
                        'check'  => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.6 2.6L16.5 9"/>',
                        'spark'  => '<path d="M12 2v5M12 17v5M2 12h5M17 12h5M5 5l3.5 3.5M15.5 15.5 19 19M19 5l-3.5 3.5M8.5 15.5 5 19"/>',
                    ];
                    $abIcon = fn ($k, $fb) => $abIcons[$k] ?? $abIcons[$fb];
                    $abDiffs = collect(is_array($pc['differentials'] ?? null) ? $pc['differentials'] : [])
                        ->filter(fn ($d) => is_array($d) && !empty($d['title']) && ($d['enabled'] ?? true))
                        ->values();
                    $abDiffsOn = ($pc['differentials_enabled'] ?? true) && $abDiffs->isNotEmpty();
                    $abHasCards = !empty($pc['mission']) || !empty($pc['vision']);
                @endphp

                {{-- HERO institucional --}}
                @if($abHeroOn)
                <div class="about-hero about-hero--{{ $abHeroAlign }}{{ $abHeroHasImg ? '' : ' about-hero--brand' }}" style="--ab-hero-h:{{ $abHeroH }};--ab-overlay:{{ $abHeroOverlay }}">
                    @if($abHeroHasImg)
                    <picture>
                        @if($abHeroImgM !== $abHeroImg)<source media="(max-width:640px)" srcset="{{ $abHeroImgM }}">@endif
                        <img src="{{ $abHeroImg }}" alt="{{ $storePage?->title ?? 'Nosotros' }}" loading="eager">
                    </picture>
                    @endif
                    <div class="about-hero-content">
                        <h2 class="about-hero-title">{{ $pc['hero_title'] ?? ($storePage?->title ?? 'Nosotros') }}</h2>
                        @if(!empty($pc['hero_subtitle']))<p class="about-hero-subtitle">{{ $pc['hero_subtitle'] }}</p>@endif
                        @if(!empty($pc['hero_desc']))<p class="about-hero-desc">{{ $pc['hero_desc'] }}</p>@endif
                    </div>
                </div>
                @elseif(!empty($pc['image']))
                <img class="store-page-hero" src="{{ $assetUrl($pc['image']) }}" alt="{{ $storePage?->title }}">
                @endif

                {{-- QUIÉNES SOMOS + MISIÓN / VISIÓN --}}
                <div class="about-grid {{ $abHasCards ? '' : 'about-grid--single' }}">
                    <div class="about-intro">
                        <span class="about-label">{{ $pc['label'] ?? 'Quiénes somos' }}</span>
                        @if(!empty($pc['heading']))<h2 class="about-heading">{{ $pc['heading'] }}</h2>@endif
                        @if(!empty($pc['body']))<div class="store-page-body">{{ $pc['body'] }}</div>@endif
                        @if(!empty($pc['button_text']) && !empty($pc['button_url']))
                        <a class="button button-primary about-cta" href="{{ $pc['button_url'] }}">{{ $pc['button_text'] }}</a>
                        @endif
                    </div>
                    @if($abHasCards)
                    <div class="about-cards">
                        @if(!empty($pc['mission']))
                        <div class="about-card">
                            <span class="about-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $abIcon($pc['mission_icon'] ?? 'target', 'target') !!}</svg></span>
                            <h3>Misión</h3><i class="about-card-line" aria-hidden="true"></i>
                            <p>{{ $pc['mission'] }}</p>
                        </div>
                        @endif
                        @if(!empty($pc['vision']))
                        <div class="about-card">
                            <span class="about-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $abIcon($pc['vision_icon'] ?? 'eye', 'eye') !!}</svg></span>
                            <h3>Visión</h3><i class="about-card-line" aria-hidden="true"></i>
                            <p>{{ $pc['vision'] }}</p>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>

                {{-- DIFERENCIALES --}}
                @if($abDiffsOn)
                <div class="about-diffs">
                    @foreach($abDiffs as $d)
                    <div class="about-diff">
                        <span class="about-diff-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $abIcon($d['icon'] ?? 'star', 'star') !!}</svg></span>
                        <strong>{{ $d['title'] }}</strong>
                        @if(!empty($d['text']))<p>{{ $d['text'] }}</p>@endif
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Bloques clásicos (compatibilidad): historia, valores, equipo --}}
                @foreach(['history'=>'Nuestra historia','values'=>'Valores','team'=>'Equipo'] as $k=>$lbl)
                    @if(!empty($pc[$k]))
                    <div class="store-page-block"><h2>{{ $lbl }}</h2><p>{{ $pc[$k] }}</p></div>
                    @endif
                @endforeach
            @else
                @if(!empty($pc['body']))<div class="store-page-body">{{ $pc['body'] }}</div>@endif
                @php
                    // Datos de contacto: lo escrito en la página manda; si está
                    // vacío, caen a la configuración general de la tienda para
                    // que la columna nunca quede en blanco.
                    $ctEmail   = $pc['email']    ?? null ?: $email;
                    $ctPhone   = $pc['phone']    ?? null ?: $phone;
                    $ctWhats   = $pc['whatsapp'] ?? null ?: ($whatsapp ?: null);
                    $ctAddress = $pc['address']  ?? null ?: $footerAddress;
                    $ctHours   = $pc['hours']    ?? null ?: $footerHours;
                @endphp
                <div class="store-page-contact">
                    <div class="store-contact-info">
                        @if($ctEmail)<p><strong>Correo:</strong> <a href="mailto:{{ $ctEmail }}">{{ $ctEmail }}</a></p>@endif
                        @if($ctPhone)<p><strong>Teléfono:</strong> {{ $ctPhone }}</p>@endif
                        @if($ctWhats)<p><strong>WhatsApp:</strong> <a href="https://wa.me/{{ preg_replace('/\D/','',$ctWhats) }}" target="_blank" rel="noopener">{{ $ctWhats }}</a></p>@endif
                        @if($ctAddress)<p><strong>Dirección:</strong> {{ $ctAddress }}</p>@endif
                        @if($ctHours)<p><strong>Horario:</strong> {{ $ctHours }}</p>@endif

                        @if($ctAddress)
                        {{-- Ubicación en Google Maps de la dirección configurada --}}
                        <div class="store-contact-map">
                            <iframe src="https://www.google.com/maps?q={{ urlencode($ctAddress) }}&output=embed&hl=es"
                                    title="Ubicación: {{ $ctAddress }}" loading="lazy" allowfullscreen
                                    referrerpolicy="no-referrer-when-downgrade"></iframe>
                            <a class="store-contact-map-link" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($ctAddress) }}"
                               target="_blank" rel="noopener">Abrir en Google Maps →</a>
                        </div>
                        <style>
                            .store-contact-map{margin-top:18px}
                            .store-contact-map iframe{width:100%;height:300px;border:0;border-radius:var(--radius-md);box-shadow:var(--shadow-md);display:block;background:#e2e8f0}
                            .store-contact-map-link{display:inline-block;margin-top:10px;color:var(--primary);font-size:13px;font-weight:700;text-decoration:none}
                            .store-contact-map-link:hover{text-decoration:underline}
                        </style>
                        @endif
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

        {{-- ═══ PÁGINAS LEGALES (privacidad / términos) ═══ --}}
        @if($storeView === 'legal' && !empty($storePage))
        <section class="store-page"><div class="container legal-container">
            <nav class="catalog-breadcrumb" aria-label="Ruta de navegación"><a href="{{ \App\Support\StorefrontNavigation::resolveUrl($project, new \App\Models\StoreMenuItem(['destination_type'=>'home'])) }}">Inicio</a><span>/</span><strong>{{ $storePage->title }}</strong></nav>
            <h1 class="store-page-title">{{ $storePage->title }}</h1>
            <p class="legal-updated">Última actualización: {{ ($storePage->updated_at ?? now())->format('d/m/Y') }}</p>
            <div class="store-page-body">{{ $storePage->content['body'] ?? '' }}</div>
        </div></section>
        @endif

        {{-- ═══ LIBRO DE RECLAMACIONES (Ley N.º 29571) ═══ --}}
        @if($storeView === 'reclamaciones')
        <section class="store-page"><div class="container legal-container">
            <nav class="catalog-breadcrumb" aria-label="Ruta de navegación"><a href="{{ \App\Support\StorefrontNavigation::resolveUrl($project, new \App\Models\StoreMenuItem(['destination_type'=>'home'])) }}">Inicio</a><span>/</span><strong>Libro de Reclamaciones</strong></nav>
            <div class="lr-head">
                <div class="lr-badge" aria-hidden="true"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h11l3 3v13H5V4Z"/><path d="M16 4v3h3"/><path d="M9 11h6M9 14.5h6"/></svg></div>
                <div>
                    <h1 class="store-page-title" style="margin:0 0 4px">Libro de Reclamaciones</h1>
                    <p class="lr-sub">{{ $storeName }} — conforme al Código de Protección y Defensa del Consumidor (Ley N.º 29571)</p>
                </div>
            </div>

            @if(session('success'))
            <div class="lr-flash" role="status">{{ session('success') }} Te enviaremos la respuesta en un plazo máximo de 15 días hábiles.</div>
            @else

            @if($errors->any())
            <div class="lr-errors" role="alert"><strong>Revisa el formulario:</strong><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <form method="post" action="" class="lr-form" novalidate>
                @csrf
                <h2>1. Identificación del consumidor</h2>
                <div class="lr-grid">
                    <label>Nombre completo *<input name="consumer_name" required maxlength="160" value="{{ old('consumer_name') }}"></label>
                    <label>Tipo de documento *
                        <select name="document_type" required>
                            @foreach(['DNI','Carnet de Extranjería','RUC','Pasaporte'] as $dt)
                            <option value="{{ $dt }}" @selected(old('document_type')===$dt)>{{ $dt }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>N.º de documento *<input name="document_number" required maxlength="40" value="{{ old('document_number') }}"></label>
                    <label>Teléfono<input name="phone" maxlength="40" value="{{ old('phone') }}"></label>
                    <label>Correo electrónico *<input name="email" type="email" required maxlength="160" value="{{ old('email') }}"></label>
                    <label class="lr-full">Dirección<input name="address" maxlength="255" value="{{ old('address') }}"></label>
                </div>

                <h2>2. Identificación del bien contratado</h2>
                <div class="lr-grid">
                    <label class="lr-full">Producto o servicio *<input name="product_or_service" required maxlength="255" value="{{ old('product_or_service') }}" placeholder="Ej. Laptop HP 15 — pedido #123"></label>
                    <label>Monto reclamado (S/)<input name="amount" type="number" step="0.01" min="0" value="{{ old('amount') }}"></label>
                </div>

                <h2>3. Detalle de la reclamación</h2>
                <div class="lr-type" role="radiogroup" aria-label="Tipo">
                    <label class="lr-type-opt"><input type="radio" name="type" value="reclamo" required @checked(old('type','reclamo')==='reclamo')><span><strong>Reclamo</strong><small>Disconformidad con el producto o servicio.</small></span></label>
                    <label class="lr-type-opt"><input type="radio" name="type" value="queja" @checked(old('type')==='queja')><span><strong>Queja</strong><small>Malestar respecto a la atención al público.</small></span></label>
                </div>
                <label class="lr-area">Detalle *<textarea name="detail" required rows="4" maxlength="5000" placeholder="Describe lo ocurrido">{{ old('detail') }}</textarea></label>
                <label class="lr-area">Pedido del consumidor *<textarea name="request" required rows="3" maxlength="5000" placeholder="¿Qué solución esperas?">{{ old('request') }}</textarea></label>

                <label class="lr-terms"><input type="checkbox" name="terms" value="1" required> Declaro que la información proporcionada es verdadera y acepto la <a href="{{ route('public.privacy', $project->slug) }}">política de privacidad</a>. *</label>
                <p class="lr-note">La respuesta será remitida al correo indicado en un plazo máximo de 15 días hábiles, conforme a ley. La formulación del reclamo no impide acudir a otras vías de solución de controversias ni es requisito previo para presentar una denuncia ante INDECOPI.</p>
                <button type="submit" class="button button-primary" style="min-width:220px">Enviar registro</button>
            </form>
            @endif
        </div></section>
        @endif
    </main>

    <div class="ck-modal" x-show="checkoutOpen" x-cloak role="dialog" aria-modal="true" aria-label="Finalizar compra">
        {{-- Carrito vacío --}}
        <div x-show="cart.length===0" style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:16px">
            <div style="max-width:340px;width:100%;padding:32px 24px;text-align:center;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(15,23,42,.15)">
                <div style="font-size:52px;margin-bottom:12px">🛒</div>
                <h3 style="margin-bottom:8px;color:var(--secondary);font-size:18px;font-weight:800">Tu carrito está vacío</h3>
                <p style="margin-bottom:20px;color:#64748b;font-size:13px">Agrega productos antes de continuar.</p>
                <button class="ck-submit" @click="checkoutOpen=false">Ir al catálogo</button>
            </div>
        </div>

        <div x-show="cart.length>0">
            <div class="ck-modal-header">
                @if(!empty($logoUrl))<a class="ck-brand" href="{{ $homeUrl ?? '/' }}" aria-label="{{ $storeName ?? 'Inicio' }}"><img src="{{ $logoUrl }}" alt="{{ $storeName ?? '' }}"></a>@endif
                <button class="ck-back" type="button" @click="checkoutOpen=false"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>Volver</button>
                <h2>{{ $isQuoteOnly ? 'Tu cotización' : 'Finalizar compra' }}</h2>
                <span style="color:#64748b;font-size:13px" x-text="itemCount()+' '+(itemCount()===1?'producto':'productos')"></span>
            </div>

            <div class="ck-grid">
                {{-- IZQUIERDA: formulario --}}
                <div>
                    {{-- Éxito --}}
                    <div class="ck-success" x-show="orderSuccess" x-cloak>
                        <div style="font-size:48px">✅</div>
                        <h3>¡Pedido confirmado!</h3>
                        <p style="color:#64748b" x-text="orderSuccessMsg"></p>
                        <button class="ck-submit" style="margin-top:16px" @click="checkoutOpen=false;orderSuccess=false">Volver al inicio</button>
                    </div>

                    <div x-show="!orderSuccess">
                        <div class="ck-error" x-show="orderError" x-cloak x-text="orderError"></div>

                        <div class="ck-section">
                            <h3>Datos de contacto</h3>
                            <div class="ck-field-row">
                                <div class="ck-field"><label>Nombre *</label><input x-model="form.fname" type="text" placeholder="Tu nombre"></div>
                                @if($ckFields['fixed']['lname']['enabled'] ?? true)
                                <div class="ck-field"><label>Apellido</label><input x-model="form.lname" type="text" placeholder="Tu apellido"></div>
                                @endif
                            </div>
                            <div class="ck-field-row">
                                <div class="ck-field"><label>Celular *</label><input x-model="form.phone" type="tel" inputmode="numeric" maxlength="15" placeholder="999 999 999" @input="form.phone=$event.target.value.replace(/[^\d\s\+\-\(\)]/g,'')"></div>
                                @if($ckFields['fixed']['email']['enabled'] ?? true)
                                <div class="ck-field"><label>Email</label><input x-model="form.email" type="email" placeholder="tu@correo.com"></div>
                                @endif
                            </div>
                            @if($ckFields['fixed']['dni']['enabled'] ?? true)
                            <div class="ck-field-row">
                                <div class="ck-field"><label>Comprobante</label>
                                    <select x-model="docType">
                                        <option value="boleta">Boleta</option>
                                        <option value="factura">Factura (con RUC)</option>
                                    </select>
                                </div>
                                <div class="ck-field">
                                    <label x-text="docType==='factura' ? 'RUC *' : 'DNI (opcional)'">DNI (opcional)</label>
                                    <input x-model="form.dni" type="text" inputmode="numeric" maxlength="11" :placeholder="docType==='factura' ? '20123456789' : '12345678'">
                                </div>
                            </div>
                            @endif
                        </div>

                        @if(($ckFields['fixed']['address']['enabled'] ?? false) || $requireAddress)
                        <div class="ck-section">
                            <h3>Dirección de entrega</h3>
                            <div class="ck-field-row">
                                <div class="ck-field"><label>Departamento</label><input x-model="form.department" type="text" placeholder="Lima"></div>
                                <div class="ck-field"><label>Distrito</label><input x-model="form.district" type="text" placeholder="Miraflores"></div>
                            </div>
                            <div class="ck-field"><label>Dirección *</label><input x-model="form.address" type="text" placeholder="Av. Principal 123"></div>
                            <div class="ck-field"><label>Referencia</label><input x-model="form.address2" type="text" placeholder="Piso 2, puerta roja..."></div>
                        </div>
                        @endif

                        @if($ckFields['fixed']['notes']['enabled'] ?? true)
                        <div class="ck-section">
                            <h3>Notas del pedido</h3>
                            <div class="ck-field"><textarea x-model="form.notes" placeholder="Instrucciones especiales, horario de entrega, etc."></textarea></div>
                        </div>
                        @endif

                        <button class="ck-submit" type="button" @click="submitCheckout()" :disabled="orderLoading">
                            <span x-show="!orderLoading">{{ $isQuoteOnly ? 'Enviar cotización' : 'Realizar el pedido' }} · <span x-text="money(checkoutTotal)"></span></span>
                            <span x-show="orderLoading" x-cloak>Procesando…</span>
                        </button>
                    </div>
                </div>

                {{-- DERECHA: resumen + métodos de pago --}}
                <div x-show="!orderSuccess">
                    <div class="ck-summary">
                        <h4>Tu pedido</h4>
                        <template x-for="item in cart" :key="item.id+'-'+(item.talla||'')">
                            <div class="ck-order-item">
                                <div style="flex:1;min-width:0">
                                    <div style="font-size:13px;font-weight:600;color:var(--secondary)" x-text="item.nombre+(item.talla?(' · Talla '+item.talla):'')"></div>
                                    <div style="display:flex;align-items:center;gap:8px;margin-top:5px">
                                        <button class="ck-qtybtn" @click="decrease(item.id,item.talla)">−</button>
                                        <span style="min-width:18px;text-align:center;font-size:13px" x-text="item.cantidad"></span>
                                        <button class="ck-qtybtn" @click="increase(item.id,item.talla)">+</button>
                                    </div>
                                </div>
                                <span style="font-size:14px;font-weight:700;white-space:nowrap" x-text="money(item.precio*item.cantidad)"></span>
                            </div>
                        </template>
                        <div class="ck-sum-row"><span>Subtotal</span><span x-text="money(total())"></span></div>
                        @if($shippingEnabled)
                        <div class="ck-sum-row"><span>Envío</span><span x-text="shippingLabel"></span></div>
                        @endif
                        <div class="ck-sum-row total"><span>Total</span><span x-text="money(checkoutTotal)"></span></div>
                    </div>

                    @if($pickupEnabled)
                    {{-- Entrega: el recojo en tienda se configuraba en el Constructor pero
                         el comprador nunca podia elegirlo. --}}
                    <div class="ck-section" style="margin-top:16px">
                        <h4 style="margin:0 0 10px;font-size:14px">¿Cómo prefieres recibirlo?</h4>
                        @if($shippingEnabled)
                        <div class="ck-pay" :class="{selected:deliveryMode==='ship'}" @click="deliveryMode='ship'">
                            <div class="ck-radio"><span x-show="deliveryMode==='ship'"></span></div>
                            <div style="flex:1"><div style="font-weight:700;font-size:13px">Envío a domicilio</div></div>
                        </div>
                        @endif
                        <div class="ck-pay" :class="{selected:deliveryMode==='pickup'}" @click="deliveryMode='pickup'">
                            <div class="ck-radio"><span x-show="deliveryMode==='pickup'"></span></div>
                            <div style="flex:1"><div style="font-weight:700;font-size:13px">Recojo en tienda</div>@if($pickupNote !== '')<div style="margin-top:2px;color:#64748b;font-size:11.5px">{{ $pickupNote }}</div>@endif</div>
                        </div>
                    </div>
                    @endif

                    @if(!$isQuoteOnly && $payManualEnabled && (($payYapeNumber && in_array('yape',$payManualMethods)) || count($payBankActive)))
                    <div class="ck-section" style="margin-top:16px">
                        <h3>Método de pago</h3>
                        @if($payYapeNumber && in_array('yape',$payManualMethods))
                        <div class="ck-pay" :class="{selected:payMethod==='yape'}" @click="payMethod='yape'">
                            <div class="ck-radio"><span x-show="payMethod==='yape'"></span></div>
                            <div style="flex:1"><div style="font-weight:700;font-size:13px">Paga con Yape @if($payYapeNote !== '')<span style="font-weight:600;font-size:11.5px;color:#64748b">({{ $payYapeNote }})</span>@endif</div><div style="font-size:11px;color:#94a3b8">Escanea el QR o usa el número</div></div>
                            <div style="padding:3px 8px;background:#6c1eb0;border-radius:5px;color:#fff;font-size:11px;font-weight:800">yape</div>
                        </div>
                        @php
                            $yapeVcard = rawurlencode("BEGIN:VCARD\r\nVERSION:3.0\r\nFN:Yape {$storeName}\r\nTEL;TYPE=CELL:+51{$payYapeNumber}\r\nEND:VCARD");
                        @endphp
                        <div x-show="payMethod==='yape'" x-cloak style="margin:0 0 8px;padding:16px;background:linear-gradient(135deg,#6c1eb0,#8b2fc9);border-radius:12px;color:#fff">
                            <p style="margin:0 0 12px;font-size:12px;font-weight:600;line-height:1.5">Escanea nuestro QR con tu aplicación Yape, o agrega nuestro número a tus contactos y realiza tu pago con Yape.</p>
                            <div style="display:flex;gap:18px;align-items:center;justify-content:center;flex-wrap:wrap">
                                @if($payYapeQr)
                                <div style="text-align:center">
                                    <div style="background:#fff;padding:8px;border-radius:12px;display:inline-block"><img src="{{ $assetUrl($payYapeQr) ?: $payYapeQr }}" alt="QR Yape de {{ $storeName }}" style="width:150px;height:150px;object-fit:contain;display:block"></div>
                                    @if(trim($payYapeName) !== '')<div style="margin-top:8px;font-size:12px;font-weight:800;letter-spacing:.03em;text-transform:uppercase">{{ $payYapeName }}</div>@endif
                                </div>
                                @endif
                                <div style="text-align:center">
                                    <div style="opacity:.9;font-size:12px;font-weight:700">Celular Yape:</div>
                                    <div style="font-size:24px;font-weight:800;letter-spacing:.5px;margin:2px 0 10px">{{ $payYapeNumber }}</div>
                                    <label style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;background:#00c9a7;color:#fff;border-radius:8px;font-size:13px;font-weight:800;cursor:pointer">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21.4 11.05 12.3 20.2a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.6 5.66l-9.1 9.1a2 2 0 0 1-2.9-2.9l8.5-8.4"/></svg>
                                        <span x-text="proofUrl ? 'Comprobante adjuntado \u2713' : (proofUploading ? 'Subiendo\u2026' : 'Adjuntar pago')"></span>
                                        <input type="file" accept="image/*" style="display:none" @change="uploadProof($event)">
                                    </label>
                                    <div x-show="proofName" x-cloak style="margin-top:7px;font-size:11px;opacity:.92" x-text="proofName"></div>
                                    <div style="margin-top:9px"><a href="data:text/vcard;charset=utf-8,{{ $yapeVcard }}" download="yape-{{ $project->slug }}.vcf" style="color:#fff;font-size:11.5px;font-weight:700;text-decoration:underline;text-underline-offset:2px;opacity:.92">Añadir a contacto</a></div>
                                    @if(!$payYapeQr && trim($payYapeName) !== '')<div style="margin-top:10px;font-size:11.5px;opacity:.9;font-weight:700">{{ $payYapeName }}</div>@endif
                                </div>
                            </div>
                        </div>
                        @endif
                        @foreach($payBankActive as $bk => $bank)
                        <div class="ck-pay" :class="{selected:payMethod==='bank_{{ $bk }}'}" @click="payMethod='bank_{{ $bk }}'">
                            <div class="ck-radio"><span x-show="payMethod==='bank_{{ $bk }}'"></span></div>
                            <div style="flex:1"><div style="font-weight:700;font-size:13px">Transferencia {{ $bank['label'] }}</div></div>
                            <div style="width:10px;height:10px;border-radius:3px;background:{{ $bank['color'] }}"></div>
                        </div>
                        <div x-show="payMethod==='bank_{{ $bk }}'" x-cloak style="margin:0 0 8px;padding:12px 14px;background:var(--surface);border:1px solid var(--border);border-radius:9px;font-size:12px;color:#475569">
                            <div style="white-space:pre-line">{{ $bank['details'] }}</div>
                            @if(!empty($bank['cci']))
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:9px;padding-top:9px;border-top:1px dashed var(--border)">
                                <span style="font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8">CCI</span>
                                <b style="font-size:13px;color:#172033;letter-spacing:.02em">{{ $bank['cci'] }}</b>
                                <button type="button" style="padding:4px 10px;font-size:10.5px;font-weight:800;color:var(--primary);background:none;border:1px solid var(--border);border-radius:6px;cursor:pointer"
                                        @click.prevent="navigator.clipboard&&navigator.clipboard.writeText('{{ $bank['cci'] }}');$el.textContent='Copiado'">Copiar</button>
                            </div>
                            @endif
                        </div>
                        @endforeach
                        <div x-show="payMethod && payMethod.startsWith('bank_')" x-cloak style="margin:0 0 8px">
                            <label style="display:inline-flex;align-items:center;gap:7px;padding:10px 16px;background:var(--primary);color:#fff;border-radius:8px;font-size:13px;font-weight:800;cursor:pointer">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21.4 11.05 12.3 20.2a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.6 5.66l-9.1 9.1a2 2 0 0 1-2.9-2.9l8.5-8.4"/></svg>
                                <span x-text="proofUrl ? 'Comprobante adjuntado \u2713' : (proofUploading ? 'Subiendo\u2026' : 'Adjuntar pago')"></span>
                                <input type="file" accept="image/*" style="display:none" @change="uploadProof($event)">
                            </label>
                            <span x-show="proofName" x-cloak style="margin-left:9px;font-size:11px;color:#475569" x-text="proofName"></span>
                        </div>
                    </div>
                    @elseif(!$isQuoteOnly)
                    {{-- Sin medios de pago cargados el bloque desaparecia y el
                         cliente llegaba al final sin saber como pagar. Se explica
                         el flujo real: el pedido se coordina por WhatsApp. --}}
                    <div class="ck-section">
                        <h3>¿Cómo pago?</h3>
                        <p class="ck-paynote">
                            Al confirmar tu pedido te escribimos por WhatsApp para coordinar
                            el pago y la entrega. No se cobra nada en este paso.
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

            {{-- Pie del checkout: mantiene la identidad de la tienda (el modal
                 cubre header y footer reales). Solo datos ya configurados. --}}
            <div class="ck-foot">
                <div class="ck-foot-inner">
                    <span>© {{ date('Y') }} {{ $storeName ?? '' }}</span>
                    <div class="ck-foot-links">
                        @if(!empty($whatsapp))<a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Z"/></svg>WhatsApp</a>@endif
                        @if(!empty($footerAddress))<span>{{ $footerAddress }}</span>@endif
                        <a href="{{ $homeUrl ?? '/' }}">Volver a la tienda</a>
                    </div>
                </div>
            </div>
    </div>


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

    {{-- ═══ PÁGINA DE CARRITO (esqueleto ecommerce, estilo computienda) ═══ --}}
    <div class="cartpage" x-show="cartPageOpen" x-cloak role="dialog" aria-modal="true" aria-label="Tu carrito">
        <div class="cartpage-inner">
            <button class="cartpage-back" type="button" @click="cartPageOpen=false;document.body.style.overflow=''"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>Seguir comprando</button>
            {{-- h2, no h1: el h1 de la pagina es el titulo del contenido, no el carrito. --}}
            <h2>Tu carrito</h2>
            <p style="color:#64748b;font-size:13px;margin:0 0 22px" x-text="itemCount()+' '+(itemCount()===1?'producto':'productos')"></p>

            <template x-if="cart.length===0">
                <div class="cartpage-empty">
                    <div style="font-size:56px;margin-bottom:12px">🛒</div>
                    <h3 style="margin-bottom:8px;color:var(--secondary);font-size:18px;font-weight:800">Tu carrito está vacío</h3>
                    <p style="margin-bottom:20px;color:#64748b;font-size:13px">Agrega productos para comenzar.</p>
                    <button class="ck-submit" style="max-width:240px;margin:0 auto" @click="cartPageOpen=false;document.body.style.overflow=''">Explorar catálogo</button>
                </div>
            </template>

            <template x-if="cart.length>0">
                <div class="cartpage-grid">
                    <div class="cartpage-lines">
                        <template x-for="item in cart" :key="item.id+'-'+(item.talla||'')">
                            <div class="cart-line">
                                <div class="cart-line-thumb">
                                    <template x-if="item.imagen"><img :src="item.imagen" :alt="item.nombre"></template>
                                    <template x-if="!item.imagen"><svg class="catalog-card-placeholder" style="width:34px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><path d="m4 7 8-4 8 4-8 4-8-4Z"></path></svg></template>
                                </div>
                                <div style="min-width:0">
                                    <div class="cart-line-cat" x-text="item.categoria" x-show="item.categoria"></div>
                                    <div class="cart-line-name" x-text="item.nombre+(item.talla?(' · Talla '+item.talla):'')"></div>
                                    <div class="cart-line-ctl">
                                        <div class="pdp-qty" style="border-radius:8px">
                                            <button type="button" @click="decrease(item.id,item.talla)" style="width:36px;height:38px">−</button>
                                            <span x-text="item.cantidad" style="min-width:28px"></span>
                                            <button type="button" @click="increase(item.id,item.talla)" style="width:36px;height:38px">+</button>
                                        </div>
                                        <button class="cart-line-remove" type="button" @click="remove(item.id,item.talla)">Eliminar</button>
                                    </div>
                                </div>
                                <div class="cart-line-price" x-text="money(item.precio*item.cantidad)"></div>
                            </div>
                        </template>
                    </div>

                    <div class="cartpage-summary">
                        <h4>Resumen del pedido</h4>
                        <div class="ck-sum-row"><span>Subtotal</span><span x-text="money(total())"></span></div>
                        @if($shippingEnabled)
                        <div class="ck-sum-row"><span>Envío</span><span x-text="shippingLabel"></span></div>
                        @if($shippingFreeFrom > 0)
                        <p style="margin:8px 0 0;color:#64748b;font-size:12px" x-show="total() < {{ $shippingFreeFrom }}">Agrega <strong x-text="money({{ $shippingFreeFrom }}-total())"></strong> más para envío gratis.</p>
                        @endif
                        @endif
                        <div class="ck-sum-row total"><span>Total</span><span x-text="money(checkoutTotal)"></span></div>
                        @php $cpFree = (float) ($settings['shipping_free_from'] ?? 0); @endphp
                        @if($cpFree > 0 && !$hidePrices)
                        <div class="cart-ship" style="margin-top:14px" x-show="total() < {{ $cpFree }}">
                            <span>Te faltan <b x-text="money({{ $cpFree }}-total())"></b> para el envío gratis</span>
                            <span class="cart-ship-bar"><i :style="'width:'+Math.min(100,(total()/{{ $cpFree }})*100)+'%'"></i></span>
                        </div>
                        <div class="cart-ship is-ok" style="margin-top:14px" x-show="total() >= {{ $cpFree }}" x-cloak>✓ ¡Tienes envío gratis!</div>
                        @endif
                        <button class="ck-submit" style="margin-top:18px" @click="cartPageOpen=false;openCheckout()">{{ $isQuoteOnly ? ($settings['btn_quote_text'] ?? 'Cotizar') : 'Finalizar compra' }}</button>
                        <button type="button" class="cart-keep cart-keep--foot" @click="cartPageOpen=false">{{ trim((string) ($settings['cart_keep_shopping_text'] ?? 'Seguir comprando')) }}</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- FOOTER: variante estructural (F1). classic = markup original --}}
@php $bxTicker = trim((string) ($settings['ticker_text'] ?? '')); @endphp
@if($bxTicker !== '')
{{-- Franja animada: va en el flujo, justo encima del pie (no flota ni tapa contenido). --}}
<div class="bx-ticker {{ (string) ($settings['ticker_force_motion'] ?? '0') !== '0' ? 'bx-force-motion' : '' }}" style="--bx-ticker-speed:{{ max(10, min(90, (int) ($settings['ticker_speed'] ?? 28))) }}s" aria-hidden="true">
    <div class="bx-ticker-track">
        @for($i = 0; $i < 8; $i++)<span>{{ $bxTicker }}</span>@endfor
    </div>
</div>
@endif
    @include(\App\Support\StorefrontLayoutPacks::view($settings, 'footers') ?? 'storefront.partials.footers.classic')

    {{-- ═══ MENÚ MÓVIL (drawer) ═══ --}}
    <div class="mobile-nav-layer" x-show="mobileNav" x-cloak @keydown.escape.window="mobileNav=false" role="dialog" aria-modal="true" aria-label="Menú">
        <div class="mobile-nav-overlay" @click="mobileNav=false"></div>
        <aside class="mobile-nav-panel">
            <div class="mobile-nav-head">
                <strong>{{ $storeName }}</strong>
                <button class="icon-button" type="button" @click="mobileNav=false" aria-label="Cerrar menú"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"></path></svg></button>
            </div>
            @if(!empty($catalogProfiles) && $catalogProfiles->count())
            <div class="mobile-nav-profiles">
                <a class="profile-chip {{ empty($activeProfile) ? 'is-active' : '' }}" href="{{ \App\Support\StorefrontNavigation::shopUrl($project) }}">Todo</a>
                @foreach($catalogProfiles as $cp)
                    <a class="profile-chip {{ (!empty($activeProfile) && $activeProfile->id === $cp->id) ? 'is-active' : '' }}"
                       @if($cp->primary_color) style="--chip-color:{{ $cp->primary_color }}" @endif
                       href="{{ \App\Support\StorefrontNavigation::profileUrl($project, $cp->slug) }}"><span class="profile-dot" aria-hidden="true"></span>{{ $cp->menu_label ?: $cp->name }}</a>
                @endforeach
            </div>
            @endif
            <nav class="mobile-nav-links">
                @if($menuRoots->count())
                    @foreach($menuRoots as $mItem)
                        @if(in_array($mItem->destination_type, ['category','subcategory']) && $mItem->destination_id)
                            <a href="{{ $shopBase }}?category={{ (int) $mItem->destination_id }}" @click="mobileNav=false">{{ $mItem->label }}</a>
                        @else
                            <a href="{{ \App\Support\StorefrontNavigation::resolveUrl($project, $mItem) }}" @click="mobileNav=false" @if($mItem->target === '_blank') target="_blank" rel="noopener" @endif>{{ $mItem->label }}</a>
                        @endif
                    @endforeach
                @endif
                @if($navCategories->count())
                    @php
                        // En "Todo" con colecciones activas, cada raíz se muestra como grupo con sus
                        // subcategorías (los chips de arriba ya llevan a la colección: no se repiten).
                        $groupMobileCats = empty($activeProfile) && !empty($catalogProfiles) && $catalogProfiles->count();
                        $mobileLoose = $groupMobileCats ? $navCategories->filter(fn ($c) => !$c->children->count())->values() : $navCategories;
                    @endphp
                    @if($groupMobileCats)
                        @foreach($navCategories as $cat)
                            @if($cat->children->count())
                                <div class="mobile-nav-section">{{ $cat->name }}</div>
                                @foreach($cat->children as $sub)
                                    <a href="{{ $shopBase }}?category={{ (int) $sub->id }}" @click="mobileNav=false">{{ $sub->name }}</a>
                                @endforeach
                            @endif
                        @endforeach
                    @endif
                    @if($mobileLoose->count())
                        <div class="mobile-nav-section">Categorías</div>
                        @foreach($mobileLoose as $cat)
                            <a href="{{ $shopBase }}?category={{ (int) $cat->id }}" @click="mobileNav=false">{{ $cat->name }}</a>
                        @endforeach
                    @endif
                @endif
            </nav>
        </aside>
    </div>


    {{-- ═══ CHECKOUT (modal fullscreen, esqueleto ecommerce) ═══ --}}

    {{-- ═══ VISTA RÁPIDA (quick view) ═══ --}}
    <div class="qv-layer" x-show="qv" x-cloak @keydown.escape.window="closeQuickView()" role="dialog" aria-modal="true" aria-label="Vista rápida del producto">
        <div class="qv-overlay" @click="closeQuickView()"></div>
        <template x-if="qv">
        <div class="qv-box">
            <button class="qv-close" type="button" @click="closeQuickView()" aria-label="Cerrar">✕</button>
            <div class="qv-head">
                <div class="qv-media" style="cursor:zoom-in;position:relative" @click="qv.image && window.__lightbox && window.__lightbox(qv.image)">
                    <span class="pdp-zoom-hint" x-show="qv.image" style="width:28px;height:28px;bottom:6px;right:6px" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3M11 8v6M8 11h6"/></svg></span>
                    <template x-if="qv.image"><img :src="qv.image" :alt="qv.name"></template>
                    <template x-if="!qv.image"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><path d="m4 7 8-4 8 4-8 4-8-4Z"></path></svg></template>
                </div>
                <div class="qv-info">
                    <div class="qv-cat" x-text="qv.category" x-show="qv.category"></div>
                    <a class="qv-name" :href="qv.url || '#'" x-text="qv.name"></a>
                    @if($hidePrices)
                    <div class="qv-prices"><span class="qv-price">Precio a solicitud</span></div>
                    @else
                    <div class="qv-prices">
                        <span class="qv-price" x-text="money(qv.price)"></span>
                        <template x-if="qv.comparePrice && qv.comparePrice>qv.price">
                            <span class="qv-compare" x-text="money(qv.comparePrice)"></span>
                        </template>
                        <template x-if="qv.comparePrice && qv.comparePrice>qv.price">
                            <span class="qv-percent" x-text="'-'+Math.round((1-qv.price/qv.comparePrice)*100)+'%'"></span>
                        </template>
                    </div>
                    @if($wholesale)<div class="wh-buy" x-show="qv.wholesalePrice" x-cloak><span class="wh-buy-info"><b x-text="'MAYORISTA '+money(qv.wholesalePrice)"></b><small x-text="'Mín. '+qv.wholesaleMinQty+' '+qv.wholesaleUnit"></small></span><button type="button" class="wh-buy-btn" @click="addWholesale(qv.id,qv.name,qv.wholesalePrice,qv.wholesaleMinQty,qv.image,qv.category)">+ Agregar por mayor</button></div>@endif
                    @endif
                    <div class="qv-low" x-show="qv.stock!==null && qv.stock!==undefined && qv.stock>0 && qv.stock<=10" x-cloak x-text="'⚡ ¡Solo quedan '+qv.stock+' unidades!'"></div>
                </div>
            </div>
            <div class="qv-foot">
                <div class="qv-out" x-show="qv.stock===0" x-cloak>⚠️ Producto agotado temporalmente</div>
                <div class="qv-sizes" x-show="qv.sizes&&qv.sizes.length" x-cloak>
                    <span class="qv-sizes-label">Talla:</span>
                    <div class="qv-size-list">
                        <template x-for="sz in (qv.sizes||[])" :key="sz">
                            <button type="button" class="qv-size" :class="qvSize===sz&&'on'" @click="qvSize=sz;qvSizeError=false" x-text="sz"></button>
                        </template>
                    </div>
                    <p class="qv-size-req" x-show="qvSizeError" x-cloak role="alert">Elige una talla para continuar</p>
                </div>
                <button class="qv-add" type="button" x-show="qv.stock!==0" @click="if(qv.sizes&&qv.sizes.length&&!qvSize){qvSizeError=true}else{add(qv.id,qv.name,qv.price,qv.image,qv.category,qvSize||'');closeQuickView()}">
                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"></path></svg>
                    <span x-text="cartButtonText"></span>
                </button>
                <a class="qv-view" :href="qv.url" x-show="qv.url">Ver producto completo</a>
            </div>
        </div>
        </template>
    </div>

    <div class="catalog-filter-layer" x-show="filterDrawerOpen" x-cloak @keydown.escape.window="filterDrawerOpen=false" role="dialog" aria-modal="true" aria-label="Filtros del catálogo">
        <div class="catalog-filter-overlay" @click="filterDrawerOpen=false"></div>
        <aside class="catalog-filter-drawer">
            <div class="catalog-filter-drawer-head"><strong>Filtrar productos</strong><button class="icon-button" type="button" @click="filterDrawerOpen=false" aria-label="Cerrar filtros"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"></path></svg></button></div>
            <div class="catalog-filter-drawer-body"><x-computienda.catalog-filters :categories="$navCategories" :catalog-products="$catalogProducts" filter-scope="mobile" /></div>
            <div class="catalog-filter-drawer-foot"><button type="button" @click="clearAllFilters()">Limpiar</button><button type="button" @click="filterDrawerOpen=false">Ver <span x-text="catalogProducts.length"></span> productos</button></div>
        </aside>
    </div>

    {{-- CARRITO: variante estructural (F1). classic = drawer original --}}
    @include(\App\Support\StorefrontLayoutPacks::view($settings, 'carts') ?? 'storefront.partials.carts.classic')

    <script>
        // Fase 2A: solo la primera página; el resto llega vía AJAX (catalogBrowser).
        const COMPUTIENDA_PRODUCTS = @json(($storeView ?? 'home') === 'tienda' ? ($catalogCards ?? []) : []);
        const COMPUTIENDA_MAX_PRICE = @json($catalogMaxPrice ?? 0);
        const COMPUTIENDA_CATEGORIES = @json($catalogCategoryIndex);

        function catalogBrowser(cfg){
            return {
                endpoint: cfg.endpoint, products: [], total: cfg.total ?? 0,
                page: 1, hasMore: cfg.hasMore ?? false, loading: false, error: false,
                serverRendered: true, sort: new URLSearchParams(location.search).get('sort') || 'recommended',
                _ctrl: null, _t: null, _ids: new Set(),
                init(){
                    window.addEventListener('popstate', () => { this.error=false; this.fetchPage(1,false); });
                    window.addEventListener('catalog:filters-changed', () => this.applyFilters());
                },
                storeData(){
                    try { return window.Alpine ? window.Alpine.$data(document.body) : null; } catch(e){ return null; }
                },
                buildQuery(page){
                    const u = new URLSearchParams();
                    const store = this.storeData();
                    if (store){
                        if (store.query) u.set('q', store.query);
                        // Multi-selección: subcategorías si las hay, si no las categorías raíz.
                        const cats = (store.filterSubCats && store.filterSubCats.length) ? store.filterSubCats : (store.filterCats || []);
                        cats.forEach(id=>u.append('category[]', id));
                        if (store.filterOnSale) u.set('sale', '1');
                        if (store.priceMin>0) u.set('min_price', store.priceMin);
                        if (store.priceMax>0 && store.maxPrice && store.priceMax<store.maxPrice) u.set('max_price', store.priceMax);
                    }
                    u.set('sort', this.sort);
                    u.set('page', page);
                    u.set('format', 'json');
                    return u;
                },
                syncUrl(){
                    const u = this.buildQuery(1); u.delete('format'); u.delete('page');
                    history.pushState({}, '', location.pathname + (u.toString()?('?'+u.toString()):''));
                },
                async fetchPage(page, append){
                    if (this._ctrl) this._ctrl.abort();
                    this._ctrl = new AbortController();
                    this.loading = true; this.error = false;
                    try{
                        const res = await fetch(this.endpoint + '?' + this.buildQuery(page).toString(), {signal:this._ctrl.signal, headers:{'Accept':'application/json'}});
                        if(!res.ok) throw new Error('http');
                        const data = await res.json();
                        this.serverRendered = false;
                        if(!append){ this.products = []; this._ids.clear(); }
                        for(const p of data.products){ if(!this._ids.has(p.id)){ this._ids.add(p.id); this.products.push(p); } }
                        this.total = data.total; this.page = data.current_page; this.hasMore = data.has_more;
                    }catch(e){ if(e.name!=='AbortError'){ this.error = true; } }
                    finally{ this.loading = false; }
                },
                applyFilters(sync=true){
                    if(this._t) clearTimeout(this._t);
                    this._t = setTimeout(()=>{ if(sync) this.syncUrl(); this.fetchPage(1,false); }, 300);
                },
                loadMore(){ if(this.hasMore && !this.loading) this.fetchPage(this.page+1,true); },
            };
        }

        function professionalStore(){
            const key=@js('bixo_store_cart_'.$project->id);
            return {
                cartOpen:false,
                cartPageOpen:false,
                filterDrawerOpen:false,
                mobileNav:false,
                searchOpen:false,
                checkoutOpen:false, docType:'boleta',
                payMethod:'',
                deliveryMode:'{{ $shippingEnabled ? 'ship' : 'pickup' }}',
                orderLoading:false,
                orderError:'',
                orderSuccess:false,
                orderSuccessMsg:'',
                form:{fname:'',lname:'',phone:'',email:'',dni:'',department:'',district:'',address:'',address2:'',notes:''},
                query:'',
                // Búsqueda predictiva (sugerencias bajo el buscador del header)
                suggest:[], suggestOpen:false, _sCtrl:null,
                async fetchSuggest(){
                    const q=(this.query||'').trim();
                    if(q.length<2){ this.suggest=[]; this.suggestOpen=false; return; }
                    if(this._sCtrl) this._sCtrl.abort();
                    this._sCtrl=new AbortController();
                    try{
                        const res=await fetch(@js($shopUrl)+'?format=json&q='+encodeURIComponent(q),{signal:this._sCtrl.signal,headers:{'Accept':'application/json'}});
                        if(!res.ok) return;
                        const d=await res.json();
                        this.suggest=(d.products||[]).slice(0,6);
                        this.suggestOpen=this.suggest.length>0;
                    }catch(e){}
                },
                goSearch(){
                    const q=(this.query||'').trim(); if(!q) return;
                    const base=@js($shopUrl);
                    if(location.href.split('?')[0].replace(/\/$/,'')===base.replace(/\/$/,'')){ this.suggestOpen=false; return; }
                    window.location=base+'?q='+encodeURIComponent(q);
                },
                filterCats:[],
                filterSubCats:[],
                filterInStock:false,
                filterOnSale:false,
                priceMin:0,
                priceMax:1000,
                maxPrice:1000,
                sortBy:'default',
                catSearch:'',
                catOpen:{},
                soldOutText:@js($settings['catalog_badge_sold_out'] ?? 'Agotado'),
                cartButtonText:@js($quoteMode ? $quoteBtnText : $cartText),
                cart:[],
                qv:null, qvSize:null, qvSizeError:false,

                init(){
                    try {
                        const saved=JSON.parse(localStorage.getItem(key)||'[]');
                        this.cart=Array.isArray(saved)?saved:[];
                    } catch(e) {
                        this.cart=[];
                    }
                    this.maxPrice=Math.ceil((Number(COMPUTIENDA_MAX_PRICE)||1000)/10)*10;
                    this.priceMax=this.maxPrice;
                    this.$watch('cart',value=>localStorage.setItem(key,JSON.stringify(value)),{deep:true});
                    // Fase 2A: al cambiar cualquier filtro, notificar al catalogBrowser (server-side).
                    ['query','filterOnSale','filterInStock','priceMin','priceMax'].forEach(f=>{
                        this.$watch(f, ()=>window.dispatchEvent(new CustomEvent('catalog:filters-changed')));
                    });
                    // Multi-selección: los arrays de categorías se vigilan en profundidad.
                    ['filterCats','filterSubCats'].forEach(f=>{
                        this.$watch(f, ()=>{ this.syncCategoryUrl(); window.dispatchEvent(new CustomEvent('catalog:filters-changed')); });
                    });
                    // Aplicar filtro desde la URL (?category=ID o ?category[]=..) al llegar desde el mega-menú
                    try {
                        const params = new URLSearchParams(location.search);
                        const cids = params.getAll('category[]').concat(params.getAll('category'));
                        cids.forEach(cid=>this.applyCategoryFromUrl(String(cid)));
                    } catch(e){}
                },
                // Detecta si el id es categoría raíz o subcategoría y lo AÑADE a la selección
                applyCategoryFromUrl(id){
                    if(!id)return;
                    const cat = COMPUTIENDA_CATEGORIES.find(c=>String(c.id)===String(id));
                    if(cat && cat.parentId){ // es subcategoría
                        if(!this.filterSubCats.includes(String(id)))this.filterSubCats.push(String(id));
                        this.catOpen[cat.parentId]=true;
                    } else { // categoría raíz (o id desconocido, se trata como raíz)
                        if(!this.filterCats.includes(String(id)))this.filterCats.push(String(id));
                        this.catOpen[id]=true;
                    }
                },
                clearCategoryFilters(){ this.filterCats=[]; this.filterSubCats=[]; },

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
                    // Alterna la categoría en la selección múltiple.
                    const s=String(id||'');
                    if(!s){ this.filterCats=[]; return; }
                    if(this.filterCats.includes(s))this.filterCats=this.filterCats.filter(x=>x!==s);
                    else { this.filterCats.push(s); this.catOpen[s]=true; }
                },
                selectSubCategory(parentId,id){
                    const s=String(id||'');
                    if(!s)return;
                    if(this.filterSubCats.includes(s))this.filterSubCats=this.filterSubCats.filter(x=>x!==s);
                    else this.filterSubCats.push(s);
                    if(parentId)this.catOpen[String(parentId)]=true;
                },
                syncCategoryUrl(){
                    try {
                        const url=new URL(window.location.href);
                        url.searchParams.delete('category');
                        url.searchParams.delete('category[]');
                        this.filterCats.concat(this.filterSubCats).forEach(id=>url.searchParams.append('category[]',String(id)));
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
                    this.filterCats=[];
                    this.filterSubCats=[];
                    this.filterInStock=false;
                    this.filterOnSale=false;
                    this.priceMin=0;
                    this.priceMax=this.maxPrice;
                    this.sortBy='default';
                    this.catSearch='';
                    this.syncCategoryUrl();
                },
                isDiscounted(product){
                    return Number(product.comparePrice)>Number(product.price);
                },
                discountLabel(product){
                    return this.isDiscounted(product) ? '-'+Math.round((1-Number(product.price)/Number(product.comparePrice))*100)+'%' : '';
                },

                get selectedCats(){ return this.filterCats.concat(this.filterSubCats); },
                get activeFilterLabel(){
                    const sel=this.selectedCats;
                    if(sel.length===1)return this.categoryName(sel[0]);
                    if(sel.length>1)return sel.length+' categorías';
                    return 'Todos los productos';
                },
                get catalogCount(){
                    // Sin filtros manda el total real del catalogo (la vista carga
                    // por paginas); con filtros, lo que de verdad quedo.
                    return this.hasActiveFilters ? this.catalogProducts.length : {{ $catalogProducts->count() }};
                },
                get catalogSubtitle(){
                    const n=this.catalogProducts.length;
                    if(this.selectedCats.length===1) return 'Todo lo que tenemos en '+this.categoryName(this.selectedCats[0])+'.';
                    if(this.query) return 'Resultados para "'+this.query+'".';
                    return 'Filtra por categoría, precio y disponibilidad para encontrar la mejor opción.';
                },
                get hasActiveFilters(){
                    return Boolean(this.query || this.selectedCats.length || this.filterInStock || this.filterOnSale || this.priceMin>0 || this.priceMax<this.maxPrice);
                },
                get activeFilterCount(){
                    return this.selectedCats.length+Number(this.filterInStock)+Number(this.filterOnSale)+Number(this.priceMin>0||this.priceMax<this.maxPrice)+Number(Boolean(this.query));
                },
                get catalogProducts(){
                    let products=COMPUTIENDA_PRODUCTS.slice();
                    if(this.filterSubCats.length){
                        const subs=this.filterSubCats.map(String);
                        products=products.filter(product=>subs.includes(String(product.categoryId)));
                    } else if(this.filterCats.length){
                        const cats=this.filterCats.map(String);
                        products=products.filter(product=>cats.includes(String(product.categoryId))||cats.includes(String(product.parentId)));
                    }
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

                // ── PRICING: fuente unica de verdad ──────────────────────────
                // Devuelve los tramos de precio de un producto, de menor a mayor
                // cantidad. Hoy son 2 (minorista / mayorista) pero la forma admite
                // N tramos sin rehacer la tarjeta ni el carrito.
                priceTiers(p){
                    const base=Number(p.price ?? p.precio ?? 0);
                    const tiers=[{min:1,price:base,label:'Por unidad'}];
                    const wp=Number(p.wholesalePrice ?? p.wholesale_price ?? 0);
                    const wq=Math.max(1,Number(p.wholesaleMinQty ?? p.wholesale_min_qty ?? 1)||1);
                    if(wp>0 && wq>1) tiers.push({min:wq,price:wp,label:'Desde '+wq+' unid.',wholesale:true});
                    else if(wp>0 && wp<base) tiers.push({min:1,price:wp,label:'Precio especial',wholesale:true});
                    return tiers.sort((a,b)=>a.min-b.min);
                },
                // Tramo aplicable a una cantidad concreta.
                priceTierFor(p,qty){
                    const q=Math.max(1,Number(qty)||1); let t=this.priceTiers(p)[0];
                    for(const c of this.priceTiers(p)) if(q>=c.min) t=c;
                    return t;
                },
                // Precio unitario efectivo y subtotal: lo usan tarjeta y carrito.
                effectivePrice(p,qty){ return Number(this.priceTierFor(p,qty).price)||0 },
                lineSubtotal(p,qty){ return this.effectivePrice(p,qty)*Math.max(1,Number(qty)||1) },
                isWholesaleQty(p,qty){ return !!this.priceTierFor(p,qty).wholesale },
                nextTier(p,qty){ const q=Math.max(1,Number(qty)||1); return this.priceTiers(p).find(t=>t.min>q)||null },
                // Alta al carrito respetando el tramo (modalidad automatica).
                addSmart(p,qty){
                    const q=Math.max(1,Number(qty)||1);
                    const t=this.priceTierFor(p,q);
                    const talla=t.wholesale?'por mayor':'';
                    const nombre=(p.name||p.nombre||'')+(t.wholesale?' (por mayor)':'');
                    const id=p.id, row=this.cart.find(i=>String(i.id)===String(id)&&(i.talla||'')===talla);
                    if(row){ row.cantidad+=q; row.precio=Number(t.price); }
                    else this.cart.push({id,nombre,precio:Number(t.price),cantidad:q,imagen:p.image||p.imagen||'',categoria:p.category||p.categoria||'',talla});
                    this.cartOpen=true;
                },
                add(id,nombre,precio,imagen,categoria,talla){
                    talla=talla||'';
                    const row=this.cart.find(item=>String(item.id)===String(id)&&(item.talla||'')===talla);
                    if(row)row.cantidad++;
                    else this.cart.push({id,nombre,precio:Number(precio),cantidad:1,imagen:imagen||'',categoria:categoria||'',talla});
                    this.cartOpen=true;
                },
                // Compra POR MAYOR: línea propia en el carrito (usa la dimensión "talla"
                // como discriminador) al precio mayorista y arrancando en el mínimo.
                addWholesale(id,nombre,precio,cantidad,imagen,categoria){
                    // 'cantidad' llega del selector de la tarjeta; si no viene, cae al minimo (1).
                    const talla='por mayor', qty=Math.max(1,Number(cantidad)||1);
                    const row=this.cart.find(item=>String(item.id)===String(id)&&(item.talla||'')===talla);
                    if(row)row.cantidad+=qty;
                    else this.cart.push({id,nombre:nombre+' (por mayor)',precio:Number(precio),cantidad:qty,imagen:imagen||'',categoria:categoria||'',talla});
                    this.cartOpen=true;
                },
                _line(id,talla){return this.cart.find(item=>String(item.id)===String(id)&&(item.talla||'')===(talla||''))},
                increase(id,talla){const row=this._line(id,talla);if(row)row.cantidad++},
                decrease(id,talla){const row=this._line(id,talla);if(!row)return;row.cantidad--;if(row.cantidad<=0)this.remove(id,talla)},
                remove(id,talla){this.cart=this.cart.filter(item=>!(String(item.id)===String(id)&&(item.talla||'')===(talla||'')))},
                itemCount(){return this.cart.reduce((sum,item)=>sum+Number(item.cantidad||0),0)},
                total(){return this.cart.reduce((sum,item)=>sum+Number(item.precio||0)*Number(item.cantidad||0),0)},
                money(value){return @js($currency)+' '+Number(value||0).toLocaleString('es-PE',{minimumFractionDigits:2,maximumFractionDigits:2})},
                // En móvil, tocar la tarjeta abre la vista previa (como ecommerce) en vez de navegar.
                qvMobile(ev, p){
                    if(window.matchMedia('(max-width:640px)').matches){ ev.preventDefault(); this.openQuickView(p); }
                },
                openQuickView(product){
                    this.qvSize=null; this.qvSizeError=false;
                    if(!product)return;
                    this.qv={
                        id:product.id,
                        name:product.name||'',
                        category:product.category||'',
                        image:product.image||'',
                        price:Number(product.price)||0,
                        comparePrice:Number(product.comparePrice)||0,
                        stock:(product.stock===null||product.stock===undefined)?null:Number(product.stock),
                        url:product.url||''
                    };
                    document.body.style.overflow='hidden';
                },
                closeQuickView(){this.qv=null;document.body.style.overflow=''},

                // ═══ Checkout ═══
                get shippingCost(){
                    @if($shippingEnabled)
                    const free = {{ $shippingFreeFrom }};
                    if(free>0 && this.total()>=free) return 0;
                    return {{ $shippingCost }};
                    @else
                    return 0;
                    @endif
                },
                get shippingLabel(){ return this.shippingCost>0 ? this.money(this.shippingCost) : 'Gratis'; },
                get checkoutTotal(){ return this.total() + this.shippingCost; },
                openCartPage(){ this.cartOpen=false; this.cartPageOpen=true; document.body.style.overflow=''; window.scrollTo({top:0}); },
                openCheckout(){ this.cartOpen=false; this.cartPageOpen=false; this.checkoutOpen=true; this.orderError='';
                    document.body.style.overflow=''; window.scrollTo({top:0,behavior:'smooth'}); },
                proofUrl:'', proofName:'', proofUploading:false,
                async uploadProof(ev){
                    const file = ev.target.files && ev.target.files[0];
                    if(!file) return;
                    this.proofUploading = true; this.orderError='';
                    try{
                        const fd = new FormData();
                        fd.append('proof', file);
                        fd.append('_token', document.querySelector('meta[name=csrf-token]')?.content||'');
                        const res = await fetch(@js(route('public.order.proof', $project->slug)), { method:'POST', headers:{'Accept':'application/json'}, body: fd });
                        const data = await res.json();
                        if(res.ok && data.ok){ this.proofUrl = data.url; this.proofName = file.name; }
                        else{ this.orderError = data.message || 'No se pudo subir el comprobante.'; }
                    }catch(e){ this.orderError = 'No se pudo subir el comprobante.'; }
                    finally{ this.proofUploading = false; ev.target.value=''; }
                },
                async submitCheckout(){
                    const fullName = ((this.form.fname||'').trim()+' '+(this.form.lname||'').trim()).trim();
                    if(!fullName){ this.orderError='Ingresa tu nombre.'; return; }
                    const rawPhone = (this.form.phone||'').replace(/\D/g,'');
                    if(!rawPhone){ this.orderError='Ingresa tu número de celular.'; return; }
                    if(rawPhone.length<7){ this.orderError='El número de celular es muy corto.'; return; }
                    if(rawPhone.length===9 && !/^9/.test(rawPhone)){ this.orderError='Los celulares peruanos empiezan con 9.'; return; }
                    const mail=(this.form.email||'').trim();
                    if(mail && !/^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i.test(mail)){ this.orderError='Revisa tu correo: parece incompleto.'; return; }
                    const doc=(this.form.dni||'').replace(/\D/g,'');
                    if(doc && doc.length!==8 && doc.length!==11){ this.orderError='El DNI tiene 8 dígitos y el RUC 11.'; return; }
                    if(this.docType==='factura' && doc.length!==11){ this.orderError='Para factura necesitamos un RUC de 11 dígitos.'; return; }
                    @if($requireAddress)
                    if(!(this.form.address||'').trim()){ this.orderError='Ingresa tu dirección de entrega.'; return; }
                    @endif
                    @if(!$isQuoteOnly && $payManualEnabled)
                    if(!this.payMethod){ this.orderError='Selecciona un método de pago.'; return; }
                    @endif
                    this.orderLoading=true; this.orderError='';
                    const addr=[this.form.address,this.form.address2,this.form.district,this.form.department].filter(Boolean).join(', ');
                    const entrega=@js($pickupEnabled)?(this.deliveryMode==='pickup'?'Entrega: RECOJO EN TIENDA':'Entrega: Envío a domicilio'):'';
                    const comprobante='Comprobante: '+(this.docType==='factura'?'FACTURA':'BOLETA');
                    const notes=[this.form.notes,this.form.dni?('DNI/RUC: '+this.form.dni):'',comprobante,entrega].filter(Boolean).join(' | ');
                    const items=this.cart.map(i=>({product_id:i.id,name:i.nombre+(i.talla?(' (Talla '+i.talla+')'):''),price:Number(i.precio),quantity:Number(i.cantidad)}));
                    try{
                        const res=await fetch(@js(route('public.order',$project->slug)),{
                            method:'POST',
                            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content||'','Accept':'application/json'},
                            body:JSON.stringify({client_name:fullName,client_phone:this.form.phone,client_email:this.form.email,delivery_address:addr,notes,items,payment_method:this.payMethod||'',payment_reference:'',payment_proof:this.proofUrl||''}),
                        });
                        const data=await res.json();
                        if(!res.ok){ this.orderError=data.message||'Error al procesar el pedido.'; }
                        else{
                            this.orderSuccess=true;
                            this.orderSuccessMsg=data.message||'¡Gracias! Tu pedido fue recibido correctamente.';
                            this.cart=[];
                        }
                    }catch(e){ this.orderError='Error de conexión. Intenta nuevamente.'; }
                    finally{ this.orderLoading=false; }
                }
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

    <x-public-store-runtime :project="$project" :settings="array_merge($settings, ['primary_color' => $primary])" :popup="$popup ?? null" :sections="$sections ?? collect()" :about-page="$aboutPage ?? null" :store-view="$storeView ?? 'home'" :own-footer="true" />
</body>
</html>
