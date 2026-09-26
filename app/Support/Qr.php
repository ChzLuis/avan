<?php

namespace App\Support;

use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Picqer\Barcode\BarcodeGeneratorSVG;

/**
 * Capa unica para generar codigos QR.
 *
 * Antes cada vista armaba una URL contra `api.qrserver.com`, un servicio de
 * terceros. Eso significaba tres cosas malas: si el servicio cae la factura
 * sale sin QR, cada carga manda el contenido del codigo a un servidor ajeno,
 * y una hoja de cien etiquetas dispara cien peticiones a internet. Aqui se
 * genera en el propio servidor y sin salir a la red.
 *
 * Dos formatos, segun donde se pinte:
 *  - `svg()`  para pantalla e impresion. Es vectorial: una etiqueta pequena
 *             sale nitida aunque la impresora tire a 600 ppp.
 *  - `png()`  para los PDF, porque dompdf no dibuja SVG de forma fiable.
 *
 * El PNG se dibuja con GD a partir de la matriz de modulos, no con Imagick:
 * el servidor de produccion tiene Imagick pero el entorno local no, y un
 * generador que solo funciona en uno de los dos no sirve para probar.
 */
class Qr
{
    /** Margen en modulos. Cuatro es lo que pide la norma para que un lector enganche. */
    private const MARGEN = 4;

    /**
     * QR en SVG, listo para incrustar en el HTML.
     *
     * @param  int  $tamano  Lado en pixeles CSS.
     */
    public static function svg(string $texto, int $tamano = 200): string
    {
        if ($texto === '') {
            return '';
        }

        $renderer = new ImageRenderer(
            new RendererStyle($tamano, self::MARGEN),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($texto);
    }

    /**
     * QR como `data:` URI de SVG, para usar directamente en `<img src="...">`.
     *
     * Sirve cuando la plantilla ya tiene una etiqueta `img` y cambiarla por
     * SVG en linea obligaria a tocar el maquetado.
     */
    public static function dataUri(string $texto, int $tamano = 200): string
    {
        if ($texto === '') {
            return '';
        }

        return 'data:image/svg+xml;base64,'.base64_encode(self::svg($texto, $tamano));
    }

    /**
     * QR en PNG como `data:` URI, para los PDF.
     *
     * @param  int  $tamano  Lado aproximado en pixeles. El real se redondea al
     *                       multiplo de modulos mas cercano para que ningun
     *                       modulo quede a medio pixel y el lector falle.
     */
    public static function png(string $texto, int $tamano = 200): string
    {
        if ($texto === '' || ! extension_loaded('gd')) {
            return '';
        }

        $matriz = Encoder::encode($texto, ErrorCorrectionLevel::M())->getMatrix();
        $modulos = $matriz->getWidth();
        $total = $modulos + (self::MARGEN * 2);

        // Un modulo tiene que medir un numero entero de pixeles: si se escala
        // por decimales, los bordes quedan borrosos y el lector no engancha.
        $escala = max(1, (int) floor($tamano / $total));
        $lado = $total * $escala;

        $img = imagecreatetruecolor($lado, $lado);
        $blanco = imagecolorallocate($img, 255, 255, 255);
        $negro = imagecolorallocate($img, 0, 0, 0);
        imagefilledrectangle($img, 0, 0, $lado - 1, $lado - 1, $blanco);

        for ($y = 0; $y < $modulos; $y++) {
            for ($x = 0; $x < $modulos; $x++) {
                if ($matriz->get($x, $y) === 1) {
                    $px = ($x + self::MARGEN) * $escala;
                    $py = ($y + self::MARGEN) * $escala;
                    imagefilledrectangle($img, $px, $py, $px + $escala - 1, $py + $escala - 1, $negro);
                }
            }
        }

        ob_start();
        imagepng($img);
        $datos = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,'.base64_encode($datos);
    }

    /**
     * Codigo de barras en SVG (Code 128).
     *
     * Convive con el QR porque resuelven cosas distintas: el lector laser de
     * una caja registradora lee barras y NO lee QR, mientras que el telefono
     * del almacenero lee QR comodamente. Una tienda con caja quiere barras;
     * un almacen que cuenta con el movil, QR.
     *
     * Code 128 admite letras y numeros, que es lo que necesitan los SKU del
     * sistema ("AGR-050"); EAN-13 solo aceptaria 13 digitos.
     *
     * @param  float  $alto  Alto de las barras en unidades del SVG.
     */
    public static function barras(string $texto, float $alto = 40, float $grosor = 2): string
    {
        if ($texto === '') {
            return '';
        }

        try {
            return (new BarcodeGeneratorSVG())->getBarcode(
                $texto,
                BarcodeGeneratorSVG::TYPE_CODE_128,
                $grosor,
                $alto
            );
        } catch (\Throwable $e) {
            // Un codigo con caracteres que Code 128 no admite no debe tumbar
            // la hoja entera: esa etiqueta sale sin barras y las demas se
            // imprimen igual.
            return '';
        }
    }
}
