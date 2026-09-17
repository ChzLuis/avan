<?php

namespace App\Support\Imagen;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Punto único por el que pasa toda imagen que entra al sistema.
 *
 * La regla es que la tienda se adapte a la imagen y no al revés: el usuario
 * sube la foto que tiene —del móvil, de WhatsApp, del proveedor— y aquí se
 * decide cómo encaja en el componente que la va a mostrar. Nadie recorta a
 * mano ni "prepara" nada antes de subir.
 *
 * El original nunca se toca: se guarda aparte en el disco privado y todas
 * las variantes se generan siempre desde él, así que reencuadrar la tienda
 * es cuestión de volver a lanzar el proceso, no de pedir las fotos otra vez.
 */
class ProcesadorImagenes
{
    /** Formatos que aceptamos de entrada, mapeados a su lector de GD. */
    private const LECTORES = [
        'image/jpeg'     => 'imagecreatefromjpeg',
        'image/pjpeg'    => 'imagecreatefromjpeg',
        'image/png'      => 'imagecreatefrompng',
        'image/webp'     => 'imagecreatefromwebp',
        'image/gif'      => 'imagecreatefromgif',
        'image/avif'     => 'imagecreatefromavif',
        'image/bmp'      => 'imagecreatefrombmp',
        'image/x-ms-bmp' => 'imagecreatefrombmp',
    ];

    /**
     * Un JPEG de 6000x8000 son ~190 MB de píxeles en memoria. Por encima de
     * este techo se rechaza con un mensaje claro en vez de tumbar PHP.
     */
    private const MEGAPIXELES_MAX = 80;

    /**
     * @param  UploadedFile|string  $origen   Archivo subido o ruta en disco.
     * @param  string  $carpeta  Carpeta destino dentro del disco público (sin barra final).
     * @param  string  $perfil   Componente que va a mostrar la imagen.
     */
    public function procesar(UploadedFile|string $origen, string $carpeta, string $perfil = 'generico', ?string $nombreBase = null, string $disco = 'public'): ResultadoImagen
    {
        $ruta = $origen instanceof UploadedFile ? $origen->getRealPath() : $origen;
        if (! $ruta || ! is_file($ruta)) {
            throw new ImagenNoProcesable('No se encontró el archivo subido.');
        }

        $perfilImg = PerfilImagen::de($perfil);
        $carpeta   = trim($carpeta, '/');
        $base      = $this->nombreBase($nombreBase, $origen);

        $lienzoOrigen = $this->abrir($ruta);
        $pesoOriginal = (int) filesize($ruta);
        $conAlfa = $this->tieneAlfa($lienzoOrigen, strtolower((string) (@getimagesize($ruta)['mime'] ?? '')));

        try {
            $anchoOrigen = imagesx($lienzoOrigen);
            $altoOrigen  = imagesy($lienzoOrigen);

            // El original se guarda antes que nada: si algo falla generando
            // variantes, la fuente sigue ahí para reintentarlo.
            $original = $this->guardarOriginal($ruta, $carpeta, $base, $origen);

            $variantes  = [];
            $pesoFinal  = 0;
            $principal  = null;
            $anchoFinal = 0;
            $altoFinal  = 0;
            $anchoPrincipal = $perfilImg->anchoPrincipal();

            foreach ($perfilImg->anchos as $ancho) {
                // Nunca se agranda una foto pequeña: estirar 200 px a 1200 no
                // añade detalle, solo peso y una imagen borrosa. El ancho más
                // chico se genera siempre para que haya al menos una variante.
                if ($ancho > $anchoOrigen && $ancho !== $perfilImg->anchos[0]) {
                    continue;
                }

                $lienzo = $this->encajar($lienzoOrigen, $perfilImg, $ancho);
                $juego  = $this->escribir($lienzo, $carpeta, $base, $ancho, $perfilImg, $disco, $conAlfa);

                $variantes[$ancho] = $juego['rutas'];
                $pesoFinal += $juego['peso'];

                if ($principal === null || $ancho === $anchoPrincipal) {
                    $principal  = $juego['rutas']['principal'];
                    $anchoFinal = imagesx($lienzo);
                    $altoFinal  = imagesy($lienzo);
                }

                imagedestroy($lienzo);
            }

            if ($principal === null) {
                throw new ImagenNoProcesable('No se pudo generar ninguna variante de la imagen.');
            }

            // Compatibilidad: las plantillas ya publicadas piden el .webp
            // hermano del archivo principal (ImageVariants::webp). Se deja ese
            // alias para que sigan funcionando sin tocarlas.
            $this->alias($variantes, $principal, $disco);

            return new ResultadoImagen(
                perfil: $perfilImg->nombre,
                disco: $disco,
                original: $original,
                principal: $principal,
                variantes: $variantes,
                ancho: $anchoFinal,
                alto: $altoFinal,
                anchoOriginal: $anchoOrigen,
                altoOriginal: $altoOrigen,
                pesoOriginal: $pesoOriginal,
                pesoFinal: $pesoFinal,
            );
        } finally {
            imagedestroy($lienzoOrigen);
        }
    }

    /**
     * Atajo para los formularios que solo necesitan guardar la ruta.
     *
     * Un SVG se guarda tal cual —ya escala sin perder nada— y cualquier fallo
     * de procesado cae en guardar el archivo original antes que dejar al
     * usuario sin su imagen.
     */
    public static function ruta(?UploadedFile $archivo, string $carpeta, string $perfil = 'generico', string $disco = 'public'): ?string
    {
        if (! $archivo) {
            return null;
        }

        if (strtolower((string) $archivo->getClientOriginalExtension()) === 'svg') {
            return $archivo->store($carpeta, $disco);
        }

        try {
            return app(self::class)->procesar($archivo, $carpeta, $perfil, disco: $disco)->principal;
        } catch (ImagenNoProcesable $e) {
            Log::warning('Imagen guardada sin procesar', ['carpeta' => $carpeta, 'error' => $e->getMessage()]);

            return $archivo->store($carpeta, $disco);
        }
    }

    // ── Lectura ───────────────────────────────────────────────────────────────

    /** Abre cualquier formato soportado y lo deja ya derecho. */
    private function abrir(string $ruta): \GdImage
    {
        $info = @getimagesize($ruta);
        if (! $info) {
            throw new ImagenNoProcesable('El archivo no es una imagen que podamos leer.');
        }

        $megapixeles = ($info[0] * $info[1]) / 1000000;
        if ($megapixeles > self::MEGAPIXELES_MAX) {
            throw new ImagenNoProcesable(sprintf(
                'La imagen es de %d x %d px (%d MP). El máximo son %d MP.',
                $info[0], $info[1], $megapixeles, self::MEGAPIXELES_MAX
            ));
        }

        $lector = self::LECTORES[strtolower($info['mime'] ?? '')] ?? null;
        if (! $lector || ! function_exists($lector)) {
            throw new ImagenNoProcesable('Formato de imagen no soportado: '.($info['mime'] ?? 'desconocido').'.');
        }

        $im = @$lector($ruta);
        if (! $im instanceof \GdImage) {
            throw new ImagenNoProcesable('La imagen está dañada o incompleta.');
        }

        return $this->enderezar($im, $ruta, (string) ($info['mime'] ?? ''));
    }

    /**
     * Las fotos de móvil llegan giradas: el sensor graba siempre igual y deja
     * la orientación real en el EXIF. Sin esto, media tienda sale tumbada.
     */
    private function enderezar(\GdImage $im, string $ruta, string $mime): \GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $im;
        }

        $exif = @exif_read_data($ruta);
        $orientacion = (int) ($exif['Orientation'] ?? 0);
        if ($orientacion < 2) {
            return $im;
        }

        $girada = match ($orientacion) {
            3       => imagerotate($im, 180, 0),
            5, 6    => imagerotate($im, -90, 0),
            7, 8    => imagerotate($im, 90, 0),
            default => null,
        };

        if ($girada instanceof \GdImage) {
            imagedestroy($im);
            $im = $girada;
        }

        // 2, 4, 5 y 7 llevan además un espejo.
        if (in_array($orientacion, [2, 4, 5, 7], true)) {
            imageflip($im, in_array($orientacion, [4, 5], true) ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
        }

        return $im;
    }

    /**
     * ¿La foto trae transparencia de verdad?
     *
     * Decide el formato de salida. Antes lo decidía el perfil —"sin color de
     * fondo declarado" se leía como "conserva el alfa"— y en cuanto el perfil de
     * producto dejó de imponer lienzo blanco, fotos de cámara sin una sola
     * transparencia empezaron a escribirse en PNG, que para una fotografía pesa
     * varias veces más que un JPG.
     */
    private function tieneAlfa(\GdImage $im, string $mime): bool
    {
        if (in_array($mime, ['image/jpeg', 'image/pjpeg', 'image/bmp', 'image/x-ms-bmp'], true)) {
            return false; // el formato ni siquiera admite canal alfa
        }

        $w = imagesx($im);
        $h = imagesy($im);
        $paso = max(1, (int) floor(min($w, $h) / 60));

        for ($y = 0; $y < $h; $y += $paso) {
            for ($x = 0; $x < $w; $x += $paso) {
                if (((imagecolorat($im, $x, $y) >> 24) & 0x7F) > 10) {
                    return true;
                }
            }
        }

        return false;
    }

    // ── Encuadre ──────────────────────────────────────────────────────────────

    /** Coloca la foto dentro del hueco que pide el perfil, sin deformarla. */
    private function encajar(\GdImage $origen, PerfilImagen $perfil, int $ancho): \GdImage
    {
        $srcW = imagesx($origen);
        $srcH = imagesy($origen);

        if ($perfil->estrategia === PerfilImagen::LIBRE) {
            // Sin proporción impuesta: se conserva la del origen y solo se
            // limita el lado mayor. Con recorte, lo que se conserva es la
            // proporción del PRODUCTO, no la del papel en que lo fotografiaron.
            [$recX, $recY, $recW, $recH] = $perfil->recorteFondo
                ? $this->cajaUtil($origen)
                : [0, 0, $srcW, $srcH];

            $escala = min(1, $ancho / max($recW, $recH));
            $dstW = max(1, (int) round($recW * $escala));
            $dstH = max(1, (int) round($recH * $escala));

            return $this->pintar($origen, $dstW, $dstH, 0, 0, $dstW, $dstH, $recX, $recY, $recW, $recH, $perfil);
        }

        $proporcion = $perfil->proporcion ?: ($srcW / max(1, $srcH));
        $lienzoW = $ancho;
        $lienzoH = max(1, (int) round($ancho / $proporcion));

        if ($perfil->estrategia === PerfilImagen::COVER) {
            // Llenar el hueco: se escala por el lado que falta y se recorta el
            // sobrante desde el centro, que es donde vive el motivo en la
            // inmensa mayoría de banners.
            $escala = max($lienzoW / $srcW, $lienzoH / $srcH);
            $recorteW = min($srcW, max(1, (int) round($lienzoW / $escala)));
            $recorteH = min($srcH, max(1, (int) round($lienzoH / $escala)));

            return $this->pintar(
                $origen, $lienzoW, $lienzoH,
                0, 0, $lienzoW, $lienzoH,
                (int) round(($srcW - $recorteW) / 2), (int) round(($srcH - $recorteH) / 2),
                $recorteW, $recorteH, $perfil
            );
        }

        // CONTAIN: la foto entera dentro del lienzo, centrada, y el resto se
        // rellena. Así un producto vertical y otro horizontal ocupan la misma
        // caja de la cuadrícula sin que ninguno pierda nada.
        //
        // Antes se separa el producto del fondo que la foto ya traía horneado:
        // encajar la imagen entera dejaba unos productos al 60% del cuadro y
        // otros al 92%, y la rejilla se veía descuadrada aunque todas las
        // imágenes fuesen cuadradas.
        [$recX, $recY, $recW, $recH] = $perfil->recorteFondo
            ? $this->cajaUtil($origen)
            : [0, 0, $srcW, $srcH];

        $util = max(0.05, min(1.0, $perfil->ocupacion));
        $escala = min(($lienzoW * $util) / $recW, ($lienzoH * $util) / $recH);
        $dstW = max(1, (int) round($recW * $escala));
        $dstH = max(1, (int) round($recH * $escala));

        return $this->pintar(
            $origen, $lienzoW, $lienzoH,
            (int) round(($lienzoW - $dstW) / 2), (int) round(($lienzoH - $dstH) / 2),
            $dstW, $dstH, $recX, $recY, $recW, $recH, $perfil
        );
    }

    /**
     * Qué parte de la foto es el motivo y qué parte es fondo.
     *
     * El fondo se deduce de las cuatro esquinas: casi todas las fotos de
     * catálogo son un producto sobre un fondo liso —blanco de estudio, gris o
     * transparente—, y si las esquinas no coinciden entre sí es que la foto
     * llega al borde y no hay nada que recortar.
     *
     * @return array{0:int,1:int,2:int,3:int}  [x, y, ancho, alto]
     */
    private function cajaUtil(\GdImage $im): array
    {
        $w = imagesx($im);
        $h = imagesy($im);
        $entero = [0, 0, $w, $h];

        $fondo = $this->colorDeFondo($im, $w, $h);
        if ($fondo === null) {
            return $entero;
        }

        // Muestreo: en una foto de 800 px basta con mirar una de cada tres
        // columnas para encontrar el borde del producto.
        $paso = max(1, (int) floor(min($w, $h) / 250));
        $x1 = $w; $y1 = $h; $x2 = -1; $y2 = -1;

        for ($y = 0; $y < $h; $y += $paso) {
            for ($x = 0; $x < $w; $x += $paso) {
                if ($this->esFondo(imagecolorat($im, $x, $y), $fondo)) {
                    continue;
                }
                if ($x < $x1) { $x1 = $x; }
                if ($y < $y1) { $y1 = $y; }
                if ($x > $x2) { $x2 = $x; }
                if ($y > $y2) { $y2 = $y; }
            }
        }

        if ($x2 < 0) {
            return $entero; // toda la imagen es fondo: no se toca
        }

        // Holgura de un paso por el muestreo, más un pelín de aire para no
        // cortar la sombra del producto.
        $margen = $paso + (int) round(min($w, $h) * 0.01);
        $x1 = max(0, $x1 - $margen);
        $y1 = max(0, $y1 - $margen);
        $x2 = min($w - 1, $x2 + $margen);
        $y2 = min($h - 1, $y2 + $margen);

        $cajaW = $x2 - $x1 + 1;
        $cajaH = $y2 - $y1 + 1;

        // Un recorte minúsculo suele ser una firma, una mota o ruido de JPEG,
        // no el producto. Ante la duda se deja la foto como estaba.
        if ($cajaW * $cajaH < $w * $h * 0.02) {
            return $entero;
        }

        return [$x1, $y1, $cajaW, $cajaH];
    }

    /** Color liso de las esquinas, o null si no hay fondo que recortar. */
    private function colorDeFondo(\GdImage $im, int $w, int $h): ?array
    {
        $esquinas = [];
        foreach ([[1, 1], [$w - 2, 1], [1, $h - 2], [$w - 2, $h - 2]] as [$x, $y]) {
            $c = imagecolorat($im, max(0, $x), max(0, $y));
            $esquinas[] = [
                'a' => ($c >> 24) & 0x7F,
                'r' => ($c >> 16) & 0xFF,
                'g' => ($c >> 8) & 0xFF,
                'b' => $c & 0xFF,
            ];
        }

        // Transparente en las esquinas: el fondo es el alfa, sin más.
        if (count(array_filter($esquinas, fn ($e) => $e['a'] > 100)) >= 3) {
            return ['a' => 127, 'r' => 0, 'g' => 0, 'b' => 0];
        }

        $ref = $esquinas[0];
        foreach ($esquinas as $e) {
            if (abs($e['r'] - $ref['r']) > 18 || abs($e['g'] - $ref['g']) > 18 || abs($e['b'] - $ref['b']) > 18) {
                return null; // esquinas distintas: la foto llega al borde
            }
        }

        return $ref;
    }

    private function esFondo(int $color, array $fondo): bool
    {
        if (($fondo['a'] ?? 0) > 100) {
            return (($color >> 24) & 0x7F) > 100;
        }

        if (((($color >> 24) & 0x7F)) > 100) {
            return true; // píxel transparente sobre fondo opaco
        }

        return abs((($color >> 16) & 0xFF) - $fondo['r']) <= 14
            && abs((($color >> 8) & 0xFF) - $fondo['g']) <= 14
            && abs(($color & 0xFF) - $fondo['b']) <= 14;
    }

    private function pintar(\GdImage $origen, int $lienzoW, int $lienzoH, int $dstX, int $dstY, int $dstW, int $dstH, int $srcX, int $srcY, int $srcW, int $srcH, PerfilImagen $perfil): \GdImage
    {
        $lienzo = imagecreatetruecolor($lienzoW, $lienzoH);

        if ($perfil->fondo) {
            [$r, $g, $b] = sscanf($perfil->fondo, '#%02x%02x%02x');
            imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, (int) $r, (int) $g, (int) $b));
        } else {
            // Sin fondo declarado se conserva la transparencia: es lo que
            // mantiene un logo PNG utilizable sobre cualquier color.
            imagealphablending($lienzo, false);
            imagesavealpha($lienzo, true);
            imagefill($lienzo, 0, 0, imagecolorallocatealpha($lienzo, 0, 0, 0, 127));
            imagealphablending($lienzo, true);
        }

        imagecopyresampled($lienzo, $origen, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);

        if (! $perfil->fondo) {
            imagealphablending($lienzo, false);
            imagesavealpha($lienzo, true);
        }

        return $lienzo;
    }

    // ── Escritura ─────────────────────────────────────────────────────────────

    /**
     * Escribe una variante en todos los formatos disponibles. El navegador
     * elegirá el más ligero que entienda vía <picture>/srcset.
     */
    private function escribir(\GdImage $lienzo, string $carpeta, string $base, int $ancho, PerfilImagen $perfil, string $nombreDisco = 'public', ?bool $conAlfa = null): array
    {
        $disco = Storage::disk($nombreDisco);
        // PNG solo cuando hay transparencia que conservar; para una fotografía
        // el JPG pesa varias veces menos con la misma calidad aparente.
        $conAlfa = $conAlfa ?? ($perfil->fondo === null);
        $extPrincipal = $conAlfa ? 'png' : 'jpg';

        $rutas = [];
        $peso = 0;

        $escribirCon = function (string $ext, callable $fn) use (&$rutas, &$peso, $disco, $carpeta, $base, $ancho) {
            $abs = $this->rutaTemporal();
            if (! @$fn($abs) || ! is_file($abs) || filesize($abs) === 0) {
                @unlink($abs);

                return;
            }
            $rel = "{$carpeta}/{$base}-{$ancho}.{$ext}";
            $disco->put($rel, file_get_contents($abs));
            $peso += (int) filesize($abs);
            @unlink($abs);
            $rutas[$ext] = $rel;
        };

        if ($conAlfa) {
            $escribirCon('png', fn ($d) => imagepng($lienzo, $d, 8));
        } else {
            $escribirCon('jpg', function ($d) use ($lienzo, $perfil) {
                imageinterlace($lienzo, true); // progresivo: se ve antes de acabar de cargar

                return imagejpeg($lienzo, $d, $perfil->calidad);
            });
        }

        if (function_exists('imagewebp')) {
            $escribirCon('webp', fn ($d) => imagewebp($lienzo, $d, $perfil->calidad));
        }

        // AVIF solo donde el PHP lo trae compilado; donde no, el <picture> cae
        // a webp sin que se note.
        if (function_exists('imageavif')) {
            $escribirCon('avif', fn ($d) => imageavif($lienzo, $d, max(30, $perfil->calidad - 20)));
        }

        if (! isset($rutas[$extPrincipal])) {
            throw new ImagenNoProcesable('No se pudo escribir la imagen en el disco.');
        }

        $rutas['principal'] = $rutas[$extPrincipal];

        return ['rutas' => $rutas, 'peso' => $peso];
    }

    /**
     * Deja junto al archivo principal su gemelo .webp con el mismo nombre,
     * que es lo que buscan las plantillas ya publicadas.
     */
    private function alias(array $variantes, string $principal, string $nombreDisco = 'public'): void
    {
        $webp = null;
        foreach ($variantes as $juego) {
            if (($juego['principal'] ?? null) === $principal && isset($juego['webp'])) {
                $webp = $juego['webp'];
                break;
            }
        }
        if (! $webp) {
            return;
        }

        $destino = preg_replace('/\.(png|jpe?g)$/i', '.webp', $principal);
        if (! $destino || $destino === $principal) {
            return;
        }

        $disco = Storage::disk($nombreDisco);
        $disco->put($destino, $disco->get($webp));
    }

    /** El original se archiva fuera de la web: sirve de fuente, no se sirve. */
    private function guardarOriginal(string $ruta, string $carpeta, string $base, UploadedFile|string $origen): ?string
    {
        $ext = $origen instanceof UploadedFile
            ? ($origen->getClientOriginalExtension() ?: $origen->extension())
            : pathinfo($ruta, PATHINFO_EXTENSION);
        $ext = strtolower((string) preg_replace('/[^a-z0-9]/i', '', (string) $ext)) ?: 'bin';

        $destino = "originales/{$carpeta}/{$base}.{$ext}";

        try {
            Storage::disk('local')->put($destino, file_get_contents($ruta));

            return $destino;
        } catch (\Throwable $e) {
            // Perder el archivo de respaldo no debe impedir publicar la foto.
            Log::warning('No se pudo archivar el original de la imagen', [
                'destino' => $destino,
                'error'   => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function nombreBase(?string $nombreBase, UploadedFile|string $origen): string
    {
        if ($nombreBase) {
            // Se respeta el nombre tal cual (solo se neutraliza lo peligroso):
            // al regenerar fotos antiguas, las variantes tienen que quedar con
            // el mismo prefijo que el archivo que ya está enlazado en la web.
            return trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $nombreBase), '-.') ?: uniqid('img_');
        }

        $nombre = $origen instanceof UploadedFile
            ? pathinfo($origen->getClientOriginalName(), PATHINFO_FILENAME)
            : pathinfo($origen, PATHINFO_FILENAME);

        $slug = Str::limit(Str::slug($nombre), 60, '');

        // El sufijo evita que dos fotos llamadas "IMG_1234" se pisen entre sí.
        return ($slug !== '' ? $slug.'-' : '').Str::lower(Str::random(6));
    }

    private function rutaTemporal(): string
    {
        return (string) tempnam(sys_get_temp_dir(), 'avanimg');
    }
}
