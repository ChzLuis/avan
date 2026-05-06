<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Support\NubefactService;
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

        (new NubefactService())->enviar($invoice);
    }
}
