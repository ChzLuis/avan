<?php

namespace App\Catalog\DTOs;

/** Página de NormalizedCategory[]. */
final class CategoryPage extends EntityPage
{
    public static function empty(SyncCursor $cursor): self
    {
        return new self(items: [], nextCursor: $cursor, hasMore: false, totalCount: 0);
    }
}
