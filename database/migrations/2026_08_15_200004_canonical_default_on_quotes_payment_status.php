<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * F1b-M3 — El DEFAULT de quotes.payment_status era 'pendiente' (vocabulario
 * español a nivel de esquema). Las filas existentes NO se tocan: QuoteStatus
 * las normaliza en lectura desde F1a. Solo cambia el default de columna.
 * DDL directo para no depender de ->change() sobre la columna.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return; // sqlite de tests: el default lo fija el codigo desde F1a
        }
        DB::statement("ALTER TABLE quotes ALTER COLUMN payment_status SET DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }
        DB::statement("ALTER TABLE quotes ALTER COLUMN payment_status SET DEFAULT 'pendiente'");
    }
};
