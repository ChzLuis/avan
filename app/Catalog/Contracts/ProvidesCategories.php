<?php

namespace App\Catalog\Contracts;

use App\Catalog\DTOs\CategoryPage;
use App\Catalog\DTOs\SyncCursor;
use App\Models\CatalogIntegration;

/** Capacidad opcional: el proveedor puede listar categorías propias. */
interface ProvidesCategories
{
    public function fetchCategories(CatalogIntegration $integration, SyncCursor $cursor): CategoryPage;
}
