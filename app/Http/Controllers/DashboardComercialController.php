<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
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

        return view('dashboard.comercial', compact(
            'project', 'ventasHoy', 'ventasMes', 'leads', 'embudo',
            'topProductos', 'stockBajo', 'proyeccion', 'conversion'
        ));
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
