<?php

namespace App\Support;

use App\Models\Project;
use App\Models\ProjectTemplate;

/**
 * Plantillas de catálogo por rubro.
 * Cada plantilla define: colores, fuente, hero, layout, card_style y textos por defecto.
 *
 * card_style: minimal | editorial | bold | food | tech | detailed | service | luxury | dark | fresh | flash | nordic
 * catalog_layout: grid | list | cards
 */
class CatalogTemplates
{
    /**
     * Plantillas que el panel permite seleccionar y aplicar.
     *
     * El resto de entradas de all() se conserva exclusivamente para mantener
     * compatibilidad con proyectos existentes.
     */
    public const SUPPORTED_KEYS = [
        'ecommerce',
        'direct',
    ];

    public static function all(): array
    {
        return [

            // ══════════════════════════════════════════════════════
            // GRUPO: GENERAL
            // ══════════════════════════════════════════════════════

            'ecommerce' => [
                'key'            => 'ecommerce',
                'name'           => 'Ecommerce',
                'short_description' => 'Sitio web completo con tienda online',
                'view'           => 'public.templates.ecommerce',
                'supported'      => true,
                'capabilities'   => ['home', 'catalog', 'filters', 'cart', 'checkout', 'about', 'contact', 'blog', 'responsive'],
                'label'          => 'Ecommerce — Web y Tienda Completa',
                'category'       => 'General',
                'icon'           => '🛒',
                'description'    => 'Sitio web comercial completo con Inicio, Tienda, Nosotros, Contacto, Blog, catálogo con filtros, ficha de producto, carrito y checkout.',
                'preview_bg'     => '#f7f7f5',
                'preview_accent' => '#3340ff',
                'components'     => ['hero','announcement','catalog','cart','whatsapp','footer','newsletter','trust_bar'],
                'settings' => [
                    'primary_color'        => '#3340ff',
                    'secondary_color'      => '#1f2bd6',
                    'hero_bg_color'        => '#0e0e10',
                    'hero_badge'           => '🛍️ Bienvenido',
                    'hero_title'           => 'Tu tienda online',
                    'hero_subtitle'        => 'Encuentra todo lo que necesitas al mejor precio. Envío rápido y seguro.',
                    'hero_align'           => 'left',
                    'hero_height'          => 'large',
                    'hero_cta1_show'       => '1',
                    'hero_cta1_text'       => 'Ver catálogo',
                    'hero_cta2_show'       => '0',
                    'catalog_layout'       => 'grid',
                    'card_style'           => 'minimal',
                    'font'                 => 'DM Sans',
                    'font_title'           => 'Space Grotesk',
                    'font_body'            => 'DM Sans',
                    'border_radius'        => 'rounded',
                    'currency_symbol'      => 'S/',
                    'catalog_cols_desktop' => '4',
                    'catalog_cols_mobile'  => '2',
                    'catalog_section_title' => 'Productos destacados',
                    'catalog_badge_sale'   => 'OFERTA',
                    'catalog_badge_new'    => 'NUEVO',
                    'float_cart_show'      => '1',
                    'float_wa_show'        => '1',
                    'float_wa_tooltip'     => '¿Necesitas ayuda?',
                    'btn_cart_text'        => 'Agregar al carrito',
                    'btn_quote_text'       => 'Cotizar',
                    'btn_shape'            => 'rounded',
                    'announcement_text'    => '🚚 Envío gratis en pedidos mayores a S/ 99',
                    'trust_icon_1'         => '🚚',
                    'trust_text_1'         => 'Envío rápido',
                    'trust_icon_2'         => '🔒',
                    'trust_text_2'         => 'Pago seguro',
                    'trust_icon_3'         => '✅',
                    'trust_text_3'         => 'Garantía de calidad',
                    'trust_icon_4'         => '💬',
                    'trust_text_4'         => 'Soporte 24/7',
                    'footer_tagline'       => 'Tu tienda online de confianza.',
                    'footer_copyright'     => '',
                ],
            ],

            'default' => [
                'label'          => 'Clásico — Default',
                'category'       => 'General',
                'icon'           => '🏠',
                'description'    => 'Diseño original del sistema. Limpio, funcional y versátil para cualquier rubro.',
                'preview_bg'     => '#1e293b',
                'preview_accent' => '#4f46e5',
                'components'     => ['hero','catalog','cart','whatsapp','footer'],
                'settings' => [
                    'primary_color'      => '#4f46e5',
                    'secondary_color'    => '#6366f1',
                    'hero_bg_color'      => '#1e293b',
                    'hero_badge'         => '🛍️ Bienvenido',
                    'hero_title'         => 'Bienvenido a nuestra tienda',
                    'hero_subtitle'      => 'Encuentra todo lo que necesitas al mejor precio.',
                    'hero_align'         => 'center',
                    'hero_height'        => 'medium',
                    'hero_overlay'       => '50',
                    'hero_cta1_show'     => '1',
                    'hero_cta1_text'     => 'Ver catálogo',
                    'hero_cta2_show'     => '0',
                    'hero_cta2_text'     => 'Contáctanos',
                    'banner1_title'      => 'Productos destacados',
                    'banner1_sub'        => 'Lo más vendido de la semana',
                    'banner2_title'      => 'Ofertas especiales',
                    'banner2_sub'        => 'Descuentos exclusivos online',
                    'catalog_layout'     => 'grid',
                    'card_style'         => 'minimal',
                    'font'               => 'Inter',
                    'font_title'         => 'Inter',
                    'font_body'          => 'Inter',
                    'border_radius'      => 'rounded',
                    'currency_symbol'    => 'S/',
                    'catalog_cols_desktop' => '3',
                    'catalog_cols_mobile'  => '2',
                    'catalog_section_title' => 'Nuestros productos',
                    'catalog_badge_sale'  => 'OFERTA',
                    'catalog_badge_new'   => 'NUEVO',
                    'float_cart_show'    => '1',
                    'float_wa_show'      => '1',
                    'float_wa_tooltip'   => '¿Necesitas ayuda?',
                    'btn_cart_text'      => 'Agregar al carrito',
                    'btn_quote_text'     => 'Cotizar',
                    'btn_shape'          => 'rounded',
                    'footer_tagline'     => '',
                    'footer_copyright'   => '',
                ],
            ],

            'direct' => [
                'key'            => 'direct',
                'name'           => 'Catálogo Directo',
                'short_description' => 'Catálogo simple para ventas y cotizaciones',
                'view'           => 'public.templates.direct',
                'supported'      => true,
                'capabilities'   => ['catalog', 'quotes', 'whatsapp', 'responsive'],
                'label'          => 'Directo — Solo Catálogo',
                'category'       => 'General',
                'icon'           => '📋',
                'description'    => 'Sin banner ni hero. Entra directo al catálogo de productos. Ideal para negocios que quieren simplicidad.',
                'preview_bg'     => '#f8fafc',
                'preview_accent' => '#4f46e5',
                'components'     => ['catalog','cart','whatsapp','footer'],
                'settings' => [
                    'primary_color'        => '#4f46e5',
                    'secondary_color'      => '#6366f1',
                    'catalog_layout'       => 'grid',
                    'card_style'           => 'minimal',
                    'font'                 => 'Inter',
                    'font_title'           => 'Inter',
                    'font_body'            => 'Inter',
                    'border_radius'        => 'rounded',
                    'currency_symbol'      => 'S/',
                    'catalog_cols_desktop' => '3',
                    'catalog_cols_mobile'  => '2',
                    'catalog_section_title'=> 'Nuestros productos',
                    'catalog_badge_sale'   => 'OFERTA',
                    'catalog_badge_new'    => 'NUEVO',
                    'float_cart_show'      => '1',
                    'float_wa_show'        => '1',
                    'float_wa_tooltip'     => '¿Necesitas ayuda?',
                    'btn_cart_text'        => 'Agregar al carrito',
                    'btn_quote_text'       => 'Cotizar',
                    'btn_shape'            => 'rounded',
                    'footer_tagline'       => '',
                    'footer_copyright'     => '',
                ],
            ],

            // ══════════════════════════════════════════════════════
            // GRUPO: MODA & ESTILO
            // ══════════════════════════════════════════════════════

            'ella' => [
                'label'          => 'Ella — Moda Minimalista',
                'category'       => 'Moda & Estilo',
                'icon'           => '👗',
                'description'    => 'Estilo editorial moderno. Bordes sharp, blanco/negro, tipografía bold. Inspirado en Ella Theme.',
                'preview_bg'     => '#121212',
                'preview_accent' => '#000000',
                'settings' => [
                    'primary_color'      => '#111111',
                    'secondary_color'    => '#444444',
                    'hero_bg_color'      => '#111111',
                    'hero_badge'         => 'NUEVA COLECCIÓN',
                    'hero_title'         => 'Descubre tu estilo',
                    'hero_subtitle'      => 'Moda contemporánea. Piezas únicas con envío a todo el país.',
                    'hero_align'         => 'center',
                    'hero_height'        => 'large',
                    'hero_overlay'       => '40',
                    'hero_cta1_show'     => '1',
                    'hero_cta1_text'     => 'Ver colección',
                    'hero_cta2_show'     => '0',
                    'hero_cta2_text'     => 'Nueva temporada',
                    'banner1_title'      => 'New Arrivals',
                    'banner1_sub'        => 'Lo último de la temporada',
                    'banner2_title'      => 'Sale — Hasta 40% off',
                    'banner2_sub'        => 'Ofertas por tiempo limitado',
                    'catalog_layout'     => 'grid',
                    'card_style'         => 'editorial',
                    'font'               => 'Jost',
                    'font_title'         => 'Jost',
                    'font_body'          => 'Jost',
                    'border_radius'      => 'sharp',
                    'currency_symbol'    => 'S/',
                    'catalog_cols_desktop' => '3',
                    'catalog_cols_mobile'  => '2',
                    'catalog_section_title' => 'Nueva colección',
                    'catalog_badge_sale'  => 'SALE',
                    'catalog_badge_new'   => 'NEW',
                    'float_cart_show'    => '1',
                    'float_wa_show'      => '1',
                    'float_wa_tooltip'   => '¿Necesitas ayuda?',
                    'btn_cart_text'      => 'Agregar',
                    'btn_quote_text'     => 'Cotizar',
                    'btn_shape'          => 'sharp',
                    'announcement_text'  => '',
                    'announcement_bg'    => '#111111',
                    'footer_tagline'     => 'Moda que inspira.',
                    'footer_copyright'   => '',
                ],
            ],

            'editorial' => [
                'label'          => 'Editorial — Boutique',
                'category'       => 'Moda & Estilo',
                'icon'           => '✂️',
                'description'    => 'Fotografía de alta gama, mucho espacio en blanco, un solo color acento. Para boutiques exclusivas.',
                'preview_bg'     => '#f5f0eb',
                'preview_accent' => '#c9a96e',
                'settings' => [
                    'primary_color'   => '#c9a96e',
                    'hero_bg_color'   => '#2c1810',
                    'hero_badge'      => 'Colección Exclusiva',
                    'hero_title'      => 'Elegancia sin límites',
                    'hero_subtitle'   => 'Piezas seleccionadas para quienes aprecian lo auténtico.',
                    'banner1_title'   => 'Accesorios & Complementos',
                    'banner1_sub'     => 'El detalle que marca la diferencia',
                    'banner2_title'   => 'Piezas de temporada',
                    'banner2_sub'     => 'Ediciones limitadas',
                    'catalog_layout'  => 'grid',
                    'card_style'      => 'minimal',
                    'font'            => 'Jost',
                ],
            ],

            'urban' => [
                'label'          => 'Urban — Streetwear',
                'category'       => 'Moda & Estilo',
                'icon'           => '👟',
                'description'    => 'Urbano, versátil, energía joven. Perfecto para sneakers, ropa casual y moda urbana.',
                'preview_bg'     => '#0a0a0a',
                'preview_accent' => '#ff3b30',
                'settings' => [
                    'primary_color'      => '#ff3b30',
                    'secondary_color'    => '#ff6b63',
                    'hero_bg_color'      => '#0a0a0a',
                    'hero_badge'         => '🔥 DROP NUEVO',
                    'hero_title'         => 'La calle es tuya',
                    'hero_subtitle'      => 'Sneakers, streetwear y accesorios. Ediciones limitadas disponibles ahora.',
                    'hero_align'         => 'left',
                    'hero_height'        => 'large',
                    'hero_overlay'       => '55',
                    'hero_cta1_show'     => '1',
                    'hero_cta1_text'     => 'Ver drops',
                    'hero_cta2_show'     => '1',
                    'hero_cta2_text'     => 'Limited Edition',
                    'banner1_title'      => 'Limited Edition',
                    'banner1_sub'        => 'Antes que se agoten',
                    'banner2_title'      => 'Colabs exclusivas',
                    'banner2_sub'        => 'Solo online',
                    'catalog_layout'     => 'grid',
                    'card_style'         => 'bold',
                    'font'               => 'Poppins',
                    'font_title'         => 'Poppins',
                    'font_body'          => 'Poppins',
                    'border_radius'      => 'rounded',
                    'currency_symbol'    => 'S/',
                    'catalog_cols_desktop' => '4',
                    'catalog_cols_mobile'  => '2',
                    'catalog_section_title' => 'Drops & Colecciones',
                    'catalog_badge_sale'  => 'SALE',
                    'catalog_badge_new'   => 'NEW DROP',
                    'float_cart_show'    => '1',
                    'float_wa_show'      => '1',
                    'float_wa_tooltip'   => '¿Buscas algo?',
                    'btn_cart_text'      => 'Agregar al carrito',
                    'btn_quote_text'     => 'Cotizar',
                    'btn_shape'          => 'rounded',
                    'announcement_text'  => '',
                    'announcement_bg'    => '#ff3b30',
                    'footer_tagline'     => 'La calle es nuestra.',
                    'footer_copyright'   => '',
                ],
            ],

            'boutique' => [
                'label'          => 'Boutique — Editorial Lujo',
                'category'       => 'Moda & Estilo',
                'icon'           => '💎',
                'description'    => 'Header centrado, tipografía serif elegante, cards con overlay hover. Para boutiques exclusivas.',
                'preview_bg'     => '#f9f6f2',
                'preview_accent' => '#8b6f47',
                'settings' => [
                    'primary_color'      => '#8b6f47',
                    'secondary_color'    => '#c4a97d',
                    'hero_bg_color'      => '#1a1a1a',
                    'hero_badge'         => 'Colección Exclusiva',
                    'hero_title'         => 'Arte en cada pieza',
                    'hero_subtitle'      => 'Moda de autor. Piezas únicas seleccionadas para ti.',
                    'hero_align'         => 'center',
                    'hero_height'        => 'full',
                    'hero_overlay'       => '45',
                    'hero_cta1_show'     => '1',
                    'hero_cta1_text'     => 'Explorar colección',
                    'hero_cta2_show'     => '0',
                    'hero_cta2_text'     => 'Nueva temporada',
                    'banner1_title'      => 'Lookbook Temporada',
                    'banner1_sub'        => 'Inspiración para cada ocasión',
                    'banner2_title'      => 'Edición Limitada',
                    'banner2_sub'        => 'Solo piezas seleccionadas',
                    'catalog_layout'     => 'grid',
                    'card_style'         => 'luxury',
                    'font'               => 'Playfair Display',
                    'font_title'         => 'Playfair Display',
                    'font_body'          => 'Jost',
                    'border_radius'      => 'sharp',
                    'currency_symbol'    => 'S/',
                    'catalog_cols_desktop' => '3',
                    'catalog_cols_mobile'  => '2',
                    'catalog_section_title' => 'Nueva colección',
                    'catalog_badge_sale'  => 'SALE',
                    'catalog_badge_new'   => 'NOUVEAU',
                    'float_cart_show'    => '1',
                    'float_wa_show'      => '1',
                    'float_wa_tooltip'   => '¿Podemos ayudarte?',
                    'btn_cart_text'      => 'Añadir al carrito',
                    'btn_quote_text'     => 'Solicitar cotización',
                    'btn_shape'          => 'sharp',
                    'footer_tagline'     => 'Lujo accesible, estilo inconfundible.',
                    'footer_copyright'   => '',
                ],
            ],

            // ══════════════════════════════════════════════════════
            // GRUPO: TECNOLOGÍA & ELECTRÓNICA
            // ══════════════════════════════════════════════════════

            'luxe' => [
                'label'          => 'Luxe — Tech Premium',
                'category'       => 'Tecnología',
                'icon'           => '⌚',
                'description'    => 'Fondo oscuro dramático, acento dorado. Para electrónica premium, relojes y gadgets de lujo.',
                'preview_bg'     => '#080808',
                'preview_accent' => '#c9a227',
                'settings' => [
                    'primary_color'   => '#c9a227',
                    'hero_bg_color'   => '#080808',
                    'hero_badge'      => '✦ Premium Selection',
                    'hero_title'      => 'Tecnología de élite',
                    'hero_subtitle'   => 'Los mejores dispositivos y gadgets. Calidad y exclusividad en cada producto.',
                    'banner1_title'   => 'Wearables & Smartwatches',
                    'banner1_sub'     => 'Tecnología en tu muñeca',
                    'banner2_title'   => 'Audio Premium',
                    'banner2_sub'     => 'Experiencia sonora superior',
                    'catalog_layout'  => 'grid',
                    'card_style'      => 'dark',
                    'font'            => 'Poppins',
                ],
            ],

            'computienda' => [
                'key'            => 'computienda',
                'name'           => 'CompuTienda',
                'short_description' => 'Tienda especializada en tecnología',
                'view'           => 'public.templates.computienda',
                'supported'      => true,
                'capabilities'   => ['catalog', 'advanced_filters', 'cart', 'checkout', 'responsive'],
                'label'          => 'CompuTienda — Informática Pro',
                'category'       => 'Tecnología',
                'icon'           => '🖥️',
                'description'    => 'Tienda corporativa de tecnología con una presentación sobria, buscador, catálogo ordenado, asesoría comercial y compra segura.',
                'preview_bg'     => '#0f172a',
                'preview_accent' => '#2563eb',
                'components'     => ['hero','announcement','catalog','cart','whatsapp','footer','newsletter','trust_bar'],
                'settings' => [
                    'primary_color'   => '#1e50a0',
                    'secondary_color' => '#0e1a30',
                    'hero_title'      => 'Tecnología para cada pasión',
                    'hero_subtitle'   => 'Los mejores equipos y accesorios al mejor precio. Garantía y soporte técnico.',
                    'announcement_text' => 'COMPRA ONLINE Y AHORRA TIEMPO Y DINERO',
                    'currency_symbol' => 'S/',
                    'card_style'      => 'tech',
                    'font'            => 'Manrope',
                    'footer_tagline'  => 'somos tu tienda de tecnología de confianza',
                ],
            ],

            'tecnologia' => [
                'label'          => 'Tech Store — Informática',
                'category'       => 'Tecnología',
                'icon'           => '💻',
                'description'    => 'Tienda de informática y tecnología estilo retail: header azul con buscador, mega-menú de categorías, banner de ofertas y footer con métodos de pago. Ideal para venta de computadoras, laptops y accesorios.',
                'preview_bg'     => '#1e50a0',
                'preview_accent' => '#e01e2b',
                'components'     => ['hero','announcement','catalog','cart','whatsapp','footer','trust_bar'],
                'settings' => [
                    'primary_color'   => '#1e50a0',
                    'secondary_color' => '#e01e2b',
                    'hero_bg_color'   => '#1e50a0',
                    'hero_badge'      => '⚡ Envío a todo el Perú',
                    'hero_title'      => 'Tecnología para cada pasión',
                    'hero_subtitle'   => 'Laptops, PCs, monitores e impresoras. Garantía oficial, soporte técnico y compra segura.',
                    'hero_cta1_show'  => '1',
                    'hero_cta1_text'  => 'Ver ofertas',
                    'banner1_title'   => 'Productos Reacondicionados',
                    'banner1_sub'     => 'Calidad garantizada, mejor precio',
                    'banner2_title'   => 'Ofertas y Novedades',
                    'banner2_sub'     => 'Hasta 30% de descuento',
                    'catalog_layout'  => 'grid',
                    'card_style'      => 'tech',
                    'font'            => 'Inter',
                    'currency_symbol' => 'S/',
                    'catalog_cols_desktop' => '4',
                    'catalog_cols_mobile'  => '2',
                    'catalog_section_title' => 'Productos destacados',
                    'catalog_badge_sale'   => 'OFERTA',
                    'catalog_badge_new'    => 'NUEVO',
                    'announcement_text'    => '🛒 Compra online y ahorra tiempo y dinero',
                    'trust_icon_1'    => '🏬', 'trust_text_1' => 'Retiro en tienda',
                    'trust_icon_2'    => '🚚', 'trust_text_2' => 'Envíos a todo el Perú',
                    'trust_icon_3'    => '🔧', 'trust_text_3' => 'Soporte Técnico',
                    'trust_icon_4'    => '⚡', 'trust_text_4' => 'Entrega Exprés',
                    'float_cart_show' => '1',
                    'float_wa_show'   => '1',
                    'footer_tagline'  => 'Somos una empresa dedicada a la venta de computadoras y laptops, por mayor y menor. Además brindamos soporte técnico de equipos informáticos.',
                ],
            ],

            // ══════════════════════════════════════════════════════
            // GRUPO: GASTRONOMÍA
            // ══════════════════════════════════════════════════════

            'bistro' => [
                'label'          => 'Bistro — Restaurante & Café',
                'category'       => 'Gastronomía',
                'icon'           => '☕',
                'description'    => 'Tonos cálidos cobre y café, menú visual apetitoso. Para restaurantes, cafés y pastelerías.',
                'preview_bg'     => '#1a0f07',
                'preview_accent' => '#c2540a',
                'settings' => [
                    'primary_color'   => '#c2540a',
                    'hero_bg_color'   => '#1a0f07',
                    'hero_badge'      => '☕ Abierto ahora',
                    'hero_title'      => 'Sabores que inspiran',
                    'hero_subtitle'   => 'Menú del día, postres artesanales y café de especialidad. Pide aquí.',
                    'banner1_title'   => 'Menú del día',
                    'banner1_sub'     => 'Recetas frescas y de temporada',
                    'banner2_title'   => 'Postres & Repostería',
                    'banner2_sub'     => 'Hechos con amor cada mañana',
                    'catalog_layout'  => 'list',
                    'card_style'      => 'food',
                    'font'            => 'Jost',
                ],
            ],

            'restaurante' => [
                'label'          => 'Delivery — Comida Rápida',
                'category'       => 'Gastronomía',
                'icon'           => '🍽️',
                'description'    => 'Menú digital optimizado para delivery rápido. Colores cálidos y llamativos.',
                'preview_bg'     => '#1c0a00',
                'preview_accent' => '#f97316',
                'settings' => [
                    'primary_color'   => '#f97316',
                    'hero_bg_color'   => '#1c0a00',
                    'hero_badge'      => '🔥 Abierto ahora',
                    'hero_title'      => 'Sabor que enamora',
                    'hero_subtitle'   => 'Pide en línea y recibe en tu puerta. Preparado con ingredientes frescos.',
                    'banner1_title'   => 'Combos del día',
                    'banner1_sub'     => 'Platos especiales con descuento',
                    'banner2_title'   => 'Delivery Express',
                    'banner2_sub'     => 'Pedido en 30 minutos',
                    'catalog_layout'  => 'list',
                    'card_style'      => 'food',
                    'font'            => 'Inter',
                ],
            ],

            'supermercado' => [
                'label'          => 'Market — Supermercado',
                'category'       => 'Gastronomía',
                'icon'           => '🛒',
                'description'    => 'Víveres, abarrotes y productos del hogar. Grid denso con precios prominentes.',
                'preview_bg'     => '#14532d',
                'preview_accent' => '#16a34a',
                'settings' => [
                    'primary_color'   => '#16a34a',
                    'hero_bg_color'   => '#14532d',
                    'hero_badge'      => '🚀 Delivery en 1 hora',
                    'hero_title'      => 'Tu mercado online',
                    'hero_subtitle'   => 'Víveres, abarrotes y más. Frescos, al mejor precio y directo a tu puerta.',
                    'banner1_title'   => 'Frutas & Verduras',
                    'banner1_sub'     => 'Frescas del día',
                    'banner2_title'   => 'Ofertas del día',
                    'banner2_sub'     => 'Solo por hoy',
                    'catalog_layout'  => 'grid',
                    'card_style'      => 'food',
                    'font'            => 'Inter',
                ],
            ],

            // ══════════════════════════════════════════════════════
            // GRUPO: SALUD & BIENESTAR
            // ══════════════════════════════════════════════════════

            'fresh' => [
                'label'          => 'Fresh — Orgánico & Wellness',
                'category'       => 'Salud & Bienestar',
                'icon'           => '🌿',
                'description'    => 'Verde natural, clean, amigable y redondeado. Para orgánicos, naturales y wellness.',
                'preview_bg'     => '#1a2e1a',
                'preview_accent' => '#4ade80',
                'settings' => [
                    'primary_color'      => '#16a34a',
                    'secondary_color'    => '#4ade80',
                    'hero_bg_color'      => '#14532d',
                    'hero_badge'         => '🌿 100% Natural',
                    'hero_title'         => 'Vive más saludable',
                    'hero_subtitle'      => 'Productos orgánicos, naturales y saludables. Directo del productor a tu mesa.',
                    'hero_align'         => 'left',
                    'hero_height'        => 'large',
                    'hero_overlay'       => '40',
                    'hero_cta1_show'     => '1',
                    'hero_cta1_text'     => 'Ver productos',
                    'hero_cta2_show'     => '1',
                    'hero_cta2_text'     => 'Conoce más',
                    'banner1_title'      => 'Productos orgánicos',
                    'banner1_sub'        => 'Sin conservantes ni aditivos',
                    'banner2_title'      => 'Suplementos naturales',
                    'banner2_sub'        => 'Bienestar desde adentro',
                    'catalog_layout'     => 'grid',
                    'card_style'         => 'fresh',
                    'font'               => 'Inter',
                    'font_title'         => 'Nunito',
                    'font_body'          => 'Inter',
                    'border_radius'      => 'pill',
                    'currency_symbol'    => 'S/',
                    'catalog_cols_desktop' => '3',
                    'catalog_cols_mobile'  => '2',
                    'catalog_section_title' => '🌿 Productos naturales',
                    'catalog_badge_sale'  => 'OFERTA',
                    'catalog_badge_new'   => 'NUEVO',
                    'trust_icon_1'       => '🌱',
                    'trust_text_1'       => '100% Orgánico',
                    'trust_icon_2'       => '🚚',
                    'trust_text_2'       => 'Envío rápido',
                    'trust_icon_3'       => '✅',
                    'trust_text_3'       => 'Garantía 30 días',
                    'float_cart_show'    => '1',
                    'float_wa_show'      => '1',
                    'float_wa_tooltip'   => '¿Necesitas ayuda?',
                    'btn_cart_text'      => 'Agregar al carrito',
                    'btn_quote_text'     => 'Cotizar',
                    'btn_shape'          => 'pill',
                    'footer_tagline'     => 'Productos naturales para una vida mejor.',
                    'footer_copyright'   => '',
                ],
            ],

            'farmacia' => [
                'label'          => 'Farmacia & Salud',
                'category'       => 'Salud & Bienestar',
                'icon'           => '💊',
                'description'    => 'Medicamentos, suplementos y productos de salud. Confiable y claro.',
                'preview_bg'     => '#0c4a6e',
                'preview_accent' => '#0ea5e9',
                'settings' => [
                    'primary_color'   => '#0ea5e9',
                    'hero_bg_color'   => '#0c4a6e',
                    'hero_badge'      => '✅ Farmacéuticos certificados',
                    'hero_title'      => 'Tu salud, nuestra prioridad',
                    'hero_subtitle'   => 'Medicamentos, vitaminas y productos de bienestar. Entrega a domicilio.',
                    'banner1_title'   => 'Vitaminas & Suplementos',
                    'banner1_sub'     => 'Refuerza tu salud',
                    'banner2_title'   => 'Medicamentos disponibles',
                    'banner2_sub'     => 'Con y sin receta médica',
                    'catalog_layout'  => 'list',
                    'card_style'      => 'detailed',
                    'font'            => 'Inter',
                ],
            ],

            'farma' => [
                'label'          => 'Farma — Farmacia Premium',
                'category'       => 'Salud & Bienestar',
                'icon'           => '💊',
                'description'    => 'Farmacia moderna con hero azul, barra de confianza, sección de ofertas y catálogo con sidebar. Adaptada de plantilla Envato.',
                'preview_bg'     => '#1a3a5c',
                'preview_accent' => '#2F80ED',
                'settings' => [
                    'primary_color'        => '#2F80ED',
                    'secondary_color'      => '#56CCF2',
                    'hero_bg_color'        => '#1a3a5c',
                    'hero_badge'           => '✅ Farmacéuticos certificados',
                    'hero_title'           => 'Tu salud, nuestra prioridad',
                    'hero_subtitle'        => 'Medicamentos, vitaminas y productos de bienestar. Entrega a domicilio.',
                    'hero_align'           => 'left',
                    'hero_height'          => 'large',
                    'hero_overlay'         => '30',
                    'hero_cta1_show'       => '1',
                    'hero_cta1_text'       => 'Ver productos',
                    'hero_cta2_show'       => '1',
                    'hero_cta2_text'       => 'Ver ofertas',
                    'banner1_title'        => 'Vitaminas & Suplementos',
                    'banner1_sub'          => 'Refuerza tu salud',
                    'banner2_title'        => 'Medicamentos disponibles',
                    'banner2_sub'          => 'Con y sin receta médica',
                    'catalog_layout'       => 'grid',
                    'card_style'           => 'detailed',
                    'font'                 => 'Inter',
                    'font_title'           => 'Poppins',
                    'font_body'            => 'Inter',
                    'border_radius'        => 'rounded',
                    'currency_symbol'      => 'S/',
                    'catalog_cols_desktop' => '4',
                    'catalog_cols_mobile'  => '2',
                    'catalog_section_title' => 'Nuestros productos',
                    'catalog_badge_sale'   => 'OFERTA',
                    'catalog_badge_new'    => 'NUEVO',
                    'trust_icon_1'         => '✅',
                    'trust_text_1'         => 'Calidad certificada',
                    'trust_icon_2'         => '🚚',
                    'trust_text_2'         => 'Envío rápido',
                    'trust_icon_3'         => '🔒',
                    'trust_text_3'         => 'Compra segura',
                    'trust_icon_4'         => '💬',
                    'trust_text_4'         => 'Soporte 24/7',
                    'float_cart_show'      => '1',
                    'float_wa_show'        => '1',
                    'float_wa_tooltip'     => '¿Necesitas ayuda?',
                    'btn_cart_text'        => 'Agregar al carrito',
                    'btn_quote_text'       => 'Cotizar',
                    'btn_shape'            => 'rounded',
                    'announcement_text'    => '🚚 Envío gratis en pedidos mayores a S/ 50',
                    'announcement_bg'      => '#2F80ED',
                    'footer_tagline'       => 'Tu salud, nuestra prioridad.',
                    'footer_copyright'     => '',
                ],
            ],

            'belleza' => [
                'label'          => 'Beauty — Cosméticos & Spa',
                'category'       => 'Salud & Bienestar',
                'icon'           => '💄',
                'description'    => 'Cosméticos, cuidado personal, spa y estética. Elegante y femenino.',
                'preview_bg'     => '#2d1b2e',
                'preview_accent' => '#d946ef',
                'settings' => [
                    'primary_color'   => '#d946ef',
                    'hero_bg_color'   => '#2d1b2e',
                    'hero_badge'      => '✨ Productos premium',
                    'hero_title'      => 'Tu belleza, nuestra pasión',
                    'hero_subtitle'   => 'Cosméticos, tratamientos y productos de belleza seleccionados para ti.',
                    'banner1_title'   => 'Skincare & Cuidado',
                    'banner1_sub'     => 'Rutina perfecta para tu piel',
                    'banner2_title'   => 'Maquillaje',
                    'banner2_sub'     => 'Colores que te destacan',
                    'catalog_layout'  => 'grid',
                    'card_style'      => 'minimal',
                    'font'            => 'Inter',
                ],
            ],

            'deporte' => [
                'label'          => 'Sport — Fitness & Gym',
                'category'       => 'Salud & Bienestar',
                'icon'           => '🏋️',
                'description'    => 'Equipos de gym, ropa deportiva y suplementos. Energía y motivación.',
                'preview_bg'     => '#0c0c0c',
                'preview_accent' => '#22c55e',
                'settings' => [
                    'primary_color'   => '#22c55e',
                    'hero_bg_color'   => '#0c0c0c',
                    'hero_badge'      => '💪 Sin límites',
                    'hero_title'      => 'Supera tus límites',
                    'hero_subtitle'   => 'Equipos, suplementos y ropa para alcanzar tus metas. Envío gratis en pedidos mayores.',
                    'banner1_title'   => 'Equipos de gym',
                    'banner1_sub'     => 'Entrena en casa o en el gym',
                    'banner2_title'   => 'Suplementos & Nutrición',
                    'banner2_sub'     => 'Proteínas y vitaminas',
                    'catalog_layout'  => 'grid',
                    'card_style'      => 'bold',
                    'font'            => 'Inter',
                ],
            ],

            'mascotas' => [
                'label'          => 'Pet Shop — Mascotas',
                'category'       => 'Salud & Bienestar',
                'icon'           => '🐾',
                'description'    => 'Alimentos, accesorios y servicios para mascotas. Amigable y colorido.',
                'preview_bg'     => '#312e81',
                'preview_accent' => '#8b5cf6',
                'settings' => [
                    'primary_color'   => '#8b5cf6',
                    'hero_bg_color'   => '#312e81',
                    'hero_badge'      => '🐾 Tu mascota lo merece',
                    'hero_title'      => 'Todo para tu mascota',
                    'hero_subtitle'   => 'Alimentos premium, accesorios y servicio veterinario. Cuida a quien te cuida.',
                    'banner1_title'   => 'Alimentos & Nutrición',
                    'banner1_sub'     => 'Las mejores marcas',
                    'banner2_title'   => 'Accesorios & Juguetes',
                    'banner2_sub'     => 'Hazlos felices',
                    'catalog_layout'  => 'grid',
                    'card_style'      => 'minimal',
                    'font'            => 'Inter',
                ],
            ],

            // ══════════════════════════════════════════════════════
            // GRUPO: HOGAR & CONSTRUCCIÓN
            // ══════════════════════════════════════════════════════

            'nordic' => [
                'label'          => 'Nordic — Hogar & Deco',
                'category'       => 'Hogar & Construcción',
                'icon'           => '🪑',
                'description'    => 'Escandinavo, beige/crema, orgánico, aireado. Para muebles, hogar y decoración.',
                'preview_bg'     => '#f5f0e8',
                'preview_accent' => '#8b6914',
                'settings' => [
                    'primary_color'      => '#8b6914',
                    'secondary_color'    => '#b8971e',
                    'hero_bg_color'      => '#3d2b1f',
                    'hero_badge'         => 'Diseño & Confort',
                    'hero_title'         => 'Tu hogar, tu espacio',
                    'hero_subtitle'      => 'Muebles, decoración y accesorios para transformar cada rincón de tu hogar.',
                    'hero_align'         => 'left',
                    'hero_height'        => 'large',
                    'hero_overlay'       => '35',
                    'hero_cta1_show'     => '1',
                    'hero_cta1_text'     => 'Ver colección',
                    'hero_cta2_show'     => '0',
                    'hero_cta2_text'     => 'Explorar',
                    'banner1_title'      => 'Sala & Comedor',
                    'banner1_sub'        => 'Espacios que inspiran',
                    'banner2_title'      => 'Dormitorio & Descanso',
                    'banner2_sub'        => 'Calidad para tu descanso',
                    'catalog_layout'     => 'grid',
                    'card_style'         => 'nordic',
                    'font'               => 'Jost',
                    'font_title'         => 'Raleway',
                    'font_body'          => 'Jost',
                    'border_radius'      => 'rounded',
                    'currency_symbol'    => 'S/',
                    'catalog_cols_desktop' => '3',
                    'catalog_cols_mobile'  => '2',
                    'catalog_section_title' => 'Colección',
                    'catalog_badge_sale'  => 'OFERTA',
                    'catalog_badge_new'   => 'NUEVO',
                    'trust_icon_1'       => '🚚',
                    'trust_text_1'       => 'Envío gratuito',
                    'trust_icon_2'       => '🔄',
                    'trust_text_2'       => 'Devolución 30 días',
                    'trust_icon_3'       => '🛡️',
                    'trust_text_3'       => 'Garantía de calidad',
                    'float_cart_show'    => '1',
                    'float_wa_show'      => '1',
                    'float_wa_tooltip'   => '¿Necesitas asesoría?',
                    'btn_cart_text'      => 'Agregar al carrito',
                    'btn_quote_text'     => 'Cotizar',
                    'btn_shape'          => 'rounded',
                    'footer_tagline'     => 'Espacios que cuentan historias.',
                    'footer_copyright'   => '',
                ],
            ],

            'ferreteria' => [
                'label'          => 'Ferretería & Construcción',
                'category'       => 'Hogar & Construcción',
                'icon'           => '🔧',
                'description'    => 'Herramientas, materiales y equipos. Robusto y directo al grano.',
                'preview_bg'     => '#111827',
                'preview_accent' => '#f59e0b',
                'settings' => [
                    'primary_color'   => '#f59e0b',
                    'hero_bg_color'   => '#111827',
                    'hero_badge'      => '🔨 Calidad garantizada',
                    'hero_title'      => 'Todo para tu obra',
                    'hero_subtitle'   => 'Herramientas, materiales y equipos de construcción. Entrega inmediata.',
                    'banner1_title'   => 'Herramientas eléctricas',
                    'banner1_sub'     => 'Las mejores marcas',
                    'banner2_title'   => 'Materiales de construcción',
                    'banner2_sub'     => 'Precios de mayorista',
                    'catalog_layout'  => 'grid',
                    'card_style'      => 'detailed',
                    'font'            => 'Inter',
                ],
            ],

            // ══════════════════════════════════════════════════════
            // GRUPO: RETAIL GENERAL
            // ══════════════════════════════════════════════════════

            'flash' => [
                'label'          => 'Flash — Ofertas & Descuentos',
                'category'       => 'Retail General',
                'icon'           => '⚡',
                'description'    => 'Agresivo, rojo/naranja, badges de oferta prominentes. Máxima conversión.',
                'preview_bg'     => '#7f1d1d',
                'preview_accent' => '#ef4444',
                'settings' => [
                    'primary_color'      => '#ef4444',
                    'secondary_color'    => '#f97316',
                    'hero_bg_color'      => '#1c0707',
                    'hero_badge'         => '⚡ OFERTAS DEL DÍA',
                    'hero_title'         => 'Precios que sorprenden',
                    'hero_subtitle'      => 'Descuentos reales hasta 60% off. Ofertas por tiempo limitado. ¡No te lo pierdas!',
                    'hero_align'         => 'center',
                    'hero_height'        => 'medium',
                    'hero_overlay'       => '60',
                    'hero_cta1_show'     => '1',
                    'hero_cta1_text'     => 'Ver ofertas',
                    'hero_cta2_show'     => '0',
                    'hero_cta2_text'     => 'Flash Sale',
                    'banner1_title'      => 'Flash Sale — 24 horas',
                    'banner1_sub'        => 'Los mejores precios del año',
                    'banner2_title'      => 'Liquidación total',
                    'banner2_sub'        => 'Stock limitado',
                    'catalog_layout'     => 'grid',
                    'card_style'         => 'flash',
                    'font'               => 'Inter',
                    'font_title'         => 'Inter',
                    'font_body'          => 'Inter',
                    'border_radius'      => 'rounded',
                    'currency_symbol'    => 'S/',
                    'catalog_cols_desktop' => '4',
                    'catalog_cols_mobile'  => '2',
                    'catalog_section_title' => '⚡ Ofertas del día',
                    'catalog_badge_sale'  => '% OFF',
                    'catalog_badge_new'   => 'NUEVO',
                    'float_cart_show'    => '1',
                    'float_wa_show'      => '1',
                    'float_wa_tooltip'   => '¿Necesitas ayuda?',
                    'btn_cart_text'      => 'Agregar al carrito',
                    'btn_quote_text'     => 'Cotizar',
                    'btn_shape'          => 'rounded',
                    'announcement_text'  => '⚡ FLASH SALE — Hasta 60% OFF hoy',
                    'announcement_bg'    => '#ef4444',
                    'countdown_label'    => '¡Oferta termina en:',
                    'footer_tagline'     => 'Los mejores precios, garantizados.',
                    'footer_copyright'   => '',
                ],
            ],

            'classic' => [
                'label'          => 'Classic — Retail General',
                'category'       => 'Retail General',
                'icon'           => '🏪',
                'description'    => 'Azul marino, clásico, completo y funcional. Para tiendas de varios rubros.',
                'preview_bg'     => '#1e3a5f',
                'preview_accent' => '#1d4ed8',
                'settings' => [
                    'primary_color'   => '#1d4ed8',
                    'hero_bg_color'   => '#1e3a5f',
                    'hero_badge'      => '🏪 Tienda oficial',
                    'hero_title'      => 'Todo lo que necesitas',
                    'hero_subtitle'   => 'Gran variedad de productos al mejor precio. Envío rápido y seguro.',
                    'banner1_title'   => 'Productos destacados',
                    'banner1_sub'     => 'Los más vendidos de la semana',
                    'banner2_title'   => 'Ofertas especiales',
                    'banner2_sub'     => 'Descuentos exclusivos online',
                    'catalog_layout'  => 'grid',
                    'card_style'      => 'detailed',
                    'font'            => 'Inter',
                ],
            ],

            'market' => [
                'label'          => 'Market — Mayorista & Abasto',
                'category'       => 'Retail General',
                'icon'           => '📦',
                'description'    => 'Grid denso con precios prominentes y badges. Para ferreterías, mayoristas y abasto.',
                'preview_bg'     => '#1c1917',
                'preview_accent' => '#ea580c',
                'settings' => [
                    'primary_color'   => '#ea580c',
                    'hero_bg_color'   => '#1c1917',
                    'hero_badge'      => '📦 Precios de mayorista',
                    'hero_title'      => 'Compra más, paga menos',
                    'hero_subtitle'   => 'Precios directos sin intermediarios. Pedidos al por mayor y menor.',
                    'banner1_title'   => 'Mejores precios garantizados',
                    'banner1_sub'     => 'Comparamos y ganamos',
                    'banner2_title'   => 'Pedidos por volumen',
                    'banner2_sub'     => 'Consulta descuentos especiales',
                    'catalog_layout'  => 'grid',
                    'card_style'      => 'flash',
                    'font'            => 'Inter',
                ],
            ],

            // ══════════════════════════════════════════════════════
            // GRUPO: SERVICIOS & PROFESIONALES
            // ══════════════════════════════════════════════════════

            'servicios' => [
                'label'          => 'Servicios Profesionales',
                'category'       => 'Servicios & Profesionales',
                'icon'           => '💼',
                'description'    => 'Consultoría, diseño, contabilidad, legal. Confiable y profesional.',
                'preview_bg'     => '#1e293b',
                'preview_accent' => '#3b82f6',
                'settings' => [
                    'primary_color'   => '#3b82f6',
                    'hero_bg_color'   => '#1e293b',
                    'hero_badge'      => '⭐ Profesionales certificados',
                    'hero_title'      => 'Soluciones que impulsan tu negocio',
                    'hero_subtitle'   => 'Servicios profesionales especializados. Resultados garantizados.',
                    'banner1_title'   => 'Consultoría & Asesoría',
                    'banner1_sub'     => 'Acompañamiento personalizado',
                    'banner2_title'   => 'Proyectos a medida',
                    'banner2_sub'     => 'Cotiza sin costo',
                    'catalog_layout'  => 'cards',
                    'card_style'      => 'service',
                    'font'            => 'Inter',
                ],
            ],

            // ══════════════════════════════════════════════════════
            // GRUPO: EDUCACIÓN & CULTURA
            // ══════════════════════════════════════════════════════

            'libreria' => [
                'label'          => 'Librería & Papelería',
                'category'       => 'Educación & Cultura',
                'icon'           => '📚',
                'description'    => 'Libros, útiles escolares y material de oficina. Claro y ordenado.',
                'preview_bg'     => '#1e3a5f',
                'preview_accent' => '#0891b2',
                'settings' => [
                    'primary_color'   => '#0891b2',
                    'hero_bg_color'   => '#1e3a5f',
                    'hero_badge'      => '📖 Conocimiento al alcance',
                    'hero_title'      => 'Tu mundo de libros',
                    'hero_subtitle'   => 'Libros, útiles y papelería. Todo lo que necesitas para estudiar y crear.',
                    'banner1_title'   => 'Textos escolares',
                    'banner1_sub'     => 'Para todas las edades y niveles',
                    'banner2_title'   => 'Material de oficina',
                    'banner2_sub'     => 'Precios por mayor y menor',
                    'catalog_layout'  => 'grid',
                    'card_style'      => 'detailed',
                    'font'            => 'Inter',
                ],
            ],

            // ══════════════════════════════════════════════════════
            // GRUPO: BEBIDAS & LICORERÍA
            // ══════════════════════════════════════════════════════

            'licoreria' => [
                'label'          => 'Licorería — Bebidas & Spirits',
                'category'       => 'Bebidas & Licorería',
                'icon'           => '🥃',
                'description'    => 'Fondo navy oscuro con acentos dorados. Tipografía serif elegante. Para licorerías, vinotecas y tiendas de spirits.',
                'preview_bg'     => '#0d1117',
                'preview_accent' => '#b8973a',
                'settings' => [
                    'primary_color'        => '#b8973a',
                    'secondary_color'      => '#8c6a1e',
                    'hero_bg_color'        => '#0d1117',
                    'hero_badge'           => '🥃 Selección Premium',
                    'hero_title'           => 'Los mejores spirits te esperan',
                    'hero_subtitle'        => 'Licores, vinos, cervezas y más. Delivery a domicilio y recojo en tienda.',
                    'hero_align'           => 'center',
                    'hero_height'          => 'large',
                    'hero_overlay'         => '50',
                    'hero_cta1_show'       => '1',
                    'hero_cta1_text'       => 'Ver catálogo',
                    'hero_cta2_show'       => '1',
                    'hero_cta2_text'       => 'Ofertas del día',
                    'banner1_title'        => 'Whiskies & Bourbon',
                    'banner1_sub'          => 'Single malt y blended seleccionados',
                    'banner2_title'        => 'Vinos & Espumantes',
                    'banner2_sub'          => 'Los mejores varietales de la región',
                    'catalog_layout'       => 'grid',
                    'card_style'           => 'dark',
                    'font'                 => 'Playfair Display',
                    'font_title'           => 'Playfair Display',
                    'font_body'            => 'Inter',
                    'border_radius'        => 'rounded',
                    'currency_symbol'      => 'S/',
                    'catalog_cols_desktop' => '4',
                    'catalog_cols_mobile'  => '2',
                    'catalog_section_title' => 'Nuestros productos',
                    'catalog_badge_sale'   => 'OFERTA',
                    'catalog_badge_new'    => 'NUEVO',
                    'float_cart_show'      => '1',
                    'float_wa_show'        => '1',
                    'float_wa_tooltip'     => '¿Necesitas ayuda?',
                    'btn_cart_text'        => 'Agregar al carrito',
                    'btn_quote_text'       => 'Cotizar',
                    'btn_shape'            => 'rounded',
                    'announcement_text'    => '🥃 Delivery disponible · Envío gratis en pedidos mayores a S/ 150',
                    'announcement_bg'      => '#161b22',
                    'trust_icon_1'         => '🚚',
                    'trust_text_1'         => 'Envío rápido a domicilio',
                    'trust_icon_2'         => '🔒',
                    'trust_text_2'         => 'Pago 100% seguro',
                    'trust_icon_3'         => '✅',
                    'trust_text_3'         => 'Producto auténtico',
                    'trust_icon_4'         => '🔞',
                    'trust_text_4'         => 'Solo mayores de 18',
                    'age_gate'             => '0',
                    'footer_tagline'       => 'Los mejores spirits, directo a tu puerta.',
                    'footer_copyright'     => '',
                ],
            ],

            // ══════════════════════════════════════════════════════
            // GRUPO: LUJO & PREMIUM
            // ══════════════════════════════════════════════════════

            'joyeria' => [
                'label'          => 'Joyería & Accesorios de Lujo',
                'category'       => 'Lujo & Premium',
                'icon'           => '💍',
                'description'    => 'Joyería fina, bisutería y accesorios de lujo. Oscuro y dorado.',
                'preview_bg'     => '#1a0a00',
                'preview_accent' => '#d97706',
                'settings' => [
                    'primary_color'   => '#d97706',
                    'hero_bg_color'   => '#1a0a00',
                    'hero_badge'      => '💎 Joyería premium',
                    'hero_title'      => 'Brilla con cada momento',
                    'hero_subtitle'   => 'Joyería fina y accesorios exclusivos. Cada pieza cuenta una historia.',
                    'banner1_title'   => 'Nueva colección 2026',
                    'banner1_sub'     => 'Diseños exclusivos y únicos',
                    'banner2_title'   => 'Personaliza tu joya',
                    'banner2_sub'     => 'Grabado y ajuste incluido',
                    'catalog_layout'  => 'grid',
                    'card_style'      => 'luxury',
                    'font'            => 'Jost',
                ],
            ],

            'porto' => [
                'label'          => 'Porto — Clásico E-commerce',
                'category'       => 'General',
                'icon'           => '🏪',
                'description'    => 'E-commerce completo. Top bar, megamenú, hero slider, tabs de productos y cards con hover avanzado.',
                'preview_bg'     => '#f4f4f4',
                'preview_accent' => '#ff7004',
                'settings' => [
                    'primary_color'      => '#ff7004',
                    'secondary_color'    => '#e55f00',
                    'hero_bg_color'      => '#212529',
                    'hero_badge'         => '🔥 Ofertas del día',
                    'hero_title'         => 'Todo lo que necesitas',
                    'hero_subtitle'      => 'Gran catálogo de productos. Envíos rápidos y seguros a todo el país.',
                    'hero_align'         => 'left',
                    'hero_height'        => 'large',
                    'hero_overlay'       => '50',
                    'hero_cta1_show'     => '1',
                    'hero_cta1_text'     => 'Ver productos',
                    'hero_cta2_show'     => '1',
                    'hero_cta2_text'     => 'Ofertas del día',
                    'banner1_title'      => 'Más vendidos',
                    'banner1_sub'        => 'Los favoritos de nuestros clientes',
                    'banner2_title'      => 'Nuevos ingresos',
                    'banner2_sub'        => 'Frescos y listos para ti',
                    'catalog_layout'     => 'grid',
                    'card_style'         => 'minimal',
                    'font'               => 'Poppins',
                    'font_title'         => 'Poppins',
                    'font_body'          => 'Poppins',
                    'border_radius'      => 'rounded',
                    'currency_symbol'    => 'S/',
                    'catalog_cols_desktop' => '4',
                    'catalog_cols_mobile'  => '2',
                    'catalog_section_title' => 'Nuestros productos',
                    'catalog_badge_sale'  => 'OFERTA',
                    'catalog_badge_new'   => 'NUEVO',
                    'tab1_label'         => 'Destacados',
                    'tab2_label'         => 'Nuevos',
                    'tab3_label'         => 'Ofertas',
                    'announcement_text'  => '',
                    'announcement_bg'    => '#ff7004',
                    'float_cart_show'    => '1',
                    'float_wa_show'      => '1',
                    'float_wa_tooltip'   => '¿Necesitas ayuda?',
                    'btn_cart_text'      => 'Agregar al carrito',
                    'btn_quote_text'     => 'Cotizar',
                    'btn_shape'          => 'rounded',
                    'footer_tagline'     => 'Tu tienda online de confianza.',
                    'footer_copyright'   => '',
                ],
            ],

            // ══════════════════════════════════════════════════════
            // GRUPO: SERVICIOS & PROFESIONALES
            // ══════════════════════════════════════════════════════

            'lavanderia' => [
                'label'          => 'Lavandería — Lavado & Planchado',
                'category'       => 'Servicios & Profesionales',
                'icon'           => '🧺',
                'description'    => 'Azul turquesa fresco y limpio. Servicios de lavado por kilo, planchado y prendas especiales. Ideal para lavanderías y tintorerías con recojo y entrega.',
                'preview_bg'     => '#0e2a3a',
                'preview_accent' => '#06b6d4',
                'settings' => [
                    'primary_color'        => '#06b6d4',
                    'secondary_color'      => '#0891b2',
                    'hero_bg_color'        => '#0e2a3a',
                    'hero_badge'           => '🧺 Recojo y entrega a domicilio',
                    'hero_title'           => 'Ropa limpia, sin complicarte',
                    'hero_subtitle'        => 'Lavado por kilo, planchado y prendas especiales. Recogemos, lavamos y te lo devolvemos listo.',
                    'hero_align'           => 'left',
                    'hero_height'          => 'large',
                    'hero_overlay'         => '45',
                    'hero_cta1_show'       => '1',
                    'hero_cta1_text'       => 'Ver servicios',
                    'hero_cta2_show'       => '1',
                    'hero_cta2_text'       => 'Agendar recojo',
                    'banner1_title'        => 'Lavado por kilo',
                    'banner1_sub'          => 'Lavado, secado y doblado desde S/ 8/kg',
                    'banner2_title'        => 'Prendas especiales',
                    'banner2_sub'          => 'Ternos, edredones, cortinas y más',
                    'catalog_layout'       => 'cards',
                    'card_style'           => 'service',
                    'font'                 => 'Poppins',
                    'font_title'           => 'Poppins',
                    'font_body'            => 'Inter',
                    'border_radius'        => 'rounded',
                    'currency_symbol'      => 'S/',
                    'catalog_cols_desktop' => '3',
                    'catalog_cols_mobile'  => '1',
                    'catalog_section_title' => 'Nuestros servicios',
                    'catalog_badge_sale'   => 'PROMO',
                    'catalog_badge_new'    => 'NUEVO',
                    'trust_icon_1'         => '🚚',
                    'trust_text_1'         => 'Recojo y entrega gratis',
                    'trust_icon_2'         => '⚡',
                    'trust_text_2'         => 'Entrega en 24-48h',
                    'trust_icon_3'         => '✨',
                    'trust_text_3'         => 'Prendas como nuevas',
                    'trust_icon_4'         => '💧',
                    'trust_text_4'         => 'Insumos hipoalergénicos',
                    'float_cart_show'      => '1',
                    'float_wa_show'        => '1',
                    'float_wa_tooltip'     => '¿Agendamos tu recojo?',
                    'btn_cart_text'        => 'Solicitar servicio',
                    'btn_quote_text'       => 'Cotizar',
                    'btn_shape'            => 'rounded',
                    'announcement_text'    => '🧺 Recojo y entrega gratis en pedidos desde S/ 30',
                    'announcement_bg'      => '#0891b2',
                    'footer_tagline'       => 'Tu ropa en las mejores manos.',
                    'footer_copyright'     => '',
                ],
            ],

        ];
    }

    /**
     * Retorna una plantilla por clave. Null si no existe.
     */
    public static function get(string $key): ?array
    {
        return static::all()[$key] ?? null;
    }

    /**
     * Claves oficialmente seleccionables en el panel administrativo.
     */
    public static function supportedKeys(): array
    {
        return self::SUPPORTED_KEYS;
    }

    /**
     * Catálogo oficial. Las entradas heredadas permanecen disponibles en
     * all(), pero nunca se filtran hacia el selector desde este método.
     */
    public static function supported(): array
    {
        return array_intersect_key(static::all(), array_flip(self::SUPPORTED_KEYS));
    }

    public static function isSupported(?string $key): bool
    {
        return is_string($key) && in_array($key, self::SUPPORTED_KEYS, true);
    }

    /**
     * Metadatos públicos y estables usados por API y Diseñador.
     */
    public static function supportedTheme(string $key): ?array
    {
        if (!static::isSupported($key)) {
            return null;
        }

        $template = static::get($key);
        if (!$template || ($template['supported'] ?? false) !== true) {
            return null;
        }

        return [
            'key' => $template['key'],
            'name' => $template['name'],
            'description' => $template['short_description'],
            'view' => $template['view'],
            'preview' => $template['preview_image'] ?? null,
            'supported' => true,
            'capabilities' => array_values($template['capabilities'] ?? []),
        ];
    }

    /**
     * Retorna las plantillas agrupadas por categoría.
     */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (static::all() as $key => $tpl) {
            $grouped[$tpl['category']][$key] = $tpl;
        }
        return $grouped;
    }

    /**
     * Lista de card_styles disponibles con su descripción visual.
     */
    public static function cardStyles(): array
    {
        return [
            'minimal'   => 'Limpio, sin bordes, imagen dominante',
            'editorial' => 'Sharp corners, blanco/negro, tipografía bold',
            'bold'      => 'Colores intensos, badges grandes, energético',
            'food'      => 'Imagen grande, precio destacado, apetitoso',
            'tech'      => 'Oscuro, specs visibles, gradiente sutil',
            'detailed'  => 'Con descripción, SKU y detalles del producto',
            'service'   => 'Card horizontal con ícono, sin imagen obligatoria',
            'luxury'    => 'Fondo oscuro, dorado, animaciones suaves',
            'dark'      => 'Superficie oscura, acento brillante',
            'fresh'     => 'Verde, redondeado, badge orgánico',
            'flash'     => 'Badge de oferta siempre visible, precio tachado',
            'nordic'    => 'Beige/crema, bordes suaves, mucho espacio',
        ];
    }

    public static function components(string $templateKey): array
    {
        $template = static::get($templateKey);
        if (!$template) {
            return [];
        }
        if (!empty($template['components']) && is_array($template['components'])) {
            return $template['components'];
        }
        return static::inferComponents($template);
    }

    protected static function inferComponents(array $template): array
    {
        $components = [];
        $settings = $template['settings'] ?? [];

        if (!empty($settings['hero_title']) || !empty($settings['hero_subtitle']) || !empty($settings['hero_badge'])) {
            $components[] = 'hero';
        }
        if (!empty($settings['announcement_text']) || !empty($settings['announcement_bg'])) {
            $components[] = 'announcement';
        }
        if (!empty($settings['countdown_end']) || !empty($settings['countdown_label'])) {
            $components[] = 'countdown';
        }
        if (!empty($settings['split_left_title']) || !empty($settings['split_right_title'])) {
            $components[] = 'split_banner';
        }
        if (!empty($settings['trust_icon_1']) || !empty($settings['trust_text_1'])) {
            $components[] = 'trust_bar';
        }
        if (!empty($settings['trust_icon_4']) || !empty($settings['trust_text_4'])) {
            $components[] = 'trust_bar_4';
        }
        if (array_key_exists('age_gate', $settings)) {
            $components[] = 'age_gate_field';
        }
        if (!empty($settings['tab1_label']) || !empty($settings['tab2_label']) || !empty($settings['tab3_label'])) {
            $components[] = 'tabs_title';
        }
        if (!empty($settings['catalog_layout']) || !empty($settings['card_style'])) {
            $components[] = 'catalog';
        }
        if (!empty($settings['float_wa_show']) || !empty($settings['float_cart_show']) || !empty($settings['quote_whatsapp'])) {
            $components[] = 'whatsapp';
            $components[] = 'cart';
        }
        if (!empty($settings['footer_tagline']) || !empty($settings['footer_pages']) || array_key_exists('footer_store_pages', $settings)
            || !empty($settings['contact_email']) || !empty($settings['contact_phone']) || !empty($settings['business_hours'])) {
            $components[] = 'footer';
        }
        if (!empty($settings['footer_newsletter_title']) || !empty($settings['footer_newsletter_url'])) {
            $components[] = 'newsletter';
        }

        return array_values(array_unique($components));
    }

    public static function componentCatalog(): array
    {
        return [
            'hero' => [
                'label' => 'Hero',
                'description' => 'Sección principal del inicio con título, subtítulo, fondo y CTAs.',
                'page' => 'home',
                'fields' => ['hero_title','hero_subtitle','hero_badge','hero_bg_color','hero_align','hero_height','hero_overlay','hero_cta1_show','hero_cta1_text','hero_cta2_show','hero_cta2_text','hero_image'],
                'variants' => ['default','split','banner'],
            ],
            'announcement' => [
                'label' => 'Banner simple',
                'description' => 'Texto de anuncio o promoción debajo del header.',
                'page' => 'home',
                'fields' => ['announcement_text','announcement_bg'],
                'variants' => ['strip','floating'],
            ],
            'countdown' => [
                'label' => 'Contador regresivo',
                'description' => 'Sección de oferta con fecha de fin visible.',
                'page' => 'home',
                'fields' => ['countdown_label','countdown_end'],
                'variants' => ['inline','banner'],
            ],
            'split_banner' => [
                'label' => 'Banner dividido',
                'description' => 'Sección de dos columnas con texto y llamado a la acción.',
                'page' => 'home',
                'fields' => ['split_left_title','split_left_sub','split_right_title','split_right_sub'],
                'variants' => ['50/50','image-left','image-right'],
            ],
            'trust_bar' => [
                'label' => 'Barra de confianza',
                'description' => 'Tres beneficios destacando envíos, garantía o soporte.',
                'page' => 'home',
                'fields' => ['trust_icon_1','trust_text_1','trust_icon_2','trust_text_2','trust_icon_3','trust_text_3'],
                'variants' => ['3-items'],
            ],
            'trust_bar_4' => [
                'label' => 'Barra de confianza 4 ítems',
                'description' => 'Cuatro beneficios destacados en el home.',
                'page' => 'home',
                'fields' => ['trust_icon_1','trust_text_1','trust_icon_2','trust_text_2','trust_icon_3','trust_text_3','trust_icon_4','trust_text_4'],
                'variants' => ['4-items'],
            ],
            'age_gate_field' => [
                'label' => 'Verificación de edad',
                'description' => 'Popup de confirmación de mayoría de edad.',
                'page' => 'home',
                'fields' => ['age_gate'],
                'variants' => ['popup'],
            ],
            'tabs_title' => [
                'label' => 'Tabs de productos',
                'description' => 'Etiquetas para secciones de productos en pestañas.',
                'page' => 'home',
                'fields' => ['tab1_label','tab2_label','tab3_label'],
                'variants' => ['tabs'],
            ],
            'catalog' => [
                'label' => 'Catálogo',
                'description' => 'Listado de productos con layout de grid, lista o cards.',
                'page' => 'catalog',
                'fields' => ['catalog_layout','card_style','catalog_cols_desktop','catalog_cols_mobile','catalog_section_title','catalog_badge_sale','catalog_badge_new','catalog_badge_featured','catalog_badge_sold_out','catalog_show_ratings','catalog_quick_view','catalog_show_sku','catalog_show_stock','wholesale_enabled','catalog_filter_price','catalog_filter_cats','catalog_filter_sale','catalog_filter_search'],
                'variants' => ['grid','list','cards'],
            ],
            'cart' => [
                'label' => 'Carrito',
                'description' => 'Drawer o modal de carrito para pedidos.',
                'page' => 'catalog',
                'fields' => ['float_cart_show','float_cart_pos'],
                'variants' => ['drawer','slide'],
            ],
            'whatsapp' => [
                'label' => 'WhatsApp',
                'description' => 'Botón flotante para contacto y cotizaciones.',
                'page' => 'global',
                'fields' => ['float_wa_show','float_wa_pos','float_wa_tooltip','quote_whatsapp','quote_whatsapp_country','quote_wa_msg','whatsapp_msg'],
                'variants' => ['floating','button'],
            ],
            'footer' => [
                'label' => 'Footer',
                'description' => 'Pie de página con datos de contacto y enlaces legales.',
                'page' => 'global',
                'fields' => ['footer_tagline','footer_copyright','footer_dev_text','contact_email','contact_phone','business_hours','footer_benefit_1_icon','footer_benefit_1_text','footer_benefit_2_icon','footer_benefit_2_text','footer_benefit_3_icon','footer_benefit_3_text','footer_pages','footer_store_pages','footer_show_social','footer_show_categories','footer_show_newsletter','footer_show_benefits','footer_show_address','footer_newsletter_title','footer_newsletter_url'],
                'variants' => ['simple','detailed'],
            ],
            'newsletter' => [
                'label' => 'Newsletter',
                'description' => 'Formulario de suscripción al boletín.',
                'page' => 'global',
                'fields' => ['footer_newsletter_title','footer_newsletter_url'],
                'variants' => ['inline','popup'],
            ],
        ];
    }

    public static function manifest(string $templateKey): array
    {
        $template = static::get($templateKey) ?: static::get('default');
        return [
            'template' => $templateKey,
            'key' => $template['key'] ?? $templateKey,
            'name' => $template['name'] ?? ($template['label'] ?? ucfirst($templateKey)),
            'label' => $template['label'] ?? ucfirst($templateKey),
            'description' => $template['short_description'] ?? ($template['description'] ?? ''),
            'view' => $template['view'] ?? null,
            'preview' => $template['preview_image'] ?? null,
            'supported' => static::isSupported($templateKey) && ($template['supported'] ?? false) === true,
            'capabilities' => array_values($template['capabilities'] ?? []),
            'components' => static::components($templateKey),
            'component_catalog' => static::componentCatalog(),
            'settings' => $template['settings'] ?? [],
        ];
    }

    public static function componentFields(string $componentKey): array
    {
        $catalog = static::componentCatalog();
        return $catalog[$componentKey]['fields'] ?? [];
    }

    public static function createProjectTemplate(Project $project, string $templateKey = 'default', ?string $name = null, bool $activate = true): ?ProjectTemplate
    {
        $template = static::get($templateKey);
        if (!$template) {
            return null;
        }

        // Establecer la plantilla activa del proyecto para el frontend.
        $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => $templateKey]);

        if ($activate) {
            ProjectTemplate::where('project_id', $project->id)->where('is_active', true)->update(['is_active' => false]);
        }

        return ProjectTemplate::create([
            'project_id'  => $project->id,
            'name'        => $name ?? ('Plantilla ' . ucfirst($templateKey)),
            'description' => 'Plantilla generada automáticamente desde el catálogo de templates.',
            'settings'    => $template['settings'] ?? [],
            'is_active'   => $activate,
        ]);
    }

    /**
     * Categorías predefinidas por plantilla.
     * Estructura: [ ['name' => 'PADRE', 'type' => 'product|service', 'children' => ['Sub1','Sub2']], ... ]
     */
    public static function categories(): array
    {
        return [

            'supermercado' => [
                ['name' => 'ABARROTES',            'type' => 'product', 'children' => ['Arroz', 'Harinas', 'Granos y Semillas', 'Menestras', 'Pastas y Fideos', 'Azúcar y Sal']],
                ['name' => 'CARNES Y DERIVADOS',   'type' => 'product', 'children' => ['Res', 'Pollo', 'Cerdo', 'Embutidos', 'Mariscos']],
                ['name' => 'FRUTAS Y VERDURAS',    'type' => 'product', 'children' => ['Frutas', 'Verduras', 'Tubérculos', 'Hierbas']],
                ['name' => 'LÁCTEOS Y HUEVOS',     'type' => 'product', 'children' => ['Leche', 'Quesos', 'Yogurt', 'Huevos']],
                ['name' => 'PANADERÍA',             'type' => 'product', 'children' => ['Pan', 'Pasteles', 'Galletas']],
                ['name' => 'BEBIDAS',               'type' => 'product', 'children' => ['Aguas', 'Jugos', 'Gaseosas', 'Energizantes']],
                ['name' => 'LIMPIEZA Y HOGAR',     'type' => 'product', 'children' => ['Detergentes', 'Desinfectantes', 'Papel y Servilletas']],
            ],

            'market' => [
                ['name' => 'ABARROTES',            'type' => 'product', 'children' => ['Arroz', 'Azúcar', 'Aceites', 'Conservas', 'Condimentos']],
                ['name' => 'ARTEFACTOS',           'type' => 'product', 'children' => ['Electrodomésticos', 'Iluminación', 'Pilas y Cables']],
                ['name' => 'CARNES Y DERIVADOS',   'type' => 'product', 'children' => ['Res', 'Pollo', 'Cerdo', 'Embutidos']],
                ['name' => 'FRUTAS Y VERDURAS',    'type' => 'product', 'children' => ['Frutas', 'Verduras', 'Tubérculos']],
                ['name' => 'PANADERÍA Y PASTELERÍA','type' => 'product', 'children' => ['Pan de molde', 'Pasteles', 'Galletas']],
                ['name' => 'VINOS, LICORES Y CERVEZAS','type' => 'product', 'children' => ['Cervezas', 'Vinos', 'Destilados', 'Sin alcohol']],
                ['name' => 'LIMPIEZA',             'type' => 'product', 'children' => ['Detergentes', 'Desinfectantes', 'Esponjas']],
            ],

            'restaurante' => [
                ['name' => 'ENTRADAS',             'type' => 'product', 'children' => ['Sopas', 'Ensaladas', 'Aperitivos']],
                ['name' => 'PLATOS DE FONDO',      'type' => 'product', 'children' => ['Carnes', 'Pollos', 'Pescados', 'Vegetariano']],
                ['name' => 'MENÚ DEL DÍA',         'type' => 'product', 'children' => ['Menú Clásico', 'Menú Ejecutivo']],
                ['name' => 'BEBIDAS',              'type' => 'product', 'children' => ['Jugos', 'Gaseosas', 'Agua', 'Calientes']],
                ['name' => 'POSTRES',              'type' => 'product', 'children' => ['Tortas', 'Helados', 'Dulces']],
                ['name' => 'DELIVERY',             'type' => 'service', 'children' => ['Delivery Express', 'Delivery Programado']],
            ],

            'bistro' => [
                ['name' => 'CAFETERÍA',            'type' => 'product', 'children' => ['Cafés', 'Tés e Infusiones', 'Bebidas Frías']],
                ['name' => 'DESAYUNOS',            'type' => 'product', 'children' => ['Sándwiches', 'Tostadas', 'Croissants']],
                ['name' => 'ALMUERZOS',            'type' => 'product', 'children' => ['Platos del día', 'Ensaladas', 'Sopas']],
                ['name' => 'REPOSTERÍA',           'type' => 'product', 'children' => ['Tortas', 'Galletas', 'Muffins']],
                ['name' => 'DELIVERY',             'type' => 'service', 'children' => ['Delivery Express', 'Take Away']],
            ],

            'farma' => [
                ['name' => 'MEDICAMENTOS',         'type' => 'product', 'children' => ['Analgésicos', 'Antibióticos', 'Antiinflamatorios', 'Antialérgicos']],
                ['name' => 'VITAMINAS Y SUPLEMENTOS','type' => 'product','children' => ['Vitamina C', 'Complejo B', 'Omega 3', 'Probióticos']],
                ['name' => 'CUIDADO PERSONAL',     'type' => 'product', 'children' => ['Cremas', 'Shampoo', 'Protector Solar']],
                ['name' => 'BEBÉ Y MATERNIDAD',    'type' => 'product', 'children' => ['Leche de fórmula', 'Pañales', 'Accesorios bebé']],
                ['name' => 'OFERTAS',              'type' => 'product', 'children' => ['Liquidación', 'Pack ahorro']],
                ['name' => 'SERVICIOS',            'type' => 'service', 'children' => ['Inyectables', 'Nebulizaciones', 'Control de presión']],
            ],

            'farmacia' => [
                ['name' => 'MEDICAMENTOS',         'type' => 'product', 'children' => ['Analgésicos', 'Antibióticos', 'Antiinflamatorios', 'Antialérgicos']],
                ['name' => 'VITAMINAS Y SUPLEMENTOS','type' => 'product','children' => ['Vitamina C', 'Complejo B', 'Omega 3', 'Probióticos']],
                ['name' => 'CUIDADO PERSONAL',     'type' => 'product', 'children' => ['Cremas', 'Shampoo', 'Protector Solar']],
                ['name' => 'BEBÉ Y MATERNIDAD',    'type' => 'product', 'children' => ['Leche de fórmula', 'Pañales', 'Accesorios bebé']],
                ['name' => 'SERVICIOS',            'type' => 'service', 'children' => ['Inyectables', 'Nebulizaciones', 'Control de presión']],
            ],

            'mascotas' => [
                ['name' => 'ALIMENTOS',            'type' => 'product', 'children' => ['Para Perros', 'Para Gatos', 'Para Aves', 'Para Peces']],
                ['name' => 'MEDICAMENTOS',         'type' => 'product', 'children' => ['Antiparasitarios', 'Vitaminas', 'Antibióticos']],
                ['name' => 'ACCESORIOS',           'type' => 'product', 'children' => ['Correas y Collares', 'Camas y Casas', 'Juguetes']],
                ['name' => 'CONSULTAS',            'type' => 'service', 'children' => ['Consulta General', 'Consulta de Emergencia']],
                ['name' => 'GROOMING',             'type' => 'service', 'children' => ['Baño y Corte', 'Corte de uñas', 'Desparasitación']],
                ['name' => 'PROCEDIMIENTOS',       'type' => 'service', 'children' => ['Vacunas', 'Cirugías', 'Ecografías']],
            ],

            'ferreteria' => [
                ['name' => 'HERRAMIENTAS',         'type' => 'product', 'children' => ['Manuales', 'Eléctricas', 'De medición']],
                ['name' => 'MATERIALES',           'type' => 'product', 'children' => ['Cemento y Concreto', 'Fierros y Tuberías', 'Pinturas']],
                ['name' => 'ELECTRICIDAD',         'type' => 'product', 'children' => ['Cables', 'Tomacorrientes', 'Lámparas']],
                ['name' => 'GASFITERÍA',           'type' => 'product', 'children' => ['Tuberías PVC', 'Llaves y Válvulas', 'Accesorios']],
                ['name' => 'SERVICIOS',            'type' => 'service', 'children' => ['Instalación Eléctrica', 'Gasfitería', 'Pintura']],
            ],

            'belleza' => [
                ['name' => 'CUIDADO DEL ROSTRO',   'type' => 'product', 'children' => ['Cremas hidratantes', 'Limpiadores', 'Protector solar']],
                ['name' => 'MAQUILLAJE',           'type' => 'product', 'children' => ['Base y corrector', 'Labiales', 'Ojos']],
                ['name' => 'CABELLO',              'type' => 'product', 'children' => ['Shampoo', 'Acondicionador', 'Tratamientos']],
                ['name' => 'PERFUMERÍA',           'type' => 'product', 'children' => ['Para ella', 'Para él', 'Unisex']],
                ['name' => 'TRATAMIENTOS',         'type' => 'service', 'children' => ['Facial', 'Corporal', 'Relajante']],
                ['name' => 'PELUQUERÍA',           'type' => 'service', 'children' => ['Corte', 'Coloración', 'Alisado']],
                ['name' => 'UÑAS',                 'type' => 'service', 'children' => ['Manicure', 'Pedicure', 'Nail Art']],
            ],

            'deporte' => [
                ['name' => 'EQUIPOS DE GYM',       'type' => 'product', 'children' => ['Pesas y mancuernas', 'Máquinas', 'Colchonetas']],
                ['name' => 'ROPA DEPORTIVA',       'type' => 'product', 'children' => ['Hombre', 'Mujer', 'Calzado']],
                ['name' => 'SUPLEMENTOS',          'type' => 'product', 'children' => ['Proteínas', 'Creatina', 'Vitaminas', 'Pre-entreno']],
                ['name' => 'CLASES',               'type' => 'service', 'children' => ['Musculación', 'Crossfit', 'Yoga', 'Spinning']],
            ],

            'nordic' => [
                ['name' => 'SALA Y COMEDOR',       'type' => 'product', 'children' => ['Sofás', 'Mesas', 'Sillas', 'Estantes']],
                ['name' => 'DORMITORIO',           'type' => 'product', 'children' => ['Camas', 'Colchones', 'Roperos', 'Veladores']],
                ['name' => 'DECORACIÓN',           'type' => 'product', 'children' => ['Cuadros', 'Floreros', 'Lámparas', 'Alfombras']],
                ['name' => 'BAÑO Y COCINA',        'type' => 'product', 'children' => ['Accesorios baño', 'Organizadores', 'Cortinas']],
            ],

            'tecnologia' => [
                ['name' => 'SMARTPHONES',          'type' => 'product', 'children' => ['Android', 'iPhone', 'Accesorios']],
                ['name' => 'COMPUTADORAS',         'type' => 'product', 'children' => ['Laptops', 'PC de escritorio', 'Tablets']],
                ['name' => 'PERIFÉRICOS',          'type' => 'product', 'children' => ['Teclados y Mouse', 'Monitores', 'Auriculares']],
                ['name' => 'GAMING',               'type' => 'product', 'children' => ['Consolas', 'Videojuegos', 'Sillas gaming']],
                ['name' => 'SOPORTE TÉCNICO',      'type' => 'service', 'children' => ['Reparación PC', 'Formateo', 'Redes']],
            ],

            'servicios' => [
                ['name' => 'CONSULTORÍA',          'type' => 'service', 'children' => ['Empresarial', 'Legal', 'Contable', 'Financiera']],
                ['name' => 'DISEÑO',               'type' => 'service', 'children' => ['Gráfico', 'Web', 'de Interiores']],
                ['name' => 'MARKETING',            'type' => 'service', 'children' => ['Redes Sociales', 'SEO', 'Publicidad Digital']],
                ['name' => 'CAPACITACIÓN',         'type' => 'service', 'children' => ['Cursos presenciales', 'Cursos online', 'Talleres']],
            ],

            'ella' => [
                ['name' => 'ROPA',                 'type' => 'product', 'children' => ['Vestidos', 'Blusas', 'Pantalones', 'Faldas']],
                ['name' => 'CALZADO',              'type' => 'product', 'children' => ['Zapatillas', 'Sandalias', 'Botines', 'Tacones']],
                ['name' => 'ACCESORIOS',           'type' => 'product', 'children' => ['Carteras', 'Cinturones', 'Joyería', 'Gafas']],
                ['name' => 'NUEVA COLECCIÓN',      'type' => 'product', 'children' => ['Temporada actual', 'Edición limitada']],
            ],

            'urban' => [
                ['name' => 'SNEAKERS',             'type' => 'product', 'children' => ['Running', 'Casual', 'Limited Edition']],
                ['name' => 'ROPA',                 'type' => 'product', 'children' => ['Polos', 'Hoodies', 'Shorts', 'Joggers']],
                ['name' => 'ACCESORIOS',           'type' => 'product', 'children' => ['Gorras', 'Mochilas', 'Calcetines']],
                ['name' => 'COLABS',               'type' => 'product', 'children' => ['Ediciones especiales', 'Drops exclusivos']],
            ],

            'licoreria' => [
                ['name' => 'WHISKIES & BOURBON',   'type' => 'product', 'children' => ['Scotch Single Malt', 'Scotch Blended', 'Bourbon', 'Irlandés', 'Japonés']],
                ['name' => 'VINOS',                'type' => 'product', 'children' => ['Tinto', 'Blanco', 'Rosé', 'Espumante', 'Dulce']],
                ['name' => 'RON & CAÑA',           'type' => 'product', 'children' => ['Ron blanco', 'Ron añejo', 'Pisco', 'Cachaza']],
                ['name' => 'VODKA & GIN',          'type' => 'product', 'children' => ['Vodka', 'Gin', 'Tónica y mixers']],
                ['name' => 'CERVEZAS',             'type' => 'product', 'children' => ['Nacional', 'Importada', 'Artesanal', 'Sin alcohol']],
                ['name' => 'LICORES & DIGESTIVOS', 'type' => 'product', 'children' => ['Amaretto', 'Baileys', 'Kahlúa', 'Aperol', 'Sambuca']],
                ['name' => 'ACCESORIOS',           'type' => 'product', 'children' => ['Copas y vasos', 'Coctelería', 'Hielo y enfriadores']],
            ],

            'lavanderia' => [
                ['name' => 'LAVADO POR KILO',      'type' => 'service', 'children' => ['Lavado + Secado', 'Lavado + Secado + Doblado', 'Solo Secado', 'Lavado Express 24h']],
                ['name' => 'PLANCHADO',            'type' => 'service', 'children' => ['Planchado por kilo', 'Planchado por prenda', 'Camisas y Blusas']],
                ['name' => 'PRENDAS ESPECIALES',   'type' => 'service', 'children' => ['Ternos y Sacos', 'Vestidos y Ternos de gala', 'Edredones y Frazadas', 'Cortinas', 'Zapatillas']],
                ['name' => 'LAVADO EN SECO',       'type' => 'service', 'children' => ['Terno completo', 'Abrigos', 'Prendas delicadas']],
                ['name' => 'DELIVERY',             'type' => 'service', 'children' => ['Recojo a domicilio', 'Entrega a domicilio']],
            ],

        ];
    }

    /**
     * Aplica los settings de una plantilla a un proyecto.
     * Si $force=true sobreescribe todos los settings existentes.
     */
    public static function applyToProject(\App\Models\Project $project, string $templateKey, bool $force = false): void
    {
        $tpl = static::get($templateKey);
        if (!$tpl) return;

        // Guardar la plantilla activa
        $project->settings()->updateOrCreate(
            ['key' => 'catalog_template'],
            ['value' => $templateKey]
        );

        foreach ($tpl['settings'] as $key => $value) {
            if ($force) {
                $project->settings()->updateOrCreate(['key' => $key], ['value' => $value]);
            } else {
                if (!$project->settings()->where('key', $key)->exists()) {
                    $project->settings()->create(['key' => $key, 'value' => $value]);
                }
            }
        }
    }

    /**
     * Crea las categorías predefinidas de una plantilla en el proyecto.
     * Solo crea las que no existen (por nombre).
     */
    public static function applyCategories(\App\Models\Project $project, string $templateKey): void
    {
        $cats = static::categories()[$templateKey] ?? [];
        if (empty($cats)) return;

        $order = $project->categories()->max('sort_order') ?? 0;

        foreach ($cats as $catDef) {
            $parent = \App\Models\Category::firstOrCreate(
                ['project_id' => $project->id, 'name' => $catDef['name']],
                [
                    'type'       => $catDef['type'],
                    'parent_id'  => null,
                    'is_active'  => true,
                    'sort_order' => ++$order,
                ]
            );

            $subOrder = 0;
            foreach ($catDef['children'] as $childName) {
                \App\Models\Category::firstOrCreate(
                    ['project_id' => $project->id, 'name' => $childName, 'parent_id' => $parent->id],
                    [
                        'type'       => $catDef['type'],
                        'is_active'  => true,
                        'sort_order' => ++$subOrder,
                    ]
                );
            }
        }
    }
}
