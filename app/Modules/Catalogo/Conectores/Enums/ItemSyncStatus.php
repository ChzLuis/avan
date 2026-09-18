<?php

namespace App\Modules\Catalogo\Conectores\Enums;

/** Estado de sincronización de una correspondencia individual (catalog_integration_items). */
enum ItemSyncStatus: string
{
    case Synced = 'synced';
    case Pending = 'pending';
    case Conflict = 'conflict';
    case Failed = 'failed';
    case Orphaned = 'orphaned'; // ya no aparece en el proveedor externo
}
