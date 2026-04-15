<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Quote;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuoteController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $quotes            = $project->quotes()->with('client')->latest()->get();
        $paymentMethods    = $this->catValues($project, 'payment_method');
        $paymentConditions = $this->catValues($project, 'payment_condition');
        $portalLayout = request()->routeIs('bixosales.*') ? 'comercial' : 'panel';
        return view('quotes.index', compact('project', 'quotes', 'paymentMethods', 'paymentConditions', 'portalLayout'));
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
            'client_name'       => 'required|string|max:100',
            'client_phone'      => 'nullable|string|max:30',
            'client_email'      => 'nullable|email|max:150',
            'client_doc_type'   => 'nullable|string|max:20',
            'client_doc_number' => 'nullable|string|max:20',
            'client_address'    => 'nullable|string|max:300',
            'client_id'         => 'nullable|integer',
            'notes'             => 'nullable|string',
            'valid_until'       => 'nullable|date',
            'payment_method'    => 'nullable|string|max:80',
            'payment_condition' => 'nullable|string|max:80',
            'items'             => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.price'       => 'required|numeric',
            'items.*.quantity'    => 'required|integer|min:1',
        ]);

        $total = collect($data['items'])->sum(fn($i) => $i['price'] * $i['quantity']);

        $quote = $project->quotes()->create([
            'client_id'         => $data['client_id'] ?? null,
            'client_name'       => $data['client_name'],
            'client_phone'      => $data['client_phone'] ?? null,
            'client_email'      => $data['client_email'] ?? null,
            'client_doc_type'   => $data['client_doc_type'] ?? null,
            'client_doc_number' => $data['client_doc_number'] ?? null,
            'client_address'    => $data['client_address'] ?? null,
            'notes'             => $data['notes'] ?? null,
            'valid_until'       => $data['valid_until'] ?? null,
            'payment_method'    => $data['payment_method'] ?? null,
            'payment_condition' => $data['payment_condition'] ?? null,
            'total'             => $total,
            'status'            => 'draft',
        ]);

        foreach ($data['items'] as $item) {
            $quote->items()->create($item);
        }

        return response()->json(['quote' => $quote->load('items')]);
    }

    public function show(Quote $quote)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($quote->project_id === $project->id, 403);
        $quote->load('items', 'client');
        return response()->json($quote);
    }

    public function update(Request $request, Quote $quote)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($quote->project_id === $project->id, 403);
        $quote->update($request->validate(['status' => 'required|in:draft,sent,accepted,rejected', 'notes' => 'nullable|string']));
        return response()->json(['quote' => $quote]);
    }

    // Genera token único y marca como enviada — devuelve el link del portal
    public function send(Quote $quote)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($quote->project_id === $project->id, 403);
        if (!$quote->token) {
            $quote->token = Str::random(48);
        }
        $quote->sent_at = now();
        $quote->status  = 'sent';
        $quote->save();

        $portalUrl = url('/b/' . $project->slug . '/c/' . $quote->token);
        return response()->json(['ok' => true, 'url' => $portalUrl, 'quote' => $quote]);
    }

    public function destroy(Quote $quote)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($quote->project_id === $project->id, 403);
        $quote->delete();
        return response()->json(['ok' => true]);
    }

    // ── Portal Facturación ────────────────────────────────────────────────────

    private function projectBySlug(string $slug): Project
    {
        return Project::where('slug', $slug)->firstOrFail();
    }

    public function indexPortal(string $slug)
    {
        $project = $this->projectBySlug($slug);
        $quotes  = $project->quotes()->with('client')->latest()->get()->map(fn($q) => [
            'id'          => $q->id,
            'client_name' => $q->client_name,
            'total'       => (float) $q->total,
            'status'      => $q->status,
            'valid_until' => $q->valid_until?->format('Y-m-d'),
            'created_at'  => $q->created_at->format('d/m/Y'),
            'items_count' => $q->items()->count(),
        ]);
        $showBase        = url("/f/{$slug}/cotizaciones");
        $updateBase      = url("/f/{$slug}/cotizaciones");
        $boletaCreateUrl = route('facturacion.boletas.create', $slug);
        $facturaCreateUrl= route('facturacion.facturas.create', $slug);
        return view('facturacion.cotizaciones.index', compact('project', 'quotes', 'showBase', 'updateBase', 'boletaCreateUrl', 'facturaCreateUrl'));
    }

    public function createPortal(string $slug)
    {
        $project   = $this->projectBySlug($slug);
        $clients   = $project->clients()->orderBy('name')->get(['id','name','email','phone']);
        $productos = $project->products()->orderBy('name')->get(['id','name','description','price'])
                        ->map(fn($p) => ['id'=>$p->id,'name'=>$p->name,'desc'=>$p->description??$p->name,'price'=>(float)$p->price]);
        $servicios = $project->services()->orderBy('name')->get(['id','name','description','price'])
                        ->map(fn($s) => ['id'=>$s->id,'name'=>$s->name,'desc'=>$s->description??$s->name,'price'=>(float)$s->price]);
        $catalogo  = $productos->merge($servicios)->values();
        $rucUrl    = route('facturacion.ruc.lookup', $slug);
        return view('facturacion.cotizaciones.create', compact('project', 'clients', 'catalogo', 'rucUrl'));
    }

    public function showPortal(string $slug, $id)
    {
        $project = $this->projectBySlug($slug);
        $quote   = Quote::where('id', $id)->where('project_id', $project->id)->firstOrFail();
        $quote->load('items');
        return response()->json($quote);
    }

    public function storePortal(Request $request, string $slug)
    {
        $project = $this->projectBySlug($slug);
        app()->instance('active_project', $project);
        return $this->store($request);
    }

    public function updatePortal(Request $request, string $slug, $id)
    {
        $project = $this->projectBySlug($slug);
        $quote   = Quote::where('id', $id)->where('project_id', $project->id)->firstOrFail();
        app()->instance('active_project', $project);
        return $this->update($request, $quote);
    }

    public function destroyPortal(string $slug, $id)
    {
        $project = $this->projectBySlug($slug);
        $quote   = Quote::where('id', $id)->where('project_id', $project->id)->firstOrFail();
        app()->instance('active_project', $project);
        return $this->destroy($quote);
    }

    public function editPortal(string $slug, $id)
    {
        $project   = $this->projectBySlug($slug);
        $quote     = Quote::where('id', $id)->where('project_id', $project->id)->with('items')->firstOrFail();
        $clients   = $project->clients()->orderBy('name')->get(['id','name','email','phone']);
        $productos = $project->products()->orderBy('name')->get(['id','name','description','price'])
                        ->map(fn($p) => ['id'=>$p->id,'name'=>$p->name,'desc'=>$p->description??$p->name,'price'=>(float)$p->price]);
        $servicios = $project->services()->orderBy('name')->get(['id','name','description','price'])
                        ->map(fn($s) => ['id'=>$s->id,'name'=>$s->name,'desc'=>$s->description??$s->name,'price'=>(float)$s->price]);
        $catalogo         = $productos->merge($servicios)->values();
        $boletaCreateUrl  = route('facturacion.boletas.create', $slug);
        $facturaCreateUrl = route('facturacion.facturas.create', $slug);
        $updateUrl        = url("/f/{$slug}/cotizaciones/{$id}/full");
        $indexUrl         = route('facturacion.cotizaciones', $slug);
        $rucUrl           = route('facturacion.ruc.lookup', $slug);
        return view('facturacion.cotizaciones.edit', compact(
            'project','quote','clients','catalogo',
            'boletaCreateUrl','facturaCreateUrl','updateUrl','indexUrl','rucUrl'
        ));
    }

    public function updateFullPortal(Request $request, string $slug, $id)
    {
        $project = $this->projectBySlug($slug);
        $quote   = Quote::where('id', $id)->where('project_id', $project->id)->firstOrFail();

        $data = $request->validate([
            'client_name'         => 'required|string|max:100',
            'client_phone'        => 'nullable|string|max:30',
            'client_email'        => 'nullable|email|max:150',
            'client_doc_type'     => 'nullable|string|max:20',
            'client_doc_number'   => 'nullable|string|max:20',
            'client_address'      => 'nullable|string|max:300',
            'notes'               => 'nullable|string',
            'valid_until'         => 'nullable|date',
            'payment_method'      => 'nullable|string|max:80',
            'payment_condition'   => 'nullable|string|max:80',
            'items'               => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.price'       => 'required|numeric',
            'items.*.quantity'    => 'required|numeric|min:0.001',
        ]);

        $total = collect($data['items'])->sum(fn($i) => $i['price'] * $i['quantity']);

        $quote->update([
            'client_name'       => $data['client_name'],
            'client_phone'      => $data['client_phone'] ?? null,
            'client_email'      => $data['client_email'] ?? null,
            'client_doc_type'   => $data['client_doc_type'] ?? null,
            'client_doc_number' => $data['client_doc_number'] ?? null,
            'client_address'    => $data['client_address'] ?? null,
            'notes'             => $data['notes'] ?? null,
            'valid_until'       => $data['valid_until'] ?? null,
            'payment_method'    => $data['payment_method'] ?? null,
            'payment_condition' => $data['payment_condition'] ?? null,
            'total'             => $total,
        ]);

        $quote->items()->delete();
        foreach ($data['items'] as $item) {
            $quote->items()->create($item);
        }

        return response()->json(['ok' => true, 'quote' => $quote->load('items')]);
    }

    public function convertirPortal(Request $request, string $slug, $id)
    {
        $project = $this->projectBySlug($slug);
        $quote   = Quote::where('id', $id)->where('project_id', $project->id)->firstOrFail();
        $quote->load('items');

        $docType = $request->input('type', 'boleta');
        $defaultSerie = $docType === 'factura'
            ? ($project->setting('serie_factura') ?? 'FFF1')
            : ($project->setting('serie_boleta')  ?? 'BBB1');

        $igvRate  = 0.18;
        $subtotal = 0;
        $igvTotal = 0;
        $items    = [];

        foreach ($quote->items as $qi) {
            $qty       = (float) $qi->quantity;
            $lineTotal = round($qty * (float) $qi->price, 2);
            $lineSub   = round($lineTotal / 1.18, 2);
            $lineIgv   = $lineTotal - $lineSub;
            $subtotal += $lineSub;
            $igvTotal += $lineIgv;
            $items[]   = [
                'description' => $qi->description,
                'unit'        => 'NIU',
                'quantity'    => $qty,
                'unit_price'  => (float) $qi->price,
                'igv_amount'  => round($lineIgv, 2),
                'total'       => round($lineTotal, 2),
            ];
        }

        $correlativo = Invoice::nextCorrelativo($project->id, $docType, $defaultSerie);
        $numero      = Invoice::buildNumero($defaultSerie, $correlativo);

        $invoice = $project->invoices()->create([
            'quote_id'            => $quote->id,
            'type'                => $docType,
            'serie'               => $defaultSerie,
            'correlativo'         => $correlativo,
            'numero'              => $numero,
            'issue_date'          => now()->toDateString(),
            'emisor_razon_social' => $project->setting('razon_social') ?? $project->name,
            'emisor_ruc'          => $project->setting('ruc'),
            'emisor_direccion'    => $project->address,
            'client_name'         => $quote->client_name,
            'client_phone'        => $quote->client_phone,
            'client_email'        => $quote->client_email,
            'client_doc_type'     => $quote->client_doc_type,
            'client_doc_number'   => $quote->client_doc_number,
            'client_address'      => $quote->client_address,
            'subtotal'            => round($subtotal, 2),
            'igv'                 => round($igvTotal, 2),
            'total'               => round($subtotal + $igvTotal, 2),
            'currency'            => $project->setting('currency') ?? 'PEN',
            'igv_included'        => true,
            'payment_method'      => $quote->payment_method,
            'status'              => 'issued',
            'notes'               => $quote->notes,
        ]);

        foreach ($items as $item) {
            $invoice->items()->create($item);
        }

        $quote->update(['status' => 'accepted']);

        $redirectUrl = $docType === 'factura'
            ? route('facturacion.facturas', $slug)
            : route('facturacion.boletas', $slug);

        return response()->json([
            'ok'          => true,
            'invoice_id'  => $invoice->id,
            'numero'      => $invoice->numero,
            'redirect'    => $redirectUrl,
        ]);
    }
}
