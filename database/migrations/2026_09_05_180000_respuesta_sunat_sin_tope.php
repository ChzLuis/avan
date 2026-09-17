<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La respuesta de APIsPERU (XML firmado + CDR en base64) se guardaba en un
 * TEXT de 64 KB. Un comprobante con muchas líneas la desborda y el XML se
 * truncaría en silencio. Pasa a LONGTEXT en comprobantes y guías.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return; // sqlite (tests) no distingue tamaños de texto
        }
        foreach (['invoices', 'guias_remision'] as $tabla) {
            if (Schema::hasTable($tabla) && Schema::hasColumn($tabla, 'sunat_cdr')) {
                DB::statement("ALTER TABLE `{$tabla}` MODIFY `sunat_cdr` LONGTEXT NULL");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        foreach (['invoices', 'guias_remision'] as $tabla) {
            if (Schema::hasTable($tabla) && Schema::hasColumn($tabla, 'sunat_cdr')) {
                DB::statement("ALTER TABLE `{$tabla}` MODIFY `sunat_cdr` TEXT NULL");
            }
        }
    }
};
