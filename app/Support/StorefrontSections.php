<?php

namespace App\Support;

use App\Models\Project;
use App\Storefront\StoreSectionWriteService;

class StorefrontSections
{
    public const COMPONENTS = [
        'hero' => 'Banner principal',
        'media_banner' => 'Banner multimedia (imagen o video)',
        'benefits' => 'Beneficios de la tienda',
        'announcements' => 'Bloque de anuncios',
        'featured_categories' => 'Categorías principales',
        'collection_showcase' => 'Colecciones / Compra por ambiente',
        'daily_offer' => 'Solo por hoy',
        'discounts' => 'Productos con descuento',
        'featured_products' => 'Productos destacados',
        'category_rows' => 'Filas por categoría (Laptops, Monitores…)',
        'brands' => 'Marcas',
        'testimonials' => 'Testimonios',
        'gallery' => 'Galería de imágenes y videos',
        'faq' => 'Preguntas frecuentes',
        'wa_advisory' => 'Asesoría por WhatsApp',
        'cta_banner' => 'Llamada a la acción',
        'locations' => 'Sucursales y ubicación',
        'about_preview' => 'Nosotros (resumen en Inicio)',
        'info_strip' => 'Banda informativa (4 bloques)',
        'blog' => 'Blog informativo',
    ];

    public static function defaults(Project $project, ?array $settings = null): array
    {
        $setting = static function (string $key, mixed $default = null) use ($project, $settings): mixed {
            if ($settings !== null && array_key_exists($key, $settings) && $settings[$key] !== null) {
                return $settings[$key];
            }

            return $settings !== null ? $default : $project->setting($key, $default);
        };
        $heroTitle = $setting('hero_title', 'Encuentra lo que necesitas');
        $heroBody = $setting('hero_subtitle', 'Productos seleccionados y atención personalizada para comprar con confianza.');

        return [
            'hero' => [
                'variant' => 'single',
                'enabled' => false,
                'content' => [
                    'mode' => 'single',
                    'single' => [
                        'desktop_image' => $setting('hero_image'),
                        'mobile_image' => null,
                        'title' => $heroTitle,
                        'body' => $heroBody,
                        'primary_text' => $setting('hero_cta1_text', 'Comprar'),
                        'primary_url' => '#catalogo',
                        'primary_color' => $setting('primary_color', '#2563eb'),
                        'secondary_text' => $setting('hero_cta2_text', 'Ver catálogo'),
                        'secondary_url' => '#catalogo',
                        'secondary_color' => $setting('secondary_color', '#0f172a'),
                    ],
                    'slides' => [],
                    'autoplay' => false,
                    'interval' => 6,
                ],
            ],
            'benefits' => [
                'variant' => 'cards',
                'enabled' => false,
                'content' => [
                    'title' => 'Compra con confianza',
                    'body' => 'Beneficios pensados para darte una mejor experiencia.',
                    'items' => [
                        ['key' => 'pickup', 'sort_order' => 10, 'title' => 'Retiro en tienda', 'description' => 'Coordina y recoge tu pedido.', 'icon' => 'store', 'image' => null, 'enabled' => true],
                        ['key' => 'shipping', 'sort_order' => 20, 'title' => 'Envíos a todo el Perú', 'description' => 'Cobertura según destino.', 'icon' => 'truck', 'image' => null, 'enabled' => true],
                        ['key' => 'express', 'sort_order' => 30, 'title' => 'Entrega express', 'description' => 'Consulta disponibilidad en tu zona.', 'icon' => 'clock', 'image' => null, 'enabled' => true],
                        ['key' => 'exclusive', 'sort_order' => 40, 'title' => 'Diseños exclusivos', 'description' => 'Opciones seleccionadas para ti.', 'icon' => 'sparkles', 'image' => null, 'enabled' => true],
                    ],
                ],
            ],
            'announcements' => [
                'variant' => 'auto',
                'enabled' => false,
                'content' => ['title' => 'Promociones', 'quantity' => 2, 'items' => []],
            ],
            'featured_categories' => [
                'variant' => 'images',
                'enabled' => false,
                'content' => ['title' => 'Encuentra lo que buscas', 'display' => 'images', 'limit' => 5, 'category_ids' => []],
            ],
            'daily_offer' => [
                'variant' => 'banner',
                'enabled' => false,
                'content' => [
                    'title' => 'Solo por hoy', 'body' => 'Aprovecha esta promoción por tiempo limitado.',
                    'ends_at' => null, 'image' => null, 'background_color' => '#0f172a',
                    'button_text' => 'Ver oferta', 'button_url' => '#catalogo', 'expired_action' => 'hide',
                    'expired_message' => 'Esta oferta ha finalizado.',
                ],
            ],
            'discounts' => [
                'variant' => 'grid',
                'enabled' => false,
                'content' => [
                    'title' => 'Productos con descuento', 'limit' => 6, 'layout' => 'grid',
                    'columns_desktop' => 3, 'columns_tablet' => 2, 'columns_mobile' => 1,
                    'selection' => 'automatic', 'product_ids' => [],
                    'show_old_price' => true, 'show_current_price' => true, 'show_percentage' => true,
                ],
            ],
            'category_rows' => [
                'variant' => 'rows',
                'enabled' => false,
                'content' => [
                    'rows' => [], 'limit' => 5, 'show_head' => true,
                ],
            ],
            'featured_products' => [
                'variant' => 'carousel',
                'enabled' => false,
                'content' => [
                    'title' => 'Productos destacados', 'limit' => 8, 'selection' => 'automatic', 'product_ids' => [],
                    'show_arrows' => true, 'allow_swipe' => true, 'autoplay' => false, 'autoplay_seconds' => 6,
                ],
            ],
            'blog' => [
                'variant' => 'cards',
                'enabled' => false,
                'content' => [
                    'title' => 'Consejos y novedades', 'limit' => 3, 'all_text' => 'Ver todos los artículos',
                    'all_url' => '#', 'items' => [],
                ],
            ],
            'media_banner' => [
                'variant' => 'image',
                'enabled' => false,
                'content' => [
                    // media_type: image | video_file | video_url (YouTube/Vimeo)
                    'media_type' => 'image', 'desktop_image' => null, 'mobile_image' => null,
                    'video_file' => null, 'video_url' => null, 'fallback_image' => null,
                    'autoplay' => true, 'muted' => true, 'loop' => true, 'show_controls' => false,
                    'overlay_color' => '#0f172a', 'overlay_opacity' => 45,
                    'height' => 'medium',           // small | medium | large | full
                    'align' => 'center',            // left | center | right
                    'title' => '', 'subtitle' => '',
                    'button_text' => '', 'button_url' => '#catalogo',
                    'button2_text' => '', 'button2_url' => '',
                ],
            ],
            'collection_showcase' => [
                'variant' => 'ambient',            // ambient | circles | mosaic | banners
                'enabled' => false,
                'content' => [
                    'title' => 'Compra por ambiente', 'subtitle' => '',
                    // items: [{title, subtitle, image, url, category_id, enabled, sort_order}]
                    'items' => [], 'columns' => 3, 'show_count' => false,
                ],
            ],
            'brands' => [
                'variant' => 'strip',              // strip | grid | carousel
                'enabled' => false,
                'content' => [
                    'title' => 'Marcas con las que trabajamos', 'subtitle' => '',
                    'grayscale' => true,
                    // items: [{name, image, url, enabled, sort_order}]
                    'items' => [],
                ],
            ],
            'testimonials' => [
                'variant' => 'cards',              // cards | carousel | band
                'enabled' => false,
                'content' => [
                    'title' => 'Lo que dicen nuestros clientes', 'subtitle' => '',
                    'source' => 'manual',          // manual | reviews (reseñas aprobadas)
                    'limit' => 3,
                    // items: [{name, role, text, rating, image, enabled, sort_order}]
                    'items' => [],
                ],
            ],
            'gallery' => [
                'variant' => 'grid',               // grid | mosaic
                'enabled' => false,
                'content' => [
                    'title' => 'Galería', 'subtitle' => '', 'columns' => 3,
                    // items: [{type: image|video_url, image, video_url, caption, enabled, sort_order}]
                    'items' => [],
                ],
            ],
            'faq' => [
                'variant' => 'accordion',          // accordion | two-columns
                'enabled' => false,
                'content' => [
                    'title' => 'Preguntas frecuentes', 'subtitle' => '',
                    // items: [{question, answer, enabled, sort_order}]
                    'items' => [],
                ],
            ],
            'wa_advisory' => [
                'variant' => 'band',               // band | card
                'enabled' => false,
                'content' => [
                    'title' => '¿Necesitas asesoría?', 'subtitle' => 'Escríbenos y te ayudamos a elegir el producto correcto.',
                    'button_text' => 'Hablar por WhatsApp', 'message' => 'Hola, necesito asesoría sobre un producto.',
                    'phone' => '',                 // vacío = usa el WhatsApp de la tienda
                ],
            ],
            'cta_banner' => [
                'variant' => 'wide',               // wide | split
                'enabled' => false,
                'content' => [
                    'title' => '', 'subtitle' => '', 'image' => null,
                    'button_text' => '', 'button_url' => '#catalogo',
                    'background_color' => '#0f172a',
                ],
            ],
            'locations' => [
                'variant' => 'cards',              // cards | map-side
                'enabled' => false,
                'content' => [
                    'title' => 'Visítanos', 'subtitle' => 'Te esperamos en nuestras tiendas',
                    // items: [{name, address, phone, hours, show_map, enabled, sort_order}]
                    'items' => [],
                ],
            ],
            'info_strip' => [
                'variant' => 'cards',              // cards | icons
                'enabled' => false,
                'content' => [
                    'title' => '', 'subtitle' => '',
                    // items: [{title, description, image, icon, url, enabled, sort_order}]
                    'items' => [],
                ],
            ],
            'about_preview' => [
                'variant' => 'image-left',         // image-left | image-right
                'enabled' => false,
                'content' => [
                    'label' => 'Quiénes somos', 'title' => '', 'body' => '',
                    'image' => null,
                    'button_text' => 'Conoce nuestra historia', 'button_url' => '/nosotros',
                    // items = indicadores: [{icon, value, title, enabled, sort_order}]
                    'items' => [],
                ],
            ],
        ];
    }

    public static function ensure(Project $project): void
    {
        app(StoreSectionWriteService::class)->ensureHomeSections($project);
    }

    public static function definition(Project $project, string $component): array
    {
        return self::defaults($project)[$component] ?? throw new \InvalidArgumentException('Componente no válido.');
    }
}
