<?php

namespace App\Modules\Catalogo\Conectores\Registry;

use App\Modules\Catalogo\CatalogServiceProvider;

use App\Modules\Catalogo\Conectores\Contracts\CatalogProviderInterface;
use App\Modules\Catalogo\Conectores\Exceptions\ProviderNotFoundException;

/**
 * Registro central de conectores de catálogo instalados. El núcleo del
 * sistema SOLO conoce esta clase — nunca el nombre de un proveedor concreto.
 *
 * Mecanismo de descubrimiento: cada conector se registra a sí mismo desde su
 * propio Service Provider (ver CatalogServiceProvider::boot()), llamando
 * register(). No hay autoload por convención de carpetas ni manifest en
 * disco — es explícito y trivial de auditar, y agregar un proveedor nuevo
 * es una sola línea en el Service Provider, sin tocar esta clase.
 */
class CatalogProviderRegistry
{
    /** @var array<string, CatalogProviderInterface> */
    private array $providers = [];

    public function register(CatalogProviderInterface $provider): void
    {
        $this->providers[$provider->key()] = $provider;
    }

    public function has(string $key): bool
    {
        return isset($this->providers[$key]);
    }

    public function resolve(string $key): CatalogProviderInterface
    {
        return $this->providers[$key] ?? throw ProviderNotFoundException::forKey($key);
    }

    /** @return CatalogProviderInterface[] */
    public function all(): array
    {
        return array_values($this->providers);
    }

    /** Listado ligero para la UI: key, name y capabilities de cada proveedor instalado. */
    public function catalog(): array
    {
        return array_map(fn (CatalogProviderInterface $p) => [
            'key' => $p->key(),
            'name' => $p->name(),
            'capabilities' => $p->capabilities()->toArray(),
        ], $this->all());
    }
}
