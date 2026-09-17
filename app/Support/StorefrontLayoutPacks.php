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
                'banda'    => 'Banda corporativa (cinta, buscador ancho y enlaces abajo)',
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
                // 2026-09-06: seis ideas distintas, no seis versiones de la
                // misma banda con columnas. Cada una tiene un rasgo que se
                // recuerda; ver DESCRIPCIONES.
                'boletin'       => 'Boletín destacado (fondo claro, tarjeta de suscripción)',
                'industrial'    => 'Industrial (negro, datos bancarios, sellos y marcas)',
                'marca'         => 'Marca gigante (editorial, el nombre en letras enormes)',
                'tarjeta'       => 'Tarjeta flotante (panel de color + enlaces en una tarjeta)',
                'local'         => 'Visítanos (mapa, horario y cómo llegar)',
                'conversacion'  => 'Conversación (burbuja de WhatsApp en el centro)',
                'banda'         => 'Una banda (corporativo horizontal, poco alto)',
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

    /**
     * Qué hace distinta a cada variante, en una frase para el Constructor.
     * Un nombre solo no basta para elegir: "Completo" y "Corporativo" suenan
     * igual hasta que se ven.
     */
    public const DESCRIPCIONES = [
        'headers' => [
            'classic'  => 'La cabecera de siempre: logo, menú y acciones en una fila.',
            'centered' => 'Logo centrado arriba y el menú debajo, en dos filas.',
            'triple'   => 'Tres filas: cinta, logo con buscador ancho y barra de categorías.',
            'compact'  => 'Una sola fila delgada con el menú en línea y el buscador desplegable.',
            'banda'    => 'Cinta fina con lema y sellos, fila con logo, buscador ancho, cotización y WhatsApp, y debajo los enlaces centrados con subrayado en el activo. Hermana del pie "Una banda".',
        ],
        'footers' => [
            'classic'       => 'La banda oscura de siempre: marca, categorías, información y contacto en cuatro columnas.',
            'simple'        => 'Una sola línea con la marca, los legales y los derechos. Para no distraer.',
            'institutional' => 'Descripción amplia de la empresa, políticas y contacto. Serio y sobrio.',
            'commercial'    => 'Franja de asesoría por WhatsApp arriba, categorías, información y medios de pago.',
            'complete'      => 'El más denso: fila de confianza, cuatro columnas y legales.',
            'minimal'       => 'Todo centrado y compacto: logo, menú corto, redes y legales.',
            'corporate'     => 'Cinco columnas con horario de atención y razón social.',
            'technology'    => 'Azul marino con acento, beneficios, llamado a la acción y sellos de seguridad.',
            'boletin'       => 'Fondo claro con títulos subrayados en color de marca y una tarjeta de boletín (o WhatsApp) a la derecha. Barra oscura con pagos en placa blanca y botón de volver arriba.',
            'industrial'    => 'Negro y denso: cuentas bancarias, sellos de garantía, datos de la empresa con iconos, categorías con flechas, enlaces y marcas. Para distribuidoras y ferreterías.',
            'marca'         => 'El nombre de la tienda ocupa todo el ancho en letras enormes, recortado abajo. Enlaces y contacto en una línea. Para marcas con personalidad.',
            'tarjeta'       => 'El pie es una tarjeta redondeada que flota sobre la página: panel en color de marca con el logo y WhatsApp, y enlaces al lado. Los pagos van fuera, discretos.',
            'local'         => 'Mapa de la dirección a media pantalla, horario por líneas, teléfono, WhatsApp y cómo llegar. Para negocios con local.',
            'conversacion'  => 'Una gran burbuja de chat en color de marca invita a escribir por WhatsApp. Menú corto, redes y legales. Ideal para tiendas por cotización.',
            'banda'         => 'Una sola franja horizontal y baja: logo con el lema al lado, contacto con iconos, enlaces en dos listas, redes en cuadros y una frase de marca en cursiva. Abajo, derechos y legales. Sobrio y corporativo.',
        ],
    ];

    /** Descripción de una variante, o vacío si no tiene. */
    public static function descripcion(string $slot, string $variante): string
    {
        return self::DESCRIPCIONES[$slot][$variante] ?? '';
    }

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
