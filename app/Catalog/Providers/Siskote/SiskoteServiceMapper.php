<?php

namespace App\Catalog\Providers\Siskote;

use App\Catalog\DTOs\NormalizedService;

/** Traduce la fila de /api/services de SISKOTE (mismo shape que products) a NormalizedService. */
class SiskoteServiceMapper
{
    public function toNormalizedService(array $row): NormalizedService
    {
        $salePrice = $this->resolveSalePrice($row);

        return new NormalizedService(
            externalId: (string) $row['id'],
            name: (string) ($row['trade_name'] ?: $row['name'] ?: ('SISKOTE #'.$row['id'])),
            description: filled($row['description'] ?? null) ? (string) $row['description'] : null,
            salePrice: $salePrice,
            currency: (string) ($row['currency_type_id'] ?? 'PEN'),
            active: true,
            categoryExternalId: null,
            externalUpdatedAt: null,
            metadata: ['item_code' => $row['item_code'] ?? null, 'item_type_id' => $row['item_type_id'] ?? null],
        );
    }

    private function resolveSalePrice(array $row): float
    {
        $lists = $row['item_unit_types'] ?? [];
        if (is_array($lists) && count($lists)) {
            $updated = collect($lists)->first(fn ($l) => str_contains((string) ($l['price_list_description'] ?? ''), 'actualizado'))
                ?? collect($lists)->first();
            if ($updated && isset($updated['sale_price'])) {
                return (float) $updated['sale_price'];
            }
        }

        return (float) ($row['sale_unit_price'] ?? 0);
    }
}
