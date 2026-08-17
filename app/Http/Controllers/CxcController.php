<?php

namespace App\Http\Controllers;

use App\Models\ReceivableTerm;
use App\Support\LineMath;
use App\Support\QuoteStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Cuentas por Cobrar — F2 v1 (LECTURA).
 *
 * Agrega lo que el negocio ya registra, sin columnas nuevas ni mutaciones:
 *   · pedidos con pago pendiente/parcial (saldo = total - advance_amount)
 *   · cotizaciones ACEPTADAS aún no convertidas con pago pendiente/parcial
 *     (una convertida NO entra: su pedido ya la representa — sin doble conteo)
 *
 * Todo el dinero se agrega en CENTAVOS (LineMath) y sale como string canónico:
 * sumar flotantes decenas de importes es exactamente como un "por cobrar"
 * termina distinto según quién lo mire. La entidad contable (asientos,
 * conciliación) es F2b/F3 y exigirá su propio diseño auditado.
 */
class CxcController extends Controller
{
    /** Estados de pago que significan "ya no se debe" (canonico + legado). */
    private const SALDADOS_PAGO = ['paid', 'pagado', 'refunded', 'rejected'];

    /** Estados comerciales que sacan el documento de la deuda. */
    private const ANULADOS = ['cancelled', 'cancelado', 'anulado'];

    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $hoy = Carbon::today();

        $filas = [];

        // ── Pedidos con saldo ────────────────────────────────────────────
        // NOT IN descarta las filas NULL en silencio (logica de tres valores):
        // un pedido sin payment_status es deuda y desaparecia del listado. En
        // ARIN eran 3 pedidos = S/ 8 298,00 invisibles. Se excluye tambien el
        // 'cancelado' legacy, que el filtro anterior no cubria.
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
            $filas[] = $this->fila('pedido', $o->id, $o->client_name, $o->client_phone,
                $o->created_at, $totalC, $cobradoC, $saldoC, $o->payment_status, $hoy);
        }

        // ── Cotizaciones aceptadas SIN convertir ─────────────────────────
        $cotizaciones = $project->quotes()
            ->where(fn ($q) => $q->whereNull('payment_status')
                ->orWhereNotIn('payment_status', self::SALDADOS_PAGO))
            ->orderBy('created_at')
            ->get(['id', 'client_name', 'client_phone', 'total', 'payment_status', 'paid_amount', 'status', 'created_at']);

        foreach ($cotizaciones as $q) {
            if (QuoteStatus::comercial($q->status) !== 'accepted') {
                continue;   // converted cuenta por su pedido; draft/sent no son deuda
            }
            $totalC   = LineMath::toCents(LineMath::canon((string) $q->total));
            $cobradoC = $q->paid_amount !== null
                ? LineMath::toCents(LineMath::canon((string) $q->paid_amount))
                : 0;
            $saldoC = max(0, $totalC - $cobradoC);
            if ($saldoC === 0) {
                continue;
            }
            $filas[] = $this->fila('cotizacion', $q->id, $q->client_name, $q->client_phone,
                $q->created_at, $totalC, $cobradoC, $saldoC, $q->payment_status, $hoy);
        }

        // ── F2b: el vencimiento PACTADO manda ────────────────────────────
        // Antes, "vencido" era "lleva mas de 15 dias creado", porque no habia
        // ninguna fecha de vencimiento en la base: una venta a 30 dias de
        // credito salia morosa el dia 16. Ahora se lee de `receivable_terms`,
        // que guarda el plazo configurado por cada negocio.
        $vencimientos = ReceivableTerm::where('project_id', $project->id)
            ->where('numero', 1)
            ->get(['payable_type', 'payable_id', 'due_date'])
            ->keyBy(fn ($t) => $t->payable_type . ':' . $t->payable_id);

        foreach ($filas as &$f) {
            $termino = $vencimientos->get($f['tipo_clave'] . ':' . $f['id']);
            // Sin vencimiento registrado se cae a la antiguedad, que es lo
            // unico que se sabe. Se marca para no presentarlo como pactado.
            $f['vence']     = $termino?->due_date?->format('d/m/Y');
            $f['atraso']    = $termino ? (int) max(0, $termino->due_date->lt($hoy) ? $termino->due_date->diffInDays($hoy) : 0) : $f['dias'];
            $f['vencido']   = $termino ? $termino->due_date->lt($hoy) : ($f['dias'] > 15);
            $f['estimado']  = $termino === null;
        }
        unset($f);

        usort($filas, fn ($a, $b) => $b['atraso'] <=> $a['atraso']);

        // ── Agregados y antigüedad, todo en centavos ─────────────────────
        $totalCents   = array_sum(array_column($filas, 'saldo_cents'));
        $vencidoCents = array_sum(array_map(fn ($f) => $f['vencido'] ? $f['saldo_cents'] : 0, $filas));
        $buckets = ['0-7' => 0, '8-15' => 0, '16-30' => 0, '31+' => 0];
        foreach ($filas as $f) {
            $d = $f['atraso'];
            $b = $d <= 7 ? '0-7' : ($d <= 15 ? '8-15' : ($d <= 30 ? '16-30' : '31+'));
            $buckets[$b] += $f['saldo_cents'];
        }

        $resumen = [
            'total'      => LineMath::present(LineMath::format($totalCents)),
            'vencido'    => LineMath::present(LineMath::format($vencidoCents)),
            // Cuando todo el saldo esta vencido, los dos KPI muestran la misma
            // cifra y parece un fallo. La proporcion lo convierte en dato: no
            // es un numero repetido, es que se debe todo desde hace tiempo.
            'vencido_pct' => $totalCents > 0 ? (int) round($vencidoCents * 100 / $totalCents) : 0,
            'documentos' => count($filas),
            'buckets'    => array_map(fn ($c) => LineMath::present(LineMath::format($c)), $buckets),
        ];

        $portalLayout = request()->routeIs('bixosales.*') ? 'comercial' : 'panel';

        $condiciones = [
            'plazo' => (int) $project->setting('cxc_plazo_dias', 0),
            'aviso' => (int) $project->setting('cxc_dias_aviso', 3),
            'puede' => auth()->user()?->can('settings.pagos') || auth()->user()?->can('manage-settings'),
        ];

        return view('cxc.index', compact('project', 'filas', 'resumen', 'portalLayout', 'condiciones'));
    }

    /**
     * Condiciones de cobro del negocio. Hasta ahora `cxc_plazo_dias` solo se
     * podia tocar por base de datos, que es tanto como no poder tocarlo: si el
     * cliente puede querer cambiarlo, tiene que estar en una pantalla.
     *
     * Cambiar el plazo NO reescribe vencimientos ya pactados —un vencimiento
     * es un hecho, no una preferencia—, salvo que se pida explicitamente
     * recalcular los pendientes, que es un acto deliberado y no un efecto
     * secundario.
     */
    public function guardarCondiciones(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $datos = $request->validate([
            'cxc_plazo_dias'  => 'required|integer|min:0|max:365',
            'cxc_dias_aviso'  => 'required|integer|min:0|max:90',
            'recalcular'      => 'nullable|boolean',
        ]);

        foreach (['cxc_plazo_dias', 'cxc_dias_aviso'] as $clave) {
            $project->settings()->updateOrCreate(['key' => $clave], ['value' => (string) $datos[$clave]]);
        }

        $recalculados = 0;
        if ($request->boolean('recalcular')) {
            $recalculados = $this->recalcularPendientes($project, (int) $datos['cxc_plazo_dias']);
        }

        $aviso = "Condiciones guardadas: " . ((int) $datos['cxc_plazo_dias'] === 0
            ? 'cobro al contado'
            : 'crédito a ' . (int) $datos['cxc_plazo_dias'] . ' días');

        return back()->with('status', $aviso . ($recalculados
            ? ", y {$recalculados} documento(s) pendiente(s) revisaron su vencimiento."
            : '.'));
    }

    /** Reasigna el vencimiento de lo que aun se debe. Nunca toca lo saldado. */
    private function recalcularPendientes(\App\Models\Project $project, int $plazo): int
    {
        $tocados = 0;

        foreach ([['order', $project->orders()], ['quote', $project->quotes()]] as [$tipo, $consulta]) {
            $docs = (clone $consulta)
                ->where(fn ($q) => $q->whereNull('payment_status')
                    ->orWhereNotIn('payment_status', self::SALDADOS_PAGO))
                ->get(['id', 'created_at', 'status']);

            foreach ($docs as $d) {
                if ($tipo === 'order' && in_array(strtolower((string) $d->status), self::ANULADOS, true)) {
                    continue;
                }
                if ($tipo === 'quote' && QuoteStatus::comercial($d->status) !== 'accepted') {
                    continue;
                }

                $tocados += ReceivableTerm::where('payable_type', $tipo)
                    ->where('payable_id', $d->id)
                    ->where('numero', 1)
                    ->update(['due_date' => Carbon::parse($d->created_at)->startOfDay()->addDays($plazo)]);
            }
        }

        return $tocados;
    }

    private function fila(string $tipo, int $id, ?string $cliente, ?string $telefono,
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
