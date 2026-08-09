<?php

namespace App\Catalog\Providers\Siskote;

use App\Catalog\Contracts\CatalogProviderInterface;
use App\Catalog\Contracts\ProvidesProducts;
use App\Catalog\Contracts\ProvidesServices;
use App\Catalog\DTOs\ConfigField;
use App\Catalog\DTOs\ConfigSchema;
use App\Catalog\DTOs\ConnectionTestResult;
use App\Catalog\DTOs\ProductPage;
use App\Catalog\DTOs\ProviderCapabilities;
use App\Catalog\DTOs\ServicePage;
use App\Catalog\DTOs\SyncCursor;
use App\Catalog\Exceptions\ConnectionFailedException;
use App\Models\CatalogIntegration;

/**
 * Conector de SISKOTE (API de catálogo tenant, auth Bearer/Sanctum). Toda
 * lógica y nombres de campo propios de SISKOTE (sale_unit_price,
 * item_unit_types, etc.) viven exclusivamente en este módulo — el núcleo
 * de sincronización nunca los conoce.
 */
class SiskoteProvider implements CatalogProviderInterface, ProvidesProducts, ProvidesServices
{
    public function __construct(private readonly SiskoteClient $client)
    {
    }

    public function key(): string
    {
        return 'siskote';
    }

    public function name(): string
    {
        return 'SISKOTE';
    }

    public function capabilities(): ProviderCapabilities
    {
        return new ProviderCapabilities(products: true, services: true, categories: false, inventory: true, webhooks: false, incremental: false);
    }

    public function configSchema(): ConfigSchema
    {
        return new ConfigSchema([
            new ConfigField(
                name: 'base_url', label: 'URL base', type: 'url', required: true,
                default: 'http://centro.siskote.net', help: 'Dominio del tenant SISKOTE, sin barra final.'
            ),
            new ConfigField(name: 'email', label: 'Correo de la API', type: 'text', required: true),
            new ConfigField(name: 'password', label: 'Contraseña de la API', type: 'password', required: true, secret: true),
            new ConfigField(
                name: 'device_name', label: 'Nombre del dispositivo', type: 'text', required: false,
                default: 'bixo', help: 'Identificador libre que exige el endpoint de autenticación de SISKOTE.'
            ),
        ]);
    }

    public function testConnection(CatalogIntegration $integration): ConnectionTestResult
    {
        try {
            $this->client->authenticate($integration);

            return ConnectionTestResult::success('Conexión con SISKOTE correcta.');
        } catch (ConnectionFailedException $e) {
            return ConnectionTestResult::failure($e->getMessage());
        }
    }

    public function fetchProducts(CatalogIntegration $integration, SyncCursor $cursor): ProductPage
    {
        $mapper = new SiskoteProductMapper();
        $response = $this->client->getProducts($integration, page: $cursor->page);

        $items = array_map(fn (array $row) => $mapper->toNormalizedProduct($row), $response['data'] ?? []);
        $meta = $response['meta'] ?? [];
        $hasMore = (int) ($meta['current_page'] ?? 1) < (int) ($meta['last_page'] ?? 1);

        return new ProductPage(
            items: $items,
            nextCursor: $cursor->withNextPage((string) ($cursor->page + 1)),
            hasMore: $hasMore,
            totalCount: $meta['total'] ?? null,
        );
    }

    public function fetchServices(CatalogIntegration $integration, SyncCursor $cursor): ServicePage
    {
        $mapper = new SiskoteServiceMapper();
        $response = $this->client->getServices($integration, page: $cursor->page);

        $items = array_map(fn (array $row) => $mapper->toNormalizedService($row), $response['data'] ?? []);
        $meta = $response['meta'] ?? [];
        $hasMore = (int) ($meta['current_page'] ?? 1) < (int) ($meta['last_page'] ?? 1);

        return new ServicePage(
            items: $items,
            nextCursor: $cursor->withNextPage((string) ($cursor->page + 1)),
            hasMore: $hasMore,
            totalCount: $meta['total'] ?? null,
        );
    }
}
