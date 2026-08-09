<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Support\NubefactService;
use App\Support\ApisPeruService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendInvoiceToSunat implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(public int $invoiceId) {}

    public function handle(): void
    {
        $invoice = Invoice::with('items', 'project')->find($this->invoiceId);

        if (!$invoice || $invoice->sunat_status === 'accepted') {
            return;
        }

        app()->instance('active_project', $invoice->project);

        // Proveedor de facturación según config del proyecto (default: nubefact)
        $provider = $invoice->project->setting('billing_provider', 'nubefact');

        $result = match ($provider) {
            'apisperu' => (new ApisPeruService())->enviar($invoice),
            default    => (new NubefactService())->enviar($invoice),
        };

        // Si el envío falló antes de llegar a la API (p.ej. credenciales sin
        // configurar), el servicio retorna el motivo pero no siempre lo persiste.
        // Sin esto, el comprobante quedaba en "pending" para siempre sin explicación.
        if (is_array($result) && ($result['ok'] ?? false) === false && !in_array($invoice->fresh()->sunat_status, ['accepted', 'error'], true)) {
            $invoice->update([
                'sunat_status' => 'error',
                'sunat_error'  => $result['message'] ?? 'Error desconocido al enviar a SUNAT.',
            ]);
        }
    }
}
