<?php

namespace App\Modules\Catalogo\Conectores\Exceptions;

/** Falló la autenticación o conexión con el proveedor externo (credenciales, red, timeout). */
class ConnectionFailedException extends CatalogProviderException
{
}
