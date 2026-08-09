<?php

namespace App\Catalog\DTOs;

/** Categoría normalizada. */
final class NormalizedCategory
{
    public function __construct(
        public readonly string $externalId,
        public readonly string $name,
        public readonly ?string $externalParentId = null,
        public readonly ?\DateTimeImmutable $externalUpdatedAt = null,
        public readonly array $metadata = [],
    ) {
    }
}
