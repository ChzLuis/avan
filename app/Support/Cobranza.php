<?php

namespace App\Support;

use App\Models\Project;
use App\Modules\Finanzas\Models\ReceivableTerm;
use Illuminate\Support\Carbon;

/**
 * La cartera por cobrar del negocio, en un solo sitio.
 *
 * Vivia dentro de CxcController::index. En cuanto el panel Comercial quiso
 * enseñar "por cobrar" y "facturas vencidas" habia dos opciones: copiar el
 * calculo o compartirlo. Copiarlo es como se llega a que dos pantallas del
 * mismo producto den cifras distintas para la misma pregunta — y en este
 * proyecto ya paso con la deuda que ocultaba el NOT IN sobre nulos.
 *
 * Reglas que se conservan tal cual estaban:
 *   · NOT IN descarta los NULL en silencio, asi que los nulos se piden aparte.
 *   · Una cotizacion convertida NO entra: su pedido ya la representa.
 *   · Vencido lo manda `receivable_terms`, no la antiguedad; sin termino
 *     registrado se cae a "mas de 15 dias" y la fila se marca como estimada.
 *   · Todo el dinero se agrega en CENTAVOS enteros (LineMath).
 */
final class Cobranza
{
    /** Estados de pago que significan "ya no se debe" (canonico + legado). */
    public const SALDADOS_PAGO = ['paid', 'pagado', 'refunded', 'rejected'];

    /** Estados comerciales que sacan el documento de la deuda. */
    public const ANULADOS = ['cancelled', 'cancelado', 'anulado'];

    /**
     * @return array{filas: array, resumen: array, total_cents: int, vencido_cents: int, vencidas: int}
     */
    public static function cartera(Project $project, ?Carbon $hoy = null): array
    {
        $hoy   = $hoy ?: Carbon::today();
        $filas = [];

        // ── Pedidos con saldo ────────────────────────────────────────────
        $pedidos = $project->orders()
            ->where(fn ($q) => $q->whereNull('payment_status')
                ->orWhereNotIn('payment_status', self::SALDADOS_PAGO))
            ->where(fn ($q) => $q->whereNull('status')
                ->orWhereNotIn('status', self::ANULADOS))
            ->orderBy('created_at')
            ->get(['id', 'client_name', 'client_phone', 'total', 'payment_status', 'advance_amount', 'created_at', 'quote_id']);

        foreach ($pedidos as $o) {
            $totalC   = LineMath::toCents(LineMath::canon((string) $o->total));
            $cobradoC = $o->advance_amount !== null
                ? LineMath::toCents(LineMath::canon((string) $o->advance_amount))
                : 0;
            $saldoC = max(0, $totalC - $cobradoC);
            if ($saldoC === 0) {
                continue;   // adelanto que cubre todo: nada que cobrar
            }
            $filas[] = self::fila('pedido', $o->id, $o->client_name, $o->client_phone,
                $o->created_at, $totalC, $cobradoC, $saldoC, $o->payment_status, $hoy);
        }

        // Las cotizaciones NO entran en la cartera.
        //
        // Una cotizacion es un documento PRE-VENTA: no reconoce ingreso, no
        // mueve inventario y no genera obligacion de pago. La cuenta por
        // cobrar nace del documento que reconoce el ingreso —el pedido y su
        // comprobante—, que es como lo resuelven Odoo, SAP B1, Dynamics o
        // NetSuite: el adelanto se documenta contra el PEDIDO, nunca contra
        // el presupuesto.
        //
        // Ademas, un adelanto recibido antes de entregar no es un activo:
        // es un anticipo de cliente, un PASIVO. Sumarlo a "por cobrar"
        // invertia el signo de la cifra.
        //
        // Lo que si queda por hacer con una cotizacion aceptada es
        // convertirla en pedido, y eso se enseña como ACCION pendiente, no
        // como dinero (ver `aceptadasSinConvertir`).

        // ── El vencimiento PACTADO manda ─────────────────────────────────
        $vencimientos = ReceivableTerm::where('project_id', $project->id)
            ->where('numero', 1)
            ->get(['payable_type', 'payable_id', 'due_date'])
            ->keyBy(fn ($t) => $t->payable_type . ':' . $t->payable_id);

        foreach ($filas as &$f) {
            $termino = $vencimientos->get($f['tipo_clave'] . ':' . $f['id']);
            $f['vence']    = $termino?->due_date?->format('d/m/Y');
            $f['atraso']   = $termino ? (int) max(0, $termino->due_date->lt($hoy) ? $termino->due_date->diffInDays($hoy) : 0) : $f['dias'];
            $f['vencido']  = $termino ? $termino->due_date->lt($hoy) : ($f['dias'] > 15);
            $f['estimado'] = $termino === null;
        }
        unset($f);

        usort($filas, fn ($a, $b) => $b['atraso'] <=> $a['atraso']);

        $totalCents   = array_sum(array_column($filas, 'saldo_cents'));
        $vencidoCents = array_sum(array_map(fn ($f) => $f['vencido'] ? $f['saldo_cents'] : 0, $filas));
        $vencidas     = count(array_filter($filas, fn ($f) => $f['vencido']));

        $buckets = ['0-7' => 0, '8-15' => 0, '16-30' => 0, '31+' => 0];
        foreach ($filas as $f) {
            $d = $f['atraso'];
            $b = $d <= 7 ? '0-7' : ($d <= 15 ? '8-15' : ($d <= 30 ? '16-30' : '31+'));
            $buckets[$b] += $f['saldo_cents'];
        }

        return [
            'filas'         => $filas,
            'total_cents'   => $totalCents,
            'vencido_cents' => $vencidoCents,
            'vencidas'      => $vencidas,
            'resumen'       => [
                'total'   => LineMath::present(LineMath::format($totalCents)),
                'vencido' => LineMath::present(LineMath::format($vencidoCents)),
                // Cuando todo el saldo esta vencido, los dos KPI muestran la
                // misma cifra y parece un fallo. La proporcion lo convierte en
                // dato: no es un numero repetido, es que se debe todo desde
                // hace tiempo.
                'vencido_pct' => $totalCents > 0 ? (int) round($vencidoCents * 100 / $totalCents) : 0,
                'documentos'  => count($filas),
                'buckets'     => array_map(fn ($c) => LineMath::present(LineMath::format($c)), $buckets),
            ],
        ];
    }

    /**
     * Cotizaciones aceptadas que aun no son pedido.
     *
     * No es dinero por cobrar: es trabajo pendiente. El cliente dijo que si y
     * falta convertirla en pedido para que exista la venta —y con ella, la
     * deuda—. Se devuelve el importe solo para dar magnitud a la accion.
     *
     * @return array{n: int, cents: int}
     */
    public static function aceptadasSinConvertir(Project $project): array
    {
        $n = 0;
        $cents = 0;
        $filas = $project->quotes()
            ->whereDoesntHave('order')
            ->get(['id', 'status', 'total']);

        foreach ($filas as $q) {
            if (QuoteStatus::comercial($q->status) !== 'accepted') {
                continue;
            }
            $n++;
            $cents += LineMath::toCents(LineMath::canon((string) $q->total));
        }

        return ['n' => $n, 'cents' => $cents];
    }

    private static function fila(string $tipo, int $id, ?string $cliente, ?string $telefono,
                                 $creado, int $totalC, int $cobradoC, int $saldoC, ?string $pago, Carbon $hoy): array
    {
        $dias = (int) Carbon::parse($creado)->startOfDay()->diffInDays($hoy);

        return [
            'tipo'        => $tipo,
            // 'pedido'/'cotizacion' es lo que se pinta; el libro usa
            // 'order'/'quote'. Se lleva la clave aparte para no traducir dos
            // veces ni acoplar la presentacion al vocabulario de la tabla.
            'tipo_clave'  => $tipo === 'pedido' ? 'order' : 'quote',
            'id'          => $id,
            'cliente'     => $cliente ?: '—',
            'telefono'    => $telefono ?: '',
            'fecha'       => Carbon::parse($creado)->format('d/m/Y'),
            'dias'        => $dias,
            'total'       => LineMath::present(LineMath::format($totalC)),
            'cobrado'     => $cobradoC > 0 ? LineMath::present(LineMath::format($cobradoC)) : '',
            'saldo'       => LineMath::present(LineMath::format($saldoC)),
            'saldo_cents' => $saldoC,
            'pago'        => QuoteStatus::pagoPresentacion($pago),
        ];
    }
}
