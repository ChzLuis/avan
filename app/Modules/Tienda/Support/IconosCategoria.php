<?php

namespace App\Modules\Tienda\Support;

/**
 * Iconos de linea por categoria (trazados SVG) y deduccion del icono por el
 * nombre. Antes vivian como variables dentro de la plantilla `computienda`;
 * al ser clase los usan tambien la cabecera (megamenu) y cualquier partial,
 * porque las variables de un @include no llegan al padre ni a los hermanos.
 */
final class IconosCategoria
{
    /** @return array<string,string> clave => trazados <path>/<rect>... sin <svg> */
    public static function paths(): array
    {
        return [
                'default' => '<rect x="4" y="4" width="16" height="16" rx="2"></rect><rect x="8" y="8" width="8" height="8" rx="1"></rect>',
                'pc' => '<rect x="4" y="3" width="16" height="12" rx="1.5"></rect><path d="M9 19h6M12 15v4"></path>',
                'laptop' => '<rect x="3" y="4" width="18" height="12" rx="1.5"></rect><path d="M2 20h20M8 20l1-3M16 20l-1-3"></path>',
                'monitor' => '<rect x="3" y="4" width="18" height="12" rx="1.5"></rect><path d="M8 20h8M12 16v4"></path>',
                'impresora' => '<path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><path d="M6 14h12v7H6z"></path>',
                'camara' => '<path d="M4 7h4l2-3h4l2 3h4v13H4z"></path><circle cx="12" cy="13" r="4"></circle>',
                'camera-security' => '<path d="M4 7h12l4 4-4 4H4z"></path><circle cx="10" cy="11" r="2"></circle><path d="M10 15v4M7 19h6"></path>',
                'disco' => '<rect x="4" y="3" width="16" height="18" rx="2"></rect><circle cx="12" cy="11" r="4"></circle><path d="M8 18h8"></path>',
                'teclado' => '<rect x="2" y="6" width="20" height="12" rx="2"></rect><path d="M6 10h.01M10 10h.01M14 10h.01M18 10h.01M6 14h12"></path>',
                'mouse' => '<rect x="7" y="3" width="10" height="18" rx="5"></rect><path d="M12 7v3"></path>',
                'audio' => '<path d="M4 14h4l5 4V6L8 10H4zM17 9a4 4 0 0 1 0 6M19 6a8 8 0 0 1 0 12"></path>',
                'celular' => '<rect x="7" y="2" width="10" height="20" rx="2"></rect><path d="M11 18h2"></path>',
                'router' => '<rect x="3" y="11" width="18" height="8" rx="2"></rect><path d="M7 15h.01M11 15h.01M17 15h.01M8 8a6 6 0 0 1 8 0M10 10a3 3 0 0 1 4 0"></path>',
                'gaming' => '<path d="M7 8h10a5 5 0 0 1 4.7 6.7l-1 2.8a2 2 0 0 1-3.3.8L15 16H9l-2.4 2.3a2 2 0 0 1-3.3-.8l-1-2.8A5 5 0 0 1 7 8z"></path><path d="M8 11v4M6 13h4M16 12h.01M18 14h.01"></path>',
                'chip' => '<rect x="7" y="7" width="10" height="10" rx="2"></rect><path d="M9 1v3M15 1v3M9 20v3M15 20v3M20 9h3M20 14h3M1 9h3M1 14h3"></path>',
                'cable' => '<path d="M7 7V3M5 3h4M17 21v-4M15 21h4M7 7c0 7 10 3 10 10"></path>',
                'escritorio' => '<rect x="4" y="3" width="16" height="12" rx="1.5"></rect><path d="M9 19h6M8 15v4M16 15v4M12 15v4"></path>',
                /* Hogar y muebles — el mapa original solo cubría informática. */
                'sofa' => '<path d="M4 12V9a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3"></path><path d="M2 13a2 2 0 0 1 2-2 2 2 0 0 1 2 2v4H2z"></path><path d="M22 13a2 2 0 0 0-2-2 2 2 0 0 0-2 2v4h4z"></path><path d="M6 17h12M5 20v-3M19 20v-3"></path>',
                'comedor' => '<path d="M3 8h18M4 8l1 12M20 8l-1 12"></path><path d="M8 12v8M16 12v8"></path>',
                'cama' => '<path d="M3 18v-8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8"></path><path d="M3 14h18M7 8V6h4v2"></path><path d="M3 18v2M21 18v2"></path>',
                'colchon' => '<rect x="2" y="7" width="20" height="10" rx="3"></rect><path d="M6 10v4M10 10v4M14 10v4M18 10v4"></path>',
                'refrigeradora' => '<rect x="6" y="2" width="12" height="20" rx="2"></rect><path d="M6 10h12M9 6v2M9 13v3"></path>',
                'lavadora' => '<rect x="4" y="3" width="16" height="18" rx="2"></rect><circle cx="12" cy="14" r="4"></circle><path d="M7 6h.01M10 6h.01"></path>',
                'cocina-electro' => '<rect x="3" y="7" width="18" height="14" rx="2"></rect><circle cx="8" cy="13" r="2.2"></circle><circle cx="16" cy="13" r="2.2"></circle><path d="M3 11h18M6 3v4M18 3v4"></path>',
                'armario' => '<rect x="4" y="2" width="16" height="20" rx="1.5"></rect><path d="M12 2v20M9 11h.5M15 11h-.5"></path>',
                'silla' => '<path d="M6 3h12v9H6z"></path><path d="M5 12h14M7 12v9M17 12v9M7 17h10"></path>',
                'lampara' => '<path d="M8 3h8l3 7H5z"></path><path d="M12 10v8M9 21h6"></path>',
                'decoracion' => '<path d="M12 3l2.6 5.6L20 9.4l-4 4.1.9 5.9-4.9-2.8-4.9 2.8.9-5.9-4-4.1 5.4-.8z"></path>',
                'bano' => '<path d="M4 12h16v3a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4z"></path><path d="M7 12V6a2 2 0 0 1 4 0"></path><path d="M7 19l-1 2M17 19l1 2"></path>',
                'organizacion' => '<rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M3 9h18M3 15h18M9 3v18"></path>',
                /* Rubro eléctrico y ferretero. Sin estos, una ferretería veía
                   el mismo cuadrado gris en Cable, Cinta, Llaves y Toma: cuatro
                   categorías distintas con el icono de "no sé qué eres". */
                'tomacorriente' => '<rect x="3" y="3" width="18" height="18" rx="3"></rect><circle cx="9" cy="10" r="1.3"></circle><circle cx="15" cy="10" r="1.3"></circle><path d="M8 16h8"></path>',
                'interruptor' => '<rect x="6" y="2" width="12" height="20" rx="2.5"></rect><path d="M9.5 8h5v8h-5z"></path>',
                'llave-termica' => '<rect x="7" y="3" width="10" height="18" rx="1.5"></rect><path d="M12 7v4l-2.5 2.5H12"></path><path d="M4 12h3M17 12h3"></path>',
                'cinta-aislante' => '<circle cx="12" cy="12" r="8.5"></circle><circle cx="12" cy="12" r="3"></circle><path d="M12 3.5v4M12 16.5v4"></path>',
                'tuberia' => '<path d="M3 8h9a3 3 0 0 1 3 3v2a3 3 0 0 0 3 3h3"></path><path d="M3 5v6M21 13v6"></path>',
                'disco-corte' => '<circle cx="12" cy="12" r="8.5"></circle><circle cx="12" cy="12" r="2"></circle><path d="M12 3.5v3M12 17.5v3M3.5 12h3M17.5 12h3"></path>',
                'herramienta' => '<path d="M14.5 5.5a4 4 0 0 0 5.2 5.2L21 12l-9 9-3-3 9-9z"></path><path d="M6 18l-2 2"></path>',
            ];
    }

    /** Clave de icono deducida del nombre de la categoria. */
    public static function auto(?string $name): string
    {

                $name = mb_strtolower($name ?? '');
                if (str_contains($name, 'conductor')) return 'cable';
                if (str_contains($name, 'canaliz') || str_contains($name, 'conduit')) return 'tuberia';
                if (str_contains($name, 'ilumin') || str_contains($name, 'foco') || str_contains($name, 'luz')) return 'lampara';
                if (str_contains($name, 'protecc')) return 'llave-termica';
                if (str_contains($name, 'herramient') || str_contains($name, 'abrasiv')) return 'herramienta';
                if (str_contains($name, 'seguridad') || str_contains($name, 'epp')) return 'herramienta';
                if (str_contains($name, 'laptop')) return 'laptop';
                if (str_contains($name, 'impres')) return 'impresora';
                if (str_contains($name, 'seguridad')) return 'camera-security';
                if (str_contains($name, 'cámara') || str_contains($name, 'camara')) return 'camara';
                if (str_contains($name, 'disco') || str_contains($name, 'memoria') || str_contains($name, 'almacen')) return 'disco';
                if (str_contains($name, 'monitor')) return 'monitor';
                if (str_contains($name, 'teclado')) return 'teclado';
                if (str_contains($name, 'mouse')) return 'mouse';
                if (str_contains($name, 'audio') || str_contains($name, 'audífono')) return 'audio';
                if (str_contains($name, 'celular')) return 'celular';
                if (str_contains($name, 'router') || str_contains($name, 'wifi')) return 'router';
                if (str_contains($name, 'gaming')) return 'gaming';
                if (str_contains($name, 'procesador') || str_contains($name, 'chip')) return 'chip';
                if (str_contains($name, 'cable')) return 'cable';
                if (str_contains($name, 'escritorio')) return 'escritorio';
                if (str_contains($name, 'comput')) return 'pc';
                /* Hogar y muebles */
                if (str_contains($name, 'sala') || str_contains($name, 'sofá') || str_contains($name, 'sofa') || str_contains($name, 'mueble')) return 'sofa';
                if (str_contains($name, 'comedor') || str_contains($name, 'mesa')) return 'comedor';
                if (str_contains($name, 'colch')) return 'colchon';
                if (str_contains($name, 'dormitorio') || str_contains($name, 'cama') || str_contains($name, 'velador')) return 'cama';
                if (str_contains($name, 'refriger') || str_contains($name, 'congel') || str_contains($name, 'frigo')) return 'refrigeradora';
                if (str_contains($name, 'lavad') || str_contains($name, 'secad')) return 'lavadora';
                if (str_contains($name, 'cocina') || str_contains($name, 'electrohogar') || str_contains($name, 'electrodom')) return 'cocina-electro';
                if (str_contains($name, 'ropero') || str_contains($name, 'armario') || str_contains($name, 'closet') || str_contains($name, 'melamine')) return 'armario';
                if (str_contains($name, 'silla') || str_contains($name, 'oficina')) return 'silla';
                if (str_contains($name, 'lámpara') || str_contains($name, 'lampara') || str_contains($name, 'ilumin')) return 'lampara';
                if (str_contains($name, 'decor') || str_contains($name, 'adorno')) return 'decoracion';
                if (str_contains($name, 'baño') || str_contains($name, 'bano')) return 'bano';
                if (str_contains($name, 'organiz') || str_contains($name, 'almacenamiento')) return 'organizacion';
                /* Eléctrico y ferretero. Va al final para no pisar reglas de
                   arriba: "cable" ya lo resuelve la de cómputo, e "iluminación"
                   la de lámpara. */
                if (str_contains($name, 'tomacorriente') || str_contains($name, 'enchufe') || str_contains($name, 'toma')) return 'tomacorriente';
                if (str_contains($name, 'interruptor') || str_contains($name, 'dimmer')) return 'interruptor';
                if (str_contains($name, 'llave') || str_contains($name, 'termomagn') || str_contains($name, 'diferencial') || str_contains($name, 'protecc')) return 'llave-termica';
                if (str_contains($name, 'cinta') || str_contains($name, 'aislan') || str_contains($name, 'teip')) return 'cinta-aislante';
                if (str_contains($name, 'tuber') || str_contains($name, 'conduit') || str_contains($name, 'tubo') || str_contains($name, 'canaleta')) return 'tuberia';
                if (str_contains($name, 'disco') || str_contains($name, 'abrasiv') || str_contains($name, 'corte')) return 'disco-corte';
                if (str_contains($name, 'herramient') || str_contains($name, 'ferret')) return 'herramienta';
                return 'default';
    }

    /** Trazados del icono para un nombre (o `default`). */
    public static function para(?string $name): string
    {
        $p = self::paths();
        return $p[self::auto($name)] ?? $p['default'];
    }
}
