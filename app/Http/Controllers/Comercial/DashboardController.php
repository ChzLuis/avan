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
        // ── Cobranza: la MISMA cartera que ve Cuentas por Cobrar ─────────
        // Antes este KPI sumaba el total de los pedidos no pagados, sin restar
        // adelantos ni incluir cotizaciones aceptadas: el panel y Cobranza
        // daban dos cifras distintas para "cuanto me deben". Ahora las dos
        // pantallas preguntan a App\Support\Cobranza.
        // "Por cobrar" es la deuda que nace de una venta. Las cotizaciones
        // aceptadas ya no suman aqui: no son deuda, son trabajo pendiente
        // (convertirlas en pedido), y como tal se enseñan.
        $cartera      = \App\Support\Cobranza::cartera($project);
        $porCobrar    = $cartera['total_cents'] / 100;
        $vencido      = $cartera['vencido_cents'] / 100;
        $docsVencidos = $cartera['vencidas'];
        $porConvertir = \App\Support\Cobranza::aceptadasSinConvertir($project);

        // ── Stock critico ────────────────────────────────────────────────
        // El catalogo guarda `stock_min` por producto desde siempre y el panel
        // no lo miraba en ningun sitio: el semaforo decia "Stock: niveles
        // normales" como texto fijo, sin consultar una sola fila.
        $stockCritico = $project->products()
            ->whereNotNull('stock')
            ->whereRaw('stock <= COALESCE(stock_min, 0)')
            ->count();

        // ── Actividad reciente ───────────────────────────────────────────
        // `order_events` ya registra los hechos del negocio (pagos, envios,
        // conversiones, aceptaciones del cliente) y no se enseñaban en ninguna
        // pantalla del portal.
        $actividad = \App\Models\OrderEvent::where('project_id', $pid)
            ->orderByDesc('created_at')->limit(6)->get();

        $meta = (float) $project->setting('sales_goal_month', 0);
        $metaPct = $meta > 0 ? round($ventasMesTotal / $meta * 100) : null;

        $labels7 = [];
        $data7   = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $labels7[] = now()->subDays($i)->locale('es')->isoFormat('ddd D');
            $data7[]   = round($semana->get($d)?->total ?? 0, 2);
        }

        // ── Series para el selector 7 días / 30 días / este mes ───────────
        // El panel solo tenia la serie de 7 dias, asi que el selector habria
        // sido un adorno que cambia de pestaña sin cambiar de datos. Cada
        // rango se consulta de verdad, con su total y su comparacion contra
        // el periodo anterior de la MISMA longitud.
        $series = [];
        foreach ([['7d', 7], ['30d', 30]] as [$clave, $dias]) {
            $series[$clave] = $this->serieVentas($project, now()->subDays($dias - 1)->startOfDay(), now()->endOfDay(), $dias);
        }
        $series['mes'] = $this->serieVentas($project, now()->startOfMonth(), now()->endOfDay(), (int) now()->day);

        $porEstado = Cache::remember("dashboard.estados.{$pid}.{$hoy}", 120, fn() =>
            $project->orders()
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->pluck('total', 'status')
        );

        // Desglose por estados del flujo configurable SOLO si el negocio lo usa
        // de verdad. `OrderFlow::supportsFlow()` devuelve true para cualquier
        // rubro con categoria, asi que una ferreteria contaba sus pedidos por
        // `laundry_status` —columna que nunca rellena— y el bloque salia en
        // cero teniendo pedidos. Lo decide el dato, no el nombre del rubro.
        $usaFlujoPropio = \App\Support\OrderFlow::supportsFlow($project->category ?? '')
            && $project->orders()->whereNotNull('laundry_status')->exists();

        if ($usaFlujoPropio) {
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

        // "Por atender" no decia si esos pedidos estaban sin tocar o a medias.
        // El desglose sale del mismo conteo por estado que ya se consulta.
        $enProceso = (int) $porEstado->get('process', 0);

        // ── Cola operativa: que atender primero ──────────────────────────
        // Sustituye a las tarjetas de "Pedidos en curso", a "Ultimos pedidos"
        // y a media tarjeta de estado: eran cinco representaciones del mismo
        // pedido. Se ordena por antiguedad porque lo que lleva mas tiempo
        // parado es lo que primero molesta al cliente.
        $atencionUmbrales = [
            'aviso'   => max(1, (int) $project->setting('orders_aviso_horas', 24)),
            'critico' => max(2, (int) $project->setting('orders_critico_horas', 72)),
        ];
        $pedidosAtencion = $project->orders()
            ->whereIn('status', ['pending', 'process'])
            ->orderBy('created_at')
            ->limit(8)
            ->get(['id', 'client_name', 'status', 'total', 'created_at'])
            ->map(function ($o) use ($atencionUmbrales) {
                $horas = (int) $o->created_at->diffInHours(now());
                return [
                    'id'      => $o->id,
                    'cliente' => $o->client_name ?: 'Cliente mostrador',
                    'estado'  => $o->status === 'process' ? 'En proceso' : 'Nuevo',
                    'horas'   => $horas,
                    'tiempo'  => $horas < 48 ? $horas . ' h' : (int) floor($horas / 24) . ' d',
                    'total'   => (float) $o->total,
                    'nivel'   => $horas >= $atencionUmbrales['critico'] ? 'critico'
                        : ($horas >= $atencionUmbrales['aviso'] ? 'aviso' : 'normal'),
                ];
            });
        $pedidosAtencionTotal = $project->orders()->whereIn('status', ['pending', 'process'])->count();

        // ── Conversion de cotizaciones ───────────────────────────────────
        // Enviadas = las que llegaron al cliente alguna vez (`sent_at`), que
        // incluye las que despues se aceptaron o convirtieron: si solo se
        // contaran las que HOY siguen en 'sent', el porcentaje subiria cada
        // vez que una cotizacion avanza, que es justo al reves.
        // Conversion = convertidas / enviadas.
        $estadosQuote = $project->quotes()
            ->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');
        $contarQuote = function (string $canonico) use ($estadosQuote) {
            $total = 0;
            foreach ($estadosQuote as $estado => $n) {
                if (\App\Support\QuoteStatus::comercial((string) $estado) === $canonico) {
                    $total += (int) $n;
                }
            }
            return $total;
        };
        $enviadas = (int) $project->quotes()->whereNotNull('sent_at')->count();
        $conversion = [
            'enviadas'    => $enviadas,
            'aceptadas'   => $contarQuote('accepted'),
            'convertidas' => $contarQuote('converted'),
            'pct'         => $enviadas > 0 ? round($contarQuote('converted') / $enviadas * 100, 1) : null,
        ];

        $pedidosRecientes = $project->orders()->with('items')->latest()->take(10)->get();

        $varPedidos = $pedidosAyer > 0 ? round((($pedidosHoy - $pedidosAyer) / $pedidosAyer) * 100) : null;
        $varVentas  = $ventasAyer  > 0 ? round((($ventasHoy  - $ventasAyer)  / $ventasAyer)  * 100) : null;

        return view('comercial.dashboard', array_merge(
            compact('canales', 'ventasMesTotal', 'porCobrar', 'meta', 'metaPct'),
            compact('vencido', 'docsVencidos', 'stockCritico', 'actividad', 'series', 'enProceso'),
            compact('porConvertir'),
            compact('pedidosAtencion', 'pedidosAtencionTotal', 'conversion'),
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

    /**
     * Ventas por dia de un rango, con su total y la variacion contra el rango
     * anterior de la misma longitud. Devuelve tambien `vacia` para que la
     * vista pinte un estado vacio honesto en vez de una linea plana en cero
     * que parece un grafico roto.
     */
    private function serieVentas(Project $project, \Illuminate\Support\Carbon $desde, \Illuminate\Support\Carbon $hasta, int $dias): array
    {
        $porDia = fn ($d, $h) => $project->orders()
            ->whereIn('status', ['process', 'done'])
            ->whereBetween('created_at', [$d, $h])
            ->select(DB::raw('DATE(created_at) as fecha'), DB::raw('SUM(total) as total'))
            ->groupBy('fecha')->pluck('total', 'fecha');

        $actual = $porDia($desde, $hasta);

        $labels = [];
        $data   = [];
        $cursor = $desde->copy();
        while ($cursor->lte($hasta)) {
            $labels[] = $cursor->locale('es')->isoFormat($dias > 14 ? 'D MMM' : 'ddd D');
            $data[]   = round((float) ($actual[$cursor->toDateString()] ?? 0), 2);
            $cursor->addDay();
        }

        $total   = array_sum($data);
        $previo  = (float) $porDia($desde->copy()->subDays($dias), $desde->copy()->subSecond())->sum();
        $varianza = $previo > 0 ? (int) round((($total - $previo) / $previo) * 100) : null;

        return [
            'labels'   => $labels,
            'data'     => $data,
            'total'    => $total,
            'previo'   => $previo,
            'varianza' => $varianza,
            'vacia'    => $total <= 0,
        ];
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
