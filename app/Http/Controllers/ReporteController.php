<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RifaVenta;
use App\Models\Project;
use App\Models\BotInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    private function getProjectIds(): array
    {
        $project = Project::findOrFail(session('comercial_project_id'));
        $botProject = BotInstance::where('bot_type', 'rifa')->value('project_id');
        return array_unique(array_filter([$project->id, $botProject]));
    }

    public function ventasBot(Request $request)
    {
        $project = Project::findOrFail(session('comercial_project_id'));
        $ids = $this->getProjectIds();
        $desde = $request->get('desde', now()->startOfMonth()->format('Y-m-d'));
        $hasta = $request->get('hasta', now()->format('Y-m-d'));

        $totales = RifaVenta::whereIn('project_id', $ids)
            ->whereBetween(DB::raw('DATE(created_at)'), [$desde, $hasta])
            ->selectRaw("COUNT(*) as total, SUM(monto) as ingresos,
                COUNT(CASE WHEN status='pendiente' THEN 1 END) as pendientes,
                COUNT(CASE WHEN status='pagado' THEN 1 END) as pagados,
                COUNT(CASE WHEN status='enviado' THEN 1 END) as enviados,
                COUNT(CASE WHEN status='cancelado' THEN 1 END) as cancelados")->first();

        $porPlan = RifaVenta::whereIn('project_id', $ids)
            ->whereBetween(DB::raw('DATE(created_at)'), [$desde, $hasta])
            ->selectRaw("plan_nombre, COUNT(*) as total, SUM(monto) as ingresos,
                COUNT(CASE WHEN status='pendiente' THEN 1 END) as pendientes,
                COUNT(CASE WHEN status='pagado' THEN 1 END) as pagados,
                COUNT(CASE WHEN status='enviado' THEN 1 END) as enviados")
            ->groupBy('plan_nombre')->orderByDesc('ingresos')->get();

        $porDia = RifaVenta::whereIn('project_id', $ids)
            ->whereBetween(DB::raw('DATE(created_at)'), [$desde, $hasta])
            ->selectRaw("DATE(created_at) as fecha, COUNT(*) as total, SUM(monto) as ingresos")
            ->groupBy('fecha')->orderBy('fecha')->get();

        return view('comercial.reportes.ventas-bot', compact('project', 'totales', 'porPlan', 'porDia', 'desde', 'hasta'));
    }

    public function seguimientoBot(Request $request)
    {
        $project = Project::findOrFail(session('comercial_project_id'));
        $ids = $this->getProjectIds();
        $desde  = $request->get('desde', now()->startOfMonth()->format('Y-m-d'));
        $hasta  = $request->get('hasta', now()->format('Y-m-d'));
        $status = $request->get('status', '');
        $buscar = $request->get('buscar', '');

        $query = RifaVenta::whereIn('project_id', $ids)
            ->whereBetween(DB::raw('DATE(created_at)'), [$desde, $hasta])
            ->orderByDesc('created_at');

        if ($status) $query->where('status', $status);
        if ($buscar) $query->where(function($q) use ($buscar) {
            $q->where('nombre', 'like', "%$buscar%")
              ->orWhere('dni', 'like', "%$buscar%")
              ->orWhere('wa_number', 'like', "%$buscar%");
        });

        $ventas = $query->get();

        $tiempoPromedio = RifaVenta::whereIn('project_id', $ids)
            ->whereIn('status', ['pagado','enviado'])
            ->whereBetween(DB::raw('DATE(created_at)'), [$desde, $hasta])
            ->selectRaw("AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as horas")
            ->value('horas');

        return view('comercial.reportes.seguimiento-bot', compact('project', 'ventas', 'desde', 'hasta', 'status', 'buscar', 'tiempoPromedio'));
    }

    /** 7.3 — Top productos más vendidos */
    public function topProductos(Request $request)
    {
        $project = Project::findOrFail(session('comercial_project_id'));
        $desde   = $request->get('desde', now()->startOfMonth()->format('Y-m-d'));
        $hasta   = $request->get('hasta', now()->format('Y-m-d'));

        $top = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.project_id', $project->id)
            ->whereNotIn('orders.status', ['cancelled'])
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$desde, $hasta])
            ->selectRaw('order_items.name, SUM(order_items.quantity) as cantidad, SUM(order_items.quantity * order_items.price) as ingresos')
            ->groupBy('order_items.name')
            ->orderByDesc('ingresos')
            ->limit(20)
            ->get();

        if ($request->get('export') === 'csv') {
            return $this->exportCsv('top-productos', ['Producto', 'Cantidad', 'Ingresos'],
                $top->map(fn($r) => [$r->name, $r->cantidad, $r->ingresos])->toArray()
            );
        }

        return view('comercial.reportes.top-productos', compact('project', 'top', 'desde', 'hasta'));
    }

    /** 7.5 — Ventas generales con filtros por vendedor, rango fecha, canal */
    public function ventas(Request $request)
    {
        $project  = Project::findOrFail(session('comercial_project_id'));
        $desde    = $request->get('desde', now()->startOfMonth()->format('Y-m-d'));
        $hasta    = $request->get('hasta', now()->format('Y-m-d'));
        $canal    = $request->get('canal', '');
        $vendedor = $request->get('vendedor', '');
        $estado   = $request->get('estado', '');

        $query = Order::allProjects()
            ->with('items')
            ->where('project_id', $project->id)
            ->whereBetween(DB::raw('DATE(created_at)'), [$desde, $hasta])
            ->orderByDesc('created_at');

        if ($canal)    $query->where('sales_channel', $canal);
        if ($estado)   $query->where('status', $estado);
        if ($vendedor) $query->where('created_by', $vendedor);

        $ordenes = $query->get();

        $ordenesActivas = $ordenes->whereNotIn('status', ['cancelled']);
        $ordenesWa      = $ordenesActivas->where('sales_channel', 'whatsapp');

        $totales = [
            'count'       => $ordenes->count(),
            'ingresos'    => $ordenesActivas->sum('total'),
            'cancelados'  => $ordenes->where('status', 'cancelled')->count(),
            'whatsapp'    => $ordenesWa->count(),
            'ingresos_wa' => $ordenesWa->sum('total'),
        ];

        $porDia = $ordenes->whereNotIn('status', ['cancelled'])
            ->groupBy(fn($o) => $o->created_at->format('Y-m-d'))
            ->map(fn($g) => ['fecha' => $g->first()->created_at->format('d/m'), 'total' => $g->sum('total')])
            ->values();

        $empleados = Employee::where('project_id', $project->id)->where('is_active', true)->get(['id', 'name']);

        if ($request->get('export') === 'csv') {
            return $this->exportCsv('ventas-' . $desde . '-' . $hasta,
                ['Fecha', 'Cliente', 'Teléfono/WA', 'Canal', 'Productos', 'Total', 'Estado', 'Estado Bot'],
                $ordenes->map(fn($o) => [
                    $o->created_at->format('d/m/Y H:i'),
                    $o->client_name,
                    $o->wa_number ?? $o->client_phone ?? '-',
                    $o->sales_channel ?? '-',
                    $o->items->map(fn($i) => $i->quantity . 'x ' . $i->name)->implode(' | '),
                    $o->total,
                    $o->status,
                    $o->wa_status ?? '-',
                ])->toArray()
            );
        }

        return view('comercial.reportes.ventas', compact(
            'project', 'ordenes', 'totales', 'porDia',
            'desde', 'hasta', 'canal', 'vendedor', 'estado', 'empleados'
        ));
    }

    /** Rentabilidad: ganancia neta por producto y por día */
    public function rentabilidad(Request $request)
    {
        $project = Project::findOrFail(session('comercial_project_id'));
        $desde   = $request->get('desde', now()->startOfMonth()->format('Y-m-d'));
        $hasta   = $request->get('hasta', now()->format('Y-m-d'));

        // Items de órdenes completadas/activas con costo del producto
        $items = OrderItem::with('product')
            ->whereHas('order', fn($q) => $q
                ->where('project_id', $project->id)
                ->whereNotIn('status', ['cancelled'])
                ->whereBetween(DB::raw('DATE(orders.created_at)'), [$desde, $hasta])
            )
            ->whereNotNull('product_id')
            ->get();

        // Rentabilidad por producto
        $porProducto = $items->groupBy('product_id')->map(function ($grupo) {
            $first    = $grupo->first();
            $costo    = (float)($first->product->cost ?? 0);
            $vendidos = $grupo->sum('quantity');
            $ingresos = $grupo->sum(fn($i) => $i->price * $i->quantity);
            $costoTotal = $costo * $vendidos;
            $ganancia   = $ingresos - $costoTotal;
            $margen     = $ingresos > 0 ? round(($ganancia / $ingresos) * 100, 1) : 0;
            return [
                'nombre'      => $first->name,
                'vendidos'    => $vendidos,
                'ingresos'    => $ingresos,
                'costo_unit'  => $costo,
                'costo_total' => $costoTotal,
                'ganancia'    => $ganancia,
                'margen'      => $margen,
            ];
        })->sortByDesc('ganancia')->values();

        // Ganancia neta por día
        $porDia = $items->groupBy(fn($i) => $i->order->created_at->format('Y-m-d'))
            ->map(function ($grupo, $fecha) {
                $ingresos   = $grupo->sum(fn($i) => $i->price * $i->quantity);
                $costoTotal = $grupo->sum(fn($i) => (float)($i->product->cost ?? 0) * $i->quantity);
                return [
                    'fecha'    => \Carbon\Carbon::parse($fecha)->format('d/m'),
                    'ingresos' => round($ingresos, 2),
                    'costo'    => round($costoTotal, 2),
                    'ganancia' => round($ingresos - $costoTotal, 2),
                ];
            })->sortKeys()->values();

        // Totales globales
        $totalIngresos  = $porProducto->sum('ingresos');
        $totalCosto     = $porProducto->sum('costo_total');
        $totalGanancia  = $porProducto->sum('ganancia');
        $margenGlobal   = $totalIngresos > 0 ? round(($totalGanancia / $totalIngresos) * 100, 1) : 0;
        $sinCosto       = $items->whereNull('product.cost')->count();

        // Items sin product_id (sin costo trackeable) — ingresos brutos
        $itemsSinProducto = OrderItem::whereNull('product_id')
            ->whereHas('order', fn($q) => $q
                ->where('project_id', $project->id)
                ->whereNotIn('status', ['cancelled'])
                ->whereBetween(DB::raw('DATE(orders.created_at)'), [$desde, $hasta])
            )->sum(DB::raw('price * quantity'));

        if ($request->get('export') === 'csv') {
            return $this->exportCsv('rentabilidad-' . $desde . '-' . $hasta,
                ['Producto', 'Unidades vendidas', 'Ingresos', 'Costo unitario', 'Costo total', 'Ganancia neta', 'Margen %'],
                $porProducto->map(fn($p) => [
                    $p['nombre'], $p['vendidos'],
                    number_format($p['ingresos'], 2),
                    number_format($p['costo_unit'], 2),
                    number_format($p['costo_total'], 2),
                    number_format($p['ganancia'], 2),
                    $p['margen'] . '%',
                ])->toArray()
            );
        }

        return view('comercial.reportes.rentabilidad', compact(
            'project', 'porProducto', 'porDia',
            'totalIngresos', 'totalCosto', 'totalGanancia', 'margenGlobal',
            'sinCosto', 'itemsSinProducto', 'desde', 'hasta'
        ));
    }

    /** 7.4 — Datos en tiempo real para dashboard (polling JSON) */
    public function dashboardData(Request $request)
    {
        $project = Project::findOrFail(session('comercial_project_id'));
        $pid     = $project->id;
        $hoy     = now()->format('Y-m-d');
        $ayer    = now()->subDay()->format('Y-m-d');

        $data = Cache::remember("reporte.dashboard.{$pid}.{$hoy}", 60, function () use ($pid, $hoy, $ayer) {
            $ventasHoy  = Order::allProjects()->where('project_id', $pid)
                ->whereDate('created_at', $hoy)->whereNotIn('status', ['cancelled'])->sum('total');
            $ventasAyer = Order::allProjects()->where('project_id', $pid)
                ->whereDate('created_at', $ayer)->whereNotIn('status', ['cancelled'])->sum('total');
            $pedidosHoy = Order::allProjects()->where('project_id', $pid)
                ->whereDate('created_at', $hoy)->count();
            $pendientes = Order::allProjects()->where('project_id', $pid)
                ->where('status', 'pending')->count();
            $variacion  = $ventasAyer > 0
                ? round((($ventasHoy - $ventasAyer) / $ventasAyer) * 100, 1)
                : ($ventasHoy > 0 ? 100 : 0);

            return compact('ventasHoy','ventasAyer','pedidosHoy','pendientes','variacion');
        });

        return response()->json([
            'ventas_hoy'   => (float) $data['ventasHoy'],
            'ventas_ayer'  => (float) $data['ventasAyer'],
            'pedidos_hoy'  => $data['pedidosHoy'],
            'pendientes'   => $data['pendientes'],
            'variacion'    => $data['variacion'],
            'updated_at'   => now()->format('H:i:s'),
        ]);
    }

    /** Helper: exportar array como CSV descargable */
    private function exportCsv(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 para Excel
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
