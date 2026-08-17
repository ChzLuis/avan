<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F1b-M1 — Trazabilidad canonica Cotizacion → Pedido.
 *
 * Nullable porque la mayoria de pedidos no nacen de cotizacion. El UNIQUE
 * refuerza la regla de producto 1 Quote → 0..1 Order a nivel de ESQUEMA
 * (NULL no colisiona en MySQL), no solo en codigo. nullOnDelete: borrar una
 * cotizacion jamas borra el pedido.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->foreignId('quote_id')->nullable()->after('client_id')
              ->constrained('quotes')->nullOnDelete();
            $t->unique('quote_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            // ORDEN CRITICO (hallado en el ensayo up/down/up): en MySQL la FK
            // se apoya en el indice UNIQUE; soltar el UNIQUE primero falla con
            // "needed in a foreign key constraint". FK primero, indice despues.
            $t->dropForeign(['quote_id']);
            $t->dropUnique(['quote_id']);
            $t->dropColumn('quote_id');
        });
    }
};
