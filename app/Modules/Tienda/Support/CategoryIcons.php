<?php

namespace App\Modules\Tienda\Support;

/**
 * Biblioteca compartida de iconos de categoría.
 *
 * Por qué existe: el mapa de iconos vivía dentro de `computienda.blade.php`, en
 * un bloque `@php` que se ejecuta DESPUÉS de incluir el encabezado. El menú
 * (`preset-shell.blade.php`) no podía alcanzarlo, así que solo mostraba icono
 * cuando alguien lo había asignado a mano categoría por categoría — y en la
 * práctica eso no lo hace nadie: Tecsist tenía 0 de 5 asignadas.
 *
 * Aquí el icono se deduce del NOMBRE de la categoría, así que funciona sin
 * configurar nada. Si el cliente asigna uno propio desde el constructor
 * (`caticon_{id}`), ese manda: esto es solo el respaldo.
 */
class CategoryIcons
{
    /** Trazos SVG (viewBox 24, sin relleno) por clave. */
    public static function paths(): array
    {
        return [
            'default'         => '<rect x="4" y="4" width="16" height="16" rx="2"></rect><rect x="8" y="8" width="8" height="8" rx="1"></rect>',
            'pc'              => '<rect x="4" y="3" width="16" height="12" rx="1.5"></rect><path d="M9 19h6M12 15v4"></path>',
            'laptop'          => '<rect x="3" y="4" width="18" height="12" rx="1.5"></rect><path d="M2 20h20M8 20l1-3M16 20l-1-3"></path>',
            'monitor'         => '<rect x="3" y="4" width="18" height="12" rx="1.5"></rect><path d="M8 20h8M12 16v4"></path>',
            'impresora'       => '<path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><path d="M6 14h12v7H6z"></path>',
            'camara'          => '<path d="M4 7h4l2-3h4l2 3h4v13H4z"></path><circle cx="12" cy="13" r="4"></circle>',
            'camera-security' => '<path d="M4 7h12l4 4-4 4H4z"></path><circle cx="10" cy="11" r="2"></circle><path d="M10 15v4M7 19h6"></path>',
            'disco'           => '<rect x="4" y="3" width="16" height="18" rx="2"></rect><circle cx="12" cy="11" r="4"></circle><path d="M8 18h8"></path>',
            'teclado'         => '<rect x="2" y="6" width="20" height="12" rx="2"></rect><path d="M6 10h.01M10 10h.01M14 10h.01M18 10h.01M6 14h12"></path>',
            'mouse'           => '<rect x="7" y="3" width="10" height="18" rx="5"></rect><path d="M12 7v3"></path>',
            'audio'           => '<path d="M4 14h4l5 4V6L8 10H4zM17 9a4 4 0 0 1 0 6M19 6a8 8 0 0 1 0 12"></path>',
            'celular'         => '<rect x="7" y="2" width="10" height="20" rx="2"></rect><path d="M11 18h2"></path>',
            'router'          => '<rect x="3" y="11" width="18" height="8" rx="2"></rect><path d="M7 15h.01M11 15h.01M17 15h.01M8 8a6 6 0 0 1 8 0M10 10a3 3 0 0 1 4 0"></path>',
            'gaming'          => '<path d="M7 8h10a5 5 0 0 1 4.7 6.7l-1 2.8a2 2 0 0 1-3.3.8L15 16H9l-2.4 2.3a2 2 0 0 1-3.3-.8l-1-2.8A5 5 0 0 1 7 8z"></path><path d="M8 11v4M6 13h4M16 12h.01M18 14h.01"></path>',
            'chip'            => '<rect x="7" y="7" width="10" height="10" rx="2"></rect><path d="M9 1v3M15 1v3M9 20v3M15 20v3M20 9h3M20 14h3M1 9h3M1 14h3"></path>',
            'cable'           => '<path d="M7 7V3M5 3h4M17 21v-4M15 21h4M7 7c0 7 10 3 10 10"></path>',
            'escritorio'      => '<rect x="4" y="3" width="16" height="12" rx="1.5"></rect><path d="M9 19h6M8 15v4M16 15v4M12 15v4"></path>',
            'punto-venta'     => '<rect x="4" y="8" width="16" height="13" rx="2"></rect><path d="M8 8V5a4 4 0 0 1 8 0v3M8 13h8"></path>',
            'sofa'            => '<path d="M4 12V9a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3"></path><path d="M2 13a2 2 0 0 1 2-2 2 2 0 0 1 2 2v4H2z"></path><path d="M22 13a2 2 0 0 0-2-2 2 2 0 0 0-2 2v4h4z"></path><path d="M6 17h12M5 20v-3M19 20v-3"></path>',
            'comedor'         => '<path d="M3 8h18M4 8l1 12M20 8l-1 12"></path><path d="M8 12v8M16 12v8"></path>',
            'cama'            => '<path d="M3 18v-8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8"></path><path d="M3 14h18M7 8V6h4v2"></path><path d="M3 18v2M21 18v2"></path>',
            'colchon'         => '<rect x="2" y="7" width="20" height="10" rx="3"></rect><path d="M6 10v4M10 10v4M14 10v4M18 10v4"></path>',
            'refrigeradora'   => '<rect x="6" y="2" width="12" height="20" rx="2"></rect><path d="M6 10h12M9 6v2M9 13v3"></path>',
            'lavadora'        => '<rect x="4" y="3" width="16" height="18" rx="2"></rect><circle cx="12" cy="14" r="4"></circle><path d="M7 6h.01M10 6h.01"></path>',
            'cocina-electro'  => '<rect x="3" y="7" width="18" height="14" rx="2"></rect><circle cx="8" cy="13" r="2.2"></circle><circle cx="16" cy="13" r="2.2"></circle><path d="M3 11h18M6 3v4M18 3v4"></path>',
            'armario'         => '<rect x="4" y="2" width="16" height="20" rx="1.5"></rect><path d="M12 2v20M9 11h.5M15 11h-.5"></path>',
            'silla'           => '<path d="M6 3h12v9H6z"></path><path d="M5 12h14M7 12v9M17 12v9M7 17h10"></path>',
            'lampara'         => '<path d="M8 3h8l3 7H5z"></path><path d="M12 10v8M9 21h6"></path>',
            'decoracion'      => '<path d="M12 3l2.6 5.6L20 9.4l-4 4.1.9 5.9-4.9-2.8-4.9 2.8.9-5.9-4-4.1 5.4-.8z"></path>',
            'bano'            => '<path d="M4 12h16v3a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4z"></path><path d="M7 12V6a2 2 0 0 1 4 0"></path><path d="M7 19l-1 2M17 19l1 2"></path>',
            'organizacion'    => '<rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M3 9h18M3 15h18M9 3v18"></path>',
            'bebe'            => '<circle cx="12" cy="9" r="5"></circle><path d="M9 8h.01M15 8h.01M10 11a3 3 0 0 0 4 0"></path><path d="M6 21a6 6 0 0 1 12 0"></path>',
        ];
    }

    /** Deduce la clave del icono a partir del nombre de la categoría. */
    public static function auto(?string $nombre): string
    {
        $n = mb_strtolower(trim((string) $nombre));
        if ($n === '') {
            return 'default';
        }

        $reglas = [
            'laptop'          => ['laptop', 'notebook', 'portátil', 'portatil'],
            'impresora'       => ['impres', 'tinta', 'tóner', 'toner'],
            'camera-security' => ['seguridad', 'vigilancia', 'videoporter', 'alarma'],
            'camara'          => ['cámara', 'camara', 'fotograf'],
            'disco'           => ['disco', 'memoria', 'almacen', 'ssd', 'usb'],
            'monitor'         => ['monitor', 'pantalla'],
            'teclado'         => ['teclado'],
            'mouse'           => ['mouse', 'ratón', 'raton'],
            'audio'           => ['audio', 'audífono', 'audifono', 'parlante', 'sonido'],
            'celular'         => ['celular', 'smartphone', 'móvil', 'movil'],
            'router'          => ['router', 'wifi', 'red', 'switch'],
            'gaming'          => ['gamer', 'gaming', 'consola'],
            'chip'            => ['procesador', 'chip', 'placa', 'componente'],
            'cable'           => ['cable', 'adaptador', 'conector'],
            'punto-venta'     => ['punto de venta', 'pos', 'caja registradora', 'ticket'],
            'escritorio'      => ['escritorio', 'all in one', 'aio'],
            'pc'              => ['comput', 'cpu', 'pc', 'servidor', 'tecnolog'],
            'sofa'            => ['sala', 'sofá', 'sofa', 'mueble'],
            'comedor'         => ['comedor', 'mesa'],
            'colchon'         => ['colch'],
            'cama'            => ['dormitorio', 'cama', 'velador'],
            'refrigeradora'   => ['refriger', 'congel', 'frigo'],
            'lavadora'        => ['lavad', 'secad'],
            'cocina-electro'  => ['cocina', 'electrohogar', 'electrodom'],
            'armario'         => ['ropero', 'armario', 'closet', 'melamine'],
            'silla'           => ['silla', 'oficina'],
            'lampara'         => ['lámpara', 'lampara', 'ilumin'],
            'decoracion'      => ['decor', 'adorno'],
            'bano'            => ['baño', 'bano'],
            'organizacion'    => ['organiz'],
            'bebe'            => ['bebé', 'bebe', 'niño', 'nino', 'niña', 'nina', 'infantil'],
        ];

        foreach ($reglas as $clave => $palabras) {
            foreach ($palabras as $p) {
                if (str_contains($n, $p)) {
                    return $clave;
                }
            }
        }

        return 'default';
    }

    /**
     * SVG listo para pintar. Si el cliente asignó uno propio desde el
     * constructor, ese manda; si no, se deduce del nombre.
     */
    public static function svg(?string $nombre, ?string $personalizado = null): string
    {
        if (filled($personalizado)) {
            return $personalizado;
        }

        $paths = self::paths();
        $d = $paths[self::auto($nombre)] ?? $paths['default'];

        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" '
            .'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$d.'</svg>';
    }
}
