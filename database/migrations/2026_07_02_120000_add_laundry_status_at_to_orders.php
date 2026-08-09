<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Momento en que el pedido entró a su estado de lavandería actual.
 * Base para calcular el SLA (tiempo límite) y disparar alertas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'laundry_status_at')) {
                $table->timestamp('laundry_status_at')->nullable()->after('laundry_status');
            }
        });

        // Inicializar con updated_at para pedidos existentes
        \DB::statement("UPDATE orders SET laundry_status_at = updated_at WHERE laundry_status_at IS NULL AND laundry_status IS NOT NULL");
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'laundry_status_at')) {
                $table->dropColumn('laundry_status_at');
            }
        });
    }
};
