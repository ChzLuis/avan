<?php

namespace App\Catalog\Enums;

enum SyncMode: string
{
    case Full = 'full';
    case Incremental = 'incremental';
}
