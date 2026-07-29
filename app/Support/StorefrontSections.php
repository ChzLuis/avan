<?php

namespace App\Support;

use App\Models\Project;
use App\Models\StoreSection;

class StorefrontSections
{
    public const COMPONENTS = [
        'hero' => 'Banner principal',
        'benefits' => 'Beneficios de la tienda',
        'announcements' => 'Bloque de anuncios',
        'featured_categories' => 'Categorías principales',
        'daily_offer' => 'Solo por hoy',
        'discounts' => 'Productos con descuento',
        'featured_products' => 'Productos destacados',
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
        ];
    }

    public static function ensure(Project $project): void
    {
        $loadedSettings = $project->relationLoaded('settings')
            ? $project->settings->pluck('value', 'key')->all()
            : null;
        $defaults = self::defaults($project, $loadedSettings);
        $existing = $project->storeSections()->where('page', 'home')->orderBy('id')->get()->groupBy('component');

        foreach (array_keys(self::COMPONENTS) as $index => $component) {
            if (($existing[$component] ?? collect())->isNotEmpty()) {
                continue;
            }

            $definition = $defaults[$component];
            StoreSection::create([
                'project_id' => $project->id,
                'page' => 'home',
                'component' => $component,
                'variant' => $definition['variant'],
                'content' => $definition['content'],
                'sort_order' => ($index + 1) * 10,
                'is_enabled' => $definition['enabled'],
                'show_desktop' => true,
                'show_tablet' => true,
                'show_mobile' => true,
                'published_at' => now(),
            ]);
        }
    }

    public static function definition(Project $project, string $component): array
    {
        return self::defaults($project)[$component] ?? throw new \InvalidArgumentException('Componente no válido.');
    }
}
