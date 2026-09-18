<?php

namespace App\Modules\Tienda\Support;

/**
 * Presets de TEMA del storefront: paquetes de tokens visuales (CSS custom
 * properties + clase de cuerpo) que cambian la personalidad completa de la
 * tienda — superficies, sombras, radios, tipografía de encabezados, fondos —
 * sin tocar el contenido ni la estructura de las secciones.
 *
 * Se seleccionan con el setting `theme_preset`. El valor vacío o `classic`
 * mantiene el comportamiento actual de la plantilla (compatibilidad total).
 */
final class StorefrontThemePresets
{
    public const KEYS = ['classic', 'minimal', 'tech-dark', 'soft-kids', 'warm-home', 'high-contrast'];

    public static function get(?string $key): ?array
    {
        $key = trim((string) $key);
        if ($key === '' || $key === 'classic') return null;   // tema base: sin overrides

        return self::all()[$key] ?? null;
    }

    public static function options(): array
    {
        return [
            'classic'       => 'Clásico (base de la plantilla)',
            'minimal'       => 'Minimal — aire y tipografía',
            'tech-dark'     => 'Tecnológico oscuro',
            'soft-kids'     => 'Suave infantil',
            'warm-home'     => 'Hogar cálido',
            'high-contrast' => 'Alto contraste',
        ];
    }

    /**
     * Cada preset define:
     *  - tokens: variables CSS que SOBRESCRIBEN las de :root (solo apariencia,
     *    nunca colores de marca: primary/secondary siguen siendo del proyecto).
     *  - body_class: activa reglas específicas del preset en la plantilla.
     *  - dark: la plantilla puede ajustar textos/imágenes si el fondo es oscuro.
     */
    private static function all(): array
    {
        return [
            'minimal' => [
                'body_class' => 'theme-minimal',
                'dark' => false,
                'tokens' => [
                    '--surface' => '#ffffff',
                    '--surface-soft' => '#fafafa',
                    '--border' => '#ececec',
                    '--shadow-sm' => 'none',
                    '--shadow-md' => '0 1px 2px rgba(0,0,0,.05)',
                    '--shadow-lg' => '0 10px 30px rgba(0,0,0,.06)',
                    '--radius-sm' => '2px',
                    '--radius-md' => '4px',
                    '--radius-lg' => '8px',
                ],
            ],
            'tech-dark' => [
                'body_class' => 'theme-tech-dark',
                'dark' => true,
                'tokens' => [
                    '--surface' => '#0b1220',
                    '--surface-soft' => '#111a2e',
                    '--border' => '#1e2a44',
                    '--text-strong' => '#f1f5f9',
                    '--text' => '#cbd5e1',
                    '--muted' => '#7c8db0',
                    '--shadow-sm' => '0 8px 20px rgba(0,0,0,.35)',
                    '--shadow-md' => '0 14px 32px rgba(0,0,0,.45)',
                    '--shadow-lg' => '0 24px 54px rgba(0,0,0,.55)',
                    '--radius-sm' => '8px',
                    '--radius-md' => '12px',
                    '--radius-lg' => '18px',
                ],
            ],
            'soft-kids' => [
                'body_class' => 'theme-soft-kids',
                'dark' => false,
                'tokens' => [
                    '--surface' => '#ffffff',
                    '--surface-soft' => '#fbfcfd',
                    '--border' => '#e9edf2',
                    '--shadow-sm' => '0 8px 20px rgba(244,114,182,.10)',
                    '--shadow-md' => '0 14px 32px rgba(244,114,182,.14)',
                    '--shadow-lg' => '0 24px 54px rgba(244,114,182,.18)',
                    '--radius-sm' => '16px',
                    '--radius-md' => '22px',
                    '--radius-lg' => '30px',
                ],
            ],
            'warm-home' => [
                'body_class' => 'theme-warm-home',
                'dark' => false,
                'tokens' => [
                    '--surface' => '#fffdfa',
                    '--surface-soft' => '#faf6f0',
                    '--border' => '#eadfd2',
                    '--text-strong' => '#292018',
                    '--text' => '#4d4237',
                    '--muted' => '#8a7c6c',
                    '--shadow-sm' => '0 8px 20px rgba(120,90,60,.08)',
                    '--shadow-md' => '0 14px 32px rgba(120,90,60,.12)',
                    '--shadow-lg' => '0 24px 54px rgba(120,90,60,.16)',
                    '--radius-sm' => '6px',
                    '--radius-md' => '10px',
                    '--radius-lg' => '14px',
                ],
            ],
            'high-contrast' => [
                'body_class' => 'theme-high-contrast',
                'dark' => false,
                'tokens' => [
                    '--surface' => '#ffffff',
                    '--surface-soft' => '#f3f4f6',
                    '--border' => '#111827',
                    '--text-strong' => '#000000',
                    '--text' => '#111827',
                    '--muted' => '#374151',
                    '--shadow-sm' => 'none',
                    '--shadow-md' => '4px 4px 0 #111827',
                    '--shadow-lg' => '8px 8px 0 #111827',
                    '--radius-sm' => '0px',
                    '--radius-md' => '0px',
                    '--radius-lg' => '0px',
                ],
            ],
        ];
    }
}
