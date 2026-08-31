<?php

namespace App\Support\Imagen;

use App\Models\ProductImageTemplate;
use Illuminate\Support\Facades\Storage;

/**
 * Compone la imagen de catálogo de un producto sobre una plantilla.
 *
 * CONTRATO CON LA VISTA PREVIA
 * El navegador pinta esta misma composición en un canvas. Para que las dos
 * salgan idénticas, todo se expresa en PORCENTAJE del lienzo y el orden de
 * dibujo es siempre el mismo:
 *
 *   1. fondo (blanco / color / imagen)
 *   2. marca de agua      (debajo del producto: es una filigrana)
 *   3. producto           (nunca deformado: se encaja por el lado que sobra)
 *   4. logo               (encima de todo: es la firma)
 *
 * Si se cambia una regla aquí hay que cambiarla también en el canvas del
 * constructor, y al revés. Es el único punto donde puede aparecer una
 * diferencia entre lo que el comerciante ve y lo que sale publicado.
 *
 * NO toca la imagen original: recibe una ruta de lectura y devuelve bytes.
 */
class CompositorProducto
{
    /** Peso máximo aceptado para logo/fondo/marca de agua de la plantilla. */
    private const MAX_BYTES_RECURSO = 8 * 1024 * 1024;

    /**
     * @param  string  $rutaOrigen  Ruta ABSOLUTA en disco de la foto del producto.
     * @return string  Bytes de la imagen compuesta (WebP, o JPEG si no hay soporte).
     *
     * @throws ImagenNoProcesable
     */
    public function componer(string $rutaOrigen, ProductImageTemplate $plantilla): string
    {
        $c = $plantilla->configCompleta();
        $ancho = max(320, min(2000, (int) $c['output_width']));
        $alto = (int) round($ancho * (ProductImageTemplate::RATIOS[$c['aspect_ratio']] ?? 1.0));

        $producto = $this->abrir($rutaOrigen);
        if (! $producto) {
            throw new ImagenNoProcesable('No se pudo leer la imagen del producto.');
        }

        try {
            $lienzo = imagecreatetruecolor($ancho, $alto);
            imagealphablending($lienzo, true);
            imagesavealpha($lienzo, true);

            $this->pintarFondo($lienzo, $ancho, $alto, $c);

            // 2. Marca de agua: por debajo del producto, como filigrana.
            if (! empty($c['watermark_enabled'])) {
                $this->pintarMarca(
                    $lienzo, $ancho, $alto,
                    $this->recursoDe($c, 'watermark', $plantilla),
                    (float) $c['watermark_x'], (float) $c['watermark_y'],
                    (float) $c['watermark_scale'], (float) $c['watermark_opacity'],
                );
            }

            $this->pintarProducto($lienzo, $ancho, $alto, $producto, $c);

            // 4. Logo: encima de todo.
            if (! empty($c['logo_enabled'])) {
                $this->pintarMarca(
                    $lienzo, $ancho, $alto,
                    $this->recursoDe($c, 'logo', $plantilla),
                    (float) $c['logo_x'], (float) $c['logo_y'],
                    (float) $c['logo_scale'], (float) $c['logo_opacity'],
                );
            }

            return $this->exportar($lienzo);
        } finally {
            imagedestroy($producto);
            if (isset($lienzo) && $lienzo instanceof \GdImage) {
                imagedestroy($lienzo);
            }
        }
    }

    // ─────────────────────────── fondo ───────────────────────────

    private function pintarFondo(\GdImage $lienzo, int $w, int $h, array $c): void
    {
        [$r, $g, $b] = $this->rgb($c['background_type'] === 'color' ? (string) $c['background_color'] : '#FFFFFF');
        imagefilledrectangle($lienzo, 0, 0, $w, $h, imagecolorallocate($lienzo, $r, $g, $b));

        if ($c['background_type'] !== 'image' || empty($c['background_image'])) {
            return;
        }

        $fondo = $this->abrirRecurso((string) $c['background_image']);
        if (! $fondo) {
            return; // sin fondo válido se queda el color: nunca se rompe la imagen
        }

        // El fondo cubre el lienzo recortando el sobrante (equivale a cover).
        $fw = imagesx($fondo);
        $fh = imagesy($fondo);
        $escala = max($w / $fw, $h / $fh);
        $dw = (int) round($fw * $escala);
        $dh = (int) round($fh * $escala);
        imagecopyresampled($lienzo, $fondo, (int) round(($w - $dw) / 2), (int) round(($h - $dh) / 2), 0, 0, $dw, $dh, $fw, $fh);
        imagedestroy($fondo);
    }

    // ─────────────────────────── producto ───────────────────────────

    private function pintarProducto(\GdImage $lienzo, int $w, int $h, \GdImage $producto, array $c): void
    {
        $pw = imagesx($producto);
        $ph = imagesy($producto);
        if ($pw < 1 || $ph < 1) {
            return;
        }

        // La escala es % del LADO MENOR del lienzo: así el producto ocupa lo
        // mismo tanto en 1:1 como en 4:5, y la proporción original se respeta
        // siempre (se encaja por el lado que sobra, nunca se estira).
        $lado = min($w, $h) * (max(10, min(100, (float) $c['product_scale'])) / 100);
        $factor = min($lado / $pw, $lado / $ph);
        $dw = max(1, (int) round($pw * $factor));
        $dh = max(1, (int) round($ph * $factor));

        $cx = $w * (max(0, min(100, (float) $c['product_x'])) / 100);
        $cy = $h * (max(0, min(100, (float) $c['product_y'])) / 100);
        $dx = (int) round($cx - $dw / 2);
        $dy = (int) round($cy - $dh / 2);

        if (($c['product_shadow'] ?? 'none') === 'soft') {
            $this->pintarSombra($lienzo, $dx, $dy, $dw, $dh);
        }

        imagecopyresampled($lienzo, $producto, $dx, $dy, 0, 0, $dw, $dh, $pw, $ph);
    }

    /**
     * Sombra suave bajo el producto: elipse negra difuminada por capas. Se
     * dibuja con GD puro para no depender de ninguna librería extra.
     */
    private function pintarSombra(\GdImage $lienzo, int $dx, int $dy, int $dw, int $dh): void
    {
        $cx = $dx + (int) round($dw / 2);
        $cy = $dy + $dh - (int) round($dh * 0.02);
        $capas = 6;
        for ($i = $capas; $i >= 1; $i--) {
            $ancho = (int) round($dw * (0.62 + 0.05 * $i));
            $alto = (int) round($dh * (0.06 + 0.012 * $i));
            $alfa = (int) round(118 - ($capas - $i) * 8);
            $color = imagecolorallocatealpha($lienzo, 0, 0, 0, max(90, min(127, $alfa)));
            imagefilledellipse($lienzo, $cx, $cy, max(1, $ancho), max(1, $alto), $color);
        }
    }

    // ─────────────────────────── logo y marca de agua ───────────────────────────

    /** Misma rutina para logo y marca de agua: solo cambian los valores. */
    private function pintarMarca(\GdImage $lienzo, int $w, int $h, ?string $ruta, float $x, float $y, float $escala, float $opacidad): void
    {
        if (! $ruta) {
            return; // sin logo se genera igual, sin logo (regla de respaldo)
        }
        $marca = $this->abrirRecurso($ruta);
        if (! $marca) {
            return;
        }

        $mw = imagesx($marca);
        $mh = imagesy($marca);
        $lado = min($w, $h) * (max(1, min(100, $escala)) / 100);
        $factor = min($lado / max(1, $mw), $lado / max(1, $mh));
        $dw = max(1, (int) round($mw * $factor));
        $dh = max(1, (int) round($mh * $factor));
        $dx = (int) round($w * (max(0, min(100, $x)) / 100) - $dw / 2);
        $dy = (int) round($h * (max(0, min(100, $y)) / 100) - $dh / 2);

        $opacidad = max(0, min(100, $opacidad));
        if ($opacidad <= 0) {
            imagedestroy($marca);

            return;
        }

        // Escalado previo a un lienzo con alfa: imagecopymerge no respeta la
        // transparencia del PNG, así que se compone a mano.
        $escalada = imagecreatetruecolor($dw, $dh);
        imagealphablending($escalada, false);
        imagesavealpha($escalada, true);
        imagefilledrectangle($escalada, 0, 0, $dw, $dh, imagecolorallocatealpha($escalada, 0, 0, 0, 127));
        imagealphablending($escalada, true);
        imagecopyresampled($escalada, $marca, 0, 0, 0, 0, $dw, $dh, $mw, $mh);
        imagedestroy($marca);

        $this->fusionar($lienzo, $escalada, $dx, $dy, $dw, $dh, $opacidad / 100);
        imagedestroy($escalada);
    }

    /**
     * Fusión con opacidad que RESPETA el canal alfa del PNG. Es lo que permite
     * poner un logo recortado al 12% sin que aparezca su caja rectangular.
     */
    private function fusionar(\GdImage $destino, \GdImage $capa, int $dx, int $dy, int $w, int $h, float $opacidad): void
    {
        $anchoDest = imagesx($destino);
        $altoDest = imagesy($destino);

        for ($y = 0; $y < $h; $y++) {
            $py = $dy + $y;
            if ($py < 0 || $py >= $altoDest) {
                continue;
            }
            for ($x = 0; $x < $w; $x++) {
                $px = $dx + $x;
                if ($px < 0 || $px >= $anchoDest) {
                    continue;
                }
                $src = imagecolorat($capa, $x, $y);
                $alfaSrc = ($src >> 24) & 0x7F;          // 0 opaco … 127 transparente
                if ($alfaSrc === 127) {
                    continue;
                }
                $peso = (1 - $alfaSrc / 127) * $opacidad;
                if ($peso <= 0) {
                    continue;
                }
                $dst = imagecolorat($destino, $px, $py);
                $r = (int) round(((($src >> 16) & 0xFF) * $peso) + ((($dst >> 16) & 0xFF) * (1 - $peso)));
                $g = (int) round(((($src >> 8) & 0xFF) * $peso) + ((($dst >> 8) & 0xFF) * (1 - $peso)));
                $b = (int) round((($src & 0xFF) * $peso) + (($dst & 0xFF) * (1 - $peso)));
                imagesetpixel($destino, $px, $py, imagecolorallocate($destino, $r, $g, $b));
            }
        }
    }

    // ─────────────────────────── utilidades ───────────────────────────

    /** Ruta del logo/marca: el de la plantilla o, si no, el del negocio. */
    private function recursoDe(array $c, string $prefijo, ProductImageTemplate $plantilla): ?string
    {
        if (($c[$prefijo.'_source'] ?? 'logo') === 'custom' && ! empty($c[$prefijo.'_image'])) {
            return (string) $c[$prefijo.'_image'];
        }

        // "Logo del negocio": se lee del proyecto DE ESTA plantilla. Nunca se
        // resuelve desde el proyecto activo de la sesión, que en un job de cola
        // podría ser otro y filtrar el logo de un tenant en el catálogo de otro.
        $proyecto = $plantilla->project;
        if (! $proyecto) {
            return null;
        }
        $logo = $proyecto->settings()->where('key', 'header_logo_url')->value('value')
            ?: $proyecto->settings()->where('key', 'logo_url')->value('value')
            ?: $proyecto->logo_url;

        return $logo ?: null;
    }

    /** Abre una imagen del disco público/uploads a partir de una ruta o URL. */
    private function abrirRecurso(string $referencia): ?\GdImage
    {
        $ruta = $this->rutaLocal($referencia);

        return $ruta ? $this->abrir($ruta) : null;
    }

    /**
     * Traduce una URL o ruta guardada a un fichero local, sin salir de las
     * carpetas permitidas. Cualquier intento de path traversal devuelve null.
     */
    public function rutaLocal(string $referencia): ?string
    {
        $ref = trim($referencia);
        if ($ref === '') {
            return null;
        }

        // De URL absoluta a ruta relativa.
        if (str_starts_with($ref, 'http://') || str_starts_with($ref, 'https://')) {
            $ref = (string) parse_url($ref, PHP_URL_PATH);
        }
        $ref = ltrim(str_replace('\\', '/', $ref), '/');
        foreach (['storage/', 'uploads/'] as $prefijo) {
            if (str_starts_with($ref, $prefijo)) {
                $ref = substr($ref, strlen($prefijo));
                break;
            }
        }
        if ($ref === '' || str_contains($ref, '..')) {
            return null;
        }

        $bases = [public_path('uploads'), storage_path('app/public')];
        foreach ($bases as $base) {
            $candidato = $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $ref);
            $real = realpath($candidato);
            $baseReal = realpath($base);
            // El fichero debe quedar DENTRO de la carpeta permitida.
            if ($real && $baseReal && str_starts_with($real, $baseReal) && is_file($real)) {
                if (filesize($real) > self::MAX_BYTES_RECURSO) {
                    return null;
                }

                return $real;
            }
        }

        return null;
    }

    /** Abre validando el contenido real, no la extensión del nombre. */
    private function abrir(string $ruta): ?\GdImage
    {
        if (! is_file($ruta)) {
            return null;
        }
        $info = @getimagesize($ruta);
        if (! $info) {
            return null;
        }
        // Un lienzo desmesurado agota la memoria: se rechaza antes de abrirlo.
        if ($info[0] * $info[1] > 50_000_000) {
            return null;
        }

        $im = match ($info['mime'] ?? '') {
            'image/jpeg', 'image/pjpeg' => @imagecreatefromjpeg($ruta),
            'image/png' => @imagecreatefrompng($ruta),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($ruta) : false,
            'image/gif' => @imagecreatefromgif($ruta),
            'image/avif' => function_exists('imagecreatefromavif') ? @imagecreatefromavif($ruta) : false,
            'image/bmp', 'image/x-ms-bmp' => @imagecreatefrombmp($ruta),
            default => false,
        };

        if (! $im) {
            return null;
        }
        imagealphablending($im, true);
        imagesavealpha($im, true);

        return $im;
    }

    private function exportar(\GdImage $lienzo): string
    {
        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($lienzo, null, 86);
        } else {
            imagejpeg($lienzo, null, 86);
        }

        return (string) ob_get_clean();
    }

    /** Extensión que corresponde a lo que exporta este compositor. */
    public function extension(): string
    {
        return function_exists('imagewebp') ? 'webp' : 'jpg';
    }

    private function rgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');
        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return [255, 255, 255];
        }

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }
}
