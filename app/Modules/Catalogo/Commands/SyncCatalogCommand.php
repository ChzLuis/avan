<?php

namespace App\Modules\Catalogo\Commands;


use App\Modules\Catalogo\Conectores\Enums\SyncTrigger;
use App\Modules\Catalogo\Jobs\RunCatalogSync;
use App\Modules\Catalogo\Models\CatalogIntegration;
use Illuminate\Console\Command;

/**
 * Comando genérico ÚNICO para sincronizar catálogos. No existe (ni debe
 * existir) un comando por proveedor — este despacha RunCatalogSync, que a
 * su vez resuelve el conector correcto vía CatalogProviderRegistry.
 *
 * Uso:
 *   php artisan catalog:sync                       (todas las integraciones activas que les toque sincronizar)
 *   php artisan catalog:sync --integration=123
 *   php artisan catalog:sync --project=45
 *   php artisan catalog:sync --provider=siskote
 *   php artisan catalog:sync --full
 *   php artisan catalog:sync --retry-failed
 */
class SyncCatalogCommand extends Command
{
    protected $signature = 'catalog:sync
        {--integration= : ID de una integración específica}
        {--project= : ID de proyecto — sincroniza todas sus integraciones activas}
        {--provider= : Clave de proveedor — sincroniza todas las integraciones de ese proveedor}
        {--full : Fuerza modo de sincronización completa aunque la integración esté en incremental}
        {--retry-failed : Solo integraciones cuya última sincronización falló}
        {--sync : Ejecuta en el proceso actual en vez de encolar (útil para depurar)}';

    protected $description = 'Sincroniza catálogos de productos/servicios desde los proveedores externos conectados.';

    public function handle(): int
    {
        $query = CatalogIntegration::withoutGlobalScopes()->where('active', true);

        if ($id = $this->option('integration')) {
            $query->where('id', $id);
        }
        if ($project = $this->option('project')) {
            $query->where('project_id', $project);
        }
        if ($provider = $this->option('provider')) {
            $query->where('provider', $provider);
        }
        if ($this->option('retry-failed')) {
            $query->where('last_sync_status', 'failed');
        }

        // Sin filtros explícitos: solo las integraciones a las que ya les toca
        // sincronizar según su intervalo configurado (el scheduler llama así).
        if (!$id && !$project && !$provider && !$this->option('retry-failed')) {
            $query->where(function ($q) {
                $q->whereNull('last_sync_completed_at')
                    ->orWhereRaw('TIMESTAMPDIFF(MINUTE, last_sync_completed_at, NOW()) >= sync_interval_minutes');
            });
        }

        $integrations = $query->get();

        if ($integrations->isEmpty()) {
            $this->info('No hay integraciones que sincronizar en este momento.');

            return self::SUCCESS;
        }

        $trigger = $this->option('retry-failed') ? SyncTrigger::Retry : SyncTrigger::Scheduler;

        foreach ($integrations as $integration) {
            if ($this->option('full')) {
                $integration->update(['sync_mode' => 'full']);
                $integration->refresh();
            }

            $job = new RunCatalogSync($integration->id, $trigger->value);

            if ($this->option('sync')) {
                $job->handle(app(\App\Modules\Catalogo\Conectores\Sync\CatalogSyncManager::class));
                $this->info("Sincronizado #{$integration->id} ({$integration->provider}) en el proceso actual.");
            } else {
                RunCatalogSync::dispatch($integration->id, $trigger->value);
                $this->info("Encolada sincronización de integración #{$integration->id} ({$integration->provider}).");
            }
        }

        return self::SUCCESS;
    }
}
