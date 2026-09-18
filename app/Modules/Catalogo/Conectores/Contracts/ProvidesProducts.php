<?php

namespace App\Modules\Catalogo\Conectores\Contracts;

use App\Modules\Catalogo\Conectores\DTOs\ProductPage;
use App\Modules\Catalogo\Conectores\DTOs\SyncCursor;
use App\Modules\Catalogo\Models\CatalogIntegration;

/** Capacidad opcional: el proveedor puede listar productos. */
interface ProvidesProducts
{
    public function fetchProducts(CatalogIntegration $integration, SyncCursor $cursor): ProductPage;
}
