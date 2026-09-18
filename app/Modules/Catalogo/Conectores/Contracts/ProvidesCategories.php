<?php

namespace App\Modules\Catalogo\Conectores\Contracts;

use App\Modules\Catalogo\Conectores\DTOs\CategoryPage;
use App\Modules\Catalogo\Conectores\DTOs\SyncCursor;
use App\Modules\Catalogo\Models\CatalogIntegration;

/** Capacidad opcional: el proveedor puede listar categorías propias. */
interface ProvidesCategories
{
    public function fetchCategories(CatalogIntegration $integration, SyncCursor $cursor): CategoryPage;
}
