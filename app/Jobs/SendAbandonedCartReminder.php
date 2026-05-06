<?php

namespace App\Jobs;

use App\Models\AbandonedCart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAbandonedCartReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public readonly int $cartId) {}

    public function handle(): void
    {
        $cart = AbandonedCart::find($this->cartId);

        if (!$cart || $cart->reminder_sent || $cart->recovered_at) {
            return;
        }

        $project  = $cart->project;
        $settings = $project?->settings()->pluck('value', 'key');
        $waNumber = $settings['wa_number'] ?? null;

        // Construir mensaje de recuperación
        $items   = collect($cart->items);
        $listing = $items->map(fn($i) => "• {$i['name']} x{$i['quantity']}")->join("\n");
        $total   = number_format((float) $cart->subtotal, 2);
        $slug    = $project->slug ?? '';

        $message = "Hola {$cart->client_name} 👋\n\n"
                 . "Dejaste estos productos en tu carrito:\n{$listing}\n\n"
                 . "💰 Total: S/ {$total}\n\n"
                 . "Completa tu pedido aquí: " . url("/{$slug}");

        // Enviar por WhatsApp si hay bot configurado
        if ($cart->client_phone && $waNumber) {
            try {
                \Illuminate\Support\Facades\Http::timeout(10)->post('http://127.0.0.1:3001/send', [
                    'token'  => config('app.bot_token', 'wa-bot-secret-2024'),
                    'to'     => $cart->client_phone . '@c.us',
                    'text'   => $message,
                ]);
            } catch (\Throwable $e) {
                Log::warning("AbandonedCart WA send failed: {$e->getMessage()}");
            }
        }

        $cart->update([
            'reminder_sent'    => true,
            'reminder_sent_at' => now(),
        ]);
    }
}
