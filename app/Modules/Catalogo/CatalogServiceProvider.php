<?php

namespace App\Modules\Catalogo;

use App\Modules\Catalogo\Conectores\Providers\Fake\FakeCatalogProvider;
use App\Modules\Catalogo\Conectores\Providers\Siskote\SiskoteProvider;
use App\Modules\Catalogo\Conectores\Registry\CatalogProviderRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Punto único de descubrimiento de conectores de catálogo. Para agregar un
 * proveedor nuevo (ej. Bsale): crear su carpeta en app/Catalog/Providers/,
 * implementar CatalogProviderInterface, y añadir UNA línea aquí. Nada más
 * del sistema (CatalogSyncManager, el comando catalog:sync, los
 * controladores, las vistas) necesita cambiar.
 */
class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CatalogProviderRegistry::class, fn () => new CatalogProviderRegistry());
    }

    public function boot(): void
    {
        /** @var CatalogProviderRegistry $registry */
        $registry = $this->app->make(CatalogProviderRegistry::class);

        $registry->register($this->app->make(SiskoteProvider::class));

        // El conector de prueba SOLO se instala fuera de producción: demuestra
        // que el motor no depende de SISKOTE sin arriesgar exponerlo en vivo.
        if ($this->app->environment(['local', 'testing'])) {
            $registry->register(new FakeCatalogProvider());
        }
    }
}
