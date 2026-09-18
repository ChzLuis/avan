<?php

namespace App\Modules\Catalogo\Conectores\DTOs;

/** Resultado de probar la conexión/autenticación con un proveedor. */
final class ConnectionTestResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly array $details = [],
    ) {
    }

    public static function success(string $message = 'Conexión correcta.', array $details = []): self
    {
        return new self(true, $message, $details);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }
}
