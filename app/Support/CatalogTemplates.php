<?php

namespace App\Support;

/**
 * Plantillas de catálogo por rubro.
 * Cada plantilla define: colores, fuente, hero, layout, card_style y textos por defecto.
 *
 * card_style: minimal | editorial | bold | food | tech | detailed | service | luxury | dark | fresh | flash | nordic
 * catalog_layout: grid | list | cards
 */
class CatalogTemplates
{
    public static function all(): array
    {
        return [

            // ══════════════════════════════════════════════════════
            // GRUPO: GENERAL
            // ══════════════════════════════════════════════════════

            'default' => [
                'label'          => 'Clásico — Default',
                'category'       => 'General',
                'icon'           => '🏠',
                'description'    => 'Diseño original del sistema. Limpio, funcional y versátil para cualquier rubro.',
                'preview_bg'     => '#1e293b',
                'preview_accent' => '#4f46e5',
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

            'tecnologia' => [
                'label'          => 'Tech — Electrónica',
                'category'       => 'Tecnología',
                'icon'           => '💻',
                'description'    => 'Celulares, computadoras, accesorios tech. Moderno y funcional.',
                'preview_bg'     => '#0f172a',
                'preview_accent' => '#6366f1',
                'settings' => [
                    'primary_color'   => '#6366f1',
                    'hero_bg_color'   => '#0f172a',
                    'hero_badge'      => '⚡ Envío en 24h',
                    'hero_title'      => 'Tech al mejor precio',
                    'hero_subtitle'   => 'Los mejores equipos y accesorios. Garantía oficial y soporte técnico.',
                    'banner1_title'   => 'Gaming & Periféricos',
                    'banner1_sub'     => 'Setup completo aquí',
                    'banner2_title'   => 'Smartphones',
                    'banner2_sub'     => 'Últimos modelos disponibles',
                    'catalog_layout'  => 'grid',
                    'card_style'      => 'tech',
                    'font'            => 'Inter',
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
}
