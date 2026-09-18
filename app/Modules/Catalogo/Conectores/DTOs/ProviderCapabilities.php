<?php

namespace App\Modules\Catalogo\Conectores\DTOs;

/**
 * Flags declarativos de qué puede hacer un proveedor. Se usa para pintar la
 * UI ("este proveedor no soporta servicios") y para que CatalogSyncManager
 * decida qué pasos ejecutar sin necesitar un switch por proveedor: consulta
 * capabilities() y, si aplica, usa instanceof contra las interfaces
 * "Provides..." o "Supports..." del proveedor concreto.
 */
final class ProviderCapabilities
{
    public function __construct(
        public readonly bool $products = false,
        public readonly bool $services = false,
        public readonly bool $categories = false,
        public readonly bool $inventory = false,
        public readonly bool $webhooks = false,
        public readonly bool $incremental = false,
    ) {
    }

    public function toArray(): array
    {
        return [
            'products' => $this->products,
            'services' => $this->services,
            'categories' => $this->categories,
            'inventory' => $this->inventory,
            'webhooks' => $this->webhooks,
            'incremental' => $this->incremental,
        ];
    }
}
