<?php

namespace App\Catalog\DTOs;

/** Página de NormalizedService[]. */
final class ServicePage extends EntityPage
{
    public static function empty(SyncCursor $cursor): self
    {
        return new self(items: [], nextCursor: $cursor, hasMore: false, totalCount: 0);
    }
}
