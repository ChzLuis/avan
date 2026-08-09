<?php

namespace App\Catalog\Enums;

/** Quién es dueño de un campo del producto: el ERP externo o BIXO (edición local). */
enum FieldOwner: string
{
    case Erp = 'erp';
    case Bixo = 'bixo';
}
