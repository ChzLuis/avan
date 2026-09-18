<?php

namespace App\Modules\Catalogo\Conectores\Enums;

/** Estado de una ejecución de sincronización (catalog_sync_runs.status). */
enum SyncStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case CompletedWithErrors = 'completed_with_errors';
    case Failed = 'failed';
}
