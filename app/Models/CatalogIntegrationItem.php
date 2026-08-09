<?php

namespace App\Models;

use App\Catalog\Enums\ItemSyncStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Correspondencia externo↔local. Única fuente de verdad de qué entidad de
 * BIXO fue creada/actualizada por qué integración. La identidad de una
 * entidad externa es (integration_id, entity_type, external_id) — nunca el
 * SKU solo (puede repetirse entre proveedores o cambiar del lado del ERP).
 */
class CatalogIntegrationItem extends Model
{
    protected $fillable = [
        'integration_id', 'entity_type', 'external_id', 'external_parent_id', 'external_sku',
        'local_type', 'local_id', 'external_updated_at', 'last_synced_at', 'sync_hash',
        'sync_status', 'sync_error', 'raw_metadata',
    ];

    protected $casts = [
        'raw_metadata' => 'array',
        'external_updated_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(CatalogIntegration::class, 'integration_id');
    }

    /** Producto/Categoría/etc. local, resuelto vía local_type polimórfico manual. */
    public function local(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'local_type', 'local_id');
    }

    public function isSynced(): bool
    {
        return $this->sync_status === ItemSyncStatus::Synced->value;
    }
}
