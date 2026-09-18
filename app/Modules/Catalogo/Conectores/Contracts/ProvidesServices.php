<?php

namespace App\Modules\Catalogo\Conectores\Contracts;

use App\Modules\Catalogo\Conectores\DTOs\ServicePage;
use App\Modules\Catalogo\Conectores\DTOs\SyncCursor;
use App\Modules\Catalogo\Models\CatalogIntegration;

/** Capacidad opcional: el proveedor puede listar servicios. */
interface ProvidesServices
{
    public function fetchServices(CatalogIntegration $integration, SyncCursor $cursor): ServicePage;
}
