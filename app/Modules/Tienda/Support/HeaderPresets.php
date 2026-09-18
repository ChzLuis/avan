<?php

namespace App\Modules\Tienda\Support;

/**
 * ENCABEZADO Y NAVEGACIÓN — registro central de los 8 presets.
 *
 * Cada preset es una COMPOSICIÓN de piezas, no una plantilla cerrada:
 *   - base_layout: partial existente de StorefrontLayoutPacks (franja del header)
 *   - nav:         módulo de navegación (storefront/partials/nav/{nav}.blade.php)
 *   - search:      tamaño/protagonismo del buscador (clase en el body)
 *   - capabilities: qué acordeones/campos muestra el Constructor
 *   - defaults:    preset inicial atractivo (solo claves aún no configuradas)
 *
 * Persistencia POR MODELO sin almacenamiento nuevo: cada preset guarda sus
 * ajustes en claves con prefijo hp_{preset}_* dentro de project_settings.
 * Cambiar de modelo solo cambia `header_preset`; volver a un modelo anterior
 * recupera sus claves intactas.
 *
 * `header_preset` vacío = comportamiento actual (header_layout clásico):
 * las tiendas existentes no cambian hasta elegir un modelo.
 */
final class HeaderPresets
{
    public const SETTING = 'header_preset';

    public const PRESETS = [
        'mega_menu' => [
            'label' => 'Mega Menú Pro',
            'desc' => 'Panel amplio de categorías con columnas, imágenes, marcas y promoción.',
            'ideal' => 'Tecnología, ferretería, electrodomésticos y catálogos grandes.',
            'base_layout' => 'classic',
            'nav' => 'mega',
            'search' => 'normal',
            'shell' => ['topbar' => true, 'logo' => 'left', 'search' => 'wide', 'nav_row' => true, 'nav_align' => 'left', 'cat_trigger' => 'mega', 'tall' => true],
            'capabilities' => ['mega' => true, 'category_images' => true, 'brands' => true, 'promo' => true, 'sidebar' => false, 'universes' => false, 'b2b' => false, 'search_pro' => false, 'visual' => false, 'cta' => false],
            'defaults' => ['hp_mega_columns' => '4', 'hp_mega_max_subs' => '6', 'hp_mega_show_images' => '1', 'hp_mega_open' => 'hover'],
        ],
        'boutique' => [
            'label' => 'Boutique Editorial',
            'desc' => 'Logo centrado, navegación limpia y dropdown editorial con imagen.',
            'ideal' => 'Moda, bebé, belleza, decoración y marcas premium.',
            'base_layout' => 'centered',
            'nav' => 'editorial',
            'search' => 'compact',
            'shell' => ['topbar' => false, 'logo' => 'center', 'search' => 'icon', 'nav_row' => true, 'nav_align' => 'center', 'cat_trigger' => 'editorial', 'universes_row' => 'below'],
            'capabilities' => ['mega' => false, 'category_images' => true, 'brands' => false, 'promo' => true, 'sidebar' => false, 'universes' => true, 'b2b' => false, 'search_pro' => false, 'visual' => false, 'cta' => false],
            'defaults' => ['hp_boutique_universe_style' => 'pill'],
        ],
        'catalog_pro' => [
            'label' => 'Catálogo Profesional',
            'desc' => 'Barra lateral de categorías siempre a la mano, con segundo nivel.',
            'ideal' => 'Distribuidores, repuestos, industria y catálogos extensos.',
            'base_layout' => 'classic',
            'nav' => 'sidebar',
            'search' => 'normal',
            'shell' => ['topbar' => true, 'logo' => 'left', 'search' => 'hero', 'nav_row' => false, 'cat_trigger' => 'none'],
            'capabilities' => ['mega' => false, 'category_images' => false, 'brands' => true, 'promo' => true, 'sidebar' => true, 'universes' => false, 'b2b' => false, 'search_pro' => false, 'visual' => false, 'cta' => false],
            'defaults' => ['hp_catalog_max_cats' => '10', 'hp_catalog_show_icons' => '1', 'hp_catalog_fixed' => '1'],
        ],
        'minimal' => [
            'label' => 'Minimal Conversión',
            'desc' => 'Compacto, rápido y enfocado en producto, con CTA opcional.',
            'ideal' => 'Tiendas pequeñas o medianas que buscan simplicidad.',
            'base_layout' => 'compact',
            'nav' => 'dropdown',
            'search' => 'icon',
            'shell' => ['topbar' => false, 'logo' => 'left', 'search' => 'icon', 'nav_row' => false, 'nav_inline' => true, 'cat_trigger' => 'none', 'low' => true],
            'capabilities' => ['mega' => false, 'category_images' => false, 'brands' => false, 'promo' => false, 'sidebar' => false, 'universes' => false, 'b2b' => false, 'search_pro' => false, 'visual' => false, 'cta' => true],
            'defaults' => ['hp_minimal_cta_text' => '', 'hp_minimal_transparent' => '0'],
        ],
        'commercial' => [
            'label' => 'Comercial / Mayorista',
            'desc' => 'Acciones comerciales al frente: cotizar, WhatsApp y venta mayorista.',
            'ideal' => 'Distribuidores, fabricantes y negocios B2B por volumen.',
            'base_layout' => 'classic',
            'nav' => 'dropdown',
            'search' => 'normal',
            'shell' => ['topbar' => 'b2b', 'logo' => 'left', 'search' => 'wide', 'nav_row' => true, 'nav_align' => 'left', 'cat_trigger' => 'mega', 'actions_cta' => true],
            'capabilities' => ['mega' => false, 'category_images' => false, 'brands' => true, 'promo' => false, 'sidebar' => false, 'universes' => false, 'b2b' => true, 'search_pro' => false, 'visual' => false, 'cta' => true],
            'defaults' => ['hp_commercial_show_wholesale' => '1', 'hp_commercial_wa_text' => 'Cotiza por WhatsApp'],
        ],
        'search_first' => [
            'label' => 'Search First / Buscador Pro',
            'desc' => 'El buscador es el protagonista, con sugerencias de productos y categorías.',
            'ideal' => 'Cientos o miles de productos, SKU y catálogos técnicos.',
            'base_layout' => 'classic',
            'nav' => 'dropdown',
            'search' => 'hero',
            'shell' => ['topbar' => false, 'logo' => 'left', 'search' => 'takeover', 'nav_row' => true, 'nav_align' => 'center', 'cat_trigger' => 'none', 'search_panel' => true],
            'capabilities' => ['mega' => false, 'category_images' => false, 'brands' => false, 'promo' => false, 'sidebar' => false, 'universes' => false, 'b2b' => false, 'search_pro' => true, 'visual' => false, 'cta' => false],
            'defaults' => ['hp_search_show_price' => '1', 'hp_search_show_thumb' => '1', 'hp_search_max' => '8'],
        ],
        'visual_collections' => [
            'label' => 'Visual Collections',
            'desc' => 'Navegación por tarjetas grandes con fotografía.',
            'ideal' => 'Muebles, decoración, moda, regalos y catálogos visuales.',
            'base_layout' => 'classic',
            'nav' => 'visual',
            'search' => 'normal',
            'shell' => ['topbar' => false, 'logo' => 'left', 'search' => 'icon', 'nav_row' => true, 'nav_align' => 'center', 'cat_trigger' => 'visual'],
            'capabilities' => ['mega' => false, 'category_images' => true, 'brands' => false, 'promo' => true, 'sidebar' => false, 'universes' => false, 'b2b' => false, 'search_pro' => false, 'visual' => true, 'cta' => false],
            'defaults' => ['hp_visual_columns' => '4', 'hp_visual_overlay' => '35'],
        ],
        'multiverse' => [
            // Retirado del selector: la banda de chips sobre la navegacion no
            // convencia y ninguna tienda lo usaba. Se conserva la entrada para
            // que un proyecto que lo tuviera guardado siga renderizando igual.
            'hidden' => true,
            'label' => 'Multiuniverso / Segmentado',
            'desc' => 'Selector de público (perfiles) siempre visible sobre la navegación.',
            'ideal' => 'Tiendas con públicos o líneas claramente separados.',
            'base_layout' => 'classic',
            'nav' => 'dropdown',
            'search' => 'normal',
            'shell' => ['topbar' => false, 'logo' => 'left', 'search' => 'wide', 'nav_row' => true, 'nav_align' => 'left', 'cat_trigger' => 'none', 'universes_row' => 'top'],
            'capabilities' => ['mega' => false, 'category_images' => false, 'brands' => false, 'promo' => false, 'sidebar' => false, 'universes' => true, 'b2b' => false, 'search_pro' => false, 'visual' => false, 'cta' => false],
            'defaults' => ['hp_multiverse_style' => 'both'],
        ],
    ];

    /** Preset activo validado contra el registro; null = comportamiento actual. */
    public static function active(array $settings): ?string
    {
        $raw = trim((string) ($settings[self::SETTING] ?? ''));
        return ($raw !== '' && isset(self::PRESETS[$raw])) ? $raw : null;
    }

    public static function get(?string $key): ?array
    {
        return $key !== null ? (self::PRESETS[$key] ?? null) : null;
    }

    /** Vista del módulo de navegación del preset (null = sin módulo extra). */
    public static function navView(?string $key): ?string
    {
        $preset = self::get($key);
        if (!$preset || empty($preset['nav'])) return null;
        $view = 'tienda::storefront.partials.nav.' . $preset['nav'];
        return view()->exists($view) ? $view : null;
    }

    /** Variante de layout base que impone el preset (registro existente de layouts). */
    public static function baseLayout(?string $key): ?string
    {
        return self::get($key)['base_layout'] ?? null;
    }

    /** Metadatos para el selector del Constructor (sin closures, serializable a JS). */
    public static function forBuilder(): array
    {
        // Los presets `hidden` no se ofrecen, pero `get()` sigue resolviendolos.
        return collect(self::PRESETS)->reject(fn ($p) => !empty($p['hidden']))->map(fn ($p, $k) => [
            'key' => $k,
            'label' => $p['label'],
            'desc' => $p['desc'],
            'ideal' => $p['ideal'],
            'capabilities' => $p['capabilities'],
            'defaults' => $p['defaults'],
        ])->values()->all();
    }
}
