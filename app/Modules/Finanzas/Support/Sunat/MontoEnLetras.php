<?php

namespace App\Modules\Finanzas\Support\Sunat;

/**
 * El importe escrito en palabras: "SON CIENTO DIECIOCHO CON 00/100 SOLES".
 *
 * Es la leyenda 1000 de SUNAT y es obligatoria dos veces: en el XML del
 * comprobante y en su representación impresa. Vivía como método privado del
 * servicio de APIsPERU, así que el PDF no podía usarla; ahora ambos leen de
 * aquí y el papel dice lo mismo que el XML.
 */
final class MontoEnLetras
{
    public static function de(float $monto, string $moneda = 'PEN'): string
    {
        $entero  = (int) floor($monto);
        $decimal = (int) round(($monto - $entero) * 100);
        $unidad  = $moneda === 'USD' ? 'DÓLARES AMERICANOS' : 'SOLES';

        return sprintf('SON %s CON %02d/100 %s', self::entero($entero), $decimal, $unidad);
    }

    private static function entero(int $n): string
    {
        if ($n === 0) {
            return 'CERO';
        }

        $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ',
            'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE'];
        $decenas  = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        $texto = '';
        if ($n >= 1000000) {
            $m = intdiv($n, 1000000);
            // "UN MILLÓN", no "UNO MILLONES": la leyenda se lee en voz alta
            // al firmar y viaja igual al XML.
            $texto .= ($m === 1 ? 'UN MILLÓN ' : self::entero($m).' MILLONES ');
            $n %= 1000000;
        }
        if ($n >= 1000)    { $m = intdiv($n, 1000); $texto .= ($m === 1 ? 'MIL ' : self::entero($m).' MIL '); $n %= 1000; }
        if ($n >= 100)     { $texto .= ($n === 100 ? 'CIEN ' : $centenas[intdiv($n, 100)].' '); $n %= 100; }
        if ($n <= 20)      { $texto .= $unidades[$n]; }
        else               { $texto .= $decenas[intdiv($n, 10)]; if ($n % 10) { $texto .= ' Y '.$unidades[$n % 10]; } }

        return trim($texto);
    }
}
