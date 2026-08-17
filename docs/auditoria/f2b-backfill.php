<?php

/**
 * F2b-4 — Backfill del libro de cobros.
 *
 * Traduce el estado actual (columnas mutables) a asientos y vencimientos, sin
 * cambiar un solo centimo de lo que el negocio ve hoy.
 *
 *   php f2b-backfill.php            -> SECO: solo informa, no escribe nada
 *   php f2b-backfill.php --aplicar  -> aplica dentro de una transaccion
 *
 * Reglas:
 *  · Cada `advance_amount`/`paid_amount` > 0 genera UN asiento `source=backfill`.
 *  · Cada documento con saldo genera su vencimiento con el plazo del proyecto
 *    (`cxc_plazo_dias`, por defecto 0 = contado).
 *  · INVARIANTE: al terminar, el total de CxC debe ser identico al de antes.
 *    Si difiere aunque sea un centimo, se revierte todo.
 *  · Idempotente: si un documento ya tiene asiento de backfill, se omite.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$aplicar = in_array('--aplicar', $argv, true);
echo $aplicar ? "== MODO APLICAR ==\n" : "== MODO SECO (no escribe nada) ==\n";

/** Total de CxC tal como lo calcula el controlador, en centavos. */
$totalCxc = function (): array {
    $ped = DB::table('orders')
        ->where(fn ($q) => $q->whereNull('payment_status')
            ->orWhereNotIn('payment_status', ['paid', 'pagado', 'refunded', 'rejected']))
        ->where(fn ($q) => $q->whereNull('status')
            ->orWhereNotIn('status', ['cancelled', 'cancelado', 'anulado']))
        ->selectRaw('COUNT(*) n, COALESCE(SUM(GREATEST(ROUND(total*100)-ROUND(COALESCE(advance_amount,0)*100),0)),0) c')
        ->first();
    $cot = DB::table('quotes')
        ->where(fn ($q) => $q->whereNull('payment_status')
            ->orWhereNotIn('payment_status', ['paid', 'pagado', 'refunded', 'rejected']))
        ->whereIn('status', ['accepted', 'aceptada', 'aceptado'])
        ->selectRaw('COUNT(*) n, COALESCE(SUM(GREATEST(ROUND(total*100)-ROUND(COALESCE(paid_amount,0)*100),0)),0) c')
        ->first();

    return [(int) $ped->c + (int) $cot->c, (int) $ped->n + (int) $cot->n];
};

[$antesC, $antesN] = $totalCxc();
printf("Antes: %s en %d documentos\n\n", number_format($antesC / 100, 2), $antesN);

$plazos = [];   // cache de cxc_plazo_dias por proyecto
$plazoDe = function (int $projectId) use (&$plazos): int {
    if (! isset($plazos[$projectId])) {
        $v = DB::table('project_settings')->where('project_id', $projectId)
            ->where('key', 'cxc_plazo_dias')->value('value');
        $plazos[$projectId] = (int) ($v ?? 0);
    }

    return $plazos[$projectId];
};

$asientos = 0; $vencimientos = 0; $omitidos = 0; $detalle = [];

$trabajo = function () use (&$asientos, &$vencimientos, &$omitidos, &$detalle, $plazoDe, $aplicar) {
    foreach ([['order', 'orders', 'advance_amount'], ['quote', 'quotes', 'paid_amount']] as [$tipo, $tabla, $colCobrado]) {
        $filas = DB::table($tabla)->select('id', 'project_id', 'total', $colCobrado, 'created_at', 'status', 'payment_status')->get();

        foreach ($filas as $d) {
            $totalC   = (int) round(((float) $d->total) * 100);
            $cobradoC = (int) round(((float) ($d->{$colCobrado} ?? 0)) * 100);

            // Solo lo que de verdad es cuenta por cobrar, con el MISMO criterio
            // que CxcController: pedidos no anulados, y cotizaciones ACEPTADAS
            // (una en borrador o enviada no la debe nadie todavia, y una
            // convertida ya la representa su pedido).
            $estado = strtolower((string) $d->status);
            if ($tipo === 'order' && in_array($estado, ['cancelled', 'cancelado', 'anulado'], true)) {
                continue;
            }
            if ($tipo === 'quote' && ! in_array($estado, ['accepted', 'aceptada', 'aceptado'], true)) {
                continue;
            }
            // Ya saldado (incluye el 'pagado' legacy de los pedidos 20-22): no
            // se debe, asi que no genera vencimiento.
            if (in_array(strtolower((string) $d->payment_status), ['paid', 'pagado', 'refunded', 'rejected'], true)) {
                continue;
            }

            $yaHay = DB::table('payments')->where('payable_type', $tipo)
                ->where('payable_id', $d->id)->where('source', 'backfill')->exists();

            if ($cobradoC > 0 && ! $yaHay) {
                if ($aplicar) {
                    DB::table('payments')->insert([
                        'project_id' => $d->project_id, 'payable_type' => $tipo, 'payable_id' => $d->id,
                        'amount_cents' => $cobradoC, 'method' => null, 'reference' => null,
                        'received_at' => $d->created_at, 'source' => 'backfill',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                $asientos++;
                $detalle[] = sprintf('  asiento %s #%d  %s', $tipo, $d->id, number_format($cobradoC / 100, 2));
            } elseif ($cobradoC > 0) {
                $omitidos++;
            }

            // Vencimiento solo para lo que aun se debe.
            if ($totalC - $cobradoC > 0) {
                $existe = DB::table('receivable_terms')->where('payable_type', $tipo)
                    ->where('payable_id', $d->id)->where('numero', 1)->exists();
                if (! $existe) {
                    $vence = date('Y-m-d', strtotime($d->created_at . ' +' . $plazoDe((int) $d->project_id) . ' days'));
                    if ($aplicar) {
                        DB::table('receivable_terms')->insert([
                            'project_id' => $d->project_id, 'payable_type' => $tipo, 'payable_id' => $d->id,
                            'numero' => 1, 'due_date' => $vence, 'amount_cents' => $totalC,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                    $vencimientos++;
                    $detalle[] = sprintf('  vencim. %s #%d  vence %s', $tipo, $d->id, $vence);
                }
            }
        }
    }
};

if ($aplicar) {
    DB::transaction(function () use ($trabajo, $totalCxc, $antesC, $antesN) {
        $trabajo();
        [$despuesC, $despuesN] = $totalCxc();
        if ($despuesC !== $antesC || $despuesN !== $antesN) {
            throw new RuntimeException(sprintf(
                'INVARIANTE ROTO: antes %s en %d, despues %s en %d. Se revierte todo.',
                number_format($antesC / 100, 2), $antesN, number_format($despuesC / 100, 2), $despuesN
            ));
        }
    });
} else {
    $trabajo();
}

foreach (array_slice($detalle, 0, 40) as $l) { echo $l . "\n"; }
if (count($detalle) > 40) { echo '  ... y ' . (count($detalle) - 40) . " mas\n"; }

printf("\nAsientos: %d · Vencimientos: %d · Omitidos (ya tenian): %d\n", $asientos, $vencimientos, $omitidos);
[$finC, $finN] = $totalCxc();
printf("Despues: %s en %d documentos %s\n", number_format($finC / 100, 2), $finN,
    ($finC === $antesC && $finN === $antesN) ? '-> INVARIANTE OK' : '-> !!! INVARIANTE ROTO');
