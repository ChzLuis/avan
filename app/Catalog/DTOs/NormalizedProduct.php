<?php

namespace App\Catalog\DTOs;

/**
 * Producto normalizado que CUALQUIER proveedor debe producir. El núcleo de
 * sincronización solo conoce estos campos — nombres propios del proveedor
 * externo (ej. sale_unit_price de SISKOTE) nunca cruzan esta frontera.
 */
final class NormalizedProduct
{
    /**
     * @param  array<string,mixed>  $metadata  Datos crudos opcionales, saneados (sin secretos), para auditoría.
     * @param  string[]  $images  URLs de imágenes (puede ir vacío).
     */
    public function __construct(
        public readonly string $externalId,
        public readonly string $name,
        public readonly ?string $sku = null,
        public readonly ?string $barcode = null,
        public readonly ?string $description = null,
        public readonly float $salePrice = 0.0,
        public readonly ?float $costPrice = null,
        public readonly string $currency = 'PEN',
        public readonly ?float $stock = null,
        public readonly bool $active = true,
        public readonly ?string $categoryExternalId = null,
        public readonly ?string $unit = null,
        public readonly array $images = [],
        public readonly ?\DateTimeImmutable $externalUpdatedAt = null,
        public readonly array $metadata = [],
    ) {
    }
}
