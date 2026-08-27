<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Support\ApisPeruService;
use App\Support\NubefactService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Comunica a SUNAT que un comprobante queda sin efecto.
 *
 * Va en segundo plano por lo mismo que el envío: la baja puede tardar y quien
 * la pide no tiene por qué esperar mirando una pantalla. Si falla, el
 * comprobante queda con el motivo del fallo a la vista y se puede reintentar,
 * en vez de quedarse en "anulando..." para siempre.
 */
class DarDeBajaEnSunat implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(public int $invoiceId) {}

    public function handle(): void
    {
        // Job de sistema: busqueda explicita fuera del scope de sesion.
        $invoice = Invoice::allProjects()->with('project')->find($this->invoiceId);

        if (! $invoice || $invoice->baja_estado === 'accepted') {
            return;
        }

        app()->instance('active_project', $invoice->project);

        $provider = (string) $invoice->project->setting('billing_provider', '');

        $result = match ($provider) {
            'apisperu' => (new ApisPeruService())->anular($invoice),
            'nubefact' => (new NubefactService())->anular($invoice),
            default    => ['ok' => false, 'message' =>
                'Elige el proveedor de facturación electrónica (Nubefact o APIsPERU) en Ajustes → Facturación.'],
        };

        if (($result['ok'] ?? false) === false && $invoice->fresh()->baja_estado !== 'accepted') {
            $invoice->update([
                'baja_estado' => 'rejected',
                'baja_error'  => $result['message'] ?? 'No se pudo comunicar la baja a SUNAT.',
                // La venta vuelve a estar viva: si la baja no entró, el
                // comprobante sigue surtiendo efecto y no puede figurar anulado.
                'status'      => 'issued',
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Invoice::where('id', $this->invoiceId)->update([
            'baja_estado' => 'rejected',
            'baja_error'  => 'No se pudo comunicar la baja: '.$e->getMessage(),
            'status'      => 'issued',
        ]);
    }
}
