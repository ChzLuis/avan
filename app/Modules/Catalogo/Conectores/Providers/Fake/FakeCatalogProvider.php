<?php

namespace App\Modules\Catalogo\Conectores\Providers\Fake;

use App\Modules\Catalogo\CatalogServiceProvider;
use App\Modules\Catalogo\Conectores\Sync\CatalogSyncManager;

use App\Modules\Catalogo\Conectores\Contracts\CatalogProviderInterface;
use App\Modules\Catalogo\Conectores\Contracts\ProvidesCategories;
use App\Modules\Catalogo\Conectores\Contracts\ProvidesProducts;
use App\Modules\Catalogo\Conectores\Contracts\ProvidesServices;
use App\Modules\Catalogo\Conectores\DTOs\CategoryPage;
use App\Modules\Catalogo\Conectores\DTOs\ConfigField;
use App\Modules\Catalogo\Conectores\DTOs\ConfigSchema;
use App\Modules\Catalogo\Conectores\DTOs\ConnectionTestResult;
use App\Modules\Catalogo\Conectores\DTOs\NormalizedCategory;
use App\Modules\Catalogo\Conectores\DTOs\NormalizedProduct;
use App\Modules\Catalogo\Conectores\DTOs\NormalizedService;
use App\Modules\Catalogo\Conectores\DTOs\ProductPage;
use App\Modules\Catalogo\Conectores\DTOs\ProviderCapabilities;
use App\Modules\Catalogo\Conectores\DTOs\ServicePage;
use App\Modules\Catalogo\Conectores\DTOs\SyncCursor;
use App\Modules\Catalogo\Models\CatalogIntegration;

/**
 * Conector de prueba, SOLO para tests automatizados. Demuestra que el motor
 * de sincronización (CatalogSyncManager), el comando catalog:sync y la UI del
 * panel de integraciones funcionan igual con cualquier proveedor que cumpla
 * el contrato — sin ninguna referencia a SISKOTE.
 *
 * Los datos se inyectan en el constructor (in-memory) para que cada test
 * controle exactamente qué páginas devuelve, sin red ni fixtures externos.
 * NO se registra en el Registry salvo en entornos local/testing
 * (ver CatalogServiceProvider::boot()).
 */
class FakeCatalogProvider implements CatalogProviderInterface, ProvidesProducts, ProvidesServices, ProvidesCategories
{
    /** @param NormalizedProduct[] $products @param NormalizedService[] $services @param NormalizedCategory[] $categories */
    public function __construct(
        private readonly array $products = [],
        private readonly array $services = [],
        private readonly array $categories = [],
        private readonly int $pageSize = 50,
        private readonly bool $connectionOk = true,
        private readonly ?string $connectionFailureMessage = null,
    ) {
    }

    public function key(): string
    {
        return 'fake';
    }

    public function name(): string
    {
        return 'Fake Provider (solo pruebas)';
    }

    public function capabilities(): ProviderCapabilities
    {
        return new ProviderCapabilities(products: true, services: true, categories: true, inventory: true, webhooks: false, incremental: true);
    }

    public function configSchema(): ConfigSchema
    {
        return new ConfigSchema([
            new ConfigField(name: 'api_key', label: 'API Key', type: 'password', required: true, secret: true),
        ]);
    }

    public function testConnection(CatalogIntegration $integration): ConnectionTestResult
    {
        return $this->connectionOk
            ? ConnectionTestResult::success('Conexión de prueba correcta.')
            : ConnectionTestResult::failure($this->connectionFailureMessage ?? 'Conexión de prueba fallida.');
    }

    public function fetchProducts(CatalogIntegration $integration, SyncCursor $cursor): ProductPage
    {
        return $this->paginate($this->products, $cursor, fn ($items, $next, $more) => new ProductPage($items, $next, $more, count($this->products)));
    }

    public function fetchServices(CatalogIntegration $integration, SyncCursor $cursor): ServicePage
    {
        return $this->paginate($this->services, $cursor, fn ($items, $next, $more) => new ServicePage($items, $next, $more, count($this->services)));
    }

    public function fetchCategories(CatalogIntegration $integration, SyncCursor $cursor): CategoryPage
    {
        return $this->paginate($this->categories, $cursor, fn ($items, $next, $more) => new CategoryPage($items, $next, $more, count($this->categories)));
    }

    private function paginate(array $all, SyncCursor $cursor, callable $wrap)
    {
        $offset = ($cursor->page - 1) * $this->pageSize;
        $slice = array_slice($all, $offset, $this->pageSize);
        $hasMore = ($offset + $this->pageSize) < count($all);

        return $wrap($slice, $cursor->withNextPage((string) ($cursor->page + 1)), $hasMore);
    }
}
