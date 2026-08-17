<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Calculo UNICO de una linea comercial con aritmetica EXACTA de enteros.
 *
 * Contrato v3 (auditoria Codex): el nucleo publico trabaja en CENTAVOS y
 * BASIS POINTS con entradas decimales CANONICAS (string "X.YY"); la salida
 * para persistencia es string "X.YY" o centavos int — NUNCA float, para no
 * reintroducir binario justo antes de la BD.
 *
 * Frontera legacy: los payloads JSON actuales traen numeros. `canon()` es el
 * UNICO punto que acepta int|float y los canoniza UNA VEZ a string 2dp tras
 * validar que representan un decimal de dominio (float con 3+ decimales
 * significativos = dato imposible => excepcion, no clamp).
 *
 * Linea (centavos, half-up): intdiv(cents*qty*(10000-bp) + 5000, 10000)
 * Total documento = SUMA de centavos; formateo solo en el borde.
 *
 * Bordes canonicos: 33.33x3@10 = 89.99 · 0.01x1@50 = 0.01 ·
 * 0.05x1@50 = 0.03 (acarreo) · 99.99x7@33.33 = 466.64
 * (69993*6667=466643331; +5000; intdiv 10^4 = 46664 centavos).
 *
 * Limite anti-overflow: price_cents*qty <= 10^14 — seguro en int64 PHP con el
 * x10^4 posterior (10^18 < 9.2x10^18). OJO: 10^18 EXCEDE Number.MAX_SAFE_INTEGER
 * (2^53 ~ 9x10^15), por lo que el espejo JS DEBE usar BigInt para la
 * multiplicacion critica; Number solo para el formateo final de centavos.
 */
class LineMath
{
    private const MAX_LINE_BASE_CENTS = 100_000_000_000_000; // 10^14

    /**
     * Frontera legacy: canoniza numerico JSON a string decimal 2dp.
     * int 33 -> "33.00" · float 33.33 -> "33.33" · float 1.005 -> excepcion.
     */
    public static function canon(string|int|float $valor): string
    {
        if (is_string($valor)) {
            return trim($valor);
        }
        if (is_int($valor)) {
            return $valor . '.00';
        }
        // float: solo si a 2dp reproduce el valor recibido (tolerancia del
        // propio binario de 64 bits); 3+ decimales reales => imposible.
        $s = number_format($valor, 2, '.', '');
        if (abs(((float) $s) - $valor) > 1e-9) {
            throw new InvalidArgumentException("Valor con mas de 2 decimales: {$valor}");
        }

        return $s;
    }

    /** "33.33" -> 3333 centavos. Estricto: decimal >= 0 con 1-2 dp. */
    public static function toCents(string $price): int
    {
        $s = trim($price);
        if (! preg_match('/^\d+(\.\d{1,2})?$/', $s)) {
            throw new InvalidArgumentException("Precio invalido: '{$s}'");
        }
        [$ent, $dec] = array_pad(explode('.', $s, 2), 2, '');
        $cents = ((int) $ent) * 100 + (int) str_pad($dec, 2, '0');
        if ($cents > 9_999_999_999) {
            throw new InvalidArgumentException("Precio fuera de limite: {$s}");
        }

        return $cents;
    }

    /** "33.33" % -> 3333 bp. Rango estricto 0..10000. */
    public static function toBasisPoints(string $discount): int
    {
        $s = trim($discount);
        if (! preg_match('/^\d+(\.\d{1,2})?$/', $s)) {
            throw new InvalidArgumentException("Descuento invalido: '{$s}'");
        }
        [$ent, $dec] = array_pad(explode('.', $s, 2), 2, '');
        $bp = ((int) $ent) * 100 + (int) str_pad($dec, 2, '0');
        if ($bp > 10_000) {
            throw new InvalidArgumentException("Descuento fuera de rango 0..100: {$s}");
        }

        return $bp;
    }

    /** Centavos de una linea, half-up exacto. Nucleo entero puro. */
    public static function lineCents(string $price, int $qty, string $discount = '0'): int
    {
        if ($qty < 1) {
            throw new InvalidArgumentException("Cantidad invalida: {$qty}");
        }
        $base = self::toCents($price) * $qty;
        if ($base > self::MAX_LINE_BASE_CENTS) {
            throw new InvalidArgumentException('Linea fuera de limite anti-overflow');
        }

        return intdiv($base * (10_000 - self::toBasisPoints($discount)) + 5_000, 10_000);
    }

    /** Centavos de un documento: suma entera de lineas. */
    public static function sumCents(iterable $items): int
    {
        $cents = 0;
        foreach ($items as $item) {
            $cents += self::lineCents(
                self::canon($item['price'] ?? $item->price ?? '0'),
                (int) ($item['quantity'] ?? $item->quantity ?? 1),
                self::canon($item['discount'] ?? $item->discount ?? '0'),
            );
        }

        return $cents;
    }

    /** 46664 -> "466.64". Salida canonica para persistencia/JSON. */
    public static function format(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $abs  = abs($cents);

        return $sign . intdiv($abs, 100) . '.' . str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);
    }

    /** Total de linea como string "X.YY" (borde de salida; nunca float). */
    public static function total(string|int|float $price, int $qty, string|int|float $discount = '0'): string
    {
        return self::format(self::lineCents(self::canon($price), $qty, self::canon($discount)));
    }

    /** Total de documento como string "X.YY". */
    public static function sum(iterable $items): string
    {
        return self::format(self::sumCents($items));
    }

    /**
     * PRESENTACION: "19345.50" -> "19,345.50". Agrupa la parte entera como
     * STRING, sin (float) ni number_format(float): el valor exacto que produjo
     * la aritmetica entera llega intacto a la pantalla, solo con separadores.
     *
     * Contrato estricto: exige el decimal canonico "X.YY" que devuelven
     * format()/total()/sum(). Una entrada que no lo sea se RECHAZA con
     * excepcion — no se coerciona, porque coercionar aqui es justo como se
     * cuela un importe equivocado en un documento del cliente.
     */
    public static function present(string $exacto, string $milesSep = ',', string $decSep = '.'): string
    {
        if (! preg_match('/^(-?)(\d+)\.(\d{2})$/', $exacto, $m)) {
            throw new \InvalidArgumentException(
                "LineMath::present espera el decimal canonico 'X.YY'; recibido: '{$exacto}'"
            );
        }

        [, $signo, $entera, $decimales] = $m;

        // Agrupacion de 3 en 3 desde la derecha, puramente sobre el string.
        $grupos = str_split(strrev($entera), 3);
        $agrupada = strrev(implode(strrev($milesSep), $grupos));

        return $signo . $agrupada . $decSep . $decimales;
    }
}
