<?php

namespace App\Catalog\Contracts;

use App\Catalog\DTOs\ProductPage;
use App\Catalog\DTOs\SyncCursor;
use App\Models\CatalogIntegration;

/** Capacidad opcional: el proveedor puede listar productos. */
interface ProvidesProducts
{
    public function fetchProducts(CatalogIntegration $integration, SyncCursor $cursor): ProductPage;
}
