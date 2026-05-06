<?php

namespace App\Console\Commands;

use App\Jobs\SendAbandonedCartReminder;
use App\Models\AbandonedCart;
use Illuminate\Console\Command;

class SendAbandonedCartReminders extends Command
{
    protected $signature = 'carts:remind {--hours=1 : Horas mínimas de abandono}';
    protected $description = 'Envía recordatorios a clientes con carritos abandonados';

    public function handle(): void
    {
        $hours = (int) $this->option('hours');

        $carts = AbandonedCart::where('reminder_sent', false)
            ->whereNull('recovered_at')
            ->where('created_at', '<=', now()->subHours($hours))
            ->whereNotNull('client_phone')
            ->get();

        foreach ($carts as $cart) {
            SendAbandonedCartReminder::dispatch($cart->id);
        }

        $this->info("Despachados {$carts->count()} recordatorios.");
    }
}
