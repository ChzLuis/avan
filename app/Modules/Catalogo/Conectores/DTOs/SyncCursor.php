<?php

namespace App\Modules\Catalogo\Conectores\DTOs;

/**
 * Posición de continuación de una sincronización paginada. Es opaco para el
 * núcleo: cada proveedor decide qué guardar en $token (número de página,
 * cursor de API, timestamp de corte, etc.) y solo él lo interpreta.
 */
final class SyncCursor
{
    public function __construct(
        public readonly ?string $token = null,
        public readonly int $page = 1,
        public readonly ?\DateTimeImmutable $since = null,
    ) {
    }

    public static function start(?\DateTimeImmutable $since = null): self
    {
        return new self(token: null, page: 1, since: $since);
    }

    public function withNextPage(?string $token): self
    {
        return new self(token: $token, page: $this->page + 1, since: $this->since);
    }

    public function toArray(): array
    {
        return ['token' => $this->token, 'page' => $this->page, 'since' => $this->since?->format(DATE_ATOM)];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            token: $data['token'] ?? null,
            page: (int) ($data['page'] ?? 1),
            since: isset($data['since']) ? new \DateTimeImmutable($data['since']) : null,
        );
    }
}
