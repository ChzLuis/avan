<?php

namespace App\Catalog\DTOs;

/** Servicio normalizado (sin variantes de stock físico). */
final class NormalizedService
{
    public function __construct(
        public readonly string $externalId,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly float $salePrice = 0.0,
        public readonly string $currency = 'PEN',
        public readonly bool $active = true,
        public readonly ?string $categoryExternalId = null,
        public readonly ?\DateTimeImmutable $externalUpdatedAt = null,
        public readonly array $metadata = [],
    ) {
    }
}
