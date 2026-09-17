<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use App\Jobs\SendInvoiceToSunat;
use App\Models\Invoice;
use App\Models\Project;
use App\Support\LineMath;
use App\Support\Sunat\Catalogos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Corregir un comprobante que SUNAT ya aceptó.
 *
 * Hasta ahora la única salida era borrar la fila: el comprobante desaparecía
 * del sistema y seguía vivo en SUNAT, con su IGV declarado. Las dos formas
 * legales de deshacerlo son:
 *
 *   - la NOTA DE CRÉDITO, que anula o rebaja el importe y vale siempre;
 *   - la COMUNICACIÓN DE BAJA, más corta pero solo dentro del plazo y solo
 *     para facturas (una boleta se corrige con nota o con resumen de baja).
 *
 * La nota de débito es la operación contraria: sube el importe (intereses de
 * mora, penalidades, un cargo que faltaba).
 */
class NotaController extends Controller
{
    /** Lo que el formulario necesita saber para ofrecer una nota. */
    public function opciones(Invoice $invoice)
    {
        $this->soloDeMiNegocio($invoice);

        return response()->json([
            'puede'            => $invoice->admiteNota(),
            'razon'            => $invoice->admiteNota() ? null : $this->porQueNoAdmiteNota($invoice),
            'afecta'           => [
                'numero' => $invoice->numero,
                'tipo'   => $invoice->getTypeLabel(),
                'total'  => (string) $invoice->total,
            ],
            'motivos_credito'  => Catalogos::MOTIVOS_NOTA_CREDITO,
            'motivos_debito'   => Catalogos::MOTIVOS_NOTA_DEBITO,
            'items'            => $invoice->items->map(fn ($i) => [
                'description' => $i->description,
                'unit'        => $i->unit,
                'quantity'    => (float) $i->quantity,
                'unit_price'  => (string) $i->unit_price,
            ])->values(),
        ]);
    }

    /**
     * Emite la nota sobre el comprobante indicado.
     *
     * Sin líneas propias se copia el documento entero, que es el caso normal:
     * anular una venta es devolver todo lo que se facturó.
     */
    public function store(Request $request, Invoice $invoice)
    {
        $project = $this->soloDeMiNegocio($invoice);

        abort_unless($invoice->admiteNota(), 422, $this->porQueNoAdmiteNota($invoice));

        $tipo = $request->input('type', 'nota_credito');

        $data = $request->validate([
            'type'               => ['required', Rule::in(['nota_credito', 'nota_debito'])],
            'motivo_codigo'      => ['required', 'string', 'size:2', Rule::in(array_keys(
                $tipo === 'nota_debito' ? Catalogos::MOTIVOS_NOTA_DEBITO : Catalogos::MOTIVOS_NOTA_CREDITO
            ))],
            'motivo_descripcion' => ['nullable', 'string', 'max:250'],
            'items'                     => ['nullable', 'array', 'min:1'],
            'items.*.description'       => ['required_with:items', 'string', 'max:300'],
            'items.*.unit'              => ['nullable', 'string', 'max:30'],
            'items.*.quantity'          => ['required_with:items', 'numeric', 'gt:0'],
            'items.*.unit_price'        => ['required_with:items', 'numeric', 'min:0'],
        ]);

        $lineas = $data['items'] ?? $invoice->items->map(fn ($i) => [
            'description' => $i->description,
            'unit'        => $i->unit,
            'quantity'    => (float) $i->quantity,
            'unit_price'  => (string) $i->unit_price,
        ])->all();

        /* La nota hereda si el precio del original llevaba IGV incluido: si no,
           una nota que anula una boleta de S/ 118 devolvería S/ 139.24. */
        $igvIncluded = (bool) $invoice->igv_included;

        [$subtotal, $igvTotal, $total, $itemsData] = $this->importes($lineas, $igvIncluded);

        $nota = DB::transaction(function () use ($project, $invoice, $data, $subtotal, $igvTotal, $total, $itemsData, $igvIncluded) {
            $serie = $this->serieDeNota($project, $invoice, $data['type']);
            [$correlativo, $numero] = Invoice::emitirNumero($project->id, $data['type'], $serie);

            $nota = $project->invoices()->create([
                'type'        => $data['type'],
                'serie'       => $serie,
                'correlativo' => $correlativo,
                'numero'      => $numero,

                // A qué documento afecta: sin esto SUNAT la rechaza.
                'afecta_invoice_id'  => $invoice->id,
                'afecta_tipo'        => $invoice->codigoSunat(),
                'afecta_numero'      => $invoice->numero,
                'motivo_codigo'      => $data['motivo_codigo'],
                // Sin descripción propia se pone la del catálogo: el campo es
                // obligatorio en el XML y `validate` no devuelve las claves que
                // no venían en la petición.
                'motivo_descripcion' => ($data['motivo_descripcion'] ?? null)
                    ?: ($data['type'] === 'nota_debito'
                        ? Catalogos::MOTIVOS_NOTA_DEBITO[$data['motivo_codigo']]
                        : Catalogos::MOTIVOS_NOTA_CREDITO[$data['motivo_codigo']]),

                // El receptor es el mismo: una nota no cambia de cliente.
                'client_id'         => $invoice->client_id,
                'client_name'       => $invoice->client_name,
                'client_phone'      => $invoice->client_phone,
                'client_email'      => $invoice->client_email,
                'client_doc_type'   => $invoice->client_doc_type,
                'client_doc_number' => $invoice->client_doc_number,
                'client_address'    => $invoice->client_address,

                'emisor_razon_social' => $invoice->emisor_razon_social,
                'emisor_ruc'          => $invoice->emisor_ruc,
                'emisor_direccion'    => $invoice->emisor_direccion,

                'subtotal'     => $subtotal,
                'igv'          => $igvTotal,
                'total'        => $total,
                'currency'     => $invoice->currency,
                'igv_included' => $igvIncluded,
                'issue_date'   => now()->toDateString(),
                'status'       => 'issued',
                'order_id'     => $invoice->order_id,
                'quote_id'     => $invoice->quote_id,
                'created_by'   => auth()->id(),
            ]);

            foreach ($itemsData as $item) {
                $nota->items()->create($item);
            }

            return $nota;
        });

        // Se manda sola: una nota emitida y no enviada no corrige nada.
        $nota->update(['sunat_status' => 'pending']);
        SendInvoiceToSunat::dispatch($nota->id);

        return response()->json([
            'ok'      => true,
            'nota'    => $nota->load('items'),
            'message' => $nota->getTypeLabel().' '.$nota->numero.' emitida. Enviando a SUNAT...',
        ]);
    }

    /**
     * Comunicación de baja: deja sin efecto el comprobante ante SUNAT.
     *
     * No borra nada. El comprobante se queda en el sistema con su número —el
     * correlativo no se reutiliza nunca— marcado como dado de baja.
     */
    public function darDeBaja(Request $request, Invoice $invoice)
    {
        $this->soloDeMiNegocio($invoice);

        abort_unless(
            $invoice->sePuedeDarDeBaja(),
            422,
            match (true) {
                $invoice->sunat_status !== 'accepted' => 'Solo se da de baja un comprobante que SUNAT ya aceptó. Este todavía no lo está: bórralo o vuelve a enviarlo.',
                $invoice->esNota() => 'Una nota no se da de baja: se corrige emitiendo otra sobre el comprobante original.',
                default => 'Este comprobante ya tiene una baja en curso o aceptada.',
            }
        );

        $data = $request->validate([
            'motivo' => ['required', 'string', 'min:3', 'max:250'],
        ]);

        $invoice->update([
            'baja_estado' => 'pending',
            'baja_motivo' => $data['motivo'],
            'baja_error'  => null,
            'status'      => 'cancelled',
        ]);

        \App\Jobs\DarDeBajaEnSunat::dispatch($invoice->id);

        return response()->json([
            'ok'      => true,
            'message' => $invoice->type === 'boleta'
                ? 'Enviando a SUNAT el resumen diario que anula '.$invoice->numero.'. El resultado llega en unos minutos.'
                : 'Comunicando la baja de '.$invoice->numero.' a SUNAT...',
        ]);
    }

    /**
     * Los importes de la nota, con la misma aritmética entera del resto del
     * sistema: un comprobante que no cuadra por un céntimo es un problema con
     * SUNAT, no una molestia.
     *
     * @return array{0:string,1:string,2:string,3:array}
     */
    private function importes(array $lineas, bool $igvIncluded): array
    {
        $subtotalCents = 0;
        $igvTotalCents = 0;
        $itemsData     = [];

        foreach ($lineas as $item) {
            $qty         = (float) $item['quantity'];
            $qtyMilli    = (int) round($qty * 1000);
            $precio      = LineMath::canon((string) $item['unit_price']);
            $precioCents = LineMath::toCents($precio);

            $lineaCents = intdiv($precioCents * $qtyMilli + 500, 1000);

            if ($igvIncluded) {
                $subCents   = intdiv($lineaCents * 100 + 59, 118);
                $igvCents   = $lineaCents - $subCents;
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
                'igv_amount'  => LineMath::format($igvCents),
                'total'       => LineMath::format($totalCents),
            ];
        }

        return [
            LineMath::format($subtotalCents),
            LineMath::format($igvTotalCents),
            LineMath::format($subtotalCents + $igvTotalCents),
            $itemsData,
        ];
    }

    /**
     * La serie de la nota.
     *
     * SUNAT exige que empiece por la misma letra que el documento afectado: una
     * nota sobre factura va con serie F, una sobre boleta con serie B. Por eso
     * el valor por defecto es la serie del propio comprobante afectado y no una
     * serie fija, que acabaría emitiendo notas B sobre facturas F.
     */
    private function serieDeNota(Project $project, Invoice $invoice, string $tipo): string
    {
        $ajuste = $tipo === 'nota_debito' ? 'serie_nota_debito' : 'serie_nota_credito';
        $serie  = trim((string) $project->setting($ajuste));
        $letra  = strtoupper(substr((string) $invoice->serie, 0, 1)) ?: 'F';

        // Una serie configurada solo vale si empieza por la letra del documento
        // afectado; si no, SUNAT rechazaría la nota entera.
        if ($serie !== '' && strtoupper($serie[0]) === $letra) {
            return strtoupper($serie);
        }

        return self::serieDerivada($letra, $tipo);
    }

    /**
     * Serie propia para la nota cuando el negocio no configuró ninguna.
     *
     * Antes se reutilizaba la serie del comprobante afectado, y eso hacía que
     * una factura y su nota de crédito se imprimieran con EL MISMO número:
     * `F001-00000001` era a la vez una factura de S/ 2 078 y una nota de
     * S/ 1 650. SUNAT lo admite porque distingue por tipo de documento (01 y
     * 07), pero para el negocio son dos papeles con el mismo número, y buscar
     * ese número devuelve dos cosas distintas.
     *
     * Se conserva la regla de SUNAT —la nota empieza por la letra del
     * documento afectado— y se le añade la inicial de su clase: FC01 y BC01
     * para crédito, FD01 y BD01 para débito.
     */
    private static function serieDerivada(string $letra, string $tipo): string
    {
        return $letra.($tipo === 'nota_debito' ? 'D' : 'C').'01';
    }

    private function porQueNoAdmiteNota(Invoice $invoice): string
    {
        if ($invoice->esNota()) {
            return 'Una nota no se corrige con otra nota: emítela sobre la factura o boleta original.';
        }

        if ($invoice->baja_estado === 'accepted') {
            return 'Este comprobante ya fue dado de baja.';
        }

        return 'Solo se emite una nota sobre un comprobante que SUNAT ya aceptó. Este está: '
            .$invoice->estadoSunatLegible().'.';
    }

    /* El portal de facturacion vive en /f/{slug}: alli el negocio no viene de
       la sesion sino de la propia direccion, asi que hay que fijarlo antes de
       tocar nada. Sin esto, `active_project` seria el del panel —o ninguno— y
       la nota podria acabar emitida contra otro RUC. */

    public function opcionesPortal(string $slug, Invoice $invoice)
    {
        $this->fijarNegocioDelPortal($slug);

        return $this->opciones($invoice);
    }

    public function storePortal(Request $request, string $slug, Invoice $invoice)
    {
        $this->fijarNegocioDelPortal($slug);

        return $this->store($request, $invoice);
    }

    public function darDeBajaPortal(Request $request, string $slug, Invoice $invoice)
    {
        $this->fijarNegocioDelPortal($slug);

        return $this->darDeBaja($request, $invoice);
    }

    private function fijarNegocioDelPortal(string $slug): Project
    {
        $project = Project::where('slug', $slug)->firstOrFail();
        app()->instance('active_project', $project);

        return $project;
    }

    private function soloDeMiNegocio(Invoice $invoice): Project
    {
        /** @var Project $project */
        $project = app('active_project');
        abort_unless($invoice->project_id === $project->id, 403);

        return $project;
    }
}
