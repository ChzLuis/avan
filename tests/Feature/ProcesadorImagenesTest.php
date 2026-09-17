<?php

namespace Tests\Feature;

use App\Support\Imagen\ImagenNoProcesable;
use App\Support\Imagen\Img;
use App\Support\Imagen\ProcesadorImagenes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * El trato con el usuario es que suba lo que tenga y la tienda se encargue.
 * Estas pruebas cubren justo eso: fotos verticales de móvil, panorámicas de
 * proveedor, logos con transparencia, capturas diminutas y archivos rotos.
 */
class ProcesadorImagenesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('gd')) {
            $this->markTestSkipped('Sin GD no hay nada que procesar.');
        }

        Storage::fake('public');
        Storage::fake('uploads');
        Storage::fake('local');
        Img::olvidar();
    }

    // ── Ayudas ────────────────────────────────────────────────────────────────

    /** Crea un JPG de las dimensiones pedidas y lo envuelve como subida. */
    private function subida(int $ancho, int $alto, string $nombre = 'foto.jpg', string $formato = 'jpg'): UploadedFile
    {
        $im = imagecreatetruecolor($ancho, $alto);

        // Un degradado da contenido real: un lienzo liso comprime a nada y no
        // detectaría una pérdida de calidad absurda.
        for ($x = 0; $x < $ancho; $x += 8) {
            $color = imagecolorallocate($im, ($x * 3) % 255, 90, 160);
            imagefilledrectangle($im, $x, 0, $x + 8, $alto, $color);
        }

        $ruta = tempnam(sys_get_temp_dir(), 'test').'.'.$formato;
        match ($formato) {
            'png'  => imagepng($im, $ruta),
            'webp' => imagewebp($im, $ruta),
            'gif'  => imagegif($im, $ruta),
            default => imagejpeg($im, $ruta, 92),
        };
        imagedestroy($im);

        return new UploadedFile($ruta, $nombre, null, null, true);
    }

    /** Logo con fondo realmente transparente: es lo que obliga a salir en PNG. */
    private function logoTransparente(int $ancho, int $alto): UploadedFile
    {
        $im = imagecreatetruecolor($ancho, $alto);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
        imagealphablending($im, true);
        imagefilledellipse($im, (int) ($ancho / 2), (int) ($alto / 2),
            (int) ($ancho * 0.7), (int) ($alto * 0.7), imagecolorallocate($im, 20, 80, 190));

        $ruta = tempnam(sys_get_temp_dir(), 'logo').'.png';
        imagealphablending($im, false);
        imagesavealpha($im, true);
        imagepng($im, $ruta);
        imagedestroy($im);

        return new UploadedFile($ruta, 'marca.png', null, null, true);
    }

    private function medidasDe(string $disco, string $ruta): array
    {
        $info = getimagesize(Storage::disk($disco)->path($ruta));

        return [$info[0], $info[1]];
    }

    // ── Cualquier proporción entra ────────────────────────────────────────────

    /**
     * El caso del enunciado: una foto de móvil en vertical entra tal cual y sale
     * usable, conservando su proporción. No se mete en un lienzo cuadrado: la
     * forma del hueco la decide la tarjeta de cada tienda (1/1, 4/3, 3/4), y
     * hornear un cuadrado desperdiciaba el ancho en las que no lo son.
     */
    public function test_una_foto_vertical_de_movil_conserva_su_proporcion(): void
    {
        $resultado = app(ProcesadorImagenes::class)
            ->procesar($this->subida(3024, 4032), 'products/1', 'producto');

        [$w, $h] = $this->medidasDe('public', $resultado->principal);

        $this->assertEqualsWithDelta(3024 / 4032, $w / $h, 0.03, 'Sale con la proporción con la que entró.');
        $this->assertSame(800, max($w, $h), 'El lado mayor se limita al ancho pedido.');
        $this->assertSame(3024, $resultado->anchoOriginal);
        $this->assertSame(4032, $resultado->altoOriginal);
    }

    public function test_una_panoramica_conserva_la_suya(): void
    {
        $resultado = app(ProcesadorImagenes::class)
            ->procesar($this->subida(4000, 1200), 'products/1', 'producto');

        [$w, $h] = $this->medidasDe('public', $resultado->principal);

        $this->assertEqualsWithDelta(4000 / 1200, $w / $h, 0.05);
        $this->assertSame(800, $w);
    }

    /**
     * Deformar es el pecado capital. Se comprueba con la proporción: una foto
     * 2:1 tiene que salir 2:1, pase por donde pase.
     */
    public function test_el_contenido_no_se_estira(): void
    {
        foreach ([[2000, 1000], [900, 1600], [1500, 1500]] as [$w0, $h0]) {
            $resultado = app(ProcesadorImagenes::class)
                ->procesar($this->subida($w0, $h0), 'products/1', 'producto', "p{$w0}x{$h0}");

            [$w, $h] = $this->medidasDe('public', $resultado->principal);

            $this->assertEqualsWithDelta($w0 / $h0, $w / $h, 0.05, "Se deformó la de {$w0}x{$h0}");
        }
    }

    public function test_una_imagen_cuadrada_pasa_sin_recorte(): void
    {
        $resultado = app(ProcesadorImagenes::class)
            ->procesar($this->subida(1500, 1500), 'products/1', 'producto');

        $this->assertSame([800, 800], $this->medidasDe('public', $resultado->principal));
    }

    // ── La rejilla se ve pareja ───────────────────────────────────────────────

    /** Foto de catálogo: un rectángulo de color sobre fondo blanco. */
    private function productoConMargen(int $lienzo, float $ocupa): UploadedFile
    {
        $im = imagecreatetruecolor($lienzo, $lienzo);
        imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));

        $lado = (int) round($lienzo * $ocupa);
        $desde = (int) round(($lienzo - $lado) / 2);
        imagefilledrectangle($im, $desde, $desde, $desde + $lado, $desde + $lado, imagecolorallocate($im, 30, 90, 200));

        $ruta = tempnam(sys_get_temp_dir(), 'prod').'.png';
        imagepng($im, $ruta);
        imagedestroy($im);

        return new UploadedFile($ruta, 'prod.png', null, null, true);
    }

    /** Mide qué parte del lienzo ocupa lo que no es blanco. */
    private function ocupacionDe(string $disco, string $ruta): float
    {
        $im = imagecreatefromjpeg(Storage::disk($disco)->path($ruta));
        $w = imagesx($im);
        $h = imagesy($im);
        $x1 = $w; $x2 = -1;

        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y += 4) {
                $c = imagecolorat($im, $x, $y);
                if ((($c >> 16) & 0xFF) > 242 && (($c >> 8) & 0xFF) > 242 && ($c & 0xFF) > 242) {
                    continue;
                }
                if ($x < $x1) { $x1 = $x; }
                if ($x > $x2) { $x2 = $x; }
            }
        }
        imagedestroy($im);

        return $x2 < 0 ? 0.0 : ($x2 - $x1 + 1) / $w;
    }

    /**
     * El caso que se veía mal en Baby Toncito: unas fotos traían el producto
     * al 60% del encuadre y otras al 92%, así que en la rejilla parecían de
     * tamaños distintos aunque todas fuesen cuadradas.
     */
    public function test_dos_fotos_con_margenes_distintos_acaban_igual_de_llenas(): void
    {
        $procesador = app(ProcesadorImagenes::class);

        $apretada = $procesador->procesar($this->productoConMargen(1000, 0.92), 'products/1', 'producto', 'a');
        $suelta   = $procesador->procesar($this->productoConMargen(1000, 0.45), 'products/1', 'producto', 'b');

        $ocupaA = $this->ocupacionDe('public', $apretada->principal);
        $ocupaB = $this->ocupacionDe('public', $suelta->principal);

        $this->assertEqualsWithDelta($ocupaA, $ocupaB, 0.06, 'Las dos deben llenar el cuadro por igual.');
        $this->assertGreaterThan(0.8, $ocupaB, 'La foto con mucho aire blanco se recorta y se agranda.');
    }

    /**
     * La propiedad que hace que el producto llene la tarjeta sea cual sea su
     * forma: la foto procesada ES el producto, sin borde de fondo. Si volviera a
     * hornearse un lienzo, en una tarjeta 4/3 se perderia un cuarto del ancho.
     */
    public function test_la_foto_procesada_no_lleva_borde_de_fondo(): void
    {
        $resultado = app(ProcesadorImagenes::class)
            ->procesar($this->productoConMargen(1000, 0.45), 'products/1', 'producto');

        // ~95%, no 100%: el recorte deja un 1% de holgura por lado a proposito,
        // para no comerse la sombra suave con la que se fotografia el producto.
        $this->assertGreaterThan(0.93, $this->ocupacionDe('public', $resultado->principal),
            'El producto debe llegar practicamente al borde de su propia foto.');
    }

    /** Una foto que llega al borde no se recorta: no hay fondo que quitar. */
    public function test_una_foto_a_sangre_no_se_recorta(): void
    {
        $resultado = app(ProcesadorImagenes::class)
            ->procesar($this->subida(1000, 1000), 'products/1', 'producto');

        $this->assertGreaterThan(0.85, $this->ocupacionDe('public', $resultado->principal));
    }

    // ── Tamaños extremos ──────────────────────────────────────────────────────

    /** Una miniatura de 120 px no se agranda a 800: se vería peor, no mejor. */
    public function test_una_imagen_pequena_no_se_agranda(): void
    {
        $resultado = app(ProcesadorImagenes::class)
            ->procesar($this->subida(120, 90), 'products/1', 'producto');

        $anchos = array_keys($resultado->variantes);
        $this->assertSame([400], $anchos, 'Solo se genera el ancho más chico.');
    }

    public function test_una_imagen_enorme_se_reduce_y_pesa_mucho_menos(): void
    {
        $resultado = app(ProcesadorImagenes::class)
            ->procesar($this->subida(4000, 3000), 'store-sections/1', 'seccion');

        [$w] = $this->medidasDe('public', $resultado->principal);

        $this->assertLessThanOrEqual(1600, $w);
        $this->assertLessThan($resultado->pesoOriginal, $resultado->pesoFinal);
    }

    public function test_una_imagen_desmedida_se_rechaza_con_un_mensaje_claro(): void
    {
        // La cabecera se fabrica a mano: crear de verdad 12000x9000 px son
        // 432 MB de RAM, justo lo que esta comprobación existe para evitar.
        $ruta = tempnam(sys_get_temp_dir(), 'enorme').'.png';
        file_put_contents($ruta, $this->cabeceraPng(12000, 9000));

        $this->expectException(ImagenNoProcesable::class);
        $this->expectExceptionMessageMatches('/MP/');

        app(ProcesadorImagenes::class)->procesar(
            new UploadedFile($ruta, 'enorme.png', null, null, true), 'products/1', 'producto'
        );
    }

    /** PNG con solo firma e IHDR: suficiente para que getimagesize lo mida. */
    private function cabeceraPng(int $ancho, int $alto): string
    {
        $ihdr = 'IHDR'.pack('N2', $ancho, $alto).pack('C5', 8, 2, 0, 0, 0);

        return "\x89PNG\r\n\x1a\n".pack('N', 13).$ihdr.pack('N', crc32($ihdr));
    }

    // ── Estrategias por tipo ──────────────────────────────────────────────────

    public function test_el_banner_llena_su_franja_con_la_proporcion_pedida(): void
    {
        $resultado = app(ProcesadorImagenes::class)
            ->procesar($this->subida(4000, 2000), 'logos/1', 'banner');

        [$w, $h] = $this->medidasDe('public', $resultado->principal);

        $this->assertEqualsWithDelta(1920 / 640, $w / $h, 0.02, 'El banner de escritorio es panorámico.');
    }

    public function test_el_banner_movil_es_una_variante_distinta_y_mas_alta(): void
    {
        $procesador = app(ProcesadorImagenes::class);
        $escritorio = $procesador->procesar($this->subida(4000, 2000), 'logos/1', 'banner', 'a');
        $movil      = $procesador->procesar($this->subida(4000, 2000), 'logos/1', 'banner_movil', 'b');

        [$we, $he] = $this->medidasDe('public', $escritorio->principal);
        [$wm, $hm] = $this->medidasDe('public', $movil->principal);

        $this->assertLessThan($we / $he, $wm / $hm, 'El de móvil es proporcionalmente más alto.');
        $this->assertEqualsWithDelta(4 / 5, $wm / $hm, 0.02);
    }

    /** Un logo no se recorta ni se rellena: la marca se conserva entera. */
    public function test_el_logo_conserva_su_proporcion_y_su_transparencia(): void
    {
        $resultado = app(ProcesadorImagenes::class)
            ->procesar($this->logoTransparente(900, 300), 'logos/1', 'logo');

        [$w, $h] = $this->medidasDe('public', $resultado->principal);

        $this->assertEqualsWithDelta(3.0, $w / $h, 0.02, 'La proporción del logo no se toca.');
        $this->assertStringEndsWith('.png', $resultado->principal, 'El PNG se mantiene para no perder el alfa.');
    }

    // ── Formatos y variantes ──────────────────────────────────────────────────

    public function test_genera_webp_ademas_del_formato_original(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('Este PHP no trae WebP.');
        }

        $resultado = app(ProcesadorImagenes::class)
            ->procesar($this->subida(1200, 1200), 'products/1', 'producto');

        foreach ($resultado->variantes as $juego) {
            $this->assertArrayHasKey('webp', $juego);
            Storage::disk('public')->assertExists($juego['webp']);
        }
    }

    public function test_genera_varios_anchos_para_servir_el_que_toque(): void
    {
        $resultado = app(ProcesadorImagenes::class)
            ->procesar($this->subida(2000, 2000), 'products/1', 'producto');

        $this->assertSame([400, 800, 1200], array_keys($resultado->variantes));
    }

    public function test_acepta_png_webp_y_gif_de_entrada(): void
    {
        foreach (['png', 'webp', 'gif'] as $formato) {
            if ($formato === 'webp' && ! function_exists('imagewebp')) {
                continue;
            }

            $resultado = app(ProcesadorImagenes::class)
                ->procesar($this->subida(900, 600, "f.{$formato}", $formato), 'products/1', 'producto', $formato);

            Storage::disk('public')->assertExists($resultado->principal);
        }
    }

    // ── El original es sagrado ────────────────────────────────────────────────

    public function test_el_original_se_archiva_fuera_de_la_web(): void
    {
        $resultado = app(ProcesadorImagenes::class)
            ->procesar($this->subida(2000, 1500), 'products/7', 'producto');

        $this->assertNotNull($resultado->original);
        $this->assertStringStartsWith('originales/products/7/', $resultado->original);
        Storage::disk('local')->assertExists($resultado->original);

        // Y el archivo guardado sigue siendo la foto tal cual la subieron.
        $info = getimagesize(Storage::disk('local')->path($resultado->original));
        $this->assertSame([2000, 1500], [$info[0], $info[1]]);
    }

    public function test_dos_fotos_con_el_mismo_nombre_no_se_pisan(): void
    {
        $procesador = app(ProcesadorImagenes::class);
        $a = $procesador->procesar($this->subida(800, 800, 'IMG_1234.jpg'), 'products/1', 'producto');
        $b = $procesador->procesar($this->subida(600, 900, 'IMG_1234.jpg'), 'products/1', 'producto');

        $this->assertNotSame($a->principal, $b->principal);
        Storage::disk('public')->assertExists($a->principal);
        Storage::disk('public')->assertExists($b->principal);
    }

    // ── Errores ───────────────────────────────────────────────────────────────

    public function test_un_archivo_que_no_es_imagen_da_un_mensaje_entendible(): void
    {
        $ruta = tempnam(sys_get_temp_dir(), 'roto');
        file_put_contents($ruta, 'esto no es una imagen');

        $this->expectException(ImagenNoProcesable::class);

        app(ProcesadorImagenes::class)->procesar(
            new UploadedFile($ruta, 'roto.jpg', null, null, true), 'products/1', 'producto'
        );
    }

    public function test_un_perfil_inventado_cae_en_el_generico_en_vez_de_reventar(): void
    {
        $resultado = app(ProcesadorImagenes::class)
            ->procesar($this->subida(1000, 700), 'products/1', 'perfil-que-no-existe');

        $this->assertSame('generico', $resultado->perfil);
        Storage::disk('public')->assertExists($resultado->principal);
    }

    // ── Presentación ──────────────────────────────────────────────────────────

    public function test_la_etiqueta_sale_con_srcset_lazy_y_medidas(): void
    {
        $resultado = app(ProcesadorImagenes::class)
            ->procesar($this->subida(2000, 2000), 'products/1', 'producto');

        Img::olvidar();
        $html = Img::etiqueta('/storage/'.$resultado->principal, 'producto', ['alt' => 'Teclado']);

        $this->assertStringContainsString('srcset=', $html);
        $this->assertStringContainsString('400w', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('width="800" height="800"', $html);
        $this->assertStringContainsString('alt="Teclado"', $html);
    }

    /** Una foto antigua sin variantes tiene que seguir viéndose igual. */
    public function test_una_imagen_sin_variantes_se_sirve_tal_cual(): void
    {
        Img::olvidar();
        $html = Img::etiqueta('/storage/logos/1/antigua.jpg', 'generico', ['alt' => 'Vieja']);

        $this->assertStringContainsString('src="/storage/logos/1/antigua.jpg"', $html);
        $this->assertStringNotContainsString('srcset=', $html);
        $this->assertStringNotContainsString('<picture>', $html);
    }

    /**
     * Una foto antigua conserva su nombre y su URL; el regenerado le deja los
     * hermanos al lado. El navegador debe poder elegir entre ambos, con la
     * original como candidata grande.
     */
    public function test_una_foto_antigua_regenerada_ofrece_original_y_variante(): void
    {
        Storage::disk('public')->put('logos/1/vieja.jpg', file_get_contents($this->subida(1000, 1000)->getRealPath()));

        app(ProcesadorImagenes::class)->procesar(
            Storage::disk('public')->path('logos/1/vieja.jpg'), 'logos/1', 'producto', 'vieja'
        );

        Img::olvidar();
        $html = Img::etiqueta('/storage/logos/1/vieja.jpg', 'producto');

        $this->assertStringContainsString('400w', $html);
        $this->assertStringContainsString('1000w', $html, 'La original entra como candidata mayor.');
        $this->assertStringContainsString('src="/storage/logos/1/vieja.jpg"', $html, 'La URL de siempre sigue siendo el respaldo.');
    }

    /**
     * Lo que se veía mal en /tienda: la rejilla la pinta Alpine desde JSON con
     * un <img> pelado, así que la URL que sale del modelo tiene que ser ya la
     * variante encuadrada, no la foto original.
     */
    public function test_mejor_devuelve_la_variante_encuadrada(): void
    {
        Storage::disk('public')->put('products/9/foto.jpg', file_get_contents($this->subida(1400, 1400)->getRealPath()));

        app(ProcesadorImagenes::class)->procesar(
            Storage::disk('public')->path('products/9/foto.jpg'), 'products/9', 'producto', 'foto'
        );

        Img::olvidar();
        $this->assertSame('/storage/products/9/foto-800.jpg', Img::mejor('/storage/products/9/foto.jpg', 'producto'));
    }

    /**
     * La ficha de producto servía el archivo original —587 KB para un cuadro de
     * 480 px, y otro tanto por cada miniatura de 60 px—. Pide el ancho que va
     * a enseñar.
     */
    public function test_de_ancho_devuelve_la_variante_pedida(): void
    {
        Storage::disk('public')->put('products/9/ficha.jpg', file_get_contents($this->subida(1600, 1600)->getRealPath()));

        app(ProcesadorImagenes::class)->procesar(
            Storage::disk('public')->path('products/9/ficha.jpg'), 'products/9', 'producto', 'ficha'
        );

        Img::olvidar();
        $this->assertSame('/storage/products/9/ficha-400.jpg', Img::deAncho('/storage/products/9/ficha.jpg', 400));
        $this->assertSame('/storage/products/9/ficha-800.jpg', Img::deAncho('/storage/products/9/ficha.jpg', 800));

        // Partiendo de una variante también se llega a otra.
        $this->assertSame('/storage/products/9/ficha-400.jpg', Img::deAncho('/storage/products/9/ficha-800.jpg', 400));
    }

    public function test_de_ancho_cae_en_la_mejor_cuando_ese_tamano_no_existe(): void
    {
        Img::olvidar();
        $this->assertSame('/storage/products/9/suelta.jpg', Img::deAncho('/storage/products/9/suelta.jpg', 400));
        $this->assertNull(Img::deAncho(null, 400));
    }

    public function test_mejor_no_toca_una_foto_sin_variantes(): void
    {
        Img::olvidar();
        $this->assertSame('/storage/products/9/sola.jpg', Img::mejor('/storage/products/9/sola.jpg', 'producto'));
        $this->assertNull(Img::mejor(null));
    }

    public function test_la_imagen_principal_se_pide_sin_esperar(): void
    {
        Img::olvidar();
        $html = Img::etiqueta('/storage/logos/1/hero.jpg', 'banner', ['eager' => true]);

        $this->assertStringContainsString('loading="eager"', $html);
        $this->assertStringContainsString('fetchpriority="high"', $html);
    }
}
