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
        // Un job no tiene sesion web y no debe depender de ella: busca
        // explicito en todos los proyectos, como todo codigo de sistema.
        $invoice = Invoice::allProjects()->with('items', 'project')->find($this->invoiceId);

        if (!$invoice || $invoice->sunat_status === 'accepted') {
            return;
        }

        app()->instance('active_project', $invoice->project);

        // Proveedor fiscal del proyecto. Cada negocio elige el suyo: hay
        // quien trabaja con Nubefact y quien con APIsPERU, y las credenciales
        // de uno no sirven para el otro.
        $provider = (string) $invoice->project->setting('billing_provider', '');

        $result = match ($provider) {
            'apisperu' => (new ApisPeruService())->enviar($invoice),
            'nubefact' => (new NubefactService())->enviar($invoice),
            // Sin proveedor elegido no se asume ninguno: mandar a configurar
            // Nubefact a quien iba a usar APIsPERU solo confunde.
            default    => ['ok' => false, 'message' =>
                'Elige el proveedor de facturación electrónica (Nubefact o APIsPERU) en Ajustes → Facturación.'],
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
