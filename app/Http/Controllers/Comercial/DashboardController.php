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

        // ── Detectar si el proyecto usa rifa_ventas ───────────────────────────
        $usaRifas = DB::table('rifa_ventas')->where('project_id', $pid)->exists();

        if ($usaRifas) {
            return $this->indexRifa($project, $pid, $hoy, $ayer);
        }

        // ── Lógica estándar (orders) ──────────────────────────────────────────
        $kpis = Cache::remember("dashboard.kpis.{$pid}.{$hoy}", 120, function () use ($project, $hoy, $ayer) {
            $pedidosHoy   = $project->orders()->whereDate('created_at', $hoy)->count();
            $pedidosAyer  = $project->orders()->whereDate('created_at', $ayer)->count();
            $ventasHoy    = $project->orders()->whereDate('created_at', $hoy)
                                ->whereIn('status', ['process','done'])->sum('total');
            $ventasAyer   = $project->orders()->whereDate('created_at', $ayer)
                                ->whereIn('status', ['process','done'])->sum('total');
            $pendientes   = $project->orders()->where('status', 'pending')->count();
            // Un pedido de WhatsApp sin wa_status es de los que crea el webhook
            // del bot, que no rellena esa columna: NULL significa "aun no se ha
            // entregado", no "no aplica". Con NOT IN a secas los nulos caian
            // fuera y el KPI marcaba 0 teniendo pedidos pendientes de verdad.
            $waPendientes = $project->orders()->where('sales_channel', 'whatsapp')
                                ->where(fn ($q) => $q->whereNull('wa_status')
                                    ->orWhereNotIn('wa_status', ['entregado','problema']))->count();
            return compact('pedidosHoy','pedidosAyer','ventasHoy','ventasAyer','pendientes','waPendientes');
        });

        extract($kpis);

        $semana = Cache::remember("dashboard.semana.{$pid}.{$hoy}", 120, function () use ($project) {
            return $project->orders()
                ->whereIn('status', ['process','done'])
                ->where('created_at', '>=', now()->subDays(6)->startOfDay())
                ->select(DB::raw('DATE(created_at) as fecha'), DB::raw('SUM(total) as total'))
                ->groupBy('fecha')->orderBy('fecha')->get()->keyBy('fecha');
        });

        // ── Ventas por canal (mes) + por cobrar + meta — corazón multicanal de BIXO ──
        $inicioMes = now()->startOfMonth();
        $canalesRaw = $project->orders()->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $inicioMes)
            ->selectRaw("COALESCE(NULLIF(sales_channel,''),'otros') as canal, COUNT(*) as n, SUM(total) as t")
            ->groupBy('canal')->get();
        $canalMap = ['ecommerce' => 'Tienda virtual', 'web' => 'Tienda virtual', 'pos' => 'POS / Mostrador',
                     'whatsapp' => 'WhatsApp', 'cotizacion' => 'Cotizaciones', 'otros' => 'Otros'];
        $canales = [];
        foreach ($canalesRaw as $c) {
            $k = $canalMap[$c->canal] ?? ucfirst($c->canal);
            $canales[$k] = ['n' => ($canales[$k]['n'] ?? 0) + (int) $c->n, 't' => ($canales[$k]['t'] ?? 0) + (float) $c->t];
        }
        uasort($canales, fn ($a, $b) => $b['t'] <=> $a['t']);
        $ventasMesTotal = array_sum(array_column($canales, 't'));
        $porCobrar = (float) $project->orders()->where('status', '!=', 'cancelled')
            ->where(fn ($q) => $q->whereNotIn('payment_status', ['paid', 'refunded'])->orWhereNull('payment_status'))
            ->sum('total');
        $meta = (float) $project->setting('sales_goal_month', 0);
        $metaPct = $meta > 0 ? round($ventasMesTotal / $meta * 100) : null;

        $labels7 = [];
        $data7   = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $labels7[] = now()->subDays($i)->locale('es')->isoFormat('ddd D');
            $data7[]   = round($semana->get($d)?->total ?? 0, 2);
        }

        $porEstado = Cache::remember("dashboard.estados.{$pid}.{$hoy}", 120, fn() =>
            $project->orders()
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->pluck('total', 'status')
        );

        // Lavandería: dona por estados del flujo configurado. Otros: estados genéricos.
        if (\App\Support\OrderFlow::supportsFlow($project->category ?? '')) {
            $porLaundry = $project->orders()
                ->select('laundry_status', DB::raw('count(*) as total'))
                ->groupBy('laundry_status')->pluck('total', 'laundry_status');

            $lavStates = \App\Support\OrderFlow::activeStates($project);
            $donaLabels = []; $donaData = []; $donaColors = [];
            foreach ($lavStates as $key => $st) {
                $donaLabels[] = $st['label'];
                $donaData[]   = (int) $porLaundry->get($key, 0);
                $donaColors[] = $st['color'];
            }
        } else {
            $donaLabels = ['Nuevos', 'En proceso', 'Completados', 'Cancelados'];
            $donaData   = [
                $porEstado->get('pending', 0),
                $porEstado->get('process', 0),
                $porEstado->get('done', 0),
                $porEstado->get('cancelled', 0),
            ];
            $donaColors = ['#F59E0B', '#3B82F6', '#10B981', '#EF4444'];
        }

        $topProductos = Cache::remember("dashboard.top5.{$pid}.{$hoy}", 300, fn() =>
            OrderItem::whereHas('order', fn($q) => $q->where('project_id', $pid))
                ->select('name', DB::raw('SUM(quantity) as qty'), DB::raw('SUM(quantity * price) as total'))
                ->groupBy('name')->orderByDesc('qty')->limit(5)->get()
        );

        $pedidosRecientes = $project->orders()->with('items')->latest()->take(10)->get();

        $varPedidos = $pedidosAyer > 0 ? round((($pedidosHoy - $pedidosAyer) / $pedidosAyer) * 100) : null;
        $varVentas  = $ventasAyer  > 0 ? round((($ventasHoy  - $ventasAyer)  / $ventasAyer)  * 100) : null;

        return view('comercial.dashboard', array_merge(
            compact('canales', 'ventasMesTotal', 'porCobrar', 'meta', 'metaPct'),
            [] ) + compact(
            'project',
            'pedidosHoy', 'pedidosAyer', 'varPedidos',
            'ventasHoy',  'ventasAyer',  'varVentas',
            'pendientes', 'waPendientes',
            'labels7', 'data7',
            'donaLabels', 'donaData', 'donaColors',
            'topProductos', 'pedidosRecientes'
        ));
    }

    // ── Dashboard específico para proyectos de rifas ──────────────────────────
    private function indexRifa(Project $project, int $pid, string $hoy, string $ayer)
    {
        $rv = fn() => DB::table('rifa_ventas')->where('project_id', $pid);

        $kpis = Cache::remember("dashboard.rifa.kpis.{$pid}.{$hoy}", 60, function () use ($rv, $hoy, $ayer) {
            $pedidosHoy  = (clone $rv())->whereDate('created_at', $hoy)->count();
            $pedidosAyer = (clone $rv())->whereDate('created_at', $ayer)->count();
            // Ventas confirmadas = pagado + enviado
            $ventasHoy   = (clone $rv())->whereDate('created_at', $hoy)
                               ->whereIn('status', ['pagado','enviado'])->sum('monto');
            $ventasAyer  = (clone $rv())->whereDate('created_at', $ayer)
                               ->whereIn('status', ['pagado','enviado'])->sum('monto');
            // Pendientes = necesitan atención (comprobante enviado sin confirmar o pendiente)
            $pendientes  = (clone $rv())->whereIn('status', ['pendiente','comprobante'])->count();
            // WA activos = conversaciones hoy
            $waPendientes = (clone $rv())->whereDate('created_at', $hoy)->count();
            return compact('pedidosHoy','pedidosAyer','ventasHoy','ventasAyer','pendientes','waPendientes');
        });

        extract($kpis);

        // ── Ventas últimos 7 días ─────────────────────────────────────────────
        $semanaRaw = Cache::remember("dashboard.rifa.semana.{$pid}.{$hoy}", 60, function () use ($rv) {
            return (clone $rv())
                ->whereIn('status', ['pagado','enviado'])
                ->where('created_at', '>=', now()->subDays(6)->startOfDay())
                ->select(DB::raw('DATE(created_at) as fecha'), DB::raw('SUM(monto) as total'))
                ->groupBy('fecha')->orderBy('fecha')->get()->keyBy('fecha');
        });

        $labels7 = [];
        $data7   = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $labels7[] = now()->subDays($i)->locale('es')->isoFormat('ddd D');
            $data7[]   = round($semanaRaw->get($d)?->total ?? 0, 2);
        }

        // ── Dona por estado ───────────────────────────────────────────────────
        $porEstado = Cache::remember("dashboard.rifa.estados.{$pid}.{$hoy}", 60, function () use ($rv) {
            return (clone $rv())
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->pluck('total', 'status');
        });

        $donaLabels = ['Pendientes', 'Con comprobante', 'Pagados', 'Enviados', 'Cancelados'];
        $donaData   = [
            $porEstado->get('pendiente', 0),
            $porEstado->get('comprobante', 0),
            $porEstado->get('pagado', 0),
            $porEstado->get('enviado', 0),
            $porEstado->get('cancelado', 0),
        ];
        $donaColors = ['#F59E0B', '#8B5CF6', '#10B981', '#3B82F6', '#EF4444'];

        // ── Top planes más vendidos (confirmados, todo el período) ────────────
        $topProductos = Cache::remember("dashboard.rifa.top5.{$pid}.{$hoy}", 300, function () use ($rv) {
            return (clone $rv())
                ->whereIn('status', ['pagado','enviado'])
                ->select('plan_nombre as name', DB::raw('COUNT(*) as qty'), DB::raw('SUM(monto) as total'))
                ->groupBy('plan_nombre')->orderByDesc('total')->limit(5)->get();
        });

        // ── Ventas recientes ──────────────────────────────────────────────────
        $pedidosRecientes = Cache::remember("dashboard.rifa.recientes.{$pid}", 30, function () use ($rv) {
            return (clone $rv())->orderByDesc('created_at')->limit(10)->get()->map(function ($v) {
                return (object) [
                    'id'          => $v->id,
                    'client_name' => $v->nombre ?? $v->wa_number,
                    'total'       => $v->monto,
                    'status'      => $v->status,
                    'created_at'  => $v->created_at,
                    'items'       => collect([(object)['name' => $v->plan_nombre, 'quantity' => $v->tickets]]),
                    'wa_number'   => $v->wa_number,
                    'order_number'=> $v->order_number,
                ];
            });
        });

        // ── Score AVAN ────────────────────────────────────────────────────────
        $totalHoy       = (clone $rv())->whereDate('created_at', $hoy)->count();
        $confirmadosHoy = (clone $rv())->whereDate('created_at', $hoy)->whereIn('status', ['pagado','enviado'])->count();
        $tasaConfirm    = $totalHoy > 0 ? round(($confirmadosHoy / $totalHoy) * 100) : 100;
        $semScore       = min(100, max(0, 60 + round($tasaConfirm * 0.4) - min(20, $pendientes)));

        $varPedidos = $pedidosAyer > 0 ? round((($pedidosHoy - $pedidosAyer) / $pedidosAyer) * 100) : null;
        $varVentas  = $ventasAyer  > 0 ? round((($ventasHoy  - $ventasAyer)  / $ventasAyer)  * 100) : null;

        return view('comercial.dashboard', compact(
            'project',
            'pedidosHoy', 'pedidosAyer', 'varPedidos',
            'ventasHoy',  'ventasAyer',  'varVentas',
            'pendientes', 'waPendientes',
            'labels7', 'data7',
            'donaLabels', 'donaData', 'donaColors',
            'topProductos', 'pedidosRecientes',
            'semScore'
        ));
    }
}
