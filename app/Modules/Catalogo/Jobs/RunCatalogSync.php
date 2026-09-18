<?php

namespace App\Modules\Catalogo\Jobs;

use App\Modules\Catalogo\Conectores\Enums\SyncTrigger;
use App\Modules\Catalogo\Conectores\Sync\CatalogSyncManager;
use App\Modules\Catalogo\Models\CatalogIntegration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Ejecuta una sincronización de catálogo en cola. El lock anti-doble-
 * ejecución vive aquí (no en CatalogSyncManager, para que el motor siga
 * siendo testeable sin depender del store de cache): si la integración ya
 * está sincronizando, este job simplemente no hace nada y se cierra rápido
 * — no encola una segunda ejecución ni falla.
 */
class RunCatalogSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 300];
    public int $timeout = 300;

    public function __construct(
        public readonly int $integrationId,
        public readonly string $trigger = 'scheduler',
        public readonly ?int $triggeredBy = null,
    ) {
    }

    public function handle(CatalogSyncManager $manager): void
    {
        $integration = CatalogIntegration::withoutGlobalScopes()->find($this->integrationId);
        if (!$integration || !$integration->active) {
            return;
        }

        $lockKey = "catalog-sync:integration:{$integration->id}";
        $lock = Cache::store('database')->lock($lockKey, 600);

        if (!$lock->get()) {
            Log::info('RunCatalogSync: ya hay una sincronización en curso, se omite.', ['integration_id' => $integration->id]);

            return;
        }

        try {
            $manager->run($integration, SyncTrigger::from($this->trigger), $this->triggeredBy);
        } finally {
            $lock->release();
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RunCatalogSync: job falló tras agotar reintentos.', ['integration_id' => $this->integrationId, 'error' => $e->getMessage()]);
    }
}
