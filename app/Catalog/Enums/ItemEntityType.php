<?php

namespace App\Catalog\Enums;

/** Tipo de entidad externa que representa una fila de catalog_integration_items. */
enum ItemEntityType: string
{
    case Product = 'product';
    case Service = 'service';
    case Category = 'category';
}
