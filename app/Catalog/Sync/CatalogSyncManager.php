<?php

namespace App\Catalog\Sync;

use App\Catalog\Contracts\ProvidesProducts;
use App\Catalog\Contracts\ProvidesServices;
use App\Catalog\DTOs\NormalizedProduct;
use App\Catalog\Enums\ItemEntityType;
use App\Catalog\Enums\ItemSyncStatus;
use App\Catalog\Enums\SyncMode;
use App\Catalog\Enums\SyncStatus;
use App\Catalog\Enums\SyncTrigger;
use App\Catalog\Exceptions\CatalogProviderException;
use App\Catalog\Registry\CatalogProviderRegistry;
use App\Models\CatalogIntegration;
use App\Models\CatalogIntegrationItem;
use App\Models\CatalogSyncRun;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Motor genérico de sincronización. NO contiene ninguna referencia a un
 * proveedor concreto: resuelve el conector por el Registry, consulta sus
 * capacidades vía instanceof (Provides*), y aplica siempre la misma
 * secuencia de pasos sin importar de dónde vienen los datos.
 *
 * Idempotente, reanudable por páginas, transaccional por LOTE (no por
 * ejecución completa), seguro ante doble ejecución (lock por integración).
 */
class CatalogSyncManager
{
    private const BATCH_SIZE = 50;

    public function __construct(
        private readonly CatalogProviderRegistry $registry,
        private readonly ItemMatcher $matcher,
        private readonly FieldOwnershipPolicy $ownership,
    ) {
    }

    /**
     * Ejecuta una sincronización completa (todas las páginas) para una
     * integración. Devuelve el CatalogSyncRun con los contadores finales.
     */
    public function run(CatalogIntegration $integration, SyncTrigger $trigger, ?int $triggeredBy = null): CatalogSyncRun
    {
        // El lock anti-doble-ejecución se adquiere en el Job/Command, no aquí:
        // run() debe poder testearse de forma aislada sin depender del store de cache.
        $provider = $this->registry->resolve($integration->provider);
        $mode = SyncMode::from($integration->sync_mode);

        $run = CatalogSyncRun::create([
            'integration_id' => $integration->id,
            'mode' => $mode->value,
            'trigger' => $trigger->value,
            'status' => SyncStatus::Running->value,
            'started_at' => now(),
            'triggered_by' => $triggeredBy,
            // Los contadores tienen default 0 en BD, pero se inicializan aquí
            // también para que el objeto en memoria (usado con ++) nunca sea null.
            'pages_processed' => 0, 'items_received' => 0, 'items_created' => 0,
            'items_updated' => 0, 'items_unchanged' => 0, 'items_skipped' => 0,
            'items_failed' => 0, 'items_deactivated' => 0,
        ]);

        $integration->update(['last_sync_started_at' => now()]);

        $seenExternalIds = [];
        $hadFailure = false;
        $cursorEnd = null;

        try {
            if ($provider instanceof ProvidesProducts) {
                $this->syncEntities(
                    $integration, $provider, $run, ItemEntityType::Product,
                    fn ($cursor) => $provider->fetchProducts($integration, $cursor),
                    $seenExternalIds, $hadFailure, $cursorEnd,
                );
            }
            if ($provider instanceof ProvidesServices) {
                // Los servicios comparten el mismo pipeline de "producto" en BIXO
                // (no hay modelo Service dedicado en products); se procesan igual
                // pero bajo su propio entity_type para no chocar IDs con productos.
                $this->syncEntities(
                    $integration, $provider, $run, ItemEntityType::Service,
                    fn ($cursor) => $provider->fetchServices($integration, $cursor),
                    $seenExternalIds, $hadFailure, $cursorEnd,
                );
            }

            // Conciliación: SOLO en modo completo y SOLO si la ejecución terminó
            // sin fallos — una sync parcial nunca debe desactivar productos en masa.
            if ($mode === SyncMode::Full && !$hadFailure) {
                $run->items_deactivated = $this->deactivateOrphans($integration, $seenExternalIds);
            }

            $run->status = ($hadFailure ? SyncStatus::CompletedWithErrors : SyncStatus::Completed)->value;
        } catch (\Throwable $e) {
            Log::error('CatalogSyncManager: ejecución fallida', ['integration_id' => $integration->id, 'error' => $e->getMessage()]);
            $run->status = SyncStatus::Failed->value;
            $run->error_summary = $this->sanitizeErrorMessage($e->getMessage());
        }

        $run->completed_at = now();
        $run->cursor_end = $cursorEnd;
        $run->save();

        $integration->update([
            'last_sync_completed_at' => now(),
            'last_sync_status' => $run->status,
            'last_sync_error' => $run->error_summary,
            'last_successful_sync_at' => in_array($run->status, [SyncStatus::Completed->value, SyncStatus::CompletedWithErrors->value], true)
                ? now() : $integration->last_successful_sync_at,
        ]);

        return $run;
    }

    /** @param callable(\App\Catalog\DTOs\SyncCursor): \App\Catalog\DTOs\EntityPage $fetchPage */
    private function syncEntities(
        CatalogIntegration $integration,
        $provider,
        CatalogSyncRun $run,
        ItemEntityType $type,
        callable $fetchPage,
        array &$seenExternalIds,
        bool &$hadFailure,
        &$cursorEnd,
    ): void {
        $cursor = \App\Catalog\DTOs\SyncCursor::start();
        if ($run->cursor_start === null) {
            $run->cursor_start = $cursor->toArray();
        }

        do {
            try {
                $page = $fetchPage($cursor);
            } catch (CatalogProviderException $e) {
                $hadFailure = true;
                $run->error_summary = $this->sanitizeErrorMessage($e->getMessage());
                Log::warning('CatalogSyncManager: página fallida', ['integration_id' => $integration->id, 'type' => $type->value, 'error' => $e->getMessage()]);
                break; // no reintenta infinito dentro de la misma corrida; el reintento es una nueva ejecución (trigger=retry)
            }

            $run->pages_processed++;
            $run->items_received += count($page->items);

            // Lote transaccional: nunca la ejecución completa en una sola transacción.
            DB::transaction(function () use ($integration, $page, $type, $run, &$seenExternalIds) {
                foreach ($page->items as $normalized) {
                    $seenExternalIds[] = $normalized->externalId;
                    $this->upsertOne($integration, $type, $normalized, $run);
                }
            });

            $cursor = $page->nextCursor;
            $cursorEnd = $cursor->toArray();
        } while ($page->hasMore ?? false);
    }

    private function upsertOne(CatalogIntegration $integration, ItemEntityType $type, NormalizedProduct|\App\Catalog\DTOs\NormalizedService $normalized, CatalogSyncRun $run): void
    {
        $hash = hash('sha256', json_encode([
            $normalized->name, $normalized->sku ?? null, $normalized->salePrice,
            $normalized->stock ?? null, $normalized->active,
        ]));

        // Conflicto: mismo SKU ya vinculado a OTRA integración del mismo proyecto.
        if (property_exists($normalized, 'sku') && $normalized->sku) {
            $conflict = $this->matcher->findConflict($integration, $normalized->sku);
            if ($conflict) {
                $item = $this->matcher->find($integration, $type, $normalized->externalId)
                    ?? $this->matcher->createPending($integration, $type, $normalized->externalId, $normalized->sku ?? null);
                $item->update(['sync_status' => ItemSyncStatus::Conflict->value, 'sync_error' => "SKU {$normalized->sku} ya está vinculado a otra integración (#{$conflict->integration_id})."]);
                $run->items_failed++;

                return;
            }
        }

        $item = $this->matcher->find($integration, $type, $normalized->externalId);

        if ($item && $item->sync_hash === $hash) {
            $item->update(['last_synced_at' => now(), 'sync_status' => ItemSyncStatus::Synced->value]);
            $run->items_unchanged++;

            return;
        }

        try {
            $product = $item && $item->local_id
                ? Product::withoutGlobalScopes()->find($item->local_id)
                : null;

            $isNew = !$product;
            if (!$product) {
                $product = new Product(['project_id' => $integration->project_id, 'catalog_integration_id' => $integration->id]);
            }

            if ($type === ItemEntityType::Product && $normalized instanceof NormalizedProduct) {
                $this->ownership->apply($product, $normalized);
            } else {
                // Servicio: mapeo mínimo directo (no hay política de ownership de stock/unidad para servicios).
                $product->name = $normalized->name;
                $product->price = $normalized->salePrice;
                $product->is_available = $normalized->active;
                $product->catalog_integration_id = $integration->id;
                $product->external_sync_status = 'synced';
            }
            if (!$product->sort_order) $product->sort_order = 0;
            $product->save();

            if (!$item) {
                $item = $this->matcher->createPending($integration, $type, $normalized->externalId, $normalized->sku ?? null);
            }
            $item->update([
                'local_type' => Product::class,
                'local_id' => $product->id,
                'external_sku' => $normalized->sku ?? null,
                'sync_hash' => $hash,
                'last_synced_at' => now(),
                'sync_status' => ItemSyncStatus::Synced->value,
                'sync_error' => null,
            ]);

            $isNew ? $run->items_created++ : $run->items_updated++;
        } catch (\Throwable $e) {
            $run->items_failed++;
            Log::warning('CatalogSyncManager: falló el upsert de un ítem', ['integration_id' => $integration->id, 'external_id' => $normalized->externalId, 'error' => $e->getMessage()]);
            if ($item) {
                $item->update(['sync_status' => ItemSyncStatus::Failed->value, 'sync_error' => $this->sanitizeErrorMessage($e->getMessage())]);
            }
        }
    }

    /** Marca como huérfanos (no elimina) los ítems que ya no aparecieron en una sync completa exitosa. */
    private function deactivateOrphans(CatalogIntegration $integration, array $seenExternalIds): int
    {
        $orphans = CatalogIntegrationItem::query()
            ->where('integration_id', $integration->id)
            ->where('entity_type', ItemEntityType::Product->value)
            ->whereNotIn('external_id', $seenExternalIds ?: ['__none__'])
            ->where('sync_status', '!=', ItemSyncStatus::Orphaned->value)
            ->get();

        $count = 0;
        foreach ($orphans as $item) {
            $item->update(['sync_status' => ItemSyncStatus::Orphaned->value]);
            if ($item->local_id) {
                Product::withoutGlobalScopes()->where('id', $item->local_id)->update(['is_available' => false, 'external_sync_status' => 'orphaned']);
            }
            $count++;
        }

        return $count;
    }

    private function sanitizeErrorMessage(string $message): string
    {
        // Nunca dejar que un token/secreto llegue al historial visible en el panel.
        return preg_replace('/(Bearer\s+|token[\"\']?\s*[:=]\s*[\"\']?)[A-Za-z0-9\-_.]{10,}/i', '$1[oculto]', $message);
    }
}
