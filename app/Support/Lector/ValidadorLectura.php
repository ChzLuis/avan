<?php

namespace App\Support\Lector;

use App\Models\Invoice;
use App\Models\Project;

/**
 * Revisa la lectura ANTES de que llegue al formulario.
 *
 * No bloquea: avisa. Una foto puede leerse casi entera y fallar en un digito,
 * y negarse a seguir por eso obligaria a teclear todo de nuevo. Lo que hace
 * es senalar que mirar, para que el usuario corrija dos campos en vez de
 * revisar veinte.
 */
class ValidadorLectura
{
    /** Un céntimo por línea de margen: los redondeos del papel no cuadran al milímetro. */
    private const TOLERANCIA = 0.05;

    /** Por debajo de esto, el campo se marca para revisar. */
    private const CONFIANZA_BAJA = 0.75;

    public function __construct(private Project $project) {}

    /**
     * @return array{avisos:array<int,string>, revisar:array<int,string>, duplicado:?array}
     */
    public function revisar(array $datos): array
    {
        $avisos  = [];
        $revisar = [];

        // ── Documento del cliente ────────────────────────────────────────
        $doc  = (string) ($datos['cliente']['numero_doc'] ?? '');
        $tipo = (string) ($datos['cliente']['tipo_doc'] ?? '');

        if ($doc !== '') {
            if ($tipo === 'RUC' && strlen($doc) !== 11) {
                $avisos[]  = 'El RUC leído no tiene 11 dígitos. Revísalo antes de emitir.';
                $revisar[] = 'cliente.numero_doc';
            }
            if ($tipo === 'DNI' && strlen($doc) !== 8) {
                $avisos[]  = 'El DNI leído no tiene 8 dígitos. Revísalo antes de emitir.';
                $revisar[] = 'cliente.numero_doc';
            }
        }

        // Una factura sin RUC no la acepta SUNAT: mejor decirlo ahora.
        if (($datos['tipo'] ?? null) === 'factura' && $tipo !== 'RUC') {
            $avisos[]  = 'Es una factura pero no leímos un RUC del cliente. Complétalo antes de emitir.';
            $revisar[] = 'cliente.numero_doc';
        }

        // ── Líneas ───────────────────────────────────────────────────────
        if (empty($datos['items'])) {
            $avisos[] = 'No pudimos leer ningún producto del comprobante. Añádelos a mano.';
        }

        $suma = 0.0;
        foreach ($datos['items'] as $i => $item) {
            $cant   = (float) ($item['cantidad'] ?? 0);
            $precio = (float) ($item['precio_unitario'] ?? 0);

            if ($cant <= 0) {
                $avisos[]  = 'La línea '.($i + 1).' no tiene una cantidad válida.';
                $revisar[] = "items.$i.cantidad";
            }
            if ($precio <= 0) {
                $avisos[]  = 'La línea '.($i + 1).' no tiene precio. Complétalo.';
                $revisar[] = "items.$i.precio_unitario";
            }

            $linea = $cant * $precio * (1 - ((float) ($item['descuento'] ?? 0) / 100));
            $suma += $linea;

            // El importe impreso contra el calculado: si no cuadran, alguno
            // de los dos números se leyó mal.
            $importe = $item['importe'] ?? null;
            if ($importe !== null && abs((float) $importe - $linea) > max(self::TOLERANCIA, $linea * 0.02)) {
                $avisos[]  = 'En la línea '.($i + 1).' el importe no cuadra con cantidad × precio.';
                $revisar[] = "items.$i.precio_unitario";
            }
        }

        // ── Total ────────────────────────────────────────────────────────
        $total = $datos['totales']['total'] ?? null;
        if ($total !== null && $suma > 0) {
            // Con precios sin IGV, la suma de líneas es la base y el total lleva IGV.
            $comparable = ($datos['igv_incluido'] ?? true) === false ? $suma * 1.18 : $suma;

            if (abs((float) $total - $comparable) > max(0.10, $comparable * 0.02)) {
                $avisos[] = sprintf(
                    'Hay una diferencia entre el total leído (%s) y la suma de las líneas (%s). Revísalo.',
                    number_format((float) $total, 2),
                    number_format($comparable, 2)
                );
                $revisar[] = 'totales.total';
            }
        }

        // ── Campos con poca confianza ────────────────────────────────────
        foreach ((array) ($datos['confianza'] ?? []) as $campo => $valor) {
            if ($valor < self::CONFIANZA_BAJA) {
                $revisar[] = (string) $campo;
            }
        }

        return [
            'avisos'    => array_values(array_unique($avisos)),
            'revisar'   => array_values(array_unique($revisar)),
            'duplicado' => $this->duplicado($datos),
        ];
    }

    /**
     * ¿Se importó ya este mismo comprobante? Se compara por tipo, serie y
     * número, que es lo que identifica a un comprobante en SUNAT.
     */
    private function duplicado(array $datos): ?array
    {
        $serie  = $datos['serie'] ?? null;
        $numero = $datos['numero'] ?? null;
        if (! $serie || ! $numero) {
            return null;
        }

        $previo = Invoice::where('project_id', $this->project->id)
            ->where('serie', $serie)
            ->where('correlativo', (int) $numero)
            ->first(['id', 'numero', 'total', 'issue_date']);

        return $previo ? [
            'id'         => $previo->id,
            'numero'     => $previo->numero,
            'total'      => (float) $previo->total,
            'fecha'      => $previo->issue_date?->format('d/m/Y'),
            'mensaje'    => "Este comprobante parece haber sido importado antes: {$previo->numero}.",
        ] : null;
    }
}
