<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $clients     = $project->clients()->withCount(['orders','appointments'])->latest()->get();
        $clientTypes = $this->catValues($project, 'client_type');
        $leadSources = $this->catValues($project, 'lead_source');
        $portalLayout = request()->routeIs('bixosales.*') ? 'comercial' : 'panel';

        // Resumen de leads (alimentado por el Copilot) para las tarjetas del CRM.
        $leadStats = [
            'caliente' => $clients->where('lead_temp', 'caliente')->count(),
            'tibio'    => $clients->where('lead_temp', 'tibio')->count(),
            'frio'     => $clients->where('lead_temp', 'frio')->count(),
            'total'    => $clients->count(),
        ];

        return view('clients.index', compact('project', 'clients', 'clientTypes', 'leadSources', 'portalLayout', 'leadStats'));
    }

    /**
     * Ficha del cliente. Las rutas `clients.show`, `clients.create` y
     * `clients.edit` estaban declaradas apuntando a metodos que NO existian:
     * pedir `/clients/{id}` devolvia **500** en produccion. No se detecto
     * porque ninguna vista enlaza ahi, pero esa URL es justo la ficha.
     *
     * Doble naturaleza, igual que Pedidos y Cotizaciones: JSON para AJAX,
     * interfaz completa para navegacion HTML.
     */
    public function show(Request $request, Client $client)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($client->project_id === $project->id, 403);

        if (! ($request->expectsJson() || $request->ajax())) {
            return $this->index();
        }

        return response()->json([
            'cliente'   => $client,
            'resumen'   => $this->resumenDe($project, $client),
            'pedidos'   => $client->orders()->latest()->limit(20)
                ->get(['id', 'total', 'status', 'payment_status', 'created_at']),
            'cotizaciones' => $client->quotes()->latest()->limit(20)
                ->get(['id', 'total', 'status', 'payment_status', 'created_at']),
            'historial' => $this->historialDe($project, $client),
        ]);
    }

    /**
     * Customer 360 (Fase 6): TODA la relación con el cliente en una sola
     * línea de tiempo — cotizó, pidió, se le facturó, pagó, se le despachó y
     * conversó. Es una AGREGACIÓN de las fuentes canónicas existentes: aquí
     * no nace ninguna tabla ni ninguna copia (ADR-003).
     */
    private function historialDe(\App\Models\Project $project, Client $client): array
    {
        $eventos = collect();

        foreach ($client->quotes()->latest()->limit(30)->get() as $q) {
            $eventos->push(['tipo' => 'cotizacion', 'etiqueta' => 'Cotización '.$q->etiqueta,
                'detalle' => \App\Support\QuoteStatus::clientePresentacion($q->status)['label'],
                'monto' => (float) $q->total, 'fecha' => $q->created_at]);
        }
        foreach ($client->orders()->latest()->limit(30)->get() as $o) {
            $eventos->push(['tipo' => 'pedido', 'etiqueta' => 'Pedido #'.$o->id,
                'detalle' => ($o->payment_status === 'paid' ? 'Pagado' : 'Pago pendiente'),
                'monto' => (float) $o->total, 'fecha' => $o->created_at]);
        }
        foreach (\App\Models\Invoice::where('client_id', $client->id)->where('status', '!=', 'draft')->latest()->limit(30)->get() as $i) {
            $eventos->push(['tipo' => 'comprobante', 'etiqueta' => $i->getTypeLabel().' '.$i->numero,
                'detalle' => $i->sunat_status === 'accepted' ? 'Aceptada SUNAT' : ($i->sunat_status ?: 'Emitida'),
                'monto' => (float) $i->total, 'fecha' => $i->created_at]);
        }
        foreach (\App\Models\GuiaRemision::where('client_id', $client->id)->latest()->limit(15)->get() as $g) {
            $eventos->push(['tipo' => 'guia', 'etiqueta' => 'Guía '.$g->numero,
                'detalle' => $g->motivoLegible(), 'monto' => null, 'fecha' => $g->created_at]);
        }
        foreach (\App\Models\SalesInteraction::where('client_id', $client->id)->latest()->limit(15)->get() as $s) {
            $eventos->push(['tipo' => 'interaccion', 'etiqueta' => 'Interacción · '.($s->canal ?: 'nota'),
                'detalle' => \Illuminate\Support\Str::limit((string) ($s->texto ?? ''), 70),
                'monto' => null, 'fecha' => $s->created_at]);
        }

        return $eventos->sortByDesc('fecha')->take(50)->values()
            ->map(function ($e) {
                $e['fecha'] = optional($e['fecha'])->format('d/m/Y H:i');
                return $e;
            })->all();
    }

    /**
     * Consolidacion comercial y financiera del cliente. Hasta ahora la ficha
     * solo sabia CONTAR pedidos y citas: no habia forma de responder "cuanto me
     * debe este cliente" sin salir a Cuentas por Cobrar y buscarlo a mano.
     * Se deriva del libro de cobros, en centavos exactos.
     */
    private function resumenDe(Project $project, Client $client): array
    {
        $deudaCents = 0;
        $vendidoCents = 0;

        foreach ($client->orders as $o) {
            $totalC = \App\Support\LineMath::toCents(\App\Support\LineMath::canon((string) $o->total));
            $vendidoCents += $totalC;
            if (in_array(strtolower((string) $o->status), ['cancelled', 'cancelado', 'anulado'], true)) {
                continue;
            }
            $deudaCents += max(0, $totalC - \App\Support\Ledger::cobradoCents($project->id, 'order', $o->id));
        }

        return [
            'pedidos'      => $client->orders->count(),
            'cotizaciones' => $client->quotes->count(),
            'vendido'      => \App\Support\LineMath::present(\App\Support\LineMath::format($vendidoCents)),
            'deuda'        => \App\Support\LineMath::present(\App\Support\LineMath::format($deudaCents)),
            'deuda_cents'  => $deudaCents,
        ];
    }

    /**
     * `create` y `edit` existen como ruta desde siempre pero el alta y la
     * edicion se hacen por AJAX contra `store`/`update` desde el listado. Se
     * resuelven devolviendo el listado en vez de reventar con 500.
     */
    public function create()
    {
        return redirect()->route(request()->routeIs('bixosales.*') ? 'bixosales.clientes' : 'clients');
    }

    public function edit(Client $client)
    {
        return $this->create();
    }

    /**
     * Vista de pipeline (Kanban) del CRM: leads agrupados por etapa comercial.
     * Es la otra mitad del Copilot: aquí el vendedor gestiona lo que la extensión capturó.
     */
    public function pipeline()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $clients = $project->clients()->withCount(['orders','quotes'])->latest('ultima_actividad')->get();

        $etapas = [
            'prospecto'   => 'Prospecto',
            'contactado'  => 'Contactado',
            'propuesta'   => 'Propuesta',
            'negociacion' => 'Negociación',
            'ganado'      => 'Ganado',
            'perdido'     => 'Perdido',
        ];
        $porEtapa = [];
        foreach ($etapas as $key => $label) {
            $porEtapa[$key] = $clients->where('etapa', $key === 'prospecto' ? 'prospecto' : $key)
                ->when($key === 'prospecto', fn ($c) => $c->concat($clients->whereNull('etapa')))
                ->values();
        }

        return view('clients.pipeline', compact('project', 'etapas', 'porEtapa'));
    }

    /** Mover un lead de etapa (drag & drop del pipeline). */
    public function moveStage(Request $request, Client $client)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($client->project_id === $project->id, 403);
        $data = $request->validate([
            'etapa' => 'required|in:prospecto,contactado,propuesta,negociacion,ganado,perdido',
        ]);
        $client->update(['etapa' => $data['etapa']]);
        return response()->json(['ok' => true]);
    }

    private function catValues(Project $project, string $type): \Illuminate\Support\Collection
    {
        $list = $project->catalogLists()->where('type', $type)->first();
        return $list ? $list->values()->where('is_active', true)->orderBy('sort_order')->pluck('label') : collect();
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'phone'       => 'nullable|string|max:30',
            'email'       => 'nullable|email|max:100',
            'notes'       => 'nullable|string',
            'client_type' => 'nullable|string|max:80',
            'lead_source' => 'nullable|string|max:80',
        ]);
        $data['project_id'] = $project->id;
        $client = Client::create($data);
        $client->loadCount(['orders','appointments']);
        return response()->json(['client' => $client]);
    }

    public function update(Request $request, Client $client)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($client->project_id === $project->id, 403);
        $client->update($request->validate([
            'name'        => 'required|string|max:100',
            'phone'       => 'nullable|string|max:30',
            'email'       => 'nullable|email|max:100',
            'notes'       => 'nullable|string',
            'client_type' => 'nullable|string|max:80',
            'lead_source' => 'nullable|string|max:80',
        ]));
        $client->loadCount(['orders','appointments']);
        return response()->json(['client' => $client->fresh()]);
    }

    public function destroy(Client $client)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($client->project_id === $project->id, 403);
        $client->delete();
        return response()->json(['ok' => true]);
    }

    // ── Portal Facturación ────────────────────────────────────────────────────

    private function projectBySlug(string $slug): Project
    {
        return Project::where('slug', $slug)->firstOrFail();
    }

    public function indexPortal(string $slug)
    {
        $project     = $this->projectBySlug($slug);
        $clients     = $project->clients()->withCount(['orders','appointments'])->latest()->get();
        $clientTypes = $this->catValues($project, 'client_type');
        $leadSources = $this->catValues($project, 'lead_source');
        return view('facturacion.clientes.index', compact('project', 'clients', 'clientTypes', 'leadSources'));
    }

    public function storePortal(Request $request, string $slug)
    {
        $project = $this->projectBySlug($slug);
        app()->instance('active_project', $project);
        return $this->store($request);
    }

    public function updatePortal(Request $request, string $slug, Client $client)
    {
        $project = $this->projectBySlug($slug);
        app()->instance('active_project', $project);
        return $this->update($request, $client);
    }

    public function destroyPortal(string $slug, Client $client)
    {
        $project = $this->projectBySlug($slug);
        app()->instance('active_project', $project);
        return $this->destroy($client);
    }
}
