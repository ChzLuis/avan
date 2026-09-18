<?php

namespace App\Modules\Finanzas\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\Finanzas\Models\ReceivableTerm;
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

        // El calculo vive en App\Support\Cobranza: el panel Comercial pregunta
        // lo mismo y dos copias del mismo calculo terminan dando dos cifras.
        $cartera = \App\Support\Cobranza::cartera($project);
        $filas   = $cartera['filas'];
        $resumen = $cartera['resumen'];

        $portalLayout = request()->routeIs('bixosales.*') ? 'comercial' : 'panel';

        $condiciones = [
            'plazo' => (int) $project->setting('cxc_plazo_dias', 0),
            'aviso' => (int) $project->setting('cxc_dias_aviso', 3),
            'puede' => auth()->user()?->can('settings.pagos') || auth()->user()?->can('manage-settings'),
        ];

        return view('finanzas::cxc.index', compact('project', 'filas', 'resumen', 'portalLayout', 'condiciones'));
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
}
