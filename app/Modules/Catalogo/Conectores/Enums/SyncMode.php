<?php

namespace App\Modules\Catalogo\Conectores\Enums;

enum SyncMode: string
{
    case Full = 'full';
    case Incremental = 'incremental';
}
