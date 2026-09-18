<?php

namespace App\Modules\Ventas\Support;

/**
 * Normalizacion y presentacion de los estados de un pedido.
 *
 * Existe para que la regla "pagado es lo mismo que paid" viva en UN sitio.
 * Antes estaba repartida en comparaciones sueltas —OrderController@index,
 * isPendingPay() en Blade, la pildora de pago— y todas contra ['paid',
 * 'refunded'], asi que los pedidos guardados como 'pagado' se contaban como
 * deuda: el KPI "Por cobrar" mostraba S/ 1 670,12 ya cobrados.
 *
 * NO se migran datos. La normalizacion es solo de lectura.
 */
class OrderStatus
{
    /** Sinonimos heredados del estado de PAGO. Solo lectura, no se escribe. */
    private const SINONIMOS_PAGO = [
        'pagado'      => 'paid',
        'cancelado'   => 'refunded',
        // Alias legacy del bot: el canonico es under_review (decision Codex
        // 2026-08-15). Solo lectura; los escritores ya emiten el canonico.
        'en_revision' => 'under_review',
    ];

    /** Estados de pago que significan "ya no se debe". */
    private const SALDADOS = ['paid', 'refunded'];

    /**
     * Estado de pago canonico. Null o vacio equivalen a 'pending', que es como
     * lo trataba la vista antes.
     */
    public static function pago(?string $raw): string
    {
        $valor = strtolower(trim((string) $raw));
        if ($valor === '') {
            return 'pending';
        }

        return self::SINONIMOS_PAGO[$valor] ?? $valor;
    }

    /** ¿El pedido sigue debiendo? */
    public static function debe(?string $estadoComercial, ?string $estadoPago): bool
    {
        if (self::comercialCanonico($estadoComercial) === 'cancelled') {
            return false;
        }

        return ! in_array(self::pago($estadoPago), self::SALDADOS, true);
    }

    /**
     * Estado COMERCIAL canonico.
     *
     * Deliberadamente NO se normaliza 'pagado' -> 'done': que un pedido este
     * cobrado no prueba que este comercialmente completado. Los ids 20, 21 y 22
     * lo tienen y quedan pendientes de auditoria, asi que se conserva el valor
     * en crudo y se etiqueta como heredado en la capa de presentacion.
     */
    public static function comercialCanonico(?string $raw): string
    {
        $valor = strtolower(trim((string) $raw));

        return match ($valor) {
            ''            => 'pending',
            'processing'  => 'process',
            'completed'   => 'done',
            default       => $valor,
        };
    }

    /** Etiqueta y color del estado comercial, para pildoras. */
    public static function comercialPresentacion(?string $raw): array
    {
        $clave = self::comercialCanonico($raw);

        return match ($clave) {
            'pending'   => ['label' => 'Pendiente',  'cls' => 's-pending',   'heredado' => false],
            'process'   => ['label' => 'En proceso', 'cls' => 's-process',   'heredado' => false],
            'done'      => ['label' => 'Completado', 'cls' => 's-done',      'heredado' => false],
            'cancelled' => ['label' => 'Cancelado',  'cls' => 's-cancelled', 'heredado' => false],
            // Valor que el sistema no genera hoy: se muestra tal cual, marcado,
            // en vez de esconderlo bajo una etiqueta que no le corresponde.
            default     => ['label' => $raw ?: '—',  'cls' => 's-legacy',    'heredado' => true],
        };
    }

    /** Etiqueta y color del estado de pago. */
    public static function pagoPresentacion(?string $raw): array
    {
        return match (self::pago($raw)) {
            'paid'     => ['label' => 'Pagado',     'cls' => 's-paid'],
            'partial'  => ['label' => 'Parcial',    'cls' => 's-partial'],
            // Pago digital reportado por el cliente, a la espera de aprobacion.
            // Reutiliza la clase s-pending para no tocar vistas (Pedidos CERRADO).
            'under_review' => ['label' => 'En revisión', 'cls' => 's-pending'],
            'rejected' => ['label' => 'Rechazado',  'cls' => 's-rejected'],
            'refunded' => ['label' => 'Reembolsado','cls' => 's-refunded'],
            default    => ['label' => 'Pendiente',  'cls' => 's-pending'],
        };
    }

    /** Valores para el filtro de pago: solo los que el sistema reconoce. */
    public static function opcionesPago(): array
    {
        return [
            'pending'      => 'Pendiente',
            'under_review' => 'En revisión',
            'partial'      => 'Parcial',
            'paid'     => 'Pagado',
            'refunded' => 'Reembolsado',
        ];
    }

    /**
     * Valores CRUDOS que la base puede tener para un estado comercial canonico.
     *
     * Existe para poder contar por SQL sin traerse los pedidos a memoria. Los
     * KPIs se calculaban sobre una coleccion con limit(500), asi que "atrasados"
     * omitia justo los pedidos viejos atascados que ese indicador debe mostrar.
     * El valor NULL cuenta como 'pending' y se trata aparte en la consulta.
     */
    public static function rawsDe(string $canonico): array
    {
        return match ($canonico) {
            'pending'   => ['pending', ''],
            'process'   => ['process', 'processing'],
            'done'      => ['done', 'completed'],
            'cancelled' => ['cancelled'],
            default     => [$canonico],
        };
    }

    /** Estados que dan el pedido por CERRADO (ni activo ni atrasado). */
    public static function rawsCerrados(): array
    {
        return array_merge(self::rawsDe('done'), self::rawsDe('cancelled'));
    }

    /** Valores para el filtro comercial. */
    public static function opcionesComercial(): array
    {
        return [
            'pending'   => 'Pendiente',
            'process'   => 'En proceso',
            'done'      => 'Completado',
            'cancelled' => 'Cancelado',
        ];
    }
}
