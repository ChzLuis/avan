<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Models\Client;
use App\Jobs\SendInvoiceToSunat;
use App\Support\NubefactService;
use App\Support\LineMath;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $invoices = $project->invoices()
            ->with('client')
            ->latest()
            ->get()
            ->map(fn($inv) => [
                'id'          => $inv->id,
                'numero'      => $inv->numero,
                'type'        => $inv->type,
                'type_label'  => $inv->getTypeLabel(),
                'client_name' => $inv->client_name,
                'total'       => (float) $inv->total,
                'status'      => $inv->status,
                'status_label'=> $inv->getStatusLabel(),
                'issue_date'  => $inv->issue_date?->format('Y-m-d'),
                'sunat_status'=> $inv->sunat_status,
            ]);

        $portalLayout = request()->routeIs('bixosales.*') ? 'comercial' : 'panel';
        $serieFactura = $project->setting('serie_factura') ?? 'F001';
        $serieBoleta  = $project->setting('serie_boleta')  ?? 'B001';
        return view('invoices.index', compact('project', 'invoices', 'portalLayout', 'serieFactura', 'serieBoleta'));
    }

    public function show(Invoice $invoice)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($invoice->project_id === $project->id, 403);
        $invoice->load('items.product', 'order', 'quote', 'client');

        $data = [
            'id'                   => $invoice->id,
            'numero'               => $invoice->numero,
            'type'                 => $invoice->type,
            'type_label'           => $invoice->getTypeLabel(),
            'serie'                => $invoice->serie,
            'correlativo'          => $invoice->correlativo,
            'status'               => $invoice->status,
            'status_label'         => $invoice->getStatusLabel(),
            'issue_date'           => $invoice->issue_date?->format('Y-m-d'),
            'due_date'             => $invoice->due_date?->format('Y-m-d'),
            'emisor_razon_social'  => $invoice->emisor_razon_social,
            'emisor_ruc'           => $invoice->emisor_ruc,
            'emisor_direccion'     => $invoice->emisor_direccion,
            'client_name'         => $invoice->client_name,
            'client_phone'        => $invoice->client_phone,
            'client_email'        => $invoice->client_email,
            'client_doc_type'     => $invoice->client_doc_type,
            'client_doc_number'   => $invoice->client_doc_number,
            'client_address'      => $invoice->client_address,
            'subtotal'             => (float) $invoice->subtotal,
            'igv'                  => (float) $invoice->igv,
            'total'                => (float) $invoice->total,
            'currency'             => $invoice->currency,
            'igv_included'         => $invoice->igv_included,
            'payment_method'       => $invoice->payment_method,
            'paid_at'              => $invoice->paid_at?->format('d/m/Y H:i'),
            'notes'                => $invoice->notes,
            'sunat_status'         => $invoice->sunat_status,
            'sunat_error'          => $invoice->sunat_error,
            'items'                => $invoice->items->map(fn($it) => [
                'id'          => $it->id,
                'description' => $it->description,
                'unit'        => $it->unit,
                'quantity'    => (float) $it->quantity,
                'unit_price'  => (float) $it->unit_price,
                'discount'    => (float) ($it->discount ?? 0),
                'igv_amount'  => (float) $it->igv_amount,
                'total'       => (float) $it->total,
            ])->values()->all(),
        ];

        return response()->json($data);
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $data = $request->validate([
            'type'                => 'required|in:boleta,factura,nota_credito,nota_debito',
            'serie'               => 'nullable|string|max:10',
            'correlativo'         => 'nullable|integer|min:1',
            // SUNAT rechaza envios con fecha de emision de mas de 3 dias
            // calendario, y una fecha futura tampoco entra. Bloquearlo aqui
            // evita emitir un comprobante condenado al rechazo.
            'issue_date'          => 'nullable|date|after_or_equal:'.now()->subDays(3)->toDateString().'|before_or_equal:'.now()->toDateString(),
            'due_date'            => 'nullable|date',
            'client_name'        => 'required|string|max:200',
            'client_phone'       => 'nullable|string|max:30',
            'client_email'       => 'nullable|email|max:150',
            'client_doc_type'    => 'nullable|in:DNI,RUC,CE,pasaporte',
            'client_doc_number'  => 'nullable|string|max:15',
            'client_address'     => 'nullable|string|max:300',
            'payment_method'      => 'nullable|string|max:80',
            'notes'               => 'nullable|string',
            'igv_included'        => 'nullable|boolean',
            'currency'            => 'nullable|string|size:3',
            'quote_id'            => 'nullable|integer',
            'items'               => 'required|array|min:1',
            'items.*.description' => 'required|string|max:300',
            'items.*.unit'        => 'nullable|string|max:20',
            'items.*.quantity'    => 'required|numeric|min:0.001',
            'items.*.unit_price'  => 'required|numeric|min:0',
            // F4: el descuento por linea ya se puede facturar.
            'items.*.discount'    => 'nullable|numeric|decimal:0,2|min:0|max:100',
        ]);

        $type   = $data['type'];
        $defaultSerie = $type === 'factura'
            ? ($project->setting('serie_factura') ?? 'FFF1')
            : ($project->setting('serie_boleta')  ?? 'BBB1');
        $serie  = $data['serie'] ?? $defaultSerie;
        $igvIncluded = $request->boolean('igv_included', true);

        // F4: importes fiscales en CENTAVOS ENTEROS.
        //
        // Esto se calculaba con flotantes —`round($qty*$price,2)` y sumas
        // acumuladas— justo en el dinero de mas consecuencias del sistema,
        // mientras el resto del proyecto usa LineMath. Un comprobante que no
        // cuadra por un centimo es un problema con SUNAT, no una molestia.
        //
        // El descuento por linea ya viaja hasta aqui: `unit_price` es el precio
        // de lista y `total` el neto, de modo que
        // `unit_price x cantidad x (1 - descuento)` = `total` de forma exacta.
        $subtotalCents = 0;
        $igvTotalCents = 0;
        $itemsData     = [];

        foreach ($data['items'] as $item) {
            // OJO: `quantity` es decimal(10,3) —se factura 2.5 kg— asi que NO
            // se puede castear a entero. Se trabaja en milesimas para que la
            // linea siga siendo aritmetica entera y no vuelva el flotante.
            $qty       = (float) $item['quantity'];
            $qtyMilli  = (int) round($qty * 1000);
            $precio    = LineMath::canon((string) $item['unit_price']);
            $descuento = LineMath::canon((string) ($item['discount'] ?? 0));
            $precioCents = LineMath::toCents($precio);
            $bp          = LineMath::toBasisPoints($descuento);

            // precioCents x (qtyMilli/1000) x (1 - bp/10000), half-up sobre 10^7.
            $lineaCents = intdiv($precioCents * $qtyMilli * (10000 - $bp) + 5_000_000, 10_000_000);

            if ($igvIncluded) {
                // El precio YA lleva IGV: la base es total/1.18, half-up exacto.
                $subCents = intdiv($lineaCents * 100 + 59, 118);
                $igvCents = $lineaCents - $subCents;
                $totalCents = $lineaCents;
            } else {
                $subCents   = $lineaCents;
                $igvCents   = intdiv($subCents * 18 + 50, 100);
                $totalCents = $subCents + $igvCents;
            }

            $subtotalCents += $subCents;
            $igvTotalCents += $igvCents;
            $itemsData[] = [
                'description' => $item['description'],
                'unit'        => $item['unit'] ?? 'NIU',
                'quantity'    => $qty,
                'unit_price'  => $precio,
                'discount'    => $descuento,
                'igv_amount'  => LineMath::format($igvCents),
                'total'       => LineMath::format($totalCents),
            ];
        }

        // El total del comprobante es la SUMA de sus lineas, no un recalculo:
        // asi el documento cuadra consigo mismo linea por linea.
        $subtotal = LineMath::format($subtotalCents);
        $igvTotal = LineMath::format($igvTotalCents);
        $totalDoc = LineMath::format($subtotalCents + $igvTotalCents);

        $correlativo = $request->filled('correlativo')
            ? (int) $request->input('correlativo')
            : Invoice::nextCorrelativo($project->id, $type, $serie);
        $numero = Invoice::buildNumero($serie, $correlativo);

        $invoice = $project->invoices()->create([
            'type'                => $type,
            'serie'               => $serie,
            'correlativo'         => $correlativo,
            'numero'              => $numero,
            'issue_date'          => $data['issue_date'] ?? now()->toDateString(),
            'due_date'            => $data['due_date'] ?? null,
            'emisor_razon_social' => $project->setting('razon_social') ?? $project->name,
            'emisor_ruc'          => $project->setting('ruc'),
            'emisor_direccion'    => $project->address,
            'client_name'        => $data['client_name'],
            'client_phone'       => $data['client_phone'] ?? null,
            'client_email'       => $data['client_email'] ?? null,
            'client_doc_type'    => $data['client_doc_type'] ?? null,
            'client_doc_number'  => $data['client_doc_number'] ?? null,
            'client_address'     => $data['client_address'] ?? null,
            'subtotal'            => $subtotal,
            'igv'                 => $igvTotal,
            'total'               => $totalDoc,
            'currency'            => $data['currency'] ?? ($project->setting('currency') ?? 'PEN'),
            'igv_included'        => $igvIncluded,
            'payment_method'      => $data['payment_method'] ?? null,
            'status'              => 'issued',
            'notes'               => $data['notes'] ?? null,
            'quote_id'            => $data['quote_id'] ?? null,
        ]);

        foreach ($itemsData as $item) {
            $invoice->items()->create($item);
        }

        return response()->json(['invoice' => $invoice->load('items')]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($invoice->project_id === $project->id, 403);
        $data = $request->validate([
            'status'         => 'nullable|in:draft,issued,sent,cancelled',
            'payment_method' => 'nullable|string|max:80',
            'paid_at'        => 'nullable|date',
            'notes'          => 'nullable|string',
            'due_date'       => 'nullable|date',
        ]);
        $invoice->update($data);
        return response()->json(['ok' => true]);
    }

    public function destroy(Invoice $invoice)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($invoice->project_id === $project->id, 403);
        abort_if($invoice->sunat_status === 'accepted', 403, 'No se puede eliminar una factura aceptada por SUNAT.');
        $invoice->delete();
        return response()->json(['ok' => true]);
    }

    public function sendSunat(Invoice $invoice)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($invoice->project_id === $project->id, 403);
        abort_if($invoice->sunat_status === 'accepted', 403, 'Este comprobante ya fue aceptado por SUNAT.');

        $invoice->update(['sunat_status' => 'pending']);
        SendInvoiceToSunat::dispatch($invoice->id);

        return response()->json(['ok' => true, 'message' => 'Enviando a SUNAT en segundo plano...']);
    }

    public function sendSunatPortal(string $slug, $invoiceId)
    {
        $project = $this->projectBySlug($slug);
        app()->instance('active_project', $project);

        $invoice = Invoice::where('id', $invoiceId)
            ->where('project_id', $project->id)
            ->firstOrFail();

        abort_if($invoice->sunat_status === 'accepted', 403, 'Este comprobante ya fue aceptado por SUNAT.');

        $invoice->update(['sunat_status' => 'pending']);
        SendInvoiceToSunat::dispatch($invoice->id);

        return response()->json(['ok' => true, 'message' => 'Enviando a SUNAT en segundo plano...']);
    }

    public function pdf(Invoice $invoice)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($invoice->project_id === $project->id, 403);
        $invoice->load('items.product');
        return view('invoices.pdf', compact('project', 'invoice'));
    }

    // ── Portal Facturación ────────────────────────────────────────────────────

    /** La misma consulta, desde el panel: el negocio sale de la sesion. */
    public function lookupRucPanel(Request $request)
    {
        /** @var Project $project */
        $project = app('active_project');

        return $this->consultarRuc($project, (string) $request->get('ruc', ''));
    }

    public function lookupRuc(Request $request, string $slug)
    {
        $project = $this->projectBySlug($slug);

        return $this->consultarRuc($project, (string) $request->get('ruc', ''));
    }

    /** El nucleo de la consulta: un solo sitio para las dos puertas. */
    private function consultarRuc(Project $project, string $rucCrudo)
    {
        $ruc = preg_replace('/\D/', '', $rucCrudo);

        if (strlen($ruc) !== 11) {
            return response()->json(['ok' => false, 'message' => 'RUC debe tener 11 dígitos.']);
        }

        $token = $project->setting('apiperu_token');
        if (!$token) {
            return response()->json(['ok' => false, 'message' => 'Configura el token de APIPERU en Ajustes → Facturación.']);
        }

        $url = "https://dniruc.apisperu.com/api/v1/ruc/{$ruc}?token={$token}";
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return response()->json(['ok' => false, 'message' => 'Error de conexión: ' . $err]);
        }

        $data = json_decode($body, true);

        if (!($data['success'] ?? false)) {
            return response()->json(['ok' => false, 'message' => $data['message'] ?? 'RUC no encontrado.']);
        }

        return response()->json([
            'ok'          => true,
            'razon_social'=> $data['razonSocial'] ?? '',
            'direccion'   => $data['direccion']   ?? '',
        ]);
    }

    private function projectBySlug(string $slug): Project
    {
        return Project::where('slug', $slug)->firstOrFail();
    }

    public function indexBoletasPortal(string $slug)
    {
        return $this->invoiceIndexPortal($slug, 'boleta');
    }

    public function indexFacturasPortal(string $slug)
    {
        return $this->invoiceIndexPortal($slug, 'factura');
    }

    public function createBoletaPortal(string $slug)
    {
        return $this->invoiceCreatePortal($slug, 'boleta');
    }

    public function createFacturaPortal(string $slug)
    {
        return $this->invoiceCreatePortal($slug, 'factura');
    }

    private function invoiceCreatePortal(string $slug, string $docType)
    {
        $project      = $this->projectBySlug($slug);
        $clients      = $project->clients()->orderBy('name')->get(['id','name','email','phone']);
        $productos    = $project->products()->orderBy('name')
                            ->get(['id','name','description','price'])->map(fn($p) => [
                                'id'    => $p->id,
                                'name'  => $p->name,
                                'desc'  => $p->description ?? $p->name,
                                'price' => (float) $p->price,
                            ]);
        $servicios    = $project->services()->orderBy('name')
                            ->get(['id','name','description','price'])->map(fn($s) => [
                                'id'    => $s->id,
                                'name'  => $s->name,
                                'desc'  => $s->description ?? $s->name,
                                'price' => (float) $s->price,
                            ]);
        $catalogo     = $productos->merge($servicios)->values();
        $serieFactura = $project->setting('serie_factura') ?? 'FFF1';
        $serieBoleta  = $project->setting('serie_boleta')  ?? 'BBB1';
        $emisorRuc    = $project->setting('ruc') ?? '';
        $emisorRazon  = $project->setting('razon_social') ?? $project->name;
        $emisorDir    = $project->address ?? '';

        // Pre-cargar datos de cotización si viene de una conversión
        $fromQuote = null;
        if ($quoteId = request()->get('from_quote')) {
            $quote = \App\Models\Quote::where('id', $quoteId)
                ->where('project_id', $project->id)
                ->with('items')
                ->first();
            if ($quote) {
                $fromQuote = [
                    'id'                => $quote->id,
                    'client_name'       => $quote->client_name,
                    'client_phone'      => $quote->client_phone ?? '',
                    'client_email'      => $quote->client_email ?? '',
                    'client_doc_type'   => $quote->client_doc_type ?? '',
                    'client_doc_number' => $quote->client_doc_number ?? '',
                    'client_address'    => $quote->client_address ?? '',
                    'notes'           => $quote->notes ?? '',
                    'payment_method'  => $quote->payment_method ?? '',
                    'items'           => $quote->items->map(function($i) {
                        return [
                            'desc'  => $i->description,
                            'unit'  => 'NIU',
                            'qty'   => (float) $i->quantity,
                            'price' => (float) $i->price,
                        ];
                    })->values()->all(),
                ];
            }
        }

        return view('facturacion.facturas.create', compact(
            'project','clients','catalogo','serieFactura','serieBoleta','docType',
            'emisorRuc','emisorRazon','emisorDir','fromQuote'
        ));
    }

    private function invoiceIndexPortal(string $slug, string $docType)
    {
        $project  = $this->projectBySlug($slug);
        $invoices = $project->invoices()
            ->where('type', $docType)
            ->with('client')->latest()->get()
            ->map(fn($inv) => [
                'id'          => $inv->id,
                'numero'      => $inv->numero,
                'type'        => $inv->type,
                'type_label'  => $inv->getTypeLabel(),
                'client_name' => $inv->client_name,
                'total'       => (float) $inv->total,
                'status'      => $inv->status,
                'status_label'=> $inv->getStatusLabel(),
                'issue_date'  => $inv->issue_date?->format('Y-m-d'),
                'sunat_status'=> $inv->sunat_status,
            ]);
        $clients      = $project->clients()->orderBy('name')->get(['id','name','email','phone']);
        $serieFactura = $project->setting('serie_factura') ?? 'FFF1';
        $serieBoleta  = $project->setting('serie_boleta')  ?? 'BBB1';
        return view('facturacion.facturas.index', compact('project', 'invoices', 'clients', 'serieFactura', 'serieBoleta', 'docType'));
    }

    /** @deprecated kept for compatibility */
    public function indexPortal(string $slug)
    {
        return $this->invoiceIndexPortal($slug, 'boleta');
    }

    public function showPortal(string $slug, Invoice $invoice)
    {
        $project = $this->projectBySlug($slug);
        abort_unless($invoice->project_id === $project->id, 403);
        $invoice->load('items.product', 'order', 'quote', 'client');
        return response()->json($this->invoiceData($invoice));
    }

    public function storePortal(\Illuminate\Http\Request $request, string $slug)
    {
        $project = $this->projectBySlug($slug);
        app()->instance('active_project', $project);
        return $this->store($request);
    }

    public function updatePortal(\Illuminate\Http\Request $request, string $slug, Invoice $invoice)
    {
        $project = $this->projectBySlug($slug);
        app()->instance('active_project', $project);
        return $this->update($request, $invoice);
    }

    public function destroyPortal(string $slug, Invoice $invoice)
    {
        $project = $this->projectBySlug($slug);
        app()->instance('active_project', $project);
        return $this->destroy($invoice);
    }

    public function pdfPortal(string $slug, Invoice $invoice)
    {
        $project = $this->projectBySlug($slug);
        abort_unless($invoice->project_id === $project->id, 403);
        $invoice->load('items.product');
        return view('invoices.pdf', compact('project', 'invoice'));
    }

    private function invoiceData(Invoice $invoice): array
    {
        return [
            'id'                   => $invoice->id,
            'numero'               => $invoice->numero,
            'type'                 => $invoice->type,
            'type_label'           => $invoice->getTypeLabel(),
            'serie'                => $invoice->serie,
            'correlativo'          => $invoice->correlativo,
            'status'               => $invoice->status,
            'status_label'         => $invoice->getStatusLabel(),
            'issue_date'           => $invoice->issue_date?->format('Y-m-d'),
            'due_date'             => $invoice->due_date?->format('Y-m-d'),
            'emisor_razon_social'  => $invoice->emisor_razon_social,
            'emisor_ruc'           => $invoice->emisor_ruc,
            'emisor_direccion'     => $invoice->emisor_direccion,
            'client_name'          => $invoice->client_name,
            'client_phone'         => $invoice->client_phone,
            'client_email'         => $invoice->client_email,
            'client_doc_type'      => $invoice->client_doc_type,
            'client_doc_number'    => $invoice->client_doc_number,
            'client_address'       => $invoice->client_address,
            'subtotal'             => (float) $invoice->subtotal,
            'igv'                  => (float) $invoice->igv,
            'total'                => (float) $invoice->total,
            'currency'             => $invoice->currency,
            'payment_method'       => $invoice->payment_method,
            'notes'                => $invoice->notes,
            'sunat_status'         => $invoice->sunat_status,
            'sunat_sent_at'        => $invoice->sunat_sent_at?->format('d/m/Y H:i'),
            'sunat_hash'           => $invoice->sunat_hash,
            'sunat_error'          => $invoice->sunat_error,
            'quote_id'             => $invoice->quote_id,
            'items'                => $invoice->items->map(fn($it) => [
                'id'          => $it->id,
                'description' => $it->description,
                'unit'        => $it->unit,
                'quantity'    => (float) $it->quantity,
                'unit_price'  => (float) $it->unit_price,
                'igv_amount'  => (float) ($it->igv_amount ?? 0),
                'total'       => (float) $it->total,
            ])->values()->all(),
        ];
    }
}
