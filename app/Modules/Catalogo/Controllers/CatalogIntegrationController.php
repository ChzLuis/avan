<?php

namespace App\Modules\Catalogo\Controllers;


use App\Modules\Catalogo\Conectores\Enums\SyncTrigger;
use App\Modules\Catalogo\Conectores\Registry\CatalogProviderRegistry;
use App\Modules\Catalogo\Conectores\Sync\CatalogSyncManager;
use App\Http\Controllers\Controller;
use App\Modules\Catalogo\Jobs\RunCatalogSync;
use App\Modules\Catalogo\Models\CatalogIntegration;
use App\Models\Project;
use Illuminate\Http\Request;

/**
 * Panel "Conecta tu catálogo". No conoce ningún proveedor concreto: lista
 * los conectores instalados vía el Registry, genera el formulario a partir
 * del ConfigSchema declarado por cada uno, y ejecuta probar/sincronizar
 * sobre la interfaz común. Agregar un proveedor nuevo no toca este archivo.
 */
class CatalogIntegrationController extends Controller
{
    public function __construct(private readonly CatalogProviderRegistry $registry)
    {
    }

    private function project(): Project
    {
        return app('active_project');
    }

    /** Lista las integraciones del proyecto + catálogo de proveedores instalados. */
    public function index()
    {
        $project = $this->project();
        $integrations = $project->catalogIntegrations()->with('creator:id,name')->orderByDesc('created_at')->get()
            ->map(function (CatalogIntegration $i) {
                $provider = $this->registry->has($i->provider) ? $this->registry->resolve($i->provider) : null;
                $secretNames = $provider?->configSchema()->secretFieldNames() ?? [];

                return array_merge($i->toSafeArray($secretNames), [
                    'provider_name' => $provider?->name() ?? $i->provider,
                    'status' => $this->deriveStatus($i),
                    'products_count' => $i->products()->count(),
                ]);
            });

        return view('catalogo::catalog.integrations.index', [
            'project' => $project,
            'integrations' => $integrations,
            'availableProviders' => $this->registry->catalog(),
        ]);
    }

    /** Esquema de configuración de un proveedor, para construir el formulario dinámico. */
    public function schema(string $provider)
    {
        if (!$this->registry->has($provider)) {
            abort(404, "No hay ningún conector de catálogo instalado con la clave \"{$provider}\".");
        }
        $p = $this->registry->resolve($provider);

        return response()->json([
            'key' => $p->key(),
            'name' => $p->name(),
            'capabilities' => $p->capabilities()->toArray(),
            'fields' => $p->configSchema()->toArray(),
        ]);
    }

    public function store(Request $request)
    {
        $project = $this->project();
        $data = $request->validate([
            'provider' => 'required|string',
            'name' => 'required|string|max:120',
            'sync_mode' => 'nullable|in:full,incremental',
            'sync_interval_minutes' => 'nullable|integer|min:5|max:1440',
            'credentials' => 'required|array',
            'settings' => 'nullable|array',
        ]);

        $provider = $this->registry->resolve($data['provider']); // ProviderNotFoundException → 404 automático si no existe

        $integration = $project->catalogIntegrations()->create([
            'provider' => $data['provider'],
            'name' => $data['name'],
            'credentials' => $data['credentials'],
            'settings' => $data['settings'] ?? [],
            'capabilities_cache' => $provider->capabilities()->toArray(),
            'sync_mode' => $data['sync_mode'] ?? 'full',
            'sync_interval_minutes' => $data['sync_interval_minutes'] ?? 60,
            'active' => true,
            'created_by' => auth()->id(),
        ]);

        return response()->json(['ok' => true, 'integration_id' => $integration->id]);
    }

    public function update(Request $request, int $integration)
    {
        $integration = $this->findIntegration($integration);
        $data = $request->validate([
            'name' => 'sometimes|string|max:120',
            'sync_mode' => 'sometimes|in:full,incremental',
            'sync_interval_minutes' => 'sometimes|integer|min:5|max:1440',
            'active' => 'sometimes|boolean',
            'credentials' => 'sometimes|array',
            'settings' => 'sometimes|array',
        ]);

        // Reemplazo total de credenciales (nunca se devuelven las anteriores al front,
        // así que si el usuario no las tocó, el form no debe enviarlas).
        if (isset($data['credentials'])) {
            $merged = array_merge($integration->credentials ?? [], array_filter($data['credentials'], fn ($v) => $v !== null && $v !== ''));
            $data['credentials'] = $merged;
        }

        $integration->update($data);

        return response()->json(['ok' => true]);
    }

    public function destroy(int $integration)
    {
        $integration = $this->findIntegration($integration);
        // No se borra físicamente: se desactiva. La configuración y el historial se preservan.
        $integration->update(['active' => false]);

        return response()->json(['ok' => true]);
    }

    public function testConnection(int $integration)
    {
        $integration = $this->findIntegration($integration);
        $provider = $this->registry->resolve($integration->provider);
        $result = $provider->testConnection($integration);

        $integration->update(['last_connection_test_at' => now(), 'last_connection_ok' => $result->ok]);

        return response()->json(['ok' => $result->ok, 'message' => $result->message]);
    }

    /** Dispara la sincronización — encolada si hay cola real, o síncrona con QUEUE_CONNECTION=sync (ver .env). */
    public function sync(int $integration)
    {
        $integration = $this->findIntegration($integration);
        RunCatalogSync::dispatch($integration->id, SyncTrigger::Manual->value, auth()->id());

        return response()->json(['ok' => true, 'message' => 'Sincronización iniciada.']);
    }

    public function history(int $integration)
    {
        $integration = $this->findIntegration($integration);
        $runs = $integration->syncRuns()->latest()->limit(30)->get();

        return response()->json(['runs' => $runs]);
    }

    /**
     * Vista previa SIN aplicar: trae la primera página real del proveedor y
     * la compara contra lo ya vinculado, para que el usuario confirme antes
     * de la primera sincronización completa.
     */
    public function preview(int $integration)
    {
        $integration = $this->findIntegration($integration);
        $provider = $this->registry->resolve($integration->provider);

        if (!($provider instanceof \App\Modules\Catalogo\Conectores\Contracts\ProvidesProducts)) {
            return response()->json(['ok' => false, 'message' => 'Este proveedor no expone productos.'], 422);
        }

        $page = $provider->fetchProducts($integration, \App\Modules\Catalogo\Conectores\DTOs\SyncCursor::start());
        $existingIds = $integration->items()->pluck('external_id')->all();

        $preview = array_map(function ($p) use ($existingIds) {
            return [
                'external_id' => $p->externalId, 'name' => $p->name, 'sku' => $p->sku,
                'price' => $p->salePrice, 'stock' => $p->stock,
                'is_new' => !in_array($p->externalId, $existingIds, true),
            ];
        }, $page->items);

        return response()->json([
            'ok' => true,
            'sample' => array_slice($preview, 0, 20),
            'total_in_page' => count($preview),
            'new_count' => count(array_filter($preview, fn ($p) => $p['is_new'])),
            'existing_count' => count(array_filter($preview, fn ($p) => !$p['is_new'])),
            'has_more_pages' => $page->hasMore,
            'total_count' => $page->totalCount,
        ]);
    }

    /** Resuelve por ID sin el scope automático de proyecto, y exige pertenencia explícita (403, no 404 encubierto). */
    private function findIntegration(int $id): CatalogIntegration
    {
        $integration = CatalogIntegration::allProjects()->findOrFail($id);
        abort_unless($integration->project_id === $this->project()->id, 403);

        return $integration;
    }

    private function deriveStatus(CatalogIntegration $i): string
    {
        if (!$i->active) return 'disabled';
        if ($i->last_sync_status === 'failed') return 'error';
        if ($i->last_sync_status === 'completed_with_errors') return 'warning';
        if ($i->last_connection_ok === false) return 'error';
        if ($i->last_successful_sync_at) return 'connected';

        return 'pending';
    }
}
