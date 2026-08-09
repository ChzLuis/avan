<?php

namespace App\Support;

/**
 * FASE 1 — Registro de variantes ESTRUCTURALES del storefront.
 *
 * Cada slot (header/footer/cart) se resuelve a un partial Blade:
 * `public/templates/partials/{slot}/{variante}.blade.php`.
 * La variante `classic` SIEMPRE es el markup actual de la plantilla
 * (compatibilidad total: tiendas existentes no cambian sin elegirlo).
 *
 * Los settings usan prefijos ya mapeados por DesignTemplateService
 * (header_*, footer_*, cart_*): toda elección es capturable en plantillas.
 */
final class StorefrontLayoutPacks
{
    public const SLOTS = [
        'headers' => [
            'setting' => 'header_layout',
            'default' => 'classic',
            'options' => [
                'classic'  => 'Clásica (actual)',
                'centered' => 'Logo centrado (dos filas)',
                'triple'   => 'Tres filas con barra de categorías',
                'compact'  => 'Compacta minimal',
            ],
        ],
        'footers' => [
            'setting' => 'footer_layout',
            'default' => 'classic',
            'options' => [
                'classic'       => 'Clásico (actual)',
                'simple'        => 'Simple (una línea)',
                'institutional' => 'Institucional (descripción + políticas)',
                'commercial'    => 'Comercial (categorías + pagos + boletín)',
                'complete'      => 'Completo',
                'minimal'       => 'Minimal centrado',
                'corporate'     => 'Corporativo (5 columnas con horario)',
                'technology'    => 'Tecnológico Pro (navy + acento, beneficios y CTA)',
            ],
        ],
        'carts' => [
            'setting' => 'cart_layout',
            'default' => 'classic',
            'options' => [
                'classic'  => 'Panel lateral (actual)',
                'side'     => 'Panel lateral rediseñado',
                'page'     => 'Página completa',
                'floating' => 'Burbuja flotante con resumen',
            ],
        ],
    ];

    /** Variantes visibles en el constructor pero NO seleccionables aún (en prueba). */
    public const EXPERIMENTAL = [
        'headers' => ['centered', 'triple', 'compact'],
        'footers' => ['simple', 'institutional', 'commercial', 'complete', 'minimal', 'corporate'],
        'carts'   => ['side', 'page', 'floating'],
    ];

    /**
     * Variante activa de un slot, SOLO desde el registro (nunca se construye
     * una ruta con el valor recibido). Valor ausente/antiguo/inválido →
     * `classic` (comportamiento actual) + advertencia administrativa.
     */
    public static function variant(array $settings, string $slot): string
    {
        $config = self::SLOTS[$slot] ?? null;
        if (!$config) return 'classic';
        $raw = (string) ($settings[$config['setting']] ?? '');
        if ($raw === '') return $config['default'];   // clave ausente = classic, sin ruido

        if (!array_key_exists($raw, $config['options'])) {
            \Illuminate\Support\Facades\Log::warning('layout_packs', [
                'event' => 'invalid_variant', 'slot' => $slot, 'value' => mb_substr($raw, 0, 40),
            ]);
            return $config['default'];
        }
        return $raw;
    }

    /**
     * Vista del partial para un slot; null = usar el markup inline actual.
     * El nombre de vista sale del REGISTRO validado, jamás del input.
     */
    public static function view(array $settings, string $slot): ?string
    {
        $variant = self::variant($settings, $slot);
        if ($variant === 'classic') return null;   // markup actual embebido, cero riesgo

        $view = "storefront.partials.{$slot}.{$variant}";
        if (!view()->exists($view)) {
            \Illuminate\Support\Facades\Log::warning('layout_packs', [
                'event' => 'missing_partial', 'slot' => $slot, 'variant' => $variant,
            ]);
            return null;   // degrada a classic sin error visible
        }
        return $view;
    }

    /** Opciones para los selectores del constructor. */
    public static function options(string $slot): array
    {
        return self::SLOTS[$slot]['options'] ?? [];
    }
}
