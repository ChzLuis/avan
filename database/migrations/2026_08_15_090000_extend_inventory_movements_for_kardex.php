<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La tabla original (marzo) solo guardaba type/quantity/notes: alcanza para un
 * historial suelto, no para un Kardex. Se agrega lo que lo vuelve auditable:
 * el motivo, el costo, el saldo que quedó y el documento que originó el movimiento.
 */
return new class extends Migration {
    public function up(): void {
        Schema::table('inventory_movements', function (Blueprint $table) {
            // Motivo concreto: compra, venta, merma, conteo… (type solo dice si entra o sale)
            if (!Schema::hasColumn('inventory_movements', 'reason'))          $table->string('reason', 30)->nullable()->after('type');
            // Costo unitario del momento: sin esto no hay valorización posible.
            if (!Schema::hasColumn('inventory_movements', 'unit_cost'))       $table->decimal('unit_cost', 12, 2)->nullable()->after('quantity');
            // Saldo tras aplicar el movimiento: permite auditar sin recalcular toda la historia.
            if (!Schema::hasColumn('inventory_movements', 'balance_after'))   $table->integer('balance_after')->nullable()->after('unit_cost');
            // Documento origen (pedido, factura, compra) para poder rastrearlo.
            if (!Schema::hasColumn('inventory_movements', 'reference_type'))  $table->string('reference_type', 40)->nullable()->after('balance_after');
            if (!Schema::hasColumn('inventory_movements', 'reference_id'))    $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');

            $table->index(['project_id', 'product_id', 'id'], 'im_proyecto_producto_idx');
        });
    }

    public function down(): void {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $cols = array_filter(
                ['reason', 'unit_cost', 'balance_after', 'reference_type', 'reference_id'],
                fn ($c) => Schema::hasColumn('inventory_movements', $c)
            );
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};
