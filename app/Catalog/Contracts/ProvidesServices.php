<?php

namespace App\Catalog\Contracts;

use App\Catalog\DTOs\ServicePage;
use App\Catalog\DTOs\SyncCursor;
use App\Models\CatalogIntegration;

/** Capacidad opcional: el proveedor puede listar servicios. */
interface ProvidesServices
{
    public function fetchServices(CatalogIntegration $integration, SyncCursor $cursor): ServicePage;
}
