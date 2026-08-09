<?php

namespace App\Catalog\Sync;

use App\Catalog\Enums\ItemEntityType;
use App\Catalog\Enums\ItemSyncStatus;
use App\Models\CatalogIntegration;
use App\Models\CatalogIntegrationItem;

/**
 * Resuelve la correspondencia externo↔local. La identidad de una entidad
 * externa es SIEMPRE (integration_id, entity_type, external_id) — nunca el
 * SKU solo, porque el SKU puede repetirse entre proveedores o cambiar del
 * lado del ERP sin que la entidad externa cambie de identidad.
 */
class ItemMatcher
{
    /** Busca la correspondencia existente para esta entidad externa dentro de ESTA integración. */
    public function find(CatalogIntegration $integration, ItemEntityType $type, string $externalId): ?CatalogIntegrationItem
    {
        return CatalogIntegrationItem::query()
            ->where('integration_id', $integration->id)
            ->where('entity_type', $type->value)
            ->where('external_id', $externalId)
            ->first();
    }

    /**
     * Si el mismo SKU ya está vinculado a OTRA integración distinta,
     * es un conflicto: no se fusiona automáticamente por nombre/SKU.
     */
    public function findConflict(CatalogIntegration $integration, ?string $sku): ?CatalogIntegrationItem
    {
        if (!$sku) return null;

        return CatalogIntegrationItem::query()
            ->where('integration_id', '!=', $integration->id)
            ->where('external_sku', $sku)
            ->whereHas('integration', fn ($q) => $q->where('project_id', $integration->project_id))
            ->first();
    }

    public function createPending(CatalogIntegration $integration, ItemEntityType $type, string $externalId, ?string $sku, ?string $parentId = null): CatalogIntegrationItem
    {
        return CatalogIntegrationItem::create([
            'integration_id' => $integration->id,
            'entity_type' => $type->value,
            'external_id' => $externalId,
            'external_sku' => $sku,
            'external_parent_id' => $parentId,
            'sync_status' => ItemSyncStatus::Pending->value,
        ]);
    }
}
