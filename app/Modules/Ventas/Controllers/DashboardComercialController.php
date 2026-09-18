<?php

namespace App\Modules\Ventas\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\Crm\Models\Client;
use App\Modules\Ventas\Models\Order;
use App\Modules\Catalogo\Models\Product;
use App\Support\ProjectContext;

/**
 * Dashboard Comercial: indicadores de negocio en tiempo real para el dueño.
 * Reúne KPIs del CRM (leads), ventas (orders) y catálogo (stock) en una vista.
 */
class DashboardComercialController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $ctx = ProjectContext::for($project);

        // ── Ventas ──
        $ventasHoy = $ctx->ventas('hoy');
        $ventasMes = $ctx->ventas('mes');

        // ── Leads / CRM ──
        $clientes = Client::allProjects()->where('project_id', $project->id);
        $leads = [
            'total'    => (clone $clientes)->count(),
            'caliente' => (clone $clientes)->where('lead_temp', 'caliente')->count(),
            'tibio'    => (clone $clientes)->where('lead_temp', 'tibio')->count(),
            'frio'     => (clone $clientes)->where('lead_temp', 'frio')->count(),
            'nuevos_hoy' => (clone $clientes)->whereDate('created_at', today())->count(),
        ];

        // ── Embudo (pipeline) ──
        $etapas = ['prospecto','contactado','propuesta','negociacion','ganado','perdido'];
        $embudo = [];
        foreach ($etapas as $e) {
            $embudo[$e] = (clone $clientes)->where('etapa', $e)->count();
        }
        // prospecto incluye los sin etapa
        $embudo['prospecto'] += (clone $clientes)->whereNull('etapa')->count();

        // ── Productos más vendidos (por items de pedidos) ──
        $topProductos = $this->topProductos($project->id);

        // ── Stock bajo ──
        $stockBajo = $ctx->stockBajo();

        // ── Proyección simple: promedio diario del mes × 30 ──
        $diaDelMes = max(1, (int) now()->day);
        $proyeccion = $ventasMes['total'] / $diaDelMes * 30;

        // ── Tasa de conversión: ganados / total leads ──
        $conversion = $leads['total'] > 0
            ? round($embudo['ganado'] / $leads['total'] * 100, 1)
            : 0;

        // ── F5: KPIs comparativos, meta, gráfico, ranking y alertas (datos reales) ──
        $okOrders = fn () => Order::allProjects()->where('project_id', $project->id)->where('status', '!=', 'cancelled');

        $ventasAyer   = (float) $okOrders()->whereDate('created_at', today()->subDay())->sum('total');
        $mesAnterior  = (float) $okOrders()->whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])->sum('total');
        $meta         = (float) $project->setting('sales_goal_month', 0);
        $metaPct      = $meta > 0 ? round($ventasMes['total'] / $meta * 100, 1) : null;
        $ticketProm   = $ventasMes['cantidad'] > 0 ? $ventasMes['total'] / $ventasMes['cantidad'] : 0;
        $porCobrar    = (float) $okOrders()->where(fn ($q) => $q->whereNotIn('payment_status', ['paid', 'refunded'])->orWhereNull('payment_status'))->sum('total');
        $clientesNuevosMes = (clone $clientes)->whereBetween('created_at', [now()->startOfMonth(), now()])->count();
        $cotizVencer  = $project->quotes()->whereIn('status', ['draft', 'sent'])
            ->whereNotNull('valid_until')->whereBetween('valid_until', [now()->subDay(), now()->addDays(3)])->count();
        $sinActualizar = $okOrders()->whereNotIn('status', ['done', 'completed'])->where('updated_at', '<', now()->subHours(48))->count();
        $pedidosActivos = $okOrders()->whereNotIn('status', ['done', 'completed'])->count();

        // Ventas por día del mes (para el gráfico SVG)
        $ventasPorDia = $okOrders()->whereBetween('created_at', [now()->startOfMonth(), now()])
            ->selectRaw('DATE(created_at) as d, SUM(total) as t')->groupBy('d')->pluck('t', 'd');
        $grafico = [];
        $acum = 0.0;
        for ($d = 1; $d <= (int) now()->day; $d++) {
            $key = now()->startOfMonth()->addDays($d - 1)->toDateString();
            $val = (float) ($ventasPorDia[$key] ?? 0);
            $acum += $val;
            $grafico[] = ['dia' => $d, 'total' => $val, 'acum' => $acum];
        }

        // Ranking por vendedor del mes. Ademas de lo vendido se cuenta lo
        // COTIZADO: un vendedor que cotiza mucho y cierra poco es un dato de
        // gestion que el ranking de solo ventas escondia.
        $desdeMes  = now()->startOfMonth();
        $cotizadas = \App\Modules\Ventas\Models\Quote::where('project_id', $project->id)
            ->whereBetween('created_at', [$desdeMes, now()])
            ->whereNotNull('created_by')
            ->selectRaw('created_by, COUNT(*) as n, SUM(total) as importe')
            ->groupBy('created_by')->get()->keyBy('created_by');

        // Un solo viaje a users para todo el ranking.
        $nombres = \App\Models\User::whereIn('id', $cotizadas->keys()
            ->merge($okOrders()->whereBetween('created_at', [$desdeMes, now()])
                ->whereNotNull('created_by')->distinct()->pluck('created_by'))
            ->unique())->pluck('name', 'id');

        $ranking = $okOrders()->whereBetween('created_at', [$desdeMes, now()])
            ->whereNotNull('created_by')
            ->selectRaw('created_by, COUNT(*) as pedidos, SUM(total) as total')
            ->groupBy('created_by')->orderByDesc('total')->limit(6)->get()
            ->map(function ($r) use ($cotizadas, $nombres) {
                $nCot = (int) ($cotizadas->get($r->created_by)->n ?? 0);

                return [
                    'nombre'     => $nombres[$r->created_by] ?? 'Usuario #'.$r->created_by,
                    'pedidos'    => (int) $r->pedidos,
                    'total'      => (float) $r->total,
                    'ticket'     => $r->pedidos ? $r->total / $r->pedidos : 0,
                    'cotizadas'  => $nCot,
                    // Cuantas de sus cotizaciones acabaron en venta.
                    'conversion' => $nCot ? (int) round($r->pedidos / $nCot * 100) : null,
                ];
            });

        // Quien cotiza pero todavia no vende no aparecia en ninguna parte, y
        // es justo el caso que un gerente quiere ver.
        $conVenta    = $ranking->pluck('nombre')->all();
        $soloCotizan = $cotizadas
            ->reject(fn ($c, $uid) => in_array($nombres[$uid] ?? 'Usuario #'.$uid, $conVenta, true))
            ->map(fn ($c, $uid) => [
                'nombre'     => $nombres[$uid] ?? 'Usuario #'.$uid,
                'pedidos'    => 0,
                'total'      => 0.0,
                'ticket'     => 0.0,
                'cotizadas'  => (int) $c->n,
                'conversion' => 0,
            ])->values();

        $ranking = $ranking->concat($soloCotizan)->take(8)->values();

        // Alertas accionables (cada una enlaza al registro correspondiente)
        $alertas = [];
        if ($porCobrar > 0)     $alertas[] = ['txt' => 'S/ '.number_format($porCobrar, 2).' pendientes de cobro', 'url' => url('/orders'), 'tipo' => 'amber'];
        if ($cotizVencer > 0)   $alertas[] = ['txt' => $cotizVencer.' cotización(es) vencen en ≤3 días', 'url' => url('/orders'), 'tipo' => 'amber'];
        if ($sinActualizar > 0) $alertas[] = ['txt' => $sinActualizar.' pedido(s) sin actualizar hace +48h', 'url' => url('/orders'), 'tipo' => 'red'];
        if ($stockBajo->count()) $alertas[] = ['txt' => $stockBajo->count().' producto(s) con stock crítico', 'url' => route('products.index'), 'tipo' => 'red'];
        if ($meta > 0 && $metaPct !== null) {
            $esperado = $meta / now()->daysInMonth * now()->day;
            if ($ventasMes['total'] < $esperado) {
                $alertas[] = ['txt' => 'Ventas '.round(($esperado - $ventasMes['total']) / max(1, $esperado) * 100).'% por debajo del ritmo de la meta', 'url' => url('/orders'), 'tipo' => 'amber'];
            }
        }

        return view('ventas::dashboard.comercial', compact(
            'project', 'ventasHoy', 'ventasMes', 'leads', 'embudo',
            'topProductos', 'stockBajo', 'proyeccion', 'conversion',
            'ventasAyer', 'mesAnterior', 'meta', 'metaPct', 'ticketProm', 'porCobrar',
            'clientesNuevosMes', 'cotizVencer', 'sinActualizar', 'pedidosActivos',
            'grafico', 'ranking', 'alertas'
        ));
    }

    /** Guarda la meta comercial del mes (setting del proyecto). */
    public function saveMeta(\Illuminate\Http\Request $request)
    {
        $project = app('active_project');
        $data = $request->validate(['meta' => 'required|numeric|min:0']);
        $project->settings()->updateOrCreate(['key' => 'sales_goal_month'], ['value' => (string) $data['meta']]);

        return back()->with('status', 'Meta comercial actualizada.');
    }

    /** Top 5 productos por cantidad vendida (vía order_items si existe). */
    private function topProductos(int $projectId): \Illuminate\Support\Collection
    {
        // Suma cantidades desde order_items unidas a orders del proyecto.
        try {
            return \DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.project_id', $projectId)
                ->whereNotIn('orders.status', ['anulado', 'cancelado'])
                ->selectRaw('order_items.name as nombre, SUM(order_items.quantity) as unidades, SUM(order_items.price * order_items.quantity) as total')
                ->groupBy('order_items.name')
                ->orderByDesc('unidades')
                ->limit(5)
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }
}
