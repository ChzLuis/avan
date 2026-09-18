<?php

use App\Providers\AppServiceProvider;
use App\Modules\Catalogo\CatalogServiceProvider;

return [
    AppServiceProvider::class,
    CatalogServiceProvider::class,
    App\Modules\ModulosServiceProvider::class,
];
