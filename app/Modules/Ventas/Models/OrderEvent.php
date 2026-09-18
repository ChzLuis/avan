<?php

namespace App\Modules\Ventas\Models;

use App\Models\User;
use App\Modules\Ventas\Support\QuoteStatus;

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
            'quote_sent'         => 'Se generó el enlace y se envió al cliente',
            // El estado se cuenta en español. Antes salia la clave cruda
            // ('draft → sent'): vocabulario interno filtrandose a pantalla.
            'status_changed'     => 'Estado: '.$this->estadoLegible($m['from'] ?? null).' → '.$this->estadoLegible($m['to'] ?? null),
            'payment_registered' => 'Pago registrado: '.($m['method'] ?? '').' S/ '.number_format((float) ($m['amount'] ?? 0), 2).(($m['status'] ?? '') === 'partial' ? ' (adelanto)' : ''),
            'payment_status'     => 'Estado de pago: '.($m['from'] ?? '—').' → '.($m['to'] ?? '—'),
            'whatsapp_sent'      => 'WhatsApp ('.($m['template'] ?? 'mensaje').') a '.($m['to'] ?? ''),
            'converted'          => 'Cotización convertida en pedido #'.($m['order_id'] ?? '').((($m['origen'] ?? '') === 'cliente') ? ' (aceptada por el cliente)' : ''),
            'accepted_by_client' => 'El cliente aceptó la cotización desde su enlace',
            'rejected_by_client' => 'El cliente rechazó la cotización'.(filled($m['reason'] ?? '') ? ': '.$m['reason'] : ''),
            'proof_uploaded'     => 'El cliente subió su comprobante de pago',
            'document_issued'    => 'Comprobante emitido: '.strtoupper((string) ($m['type'] ?? '')).(filled($m['number'] ?? '') ? ' '.$m['number'] : ''),
            'cancelled'          => 'Anulado'.(filled($m['reason'] ?? '') ? ': '.$m['reason'] : ''),
            // El libro de cobros (F2b/F3) escribe estos dos y no estaban aqui:
            // caian al default y se pintaba el slug crudo.
            'payment_recorded'   => 'Cobro registrado'.(isset($m['importe']) ? ': S/ '.number_format((float) $m['importe'] / 100, 2) : ''),
            // Auditoria: que campos se tocaron y con que valores.
            'edited'             => self::resumenEdicion($m),
            'deleted'            => 'Eliminada '.($m['numero'] ?? '').' de '.($m['cliente'] ?? '—')
                                    .(isset($m['total']) ? ' por S/ '.$m['total'] : ''),
            'payment_reversed'   => 'Cobro revertido'.(filled($m['motivo'] ?? '') ? ': '.$m['motivo'] : ''),
            default              => $this->titulo,
        };
    }

    /**
     * Etiqueta corta para listados ("Pago registrado", "Pedido creado").
     *
     * `label` cuenta el detalle y sirve para el historial de un documento; en
     * una lista de actividad ese detalle no cabe y ademas se repite. Lo que
     * NUNCA puede pasar —y pasaba— es que salga el nombre interno del evento:
     * el usuario leia `payment_registered` en pantalla. Por eso el default
     * humaniza en vez de devolver el slug.
     */
    public function getTituloAttribute(): string
    {
        return match ($this->action) {
            // Un mismo evento sirve a dos documentos: la fila dice cual es.
            'created'            => $this->quote_id ? 'Cotización creada' : 'Pedido creado',
            'quote_sent'         => 'Cotización enviada al cliente',
            'status_changed'     => 'Estado actualizado',
            'payment_registered' => 'Pago registrado',
            'payment_recorded'   => 'Cobro registrado',
            'payment_reversed'   => 'Cobro revertido',
            'payment_status'     => 'Estado de pago actualizado',
            'whatsapp_sent'      => 'Enviado por WhatsApp',
            'converted'          => 'Cotización convertida en pedido',
            'accepted_by_client' => 'Cotización aceptada por el cliente',
            'rejected_by_client' => 'Cotización rechazada por el cliente',
            'proof_uploaded'     => 'Comprobante enviado por el cliente',
            'document_issued'    => 'Comprobante emitido',
            'cancelled'          => 'Anulado',
            'custom_text'        => 'Aviso enviado',
            'edited'             => 'Documento editado',
            'deleted'            => 'Documento eliminado',
            default              => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Resumen legible de una edicion: que campo, de que a que.
     *
     * Un 'documento editado' a secas no sirve para auditar. Lo que se
     * pregunta es si alguien bajo un precio, cambio al cliente o alargo la
     * validez, y desde que valor.
     */
    private static function resumenEdicion(array $m): string
    {
        $nombres = [
            'cliente' => 'Cliente', 'documento' => 'DNI/RUC', 'total' => 'Total',
            'validez' => 'Válida hasta', 'metodo' => 'Método de pago',
            'condicion' => 'Condición de pago', 'lineas' => 'Productos',
        ];

        $partes = [];
        foreach (($m['detalle'] ?? []) as $campo => $valor) {
            $partes[] = ($nombres[$campo] ?? ucfirst($campo)).': '.($valor['de'] ?? '—').' → '.($valor['a'] ?? '—');
        }

        return $partes ? implode(' · ', $partes) : 'Documento editado';
    }

    /**
     * Traduce una clave de estado a su nombre visible.
     *
     * Los eventos de una COTIZACION hablan el vocabulario de QuoteStatus; los
     * de un PEDIDO, el suyo. Un evento sin `quote_id` es de pedido, y ahi la
     * clave se humaniza sin mas: inventarle la traduccion de cotizaciones
     * pondria "Aceptada" donde el pedido dice otra cosa.
     */
    private function estadoLegible(?string $clave): string
    {
        if (! filled($clave)) {
            return '—';
        }

        if ($this->quote_id) {
            return \App\Modules\Ventas\Support\QuoteStatus::comercialPresentacion($clave)['label'];
        }

        return ucfirst(str_replace('_', ' ', $clave));
    }

    /**
     * El historial es INMUTABLE: se escribe una vez y no se corrige.
     *
     * Un registro de auditoria que se puede editar o borrar no prueba nada:
     * quien quisiera tapar un cambio solo tendria que reescribir su rastro.
     * Si un evento se anoto mal, se anota otro que lo aclare.
     */
    public function update(array $attributes = [], array $options = [])
    {
        throw new \RuntimeException('El historial no se modifica: es el registro de auditoría del documento.');
    }

    public function delete()
    {
        throw new \RuntimeException('El historial no se borra: es el registro de auditoría del documento.');
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
