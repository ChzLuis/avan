<?php
namespace App\Modules\Finanzas\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\Finanzas\Models\Caja;
use App\Modules\Finanzas\Models\CajaMovimiento;
use App\Models\Project;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    private function project(): Project
    {
        $isSales = request()->routeIs('bixosales.*');
        return $isSales
            ? Project::findOrFail(session('comercial_project_id'))
            : app('active_project');
    }

    public function index()
    {
        $project = $this->project();
        $cajaAbierta = Caja::where('project_id', $project->id)
            ->where('status', 'open')
            ->with('movimientos')
            ->latest('opened_at')
            ->first();

        $historial = Caja::where('project_id', $project->id)
            ->where('status', 'closed')
            ->orderByDesc('closed_at')
            ->limit(15)
            ->get();

        $cajaJson = $cajaAbierta ? $this->formatCaja($cajaAbierta) : null;

        $historialJson = $historial->map(function ($c) {
            return [
                'id'             => $c->id,
                'user_name'      => $c->user_name,
                'monto_apertura' => (float) $c->monto_apertura,
                'monto_cierre'   => (float) $c->monto_cierre,
                'monto_esperado' => (float) $c->monto_esperado,
                'diferencia'     => (float) $c->diferencia,
                'opened_at'      => $c->opened_at->format('d/m/Y H:i'),
                'closed_at'      => $c->closed_at?->format('d/m/Y H:i'),
                'duracion'       => $c->opened_at->diffForHumans($c->closed_at, true),
            ];
        })->values()->all();

        $paymentMethods = $this->catValues($project, 'payment_method');

        return view('comercial.caja', compact('project', 'cajaJson', 'historialJson', 'paymentMethods'));
    }

    public function abrir(Request $request)
    {
        $project = $this->project();

        // Solo una caja abierta por proyecto
        $yaAbierta = Caja::where('project_id', $project->id)->where('status', 'open')->exists();
        if ($yaAbierta) {
            return response()->json(['ok' => false, 'message' => 'Ya hay una caja abierta.'], 422);
        }

        $data = $request->validate([
            'monto_apertura' => 'required|numeric|min:0',
            'notas_apertura' => 'nullable|string|max:300',
        ]);

        $caja = Caja::create([
            'project_id'      => $project->id,
            'user_id'         => auth()->id(),
            'user_name'       => auth()->user()->name,
            'monto_apertura'  => $data['monto_apertura'],
            'notas_apertura'  => $data['notas_apertura'] ?? null,
            'opened_at'       => now(),
            'status'          => 'open',
        ]);

        return response()->json(['ok' => true, 'caja' => $this->formatCaja($caja->load('movimientos'))]);
    }

    public function cerrar(Request $request, Caja $caja)
    {
        $project = $this->project();
        abort_unless($caja->project_id === $project->id && $caja->status === 'open', 403);

        $data = $request->validate([
            'monto_cierre'  => 'required|numeric|min:0',
            'notas_cierre'  => 'nullable|string|max:300',
        ]);

        $esperado   = $caja->saldoEsperado();
        $diferencia = (float) $data['monto_cierre'] - $esperado;

        $caja->update([
            'monto_cierre'   => $data['monto_cierre'],
            'monto_esperado' => $esperado,
            'diferencia'     => $diferencia,
            'notas_cierre'   => $data['notas_cierre'] ?? null,
            'closed_at'      => now(),
            'status'         => 'closed',
        ]);

        return response()->json([
            'ok'         => true,
            'esperado'   => $esperado,
            'diferencia' => $diferencia,
        ]);
    }

    public function movimiento(Request $request, Caja $caja)
    {
        $project = $this->project();
        abort_unless($caja->project_id === $project->id && $caja->status === 'open', 403);

        $data = $request->validate([
            'tipo'        => 'required|in:ingreso,egreso',
            'concepto'    => 'required|string|max:150',
            'monto'       => 'required|numeric|min:0.01',
            'metodo_pago' => 'nullable|string|max:60',
        ]);

        $mov = CajaMovimiento::create([
            'caja_id'     => $caja->id,
            'project_id'  => $project->id,
            'tipo'        => $data['tipo'],
            'concepto'    => $data['concepto'],
            'monto'       => $data['monto'],
            'metodo_pago' => $data['metodo_pago'] ?? null,
            'user_id'     => auth()->id(),
        ]);

        return response()->json(['ok' => true, 'movimiento' => $mov]);
    }

    public function data(Caja $caja)
    {
        $project = $this->project();
        abort_unless($caja->project_id === $project->id, 403);
        return response()->json(['caja' => $this->formatCaja($caja->load('movimientos'))]);
    }

    private function formatCaja(Caja $caja): array
    {
        $movs = $caja->movimientos->map(function ($m) {
            return [
                'id'          => $m->id,
                'tipo'        => $m->tipo,
                'concepto'    => $m->concepto,
                'monto'       => (float) $m->monto,
                'metodo_pago' => $m->metodo_pago,
                'created_at'  => $m->created_at->format('H:i'),
            ];
        })->values()->all();

        return [
            'id'             => $caja->id,
            'user_name'      => $caja->user_name,
            'monto_apertura' => (float) $caja->monto_apertura,
            'opened_at'      => $caja->opened_at->format('d/m/Y H:i'),
            'total_ventas'   => $caja->totalVentas(),
            'total_ingresos' => $caja->totalIngresos(),
            'total_egresos'  => $caja->totalEgresos(),
            'saldo_esperado' => $caja->saldoEsperado(),
            'movimientos'    => $movs,
        ];
    }

    private function catValues(Project $project, string $type): \Illuminate\Support\Collection
    {
        $list = $project->catalogLists()->where('type', $type)->first();
        return $list ? $list->values()->where('is_active', true)->orderBy('sort_order')->pluck('label') : collect();
    }
}
