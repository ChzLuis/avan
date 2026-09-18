<?php

namespace App\Modules\Catalogo\Conectores\Providers\Siskote;

use App\Modules\Catalogo\Conectores\DTOs\NormalizedProduct;

/**
 * Traduce la fila de /api/products de SISKOTE (según su manual: id,
 * internal_id, item_code, item_code_gs1, name, trade_name, description,
 * item_type_id, unit_type_id, currency_type_id, sale_unit_price, has_igv,
 * stock, image, image_url, item_unit_types[]) a NormalizedProduct.
 *
 * Precio de venta: se usa el de item_unit_types[].sale_price de la lista
 * "Precio actualizado" cuando existe (es el precio real de venta al público
 * verificado en la prueba de la API); si no viene, se cae a
 * sale_unit_price como respaldo. Esta es la ÚNICA clase del sistema que
 * conoce el nombre "sale_unit_price".
 */
class SiskoteProductMapper
{
    public function toNormalizedProduct(array $row): NormalizedProduct
    {
        $salePrice = $this->resolveSalePrice($row);

        return new NormalizedProduct(
            externalId: (string) $row['id'],
            name: (string) ($row['trade_name'] ?: $row['name'] ?: ('SISKOTE #'.$row['id'])),
            sku: filled($row['internal_id'] ?? null) ? (string) $row['internal_id'] : null,
            barcode: filled($row['item_code_gs1'] ?? null) ? (string) $row['item_code_gs1'] : null,
            description: filled($row['description'] ?? null) ? (string) $row['description'] : null,
            salePrice: $salePrice,
            costPrice: isset($row['sale_unit_price']) ? (float) $row['sale_unit_price'] : null,
            currency: (string) ($row['currency_type_id'] ?? 'PEN'),
            stock: isset($row['stock']) ? (float) $row['stock'] : null,
            active: true, // SISKOTE no expone un flag de "descontinuado" en el manual — se asume activo si aparece en el listado
            categoryExternalId: null, // el manual no documenta categoría en /api/products
            unit: filled($row['unit_type_id'] ?? null) ? (string) $row['unit_type_id'] : null,
            images: filled($row['image_url'] ?? null) && !str_contains((string) $row['image_url'], 'imagen-no-disponible')
                ? [(string) $row['image_url']]
                : [],
            externalUpdatedAt: null, // el manual no documenta updated_at — por eso SISKOTE no soporta sync incremental (capabilities()->incremental = false)
            metadata: [
                'item_code' => $row['item_code'] ?? null,
                'item_type_id' => $row['item_type_id'] ?? null,
                'has_igv' => $row['has_igv'] ?? null,
            ],
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
