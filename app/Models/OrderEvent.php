<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Registro de auditoría del Centro de Pedidos (pedidos y cotizaciones). */
class OrderEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['project_id', 'order_id', 'quote_id', 'user_id', 'action', 'meta', 'created_at'];

    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Etiqueta humana en español para el historial. */
    public function getLabelAttribute(): string
    {
        $m = $this->meta ?? [];

        return match ($this->action) {
            'created'            => 'Registro creado',
            'status_changed'     => 'Estado: '.($m['from'] ?? '—').' → '.($m['to'] ?? '—'),
            'payment_registered' => 'Pago registrado: '.($m['method'] ?? '').' S/ '.number_format((float) ($m['amount'] ?? 0), 2).(($m['status'] ?? '') === 'partial' ? ' (adelanto)' : ''),
            'payment_status'     => 'Estado de pago: '.($m['from'] ?? '—').' → '.($m['to'] ?? '—'),
            'whatsapp_sent'      => 'WhatsApp ('.($m['template'] ?? 'mensaje').') a '.($m['to'] ?? ''),
            'converted'          => 'Cotización convertida en pedido #'.($m['order_id'] ?? '').((($m['origen'] ?? '') === 'cliente') ? ' (aceptada por el cliente)' : ''),
            'accepted_by_client' => 'El cliente aceptó la cotización desde su enlace',
            'rejected_by_client' => 'El cliente rechazó la cotización'.(filled($m['reason'] ?? '') ? ': '.$m['reason'] : ''),
            'proof_uploaded'     => 'El cliente subió su comprobante de pago',
            'document_issued'    => 'Comprobante emitido: '.strtoupper((string) ($m['type'] ?? '')).(filled($m['number'] ?? '') ? ' '.$m['number'] : ''),
            'cancelled'          => 'Anulado'.(filled($m['reason'] ?? '') ? ': '.$m['reason'] : ''),
            default              => $this->action,
        };
    }

    /** Registra un evento (helper único usado por todos los controladores). */
    public static function log(int $projectId, string $action, array $meta = [], ?int $orderId = null, ?int $quoteId = null): void
    {
        static::create([
            'project_id' => $projectId,
            'order_id'   => $orderId,
            'quote_id'   => $quoteId,
            'user_id'    => auth()->id(),
            'action'     => $action,
            'meta'       => $meta ?: null,
            'created_at' => now(),
        ]);
    }
}
