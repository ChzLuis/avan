<?php

namespace App\Storefront;

/**
 * Auditoría automática del Constructor.
 *
 * Nace de un problema real: durante la reorganización aparecieron una y otra
 * vez claves con dos editores, ajustes vivos sin editor y controles que
 * escribían en el vacío — y cada hallazgo se descubría a mano, tarde. Esto lo
 * mide solo.
 *
 * Reglas de barrido aprendidas a base de equivocarse:
 *   · Un `grep` de la clave NO prueba que exista control: puede estar dentro de
 *     un bloque `@if(false)`. Se excluyen esos rangos.
 *   · Hay CUATRO mecanismos de escritura, no uno: `setSetting()`,
 *     `<x-bxb-color clave="">`, arrays PHP `['key' => …]` y `setSetting` desde
 *     JavaScript. Mirar solo el primero da cifras falsas.
 *   · Para declarar una clave muerta hay que barrer TODAS las superficies de
 *     render, incluida `resources/views/storefront/`, que se escapó dos veces.
 *   · Dos claves con nombre parecido no son la misma: `card_style` es la familia
 *     visual de la PLANTILLA y `product_card_style` el estilo de la tarjeta.
 */
final class ConstructorAuditor
{
    /** Etapa propietaria por prefijo o clave exacta. El orden importa: gana la primera. */
    private const PROPIEDAD = [
        // 01 Datos del negocio — datos maestros e identidad
        'business_name' => '01', 'business_category' => '01', 'contact_email' => '01',
        'business_hours' => '01', 'razon_social' => '01', 'ruc' => '01',
        'contact_numbers' => '01',
        'has_physical_store' => '01', 'contact_city' => '01', 'logo_url' => '01',
        'facebook_url' => '01', 'instagram_url' => '01', 'tiktok_url' => '01',
        'youtube_url' => '01', 'linkedin_url' => '01', 'twitter_url' => '01',
        // 02 Apariencia — identidad visual global, barra superior, encabezado
        'theme_preset' => '02', 'catalog_template' => '02', 'border_radius' => '02',
        'hover_card_effect' => '02', 'hover_image_zoom' => '02', 'anim_stagger_ms' => '02',
        'btn_shape' => '02', 'btn_show_icon' => '02', 'font_title' => '02', 'font_body' => '02',
        'ticker_' => '02', 'announcement_' => '02', 'header_' => '02', 'hp_' => '02',
        // Boton y panel de categorias del menu (mega, lista, texto, marcas, ayuda)
        'mega_' => '02', 'megacat_' => '02',
        'logo_wordmark' => '02', 'logo_wordmark_text' => '02', 'uni_top_note' => '02', 'section_head_' => '02', 'float_' => '02', 'login_' => '02',
        'primary_color' => '02', 'secondary_color' => '02', 'accent_color' => '02',
        'text_color' => '02', 'text_muted_color' => '02', 'text_strong_color' => '02',
        'surface_soft_color' => '02', 'surface_color' => '02', 'border_color' => '02',
        'border_warm_color' => '02', 'sale_color' => '02', 'page_bg_color' => '02',
        'popup_bg_color' => '02', 'buy_button_color' => '02', 'content_max_width' => '02',
        // 03 Página de inicio
        'section_' => '03', 'hero_' => '03', 'anim_' => '03', 'intro_' => '03', 'shape_' => '03', 'overlay_' => '03',
        'home_template' => '03', 'home_hero_variant' => '03',
        'pastel_band_' => '03', 'featured_categories_' => '03', 'featured_products_view' => '03', 'featured_products_autoplay' => '03',
        'flash_sale_' => '03', 'trust_' => '03', 'promo_style' => '03',
        'promo_autoplay' => '03', 'promo_show_dots' => '03', 'collection_' => '03',
        // 04 Catálogo — presentación
        'catalog_' => '04', 'card_' => '04', 'cats_mobile_limit' => '04',
        'pdp_' => '04', 'brands_page_' => '04', 'promo_cards_' => '04',
        'new_badge_days' => '04', 'product_card_style' => '04', 'related_title' => '04',
        'sold_out_text' => '04', 'price_on_request_text' => '04', 'txt_' => '04',
        // 05 Venta — comportamiento comercial
        'store_mode' => '05', 'purchase_mode' => '05', 'currency_symbol' => '05',
        'cart_' => '05', 'checkout_' => '05', 'payment_' => '05', 'pickup_' => '05',
        'shipping_' => '05', 'wholesale_' => '05', 'quote_' => '05', 'buy_' => '05',
        'btn_' => '05', 'culqi_' => '05', 'mp_enabled' => '05', 'require_address' => '05',
        'wa_product_msg' => '05', 'accepted_payments' => '05', 'product_button_mode' => '05',
        // 07 Footer y legales
        'footer_' => '07',
        // 08 Configuración
        'seo_' => '08', 'og_' => '08', 'ga_id' => '08', 'gtm_id' => '08',
        'fb_pixel_id' => '08', 'tiktok_pixel_id' => '08',
        'google_site_verification' => '08', 'bing_site_verification' => '08',
    ];

    /**
     * Excepciones declaradas. Todo lo que no sea ACTIVE vive aquí, con su
     * motivo: es la única forma de que "controles inertes = 0" signifique algo.
     */
    private const ESTADOS = [
        // Alias de transición: se leen, pero la fuente maestra es `projects`.
        'contact_phone' => ['ALIAS', 'Alias de projects.phone (01)'],
        'quote_whatsapp' => ['ALIAS', 'Alias de projects.whatsapp (01)'],
        'quote_whatsapp_country' => ['ALIAS', 'Prefijo del alias de WhatsApp'],
        'contact_address' => ['ALIAS', 'Alias de projects.address (01)'],
        // Tokens de la definición de plantilla: a nivel de proyecto no renderizan.
        'card_style' => ['DEPRECATED_CANDIDATE', 'Familia visual de la PLANTILLA; a nivel de proyecto no lo renderiza nadie'],
        'catalog_layout' => ['DEPRECATED_CANDIDATE', 'Disposición declarada por la plantilla; sin consumidor de render'],
        'purchase_mode' => ['DEPRECATED_CANDIDATE', '1 consumidor frente a 30 de store_mode'],
        'product_show_low_stock' => ['DEPRECATED_CANDIDATE', 'Sin consumidores ni datos'],
        'product_show_related' => ['DEPRECATED_CANDIDATE', 'Sin consumidores ni datos'],
        'product_show_share' => ['DEPRECATED_CANDIDATE', 'Sin consumidores ni datos'],
        'product_show_sku' => ['DEPRECATED_CANDIDATE', 'Sin consumidores ni datos'],

        // Presets de encabezado que el Constructor escribe y `HeaderPresets` NO
        // lee: controles sin efecto. Se dejan visibles a proposito hasta la
        // limpieza de deuda —retirarlos a mano rompio el Blade— pero quedan
        // declarados para que no cuenten como activos ni sirvan de base a nada.
        'hp_boutique_promo_desc' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_boutique_promo_title' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_boutique_promo_url' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_commercial_cta_title' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_commercial_cta_url' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_commercial_wholesale_text' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_header_phone_label' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_mega_brands' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_mega_layout' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_mega_promo_badge' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_mega_promo_desc' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_mega_promo_title' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_mega_promo_url' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_menu_icons' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_minimal_cta_url' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_multiverse_use_categories' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_nav_chip_' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_phone_btn_bg' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_phone_btn_color' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_topbar_social_gap' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_topbar_social_size' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'hp_topbar_social_style' => ['DEPRECATED_CANDIDATE', 'Control sin consumidor en HeaderPresets'],
        'payment_cash_note' => ['DEPRECATED_CANDIDATE', 'Sin consumidor de render'],
    ];

    /**
     * No son ajustes: son VALORES (metodos de pago dentro de `accepted_payments`)
     * o identificadores de pestaña. Aparecen en `name="..."` de las vistas
     * legacy y falsearian las metricas.
     */
    private const NO_SON_AJUSTES = [
        'yape', 'plin', 'tarjeta', 'efectivo', 'transferencia', 'contra_entrega',
        'culqi', 'manual', 'methods', 'analytics', 'basico', 'social', 'schema',
        'indexacion', 'avanzado', 'general',
        // Nombres de icono y de banco: valores dentro de un @foreach.
        'award', 'check', 'clock', 'heart', 'percent', 'shield', 'sparkles',
        'store', 'support', 'truck', 'users', 'warranty', 'label',
        'interbank', 'nacion', 'scotiabank', 'pages_placeholder',
    ];

    /** Datos maestros: solo la etapa 01 puede escribirlos. */
    public const MAESTRAS = [
        'business_name', 'contact_phone', 'quote_whatsapp', 'contact_address',
        'logo_url', 'business_category',
    ];

    /** Superficies del Constructor: el editor OFICIAL. */
    private const EDITOR_OFICIAL = 'resources/views/settings/builder';

    /**
     * Superficies legacy que TODAVIA son alcanzables y editan configuracion de
     * la tienda. Una vista cuyo controlador redirige al Constructor deja de
     * contar: el archivo sigue ahi, pero ningun comerciante llega a el.
     *
     * Ya retiradas (redirigen salvo superadmin con ?classic=1):
     *   settings/design · settings/designer · settings/payments · settings/seo
     */
    private const EDITORES_LEGACY = [
        // (vacio) Ninguna superficie legacy edita ya configuracion de Mi Tienda:
        //   settings/design, settings/designer, settings/payments y settings/seo
        //   redirigen al Constructor;
        //   settings/index dejo de aceptar redes, SEO y envio en `update()`
        //   —conserva solo facturacion y datos fiscales, que no son Mi Tienda—.
    ];

    /** Dónde se CONSUME una clave (render real, no edición). */
    private const CONSUMIDORES = [
        'app',
        'resources/views/public',
        'resources/views/components',
        'resources/views/storefront',
        'resources/views/layouts',
        'resources/js',
        'public/js',
    ];

    private array $cache = [];

    public function __construct(private string $base) {}

    public static function make(): self
    {
        return new self(base_path());
    }

    /** @return array<string,mixed> */
    public function auditar(): array
    {
        $oficiales = $this->editoresDe($this->rutaAbs(self::EDITOR_OFICIAL));
        $legacy = [];
        foreach (self::EDITORES_LEGACY as $ruta) {
            foreach ($this->editoresDe($this->rutaAbs($ruta)) as $clave => $archivos) {
                $legacy[$clave] = array_merge($legacy[$clave] ?? [], $archivos);
            }
        }

        $filas = [];
        foreach (array_unique(array_merge(array_keys($oficiales), array_keys($legacy))) as $clave) {
            $estado = self::ESTADOS[$clave][0] ?? 'ACTIVE';
            $filas[$clave] = [
                'clave' => $clave,
                'etapa' => $this->etapaDe($clave),
                'editor_oficial' => $oficiales[$clave] ?? [],
                'editores_legacy' => $legacy[$clave] ?? [],
                'consumidores' => $this->consumidoresDe($clave),
                'estado' => $estado,
                'nota' => self::ESTADOS[$clave][1] ?? null,
            ];
        }
        ksort($filas);

        return ['claves' => $filas] + $this->metricas($filas);
    }

    /** @param array<string,array<string,mixed>> $filas */
    private function metricas(array $filas): array
    {
        $duplicados = $huerfanos = $inertes = $violaciones = $sinClasificar = [];

        foreach ($filas as $clave => $f) {
            $enOficial = $f['editor_oficial'] !== [];
            $enLegacy = $f['editores_legacy'] !== [];
            $seConsume = $f['consumidores'] !== [];
            $deprecada = $f['estado'] === 'DEPRECATED_CANDIDATE';

            // Dos interfaces editando lo mismo.
            if ($enOficial && $enLegacy) {
                $duplicados[$clave] = $f['editores_legacy'];
            }
            // Se consume de verdad pero nadie la puede editar en el Constructor.
            if ($seConsume && ! $enOficial && ! $deprecada) {
                $huerfanos[$clave] = $f['consumidores'];
            }
            // Hay control, pero la clave no la lee nadie.
            if ($enOficial && ! $seConsume && ! $deprecada) {
                $inertes[$clave] = $f['editor_oficial'];
            }
            // Dato maestro editado fuera de la etapa 01.
            if (in_array($clave, self::MAESTRAS, true)) {
                // `builder/script.blade.php` es el runtime compartido del
                // Constructor, no una etapa: escribe por cuenta de la etapa 01.
                $fuera = array_filter($f['editor_oficial'], fn ($a) => ! str_contains($a, 'business')
                    && ! str_contains($a, 'builder/script'));
                if ($fuera !== [] || $enLegacy) {
                    $violaciones[$clave] = array_values(array_merge($fuera, $f['editores_legacy']));
                }
            }
            if ($f['etapa'] === null && ! $deprecada) {
                $sinClasificar[] = $clave;
            }
        }

        return [
            'DUPLICATE_EDITORS' => count($duplicados),
            'LIVE_SETTINGS_WITHOUT_EDITOR' => count($huerfanos),
            'INERT_CONTROLS' => count($inertes),
            'MASTER_DATA_WRITE_VIOLATIONS' => count($violaciones),
            'UNCLASSIFIED' => count($sinClasificar),
            'detalle' => [
                'duplicados' => $duplicados,
                'huerfanos' => $huerfanos,
                'inertes' => $inertes,
                'violaciones' => $violaciones,
                'sin_clasificar' => $sinClasificar,
            ],
        ];
    }

    private function etapaDe(string $clave): ?string
    {
        // Un alias pertenece a la etapa de su dato maestro.
        if ((self::ESTADOS[$clave][0] ?? '') === 'ALIAS') {
            return '01';
        }
        if (isset(self::PROPIEDAD[$clave])) {
            return self::PROPIEDAD[$clave];
        }
        foreach (self::PROPIEDAD as $prefijo => $etapa) {
            if (str_ends_with($prefijo, '_') && str_starts_with($clave, $prefijo)) {
                return $etapa;
            }
        }

        return null;
    }

    /**
     * Claves que un conjunto de vistas EDITA, ignorando los bloques `@if(false)`.
     *
     * @return array<string,array<int,string>> clave => archivos
     */
    private function editoresDe(string $ruta): array
    {
        $salida = [];
        foreach ($this->archivos($ruta, ['blade.php', 'php']) as $archivo) {
            $texto = $this->leer($archivo);
            $muertos = $this->rangosMuertos($texto);
            $lineas = explode("\n", $texto);

            foreach ($this->clavesEscritas($texto) as $clave) {
                foreach ($lineas as $i => $linea) {
                    if (! str_contains($linea, $clave)) {
                        continue;
                    }
                    $n = $i + 1;
                    $viva = true;
                    foreach ($muertos as [$a, $b]) {
                        if ($n >= $a && $n <= $b) {
                            $viva = false;
                            break;
                        }
                    }
                    if ($viva) {
                        $salida[$clave][] = $this->relativo($archivo);
                        continue 2;
                    }
                }
            }
        }

        return array_map('array_unique', $salida);
    }

    /** Los cuatro mecanismos de escritura. @return array<int,string> */
    private function clavesEscritas(string $texto): array
    {
        $claves = [];
        preg_match_all("/setSetting\\('([a-z0-9_]+)'/", $texto, $m);
        $claves = array_merge($claves, $m[1]);
        preg_match_all('/clave="([a-z0-9_]+)"/', $texto, $m);
        $claves = array_merge($claves, $m[1]);
        preg_match_all("/'key'\\s*=>\\s*'([a-z0-9_]+)'/", $texto, $m);
        $claves = array_merge($claves, $m[1]);
        // 5o mecanismo: la clave viene de un @foreach y se interpola
        //   @foreach([['facebook_url', ...], ...]) ... setSetting('{{ $key }}', ...)
        // Sin esto, las 6 redes sociales de la etapa 01 parecian no tener editor.
        // Se cosecha SOLO dentro del array del foreach, no de todo el archivo.
        if (preg_match('/setSetting\(.\{\{|setSetting\(.\s*\.\s*\$/', $texto)) {
            preg_match_all('/@foreach\s*\(\s*\[(.*?)\]\s*as\s/s', $texto, $bloques);
            foreach ($bloques[1] as $bloque) {
                // Solo la PRIMERA cadena de cada fila `['clave', 'Etiqueta', ...]`
                // es la clave; las demas son textos de la interfaz. Antes se
                // cosechaba todo y los nombres de icono de un mapa entraban
                // como ajustes fantasma que nunca se podian clasificar.
                preg_match_all("/\[\s*'([a-z][a-z0-9_]{4,})'/", $bloque, $m2);
                $claves = array_merge($claves, $m2[1]);
            }
        }

        preg_match_all('/name="([a-z0-9_]+)"/', $texto, $m);
        foreach ($m[1] as $k) {
            if (in_array($k, $this->universo(), true)) {
                $claves[] = $k;
            }
        }

        return array_values(array_unique(array_filter($claves,
            fn ($k) => strlen($k) > 3 && ! in_array($k, self::NO_SON_AJUSTES, true))));
    }

    /**
     * Universo de claves que SON configuracion de tienda: las que el Constructor
     * escribe por sus mecanismos explicitos. Sirve para filtrar los `name="..."`
     * de las vistas legacy, llenas de campos que no son ajustes (facturacion,
     * pestañas, filtros).
     *
     * @return array<int,string>
     */
    private function universo(): array
    {
        if (isset($this->cache['universo'])) {
            return $this->cache['universo'];
        }
        $claves = [];
        foreach ($this->archivos($this->rutaAbs(self::EDITOR_OFICIAL), ['blade.php']) as $archivo) {
            $texto = $this->leer($archivo);
            preg_match_all("/setSetting\('([a-z0-9_]+)'/", $texto, $m);
            $claves = array_merge($claves, $m[1]);
            preg_match_all('/clave="([a-z0-9_]+)"/', $texto, $m);
            $claves = array_merge($claves, $m[1]);
            preg_match_all("/'key'\s*=>\s*'([a-z0-9_]+)'/", $texto, $m);
            $claves = array_merge($claves, $m[1]);
        }

        return $this->cache['universo'] = array_values(array_unique($claves));
    }

    /** Rangos `@if(false) … @endif`, que existen pero no se renderizan. */
    private function rangosMuertos(string $texto): array
    {
        $rangos = $pila = [];
        foreach (explode("\n", $texto) as $i => $linea) {
            $l = trim($linea);
            if (preg_match('/^@if\s*\(\s*false\s*\)/', $l)) {
                $pila[] = $i + 1;
            } elseif (str_starts_with($l, '@if') && $pila !== []) {
                $pila[] = null;
            } elseif (str_starts_with($l, '@endif') && $pila !== []) {
                $ini = array_pop($pila);
                if ($ini !== null) {
                    $rangos[] = [$ini, $i + 1];
                }
            }
        }

        return $rangos;
    }

    /** @return array<int,string> archivos que LEEN la clave para renderizar */
    private function consumidoresDe(string $clave): array
    {
        $encontrados = [];
        foreach (self::CONSUMIDORES as $ruta) {
            foreach ($this->archivos($this->rutaAbs($ruta), ['php', 'js', 'blade.php']) as $archivo) {
                $rel = $this->relativo($archivo);
                // Las superficies de edición no cuentan como consumo.
                if (str_contains($rel, 'views/settings/')) {
                    continue;
                }
                if (preg_match("/['\"]".preg_quote($clave, '/')."['\"]/", $this->leer($archivo))) {
                    $encontrados[] = $rel;
                }
            }
        }

        return array_values(array_unique($encontrados));
    }

    /** @return array<int,string> */
    private function archivos(string $ruta, array $extensiones): array
    {
        if (isset($this->cache['ls'][$ruta])) {
            return $this->cache['ls'][$ruta];
        }
        $out = [];
        if (is_file($ruta)) {
            $out = [$ruta];
        } elseif (is_dir($ruta)) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($ruta, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if (! $f->isFile()) {
                    continue;
                }
                foreach ($extensiones as $ext) {
                    if (str_ends_with($f->getFilename(), '.'.$ext)) {
                        $out[] = $f->getPathname();
                        break;
                    }
                }
            }
        }

        return $this->cache['ls'][$ruta] = $out;
    }

    private function leer(string $archivo): string
    {
        return $this->cache['txt'][$archivo] ??= (string) file_get_contents($archivo);
    }

    private function rutaAbs(string $rel): string
    {
        return $this->base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
    }

    private function relativo(string $abs): string
    {
        return str_replace([$this->base.DIRECTORY_SEPARATOR, '\\'], ['', '/'], $abs);
    }
}
