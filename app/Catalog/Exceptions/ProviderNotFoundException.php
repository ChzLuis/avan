<?php

namespace App\Catalog\Exceptions;

/** El identificador de proveedor no tiene ningún conector registrado en el Registry. */
class ProviderNotFoundException extends CatalogProviderException
{
    public static function forKey(string $key): self
    {
        return new self("No hay ningún conector de catálogo instalado con la clave \"{$key}\".");
    }
}
