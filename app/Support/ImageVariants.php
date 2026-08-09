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
        $destino = preg_replace('/\.(png|jpe?g)$/i', '-fav64.png', $origen);

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
            $w = imagesx($im);
            $h = imagesy($im);
            $lado = max($w, $h) ?: 1;
            $out = imagecreatetruecolor(64, 64);
            imagealphablending($out, false);
            imagesavealpha($out, true);
            imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
            $nw = (int) round($w * 64 / $lado);
            $nh = (int) round($h * 64 / $lado);
            imagecopyresampled($out, $im, (int) ((64 - $nw) / 2), (int) ((64 - $nh) / 2), 0, 0, $nw, $nh, $w, $h);
            imagepng($out, $destino, 9);
            imagedestroy($im);
            imagedestroy($out);
        }

        return preg_replace('/\.(png|jpe?g)$/i', '-fav64.png', $url);
    }

    /**
     * Enlace a la ficha de un producto respetando el host del visitante.
     *
     * En un dominio propio devuelve /p/123; en arindg.com mantiene el slug.
     */
    public static function productUrl(\App\Models\Project $project, int $id): string
    {
        $dominio = trim((string) $project->custom_domain, '/');

        return ($dominio !== '' && request()->getHost() === $dominio)
            ? 'https://'.$dominio.'/p/'.$id
            : route('public.product', [$project->slug, $id]);
    }
}
