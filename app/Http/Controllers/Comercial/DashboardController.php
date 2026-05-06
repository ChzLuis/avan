<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $project = Project::findOrFail(session('comercial_project_id'));
        $pid     = $project->id;

        $hoy  = now()->toDateString();
        $ayer = now()->subDay()->toDateString();

        $kpis = Cache::remember("dashboard.kpis.{$pid}.{$hoy}", 120, function () use ($project, $hoy, $ayer) {
            $pedidosHoy   = $project->orders()->whereDate('created_at', $hoy)->count();
            $pedidosAyer  = $project->orders()->whereDate('created_at', $ayer)->count();
            $ventasHoy    = $project->orders()->whereDate('created_at', $hoy)
                                ->whereIn('status', ['process','done'])->sum('total');
            $ventasAyer   = $project->orders()->whereDate('created_at', $ayer)
                                ->whereIn('status', ['process','done'])->sum('total');
            $pendientes   = $project->orders()->where('status', 'pending')->count();
            $waPendientes = $project->orders()->where('sales_channel', 'whatsapp')
                                ->whereNotIn('wa_status', ['entregado','problema'])->count();
            return compact('pedidosHoy','pedidosAyer','ventasHoy','ventasAyer','pendientes','waPendientes');
        });

        extract($kpis);

        // ── Ventas últimos 7 días ─────────────────────────────────
        $semana = Cache::remember("dashboard.semana.{$pid}.{$hoy}", 120, function () use ($project) {
            return $project->orders()
                ->whereIn('status', ['process','done'])
                ->where('created_at', '>=', now()->subDays(6)->startOfDay())
                ->select(DB::raw('DATE(created_at) as fecha'), DB::raw('SUM(total) as total'))
                ->groupBy('fecha')
                ->orderBy('fecha')
                ->get()
                ->keyBy('fecha');
        });

        $labels7 = [];
        $data7   = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $labels7[] = now()->subDays($i)->locale('es')->isoFormat('ddd D');
            $data7[]   = round($semana->get($d)?->total ?? 0, 2);
        }

        // ── Pedidos por estado (dona) ─────────────────────────────
        $porEstado = Cache::remember("dashboard.estados.{$pid}.{$hoy}", 120, fn() =>
            $project->orders()
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
        );

        $donaLabels = ['Nuevos', 'En proceso', 'Completados', 'Cancelados'];
        $donaData   = [
            $porEstado->get('pending', 0),
            $porEstado->get('process', 0),
            $porEstado->get('done', 0),
            $porEstado->get('cancelled', 0),
        ];

        // ── Top 5 productos más vendidos ─────────────────────────
        $topProductos = Cache::remember("dashboard.top5.{$pid}.{$hoy}", 300, fn() =>
            OrderItem::whereHas('order', fn($q) => $q->where('project_id', $pid))
                ->select('name', DB::raw('SUM(quantity) as qty'), DB::raw('SUM(quantity * price) as total'))
                ->groupBy('name')
                ->orderByDesc('qty')
                ->limit(5)
                ->get()
        );

        // ── Pedidos recientes ─────────────────────────────────────
        $pedidosRecientes = $project->orders()->with('items')->latest()->take(10)->get();

        // ── Variación porcentual ──────────────────────────────────
        $varPedidos = $pedidosAyer > 0 ? round((($pedidosHoy - $pedidosAyer) / $pedidosAyer) * 100) : null;
        $varVentas  = $ventasAyer  > 0 ? round((($ventasHoy  - $ventasAyer)  / $ventasAyer)  * 100) : null;

        return view('comercial.dashboard', compact(
            'project',
            'pedidosHoy', 'pedidosAyer', 'varPedidos',
            'ventasHoy',  'ventasAyer',  'varVentas',
            'pendientes', 'waPendientes',
            'labels7', 'data7',
            'donaLabels', 'donaData',
            'topProductos',
            'pedidosRecientes'
        ));
    }
}
