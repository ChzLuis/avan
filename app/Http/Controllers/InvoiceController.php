<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Models\Client;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Project $project)
    {
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
                'issue_date'  => $inv->issue_date?->format('d/m/Y'),
                'sunat_status'=> $inv->sunat_status,
            ]);

        return view('invoices.index', compact('project', 'invoices'));
    }

    public function show(Project $project, Invoice $invoice)
    {
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
                'igv_amount'  => (float) $it->igv_amount,
                'total'       => (float) $it->total,
            ])->values()->all(),
        ];

        return response()->json($data);
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'type'                => 'required|in:boleta,factura,nota_credito,nota_debito',
            'serie'               => 'nullable|string|max:10',
            'issue_date'          => 'nullable|date',
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
            'items'               => 'required|array|min:1',
            'items.*.description' => 'required|string|max:300',
            'items.*.unit'        => 'nullable|string|max:20',
            'items.*.quantity'    => 'required|numeric|min:0.001',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $type   = $data['type'];
        $serie  = $data['serie'] ?? ($type === 'factura' ? 'F001' : 'B001');
        $igvIncluded = $request->boolean('igv_included', true);

        // Calcular importes
        $igvRate   = 0.18;
        $subtotal  = 0;
        $igvTotal  = 0;
        $itemsData = [];

        foreach ($data['items'] as $item) {
            $qty   = (float) $item['quantity'];
            $price = (float) $item['unit_price'];
            $lineTotal = round($qty * $price, 2);

            if ($igvIncluded) {
                $lineSub = round($lineTotal / 1.18, 2);
                $lineIgv = $lineTotal - $lineSub;
            } else {
                $lineSub = $lineTotal;
                $lineIgv = round($lineTotal * $igvRate, 2);
                $lineTotal += $lineIgv;
            }

            $subtotal += $lineSub;
            $igvTotal += $lineIgv;
            $itemsData[] = [
                'description' => $item['description'],
                'unit'        => $item['unit'] ?? 'NIU',
                'quantity'    => $qty,
                'unit_price'  => $price,
                'igv_amount'  => round($lineIgv, 2),
                'total'       => round($lineTotal, 2),
            ];
        }

        $correlativo = Invoice::nextCorrelativo($project->id, $type, $serie);
        $numero      = Invoice::buildNumero($serie, $correlativo);

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
            'subtotal'            => round($subtotal, 2),
            'igv'                 => round($igvTotal, 2),
            'total'               => round($subtotal + $igvTotal, 2),
            'currency'            => $data['currency'] ?? ($project->setting('currency') ?? 'PEN'),
            'igv_included'        => $igvIncluded,
            'payment_method'      => $data['payment_method'] ?? null,
            'status'              => 'issued',
            'notes'               => $data['notes'] ?? null,
        ]);

        foreach ($itemsData as $item) {
            $invoice->items()->create($item);
        }

        return response()->json(['invoice' => $invoice->load('items')]);
    }

    public function update(Request $request, Project $project, Invoice $invoice)
    {
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

    public function destroy(Project $project, Invoice $invoice)
    {
        abort_unless($invoice->project_id === $project->id, 403);
        abort_if($invoice->sunat_status === 'accepted', 403, 'No se puede eliminar una factura aceptada por SUNAT.');
        $invoice->delete();
        return response()->json(['ok' => true]);
    }

    public function pdf(Project $project, Invoice $invoice)
    {
        abort_unless($invoice->project_id === $project->id, 403);
        $invoice->load('items.product');
        return view('invoices.pdf', compact('project', 'invoice'));
    }
}
