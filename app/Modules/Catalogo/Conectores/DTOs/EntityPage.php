<?php

namespace App\Modules\Catalogo\Conectores\DTOs;

/**
 * Página de resultados devuelta por un proveedor. Base común de
 * ProductPage/ServicePage/CategoryPage — misma forma, distinto tipo de
 * elemento en $items, para que el contrato pueda tipar cada método
 * (fetchProducts(): ProductPage, etc.) sin repetir la clase entera.
 */
abstract class EntityPage
{
    /** @param  array<int, object>  $items  NormalizedProduct[]|NormalizedService[]|NormalizedCategory[] según la subclase. */
    public function __construct(
        public readonly array $items,
        public readonly SyncCursor $nextCursor,
        public readonly bool $hasMore,
        public readonly ?int $totalCount = null,
    ) {
    }
}
