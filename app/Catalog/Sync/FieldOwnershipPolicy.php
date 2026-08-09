<?php

namespace App\Catalog\Sync;

use App\Catalog\DTOs\NormalizedProduct;
use App\Models\Product;

/**
 * Qué campos controla el ERP externo vs qué campos preserva BIXO. El ERP
 * nunca pisa descripción/imágenes/SEO/destacado que el usuario ya editó a
 * mano en BIXO; BIXO nunca pisa precio/stock/SKU que vienen del ERP.
 *
 * v1: una integración primaria por producto (el que lo creó). Si el
 * producto ya tiene una foto o el usuario editó su descripción en BIXO
 * (marcado en owner_scope), la sync respeta ese campo y no lo sobreescribe.
 */
class FieldOwnershipPolicy
{
    /** Campos que el ERP SIEMPRE controla, sin importar qué edite el usuario en BIXO. */
    private const ERP_OWNED_FIELDS = ['sku', 'barcode', 'price', 'cost', 'stock', 'unit', 'is_available'];

    /**
     * Aplica los campos normalizados del ERP sobre el producto local,
     * respetando los campos que el usuario ya reclamó como propios en BIXO
     * (owner_scope). Devuelve true si algo cambió realmente.
     */
    public function apply(Product $product, NormalizedProduct $normalized): bool
    {
        $bixoOwned = (array) ($product->owner_scope['bixo'] ?? []);
        $dirtyBefore = $product->getDirty();

        $product->name = in_array('name', $bixoOwned, true) ? $product->name : $normalized->name;
        $product->sku = $normalized->sku ?: $product->sku;
        $product->barcode = $normalized->barcode ?: $product->barcode;
        $product->price = $normalized->salePrice;
        if ($normalized->costPrice !== null) {
            $product->cost = $normalized->costPrice;
        }
        $product->stock = $normalized->stock;
        $product->unit = $normalized->unit ?: $product->unit;
        $product->is_available = $normalized->active;

        // La descripción es terreno de BIXO por defecto, salvo que el producto
        // aún no tenga una propia (recién creado por el ERP).
        if (!in_array('description', $bixoOwned, true) && blank($product->description)) {
            $product->description = $normalized->description;
        }

        return $product->isDirty();
    }

    /** Marca un campo como "editado a mano en BIXO" — la sync ya no lo tocará. */
    public function markBixoOwned(Product $product, string $field): void
    {
        $scope = $product->owner_scope ?? [];
        $bixo = array_unique(array_merge($scope['bixo'] ?? [], [$field]));
        $product->owner_scope = array_merge($scope, ['bixo' => array_values($bixo)]);
    }

    public function erpOwnedFields(): array
    {
        return self::ERP_OWNED_FIELDS;
    }
}
