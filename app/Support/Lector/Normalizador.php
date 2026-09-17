<?php

namespace App\Support\Lector;

use Illuminate\Support\Carbon;

/**
 * Pone la lectura en la forma que entiende el sistema.
 *
 * El modelo devuelve lo que ve; aqui se traduce a los valores que usa el
 * comprobante: tipos de documento tal como los valida `InvoiceController`,
 * unidades del catalogo de SUNAT y fechas ISO. Sin esta capa, cada cambio
 * de proveedor obligaria a retocar el formulario.
 */
final class Normalizador
{
    /** Lo que la gente escribe en el papel -> codigo de unidad de SUNAT. */
    private const UNIDADES = [
        'unidad' => 'NIU', 'und' => 'NIU', 'un' => 'NIU', 'u' => 'NIU', 'pza' => 'NIU',
        'pieza' => 'NIU', 'niu' => 'NIU', 'rollo' => 'NIU', 'rollos' => 'NIU',
        'caja' => 'BX', 'cajas' => 'BX', 'bx' => 'BX',
        'kg' => 'KGM', 'kilo' => 'KGM', 'kilos' => 'KGM', 'kgm' => 'KGM',
        'm' => 'MTR', 'metro' => 'MTR', 'metros' => 'MTR', 'mtr' => 'MTR',
        'l' => 'LTR', 'litro' => 'LTR', 'litros' => 'LTR', 'ltr' => 'LTR',
        'gal' => 'GLL', 'galon' => 'GLL',
        'servicio' => 'ZZ', 'zz' => 'ZZ',
        'bolsa' => 'BG', 'paquete' => 'PK', 'pack' => 'PK',
    ];

    public static function aplicar(array $d): array
    {
        $items = [];
        foreach ((array) ($d['items'] ?? []) as $i) {
            $desc = trim((string) ($i['descripcion'] ?? ''));
            if ($desc === '') {
                continue; // una fila sin descripcion no es una linea, es ruido.
            }

            $items[] = [
                'codigo'          => self::texto($i['codigo'] ?? null, 60),
                'descripcion'     => mb_substr($desc, 0, 300),
                'cantidad'        => self::numero($i['cantidad'] ?? null) ?: 1,
                'unidad'          => self::unidad($i['unidad'] ?? null),
                'precio_unitario' => self::numero($i['precio_unitario'] ?? null) ?? 0,
                'descuento'       => min(100, max(0, self::numero($i['descuento'] ?? null) ?? 0)),
                'importe'         => self::numero($i['importe'] ?? null),
            ];
        }

        $cliente = (array) ($d['cliente'] ?? []);
        $docNum  = preg_replace('/\D/', '', (string) ($cliente['numero_doc'] ?? '')) ?: null;

        return [
            'legible'           => (bool) ($d['legible'] ?? true),
            'tipo'              => self::tipo($d['tipo'] ?? null, $docNum),
            'serie'             => self::serie($d['serie'] ?? null),
            'numero'            => self::texto($d['numero'] ?? null, 12),
            'fecha_emision'     => self::fecha($d['fecha_emision'] ?? null),
            'fecha_vencimiento' => self::fecha($d['fecha_vencimiento'] ?? null),
            'moneda'            => self::moneda($d['moneda'] ?? null),
            'forma_pago'        => self::texto($d['forma_pago'] ?? null, 80),
            'observaciones'     => self::texto($d['observaciones'] ?? null, 1000),
            'igv_incluido'      => $d['precios_incluyen_igv'] ?? null,
            'emisor'  => [
                'ruc'          => preg_replace('/\D/', '', (string) (($d['emisor']['ruc'] ?? ''))) ?: null,
                'razon_social' => self::texto($d['emisor']['razon_social'] ?? null, 200),
                'direccion'    => self::texto($d['emisor']['direccion'] ?? null, 300),
            ],
            'cliente' => [
                'tipo_doc'     => self::tipoDoc($cliente['tipo_doc'] ?? null, $docNum),
                'numero_doc'   => $docNum ? mb_substr($docNum, 0, 15) : null,
                'razon_social' => self::texto($cliente['razon_social'] ?? null, 200),
                'direccion'    => self::texto($cliente['direccion'] ?? null, 300),
            ],
            'items'     => $items,
            'totales'   => self::totales((array) ($d['totales'] ?? [])),
            'confianza' => self::confianza((array) ($d['confianza'] ?? [])),
        ];
    }

    /**
     * Factura o boleta. Si el modelo no lo dijo, manda el documento del
     * cliente: un RUC solo puede ir en factura.
     */
    private static function tipo(?string $tipo, ?string $docCliente): ?string
    {
        $t = mb_strtolower(trim((string) $tipo));
        if (str_contains($t, 'factura')) {
            return 'factura';
        }
        if (str_contains($t, 'boleta')) {
            return 'boleta';
        }

        return $docCliente && strlen($docCliente) === 11 ? 'factura' : null;
    }

    private static function tipoDoc(?string $tipo, ?string $numero): ?string
    {
        $t = mb_strtoupper(trim((string) $tipo));
        if (in_array($t, ['RUC', 'DNI', 'CE'], true)) {
            return $t;
        }
        if (str_contains($t, 'PASAPORTE')) {
            return 'pasaporte';
        }

        // Sin etiqueta legible, la longitud decide: 11 es RUC, 8 es DNI.
        return match (strlen((string) $numero)) {
            11 => 'RUC',
            8  => 'DNI',
            default => null,
        };
    }

    /** Serie: 4 caracteres alfanumericos (F001, B001, E001). */
    private static function serie(?string $serie): ?string
    {
        $s = mb_strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $serie) ?? '');

        return preg_match('/^[A-Z0-9]{4}$/', $s) ? $s : null;
    }

    private static function moneda(?string $m): string
    {
        $t = mb_strtoupper(trim((string) $m));
        if (str_contains($t, 'USD') || str_contains($t, 'DOLAR') || str_contains($t, 'DÓLAR')) {
            return 'USD';
        }

        return 'PEN';
    }

    private static function unidad(?string $u): string
    {
        $clave = mb_strtolower(trim((string) $u));
        $clave = strtr($clave, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', '.' => '']);

        return self::UNIDADES[$clave] ?? 'NIU';
    }

    /** Fecha ISO. Acepta d/m/Y y d-m-Y, que es como se escribe en Perú. */
    private static function fecha(?string $f): ?string
    {
        $v = trim((string) $f);
        if ($v === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd/m/y', 'Y/m/d'] as $formato) {
            try {
                $fecha = Carbon::createFromFormat($formato, $v);
                // Una fecha absurda (1970, o dentro de 5 años) es un error de lectura.
                if ($fecha && $fecha->year >= 2000 && $fecha->year <= (int) date('Y') + 1) {
                    return $fecha->format('Y-m-d');
                }
            } catch (\Throwable) {
                // Se prueba el siguiente formato.
            }
        }

        return null;
    }

    private static function totales(array $t): array
    {
        $salida = [];
        foreach (['gravadas', 'exoneradas', 'inafectas', 'gratuitas', 'descuento_global',
                  'igv', 'isc', 'otros_cargos', 'subtotal', 'total'] as $k) {
            $salida[$k] = self::numero($t[$k] ?? null);
        }
        $salida['total_en_letras'] = self::texto($t['total_en_letras'] ?? null, 300);

        return $salida;
    }

    private static function confianza(array $c): array
    {
        $salida = [];
        foreach ($c as $campo => $valor) {
            if (is_numeric($valor)) {
                $salida[(string) $campo] = max(0, min(1, (float) $valor));
            }
        }

        return $salida;
    }

    /** Numero tolerante: acepta "S/ 1,234.50" y "1.234,50". */
    private static function numero($v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return (float) $v;
        }

        $s = preg_replace('/[^0-9,.\-]/', '', (string) $v) ?? '';
        if ($s === '') {
            return null;
        }

        // Si hay coma y punto, el ultimo separador es el decimal.
        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = strrpos($s, ',') > strrpos($s, '.')
                ? str_replace(['.', ','], ['', '.'], $s)
                : str_replace(',', '', $s);
        } elseif (str_contains($s, ',')) {
            // Coma sola: decimal si deja 1-2 cifras detras, si no es de miles.
            $s = preg_match('/,\d{1,2}$/', $s) ? str_replace(',', '.', $s) : str_replace(',', '', $s);
        }

        return is_numeric($s) ? (float) $s : null;
    }

    private static function texto($v, int $max): ?string
    {
        $t = trim((string) ($v ?? ''));

        return $t === '' ? null : mb_substr($t, 0, $max);
    }
}
