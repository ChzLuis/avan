<?php

namespace App\Catalog\Exceptions;

/** Excepción base de cualquier error controlado dentro de un conector de catálogo. */
class CatalogProviderException extends \RuntimeException
{
    /** @param bool $retryable Si true, el motor de sync puede reintentar la página/ejecución. */
    public function __construct(string $message, public readonly bool $retryable = false, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
