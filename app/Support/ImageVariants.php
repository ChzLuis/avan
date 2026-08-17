<?php

namespace App\Support;

/**
 * Sirve la versión .webp de una imagen cuando existe junto al original.
 *
 * Las fotos subidas (logos, productos, categorías) llegan como PNG/JPG de varios
 * MB. Se genera un .webp hermano y este helper lo devuelve si está en disco; si
 * no, deja la URL original intacta, así que nunca se rompe una imagen.
 */
class ImageVariants
{
    public static function webp(?string $url): ?string
    {
        if (! $url) {
            return $url;
        }

        // Solo se tocan imágenes servidas por esta web (las externas se dejan).
        $ruta = parse_url($url, PHP_URL_PATH);
        if (! $ruta || ! preg_match('/\.(png|jpe?g)$/i', $ruta)) {
            return $url;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if ($host && $host !== request()->getHost()) {
            return $url;
        }

        $enDisco = public_path(ltrim($ruta, '/'));
        $webp    = preg_replace('/\.(png|jpe?g)$/i', '.webp', $enDisco);

        return is_file($webp)
            ? preg_replace('/\.(png|jpe?g)$/i', '.webp', $url)
            : $url;
    }

    /**
     * Devuelve un icono reducido (64 px) para la pestaña del navegador,
     * generándolo la primera vez a partir de la imagen original.
     */
    public static function favicon(?string $url): ?string
    {
        if (! $url) {
            return $url;
        }

        $ruta = parse_url($url, PHP_URL_PATH);
        if (! $ruta || ! preg_match('/\.(png|jpe?g)$/i', $ruta)) {
            return $url;
        }

        $origen  = public_path(ltrim($ruta, '/'));
        $destino = preg_replace('/\.(png|jpe?g)$/i', '-fav128.png', $origen);

        if (! is_file($origen)) {
            return $url;
        }

        if (! is_file($destino) || filemtime($destino) < filemtime($origen)) {
            if (! function_exists('imagecreatefrompng')) {
                return $url;
            }
            $ext = strtolower(pathinfo($origen, PATHINFO_EXTENSION));
            $im  = @($ext === 'png' ? imagecreatefrompng($origen) : imagecreatefromjpeg($origen));
            if (! $im) {
                return $url;
            }
            // El favicon sale del logo de la tienda, que suele ser horizontal
            // (800x533) y con margen blanco propio. Metido tal cual en un
            // cuadrado, la marca acaba ocupando menos de la mitad del lienzo y
            // a 16 px de la pestana no se distingue nada. Se recorta primero
            // todo el borde vacio -transparente o blanco- para que lo que se
            // escale sea solo el dibujo.
            [$ox, $oy, $w, $h] = self::recorteUtil($im);

            $lado = max($w, $h) ?: 1;
            $lienzo = 128;
            // 4% de aire: pegado al borde el icono se ve apretado y algunos
            // navegadores le aplican esquinas redondeadas.
            $util = (int) round($lienzo * 0.92);
            $out = imagecreatetruecolor($lienzo, $lienzo);
            imagealphablending($out, false);
            imagesavealpha($out, true);
            imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
            $nw = (int) round($w * $util / $lado);
            $nh = (int) round($h * $util / $lado);
            imagecopyresampled(
                $out, $im,
                (int) (($lienzo - $nw) / 2), (int) (($lienzo - $nh) / 2),
                $ox, $oy, $nw, $nh, $w, $h
            );
            imagepng($out, $destino, 9);
            imagedestroy($im);
            imagedestroy($out);
        }

        return preg_replace('/\.(png|jpe?g)$/i', '-fav128.png', $url);
    }

    /**
     * Caja util de una imagen: descarta el borde transparente o casi blanco.
     * Devuelve [x, y, ancho, alto]; si la imagen es toda fondo devuelve la
     * imagen entera para no acabar con un recorte de 0 px.
     */
    private static function recorteUtil($im): array
    {
        $w = imagesx($im);
        $h = imagesy($im);

        // Muestreo cada 2 px: en un logo de 800 px de ancho basta para
        // encontrar el borde y evita recorrer medio millon de pixeles.
        $paso = max(1, (int) floor(min($w, $h) / 200));
        $x1 = $w; $y1 = $h; $x2 = -1; $y2 = -1;

        for ($y = 0; $y < $h; $y += $paso) {
            for ($x = 0; $x < $w; $x += $paso) {
                $c = imagecolorat($im, $x, $y);
                if ((($c >> 24) & 0x7F) > 100) {
                    continue; // transparente
                }
                $r = ($c >> 16) & 0xFF; $g = ($c >> 8) & 0xFF; $b = $c & 0xFF;
                if ($r > 244 && $g > 244 && $b > 244) {
                    continue; // blanco de fondo
                }
                if ($x < $x1) { $x1 = $x; }
                if ($y < $y1) { $y1 = $y; }
                if ($x > $x2) { $x2 = $x; }
                if ($y > $y2) { $y2 = $y; }
            }
        }

        if ($x2 < 0 || $y2 < 0) {
            return [0, 0, $w, $h];
        }

        // Se devuelve un pixel de holgura por el muestreo.
        $x1 = max(0, $x1 - $paso);
        $y1 = max(0, $y1 - $paso);
        $x2 = min($w - 1, $x2 + $paso);
        $y2 = min($h - 1, $y2 + $paso);

        return [$x1, $y1, $x2 - $x1 + 1, $y2 - $y1 + 1];
    }

    /**
     * Enlace a la ficha de un producto respetando el host del visitante.
     *
     * La URL lleva el nombre del producto por delante y el id al final
     * (/producto/teclado-mecanico-rgb-460): se lee, sirve para buscadores y
     * sigue resolviendo por id sin depender de una columna slug en la tabla.
     */
    public static function productUrl(\App\Models\Project $project, int $id, ?string $nombre = null): string
    {
        $dominio = trim((string) $project->custom_domain, '/');

        return ($dominio !== '' && request()->getHost() === $dominio)
            ? 'https://'.$dominio.'/producto/'.self::claveProducto($id, $nombre)
            : url('/'.$project->slug.'/producto/'.self::claveProducto($id, $nombre));
    }

    /** nombre-del-producto-123, o solo el id si el nombre no deja nada util. */
    public static function claveProducto(int $id, ?string $nombre = null): string
    {
        $slug = \Illuminate\Support\Str::slug((string) $nombre);
        if ($slug === '') {
            return (string) $id;
        }

        return \Illuminate\Support\Str::limit($slug, 70, '').'-'.$id;
    }

    /** Id que cierra una clave legible: "teclado-rgb-460" => 460. */
    public static function idDeClave(string $clave): int
    {
        return preg_match('/(\d+)$/', $clave, $m) ? (int) $m[1] : 0;
    }
}
