<?php

namespace App\Modules\Catalogo\Conectores\Enums;

/** Quién / qué disparó una ejecución de sincronización. */
enum SyncTrigger: string
{
    case Manual = 'manual';
    case Scheduler = 'scheduler';
    case Webhook = 'webhook';
    case FirstConnection = 'first_connection';
    case Retry = 'retry';
    case Reconciliation = 'reconciliation';
}
