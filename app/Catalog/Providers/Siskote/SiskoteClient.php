<?php

namespace App\Catalog\Providers\Siskote;

use App\Catalog\Exceptions\ConnectionFailedException;
use App\Models\CatalogIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente HTTP de la API de SISKOTE (auth Bearer/Sanctum vía POST
 * /api/auth/token). Endpoints y formato de respuesta verificados contra el
 * manual oficial y una llamada real de prueba — no se inventa nada.
 *
 * El token se cachea en memoria de request (no se persiste) y se renueva
 * solo si el proveedor responde 401 — así una integración inactiva no
 * mantiene tokens vivos innecesariamente.
 */
class SiskoteClient
{
    private const TIMEOUT_SECONDS = 15;

    private ?string $tokenCache = null;

    public function authenticate(CatalogIntegration $integration): string
    {
        if ($this->tokenCache) {
            return $this->tokenCache;
        }

        $creds = $integration->credentials ?? [];
        $baseUrl = rtrim((string) ($creds['base_url'] ?? ''), '/');
        $email = (string) ($creds['email'] ?? '');
        $password = (string) ($creds['password'] ?? '');
        $device = (string) ($creds['device_name'] ?? 'bixo');

        if (!$baseUrl || !$email || !$password) {
            throw new ConnectionFailedException('Faltan credenciales de SISKOTE: URL base, correo y contraseña son obligatorios.');
        }

        try {
            $res = Http::timeout(self::TIMEOUT_SECONDS)
                ->acceptJson()
                ->asJson()
                ->post("{$baseUrl}/api/auth/token", [
                    'email' => $email,
                    'password' => $password,
                    'device_name' => $device,
                ]);
        } catch (\Throwable $e) {
            Log::warning('SISKOTE: fallo de red al autenticar', ['integration_id' => $integration->id, 'error' => $e->getMessage()]);
            throw new ConnectionFailedException('No se pudo conectar con SISKOTE (red o timeout).', retryable: true, previous: $e);
        }

        if ($res->status() === 401 || $res->status() === 422) {
            $msg = (string) ($res->json('message') ?? 'Credenciales inválidas.');
            throw new ConnectionFailedException("SISKOTE rechazó las credenciales: {$msg}");
        }

        if (!$res->successful() || !$res->json('success')) {
            throw new ConnectionFailedException('SISKOTE respondió con un error inesperado (HTTP '.$res->status().').', retryable: true);
        }

        $token = (string) $res->json('token');
        if (!$token) {
            throw new ConnectionFailedException('SISKOTE no devolvió un token válido.');
        }

        return $this->tokenCache = $token;
    }

    /** GET /api/products — paginado, filtrable por texto libre (nombre/código/barcode). */
    public function getProducts(CatalogIntegration $integration, int $page = 1, ?string $search = null): array
    {
        return $this->getPaginated($integration, '/api/products', $page, $search);
    }

    /** GET /api/services — mismo formato de paginación que productos. */
    public function getServices(CatalogIntegration $integration, int $page = 1, ?string $search = null): array
    {
        return $this->getPaginated($integration, '/api/services', $page, $search);
    }

    private function getPaginated(CatalogIntegration $integration, string $path, int $page, ?string $search): array
    {
        $baseUrl = rtrim((string) (($integration->credentials ?? [])['base_url'] ?? ''), '/');
        $perPage = (int) (($integration->settings ?? [])['per_page'] ?? 50);

        return $this->withAuthRetry($integration, function (string $token) use ($baseUrl, $path, $page, $perPage, $search) {
            $query = array_filter(['per_page' => $perPage, 'page' => $page, 'input' => $search]);

            $res = Http::timeout(self::TIMEOUT_SECONDS)
                ->withToken($token)
                ->acceptJson()
                ->get("{$baseUrl}{$path}", $query);

            if ($res->status() === 429) {
                throw new ConnectionFailedException('SISKOTE aplicó rate limit (HTTP 429). Reintentar más tarde.', retryable: true);
            }
            if (!$res->successful()) {
                throw new ConnectionFailedException("SISKOTE respondió HTTP {$res->status()} al consultar {$path}.", retryable: $res->status() >= 500);
            }

            $json = $res->json();
            if (!is_array($json) || !($json['success'] ?? false)) {
                throw new ConnectionFailedException("Respuesta inválida de SISKOTE en {$path}.", retryable: true);
            }

            return $json;
        });
    }

    /** Ejecuta $callback con un token válido; si SISKOTE responde 401 a mitad de camino, renueva una vez y reintenta. */
    private function withAuthRetry(CatalogIntegration $integration, \Closure $callback): array
    {
        $token = $this->authenticate($integration);
        try {
            return $callback($token);
        } catch (ConnectionFailedException $e) {
            if (!str_contains($e->getMessage(), 'HTTP 401')) {
                throw $e;
            }
            $this->tokenCache = null;
            $token = $this->authenticate($integration);

            return $callback($token);
        }
    }
}
