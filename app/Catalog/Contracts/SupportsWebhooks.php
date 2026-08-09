<?php

namespace App\Catalog\Contracts;

use App\Models\CatalogIntegration;
use Illuminate\Http\Request;

/** Capacidad opcional: el proveedor puede notificar cambios en tiempo real vía webhook. */
interface SupportsWebhooks
{
    /** Verifica la firma/autenticidad del webhook (ej. HMAC) antes de aceptarlo. */
    public function verifyWebhook(CatalogIntegration $integration, Request $request): bool;

    /**
     * Identificador idempotente del evento (para no procesar el mismo webhook dos veces).
     * Devuelve null si el proveedor no ofrece uno — en ese caso el receptor genérico
     * hace fallback a un hash del payload.
     */
    public function webhookEventId(Request $request): ?string;
}
