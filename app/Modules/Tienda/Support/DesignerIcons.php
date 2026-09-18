<?php

namespace App\Modules\Tienda\Support;

/**
 * Set de iconos propio del Diseñador ARIN (24x24, trazo 1.7 redondeado).
 * Un solo origen para el árbol de estructura, el shell Alpine y el wizard.
 */
class DesignerIcons
{
    /** @return array<string,string> */
    public static function all(): array
    {
        $svg = fn (string $paths): string => '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths.'</svg>';

        return [
            // Secciones del Inicio
            'announcement_bar'    => $svg('<path d="M4 9v6"/><path d="M4 11c9 0 12-4 16-5v12c-4-1-7-5-16-5"/><path d="M9 15.5l1 4"/>'),
            'hero'                => $svg('<rect x="3" y="5" width="18" height="14" rx="2.5"/><circle cx="9" cy="10" r="1.6"/><path d="M3.5 17l5-4.5 4 3.5 4.5-4 3.5 3"/>'),
            'benefits'            => $svg('<path d="M12 3l7 2.5v5.5c0 4.5-3 7.5-7 9.5-4-2-7-5-7-9.5V5.5L12 3Z"/><path d="M9.2 12l2 2 3.6-3.8"/>'),
            'announcements'       => $svg('<path d="M12 4a5.5 5.5 0 0 1 5.5 5.5c0 3 .8 4.4 1.5 5.5H5c.7-1.1 1.5-2.5 1.5-5.5A5.5 5.5 0 0 1 12 4Z"/><path d="M10 18.5a2 2 0 0 0 4 0"/>'),
            'featured_categories' => $svg('<rect x="4" y="4" width="7" height="7" rx="2"/><rect x="13" y="4" width="7" height="7" rx="2"/><rect x="4" y="13" width="7" height="7" rx="2"/><circle cx="16.5" cy="16.5" r="3.5"/>'),
            'featured_products'   => $svg('<path d="M12 4.5l2.2 4.6 5 .7-3.6 3.5.9 5-4.5-2.4-4.5 2.4.9-5L4.8 9.8l5-.7L12 4.5Z"/>'),
            'daily_offer'         => $svg('<circle cx="12" cy="13" r="7.5"/><path d="M12 9.5V13l2.5 1.8"/><path d="M9.5 3h5"/>'),
            'custom_page'         => $svg('<path d="M7 3.5h7l4 4v13H7V3.5Z"/><path d="M14 3.5v4h4"/><path d="M10 12h5M10 15.5h5"/>'),
            // Páginas fijas
            'header'              => $svg('<rect x="3" y="4" width="18" height="16" rx="2.5"/><path d="M3 9.5h18"/><circle cx="6.5" cy="6.8" r=".9" fill="currentColor" stroke="none"/><path d="M10 6.8h7"/>'),
            'catalog'             => $svg('<path d="M5.5 8h13l-1 11.5h-11L5.5 8Z"/><path d="M9 10.5V7a3 3 0 0 1 6 0v3.5"/>'),
            'product'             => $svg('<path d="M4 8l8-4.5L20 8v8l-8 4.5L4 16V8Z"/><path d="M4 8l8 4.5L20 8M12 12.5v8"/>'),
            'footer'              => $svg('<rect x="3" y="4" width="18" height="16" rx="2.5"/><path d="M3 14.5h18"/><path d="M6.5 17.3h4"/>'),
            'pages'               => $svg('<path d="M8 6.5h10.5V20H8V6.5Z"/><path d="M5.5 17.5V4H16"/>'),
            'checkout'            => $svg('<rect x="3" y="6" width="18" height="13" rx="2.5"/><path d="M3 10.5h18"/><path d="M6.5 15.5h4"/>'),
            // Utilitarios del panel
            'bolt'                => $svg('<path d="M13 3L5.5 13.5H11L10 21l7.5-10.5H12L13 3Z"/>'),
            'select'              => $svg('<path d="M5 4l6 15 2.2-6L19 11 5 4Z"/><path d="M13 13l5.5 5.5"/>'),
            'cart'                => $svg('<path d="M3.5 4.5h2l2 10.5h10l2-8H7"/><circle cx="9.5" cy="19" r="1.4"/><circle cx="16.5" cy="19" r="1.4"/>'),
            'desktop'             => $svg('<rect x="3" y="4" width="18" height="12.5" rx="2"/><path d="M9 20.5h6M12 16.5v4"/>'),
            'mobile'              => $svg('<rect x="7.5" y="3" width="9" height="18" rx="2.2"/><path d="M11 18.2h2"/>'),
            // Rubros del asistente rápido
            'rubro_tecnologia'    => $svg('<rect x="3.5" y="5" width="17" height="11" rx="2"/><path d="M2.5 19h19"/><path d="M10 19v-3h4v3"/>'),
            'rubro_moda'          => $svg('<path d="M9 4.5 5 7l1.5 3.5 2-1V19.5h7V9.5l2 1L19 7l-4-2.5a3 3 0 0 1-6 0Z"/>'),
            'rubro_bebes'         => $svg('<path d="M10 7h4v13a2 2 0 0 1-2 2 2 2 0 0 1-2-2V7Z"/><path d="M10 7a2 2 0 0 1 4 0"/><path d="M11 3.5h2V5h-2z"/><path d="M10 11h4M10 14.5h4"/>'),
            'rubro_muebles'       => $svg('<path d="M5 11V8a2.5 2.5 0 0 1 2.5-2.5h9A2.5 2.5 0 0 1 19 8v3"/><path d="M3.5 13.5a2 2 0 0 1 4 0V15h9v-1.5a2 2 0 0 1 4 0V18h-17v-4.5Z"/><path d="M6 18v2M18 18v2"/>'),
            'rubro_ferreteria'    => $svg('<path d="M14.5 6.5a4 4 0 0 0-5.4 4.8L4 16.4a1.8 1.8 0 1 0 2.6 2.6l5.1-5.1a4 4 0 0 0 4.8-5.4l-2.5 2.5-2-2 2.5-2.5Z"/>'),
            'rubro_alimentos'     => $svg('<path d="M12 8c-1-1.5-2.8-2-4.3-1.3C5 7.9 4.2 11 5.6 14.3c1.2 2.8 3.3 4.8 5 4.4.5-.1 1-.1 1.4 0 1.7.4 3.8-1.6 5-4.4 1.4-3.3.6-6.4-2.1-7.6C13.8 6 13 6.5 12 8Z"/><path d="M12 8c0-2 1-3.5 2.5-4"/>'),
            'rubro_servicios'     => $svg('<rect x="3.5" y="8" width="17" height="12" rx="2"/><path d="M9 8V6.5A1.5 1.5 0 0 1 10.5 5h3A1.5 1.5 0 0 1 15 6.5V8"/><path d="M3.5 13h17"/><path d="M10.5 13v2h3v-2"/>'),
            'rubro_mayorista'     => $svg('<path d="M4 13h7v7H4z"/><path d="M13 13h7v7h-7z"/><path d="M8.5 4h7v7h-7z"/>'),
        ];
    }

    public static function get(string $name): string
    {
        return self::all()[$name] ?? self::all()['custom_page'];
    }
}
