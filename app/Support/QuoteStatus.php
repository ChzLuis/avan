<?php

namespace App\Support;

/**
 * Normalizacion y presentacion de los estados de una cotizacion.
 *
 * Espejo del patron probado en OrderStatus: la regla "borrador es draft" vive
 * en UN sitio y es de SOLO LECTURA — las 16 filas de ARIN (3 de ellas con
 * status='borrador' creado por la extension) no se migran: se normalizan al
 * leer. Los dominios se mantienen separados a proposito: quotes no tiene
 * under_review y orders no tiene converted.
 *
 * La vigencia es DERIVADA (valid_until + estado abierto) y jamas se almacena
 * como estado (decision Codex 2026-08-15).
 */
class QuoteStatus
{
    /** Sinonimos heredados del estado COMERCIAL. Solo lectura. */
    private const SINONIMOS_COMERCIAL = [
        'borrador' => 'draft',
        'enviada'  => 'sent',
        'aceptada' => 'accepted',
    ];

    /** Sinonimos heredados del estado de PAGO (mismo criterio que OrderStatus). */
    private const SINONIMOS_PAGO = [
        'pendiente' => 'pending',
        'parcial'   => 'partial',   // faltaba: update() lo acepta como entrada
        'pagado'    => 'paid',
        'cancelado' => 'refunded',
    ];

    private const ABIERTAS = ['draft', 'sent'];

    public static function comercial(?string $raw): string
    {
        $valor = strtolower(trim((string) $raw));
        if ($valor === '') {
            return 'draft';
        }

        return self::SINONIMOS_COMERCIAL[$valor] ?? $valor;
    }

    public static function comercialPresentacion(?string $raw): array
    {
        return match (self::comercial($raw)) {
            'draft'     => ['label' => 'Borrador',   'cls' => 's-pending',   'heredado' => false],
            'sent'      => ['label' => 'Enviada',    'cls' => 's-process',   'heredado' => false],
            'accepted'  => ['label' => 'Aceptada',   'cls' => 's-done',      'heredado' => false],
            'rejected'  => ['label' => 'Rechazada',  'cls' => 's-cancelled', 'heredado' => false],
            'converted' => ['label' => 'Convertida', 'cls' => 's-done',      'heredado' => false],
            // Valor desconocido: se muestra tal cual y marcado, nunca se
            // esconde bajo una etiqueta que no le corresponde.
            default     => ['label' => $raw ?: '—',  'cls' => 's-legacy',    'heredado' => true],
        };
    }

    public static function pago(?string $raw): string
    {
        $valor = strtolower(trim((string) $raw));
        if ($valor === '') {
            return 'pending';
        }

        return self::SINONIMOS_PAGO[$valor] ?? $valor;
    }

    public static function pagoPresentacion(?string $raw): array
    {
        return match (self::pago($raw)) {
            'paid'     => ['label' => 'Pagado',      'cls' => 's-paid'],
            'partial'  => ['label' => 'Parcial',     'cls' => 's-partial'],
            'refunded' => ['label' => 'Reembolsado', 'cls' => 's-refunded'],
            default    => ['label' => 'Pendiente',   'cls' => 's-pending'],
        };
    }

    /**
     * Vigencia DERIVADA: vencida si su fecha paso y sigue comercialmente
     * abierta. Una aceptada/convertida/rechazada nunca "vence".
     */
    public static function vencida(?string $estadoComercial, $validUntil): bool
    {
        if (! $validUntil) {
            return false;
        }
        if (! in_array(self::comercial($estadoComercial), self::ABIERTAS, true)) {
            return false;
        }

        $fecha = $validUntil instanceof \DateTimeInterface
            ? \Illuminate\Support\Carbon::instance($validUntil)
            : \Illuminate\Support\Carbon::parse($validUntil);

        return $fecha->isBefore(today());
    }

    public static function opcionesComercial(): array
    {
        return [
            'draft'     => 'Borrador',
            'sent'      => 'Enviada',
            'accepted'  => 'Aceptada',
            'rejected'  => 'Rechazada',
            'converted' => 'Convertida',
        ];
    }

    public static function opcionesPago(): array
    {
        return [
            'pending'  => 'Pendiente',
            'partial'  => 'Parcial',
            'paid'     => 'Pagado',
            'refunded' => 'Reembolsado',
        ];
    }
}
