<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 'pending_payment' = esperando pago | 'paid' = pagado | 'failed' = fallido
            $table->string('payment_status', 30)->nullable()->after('status');
            $table->string('payment_reference', 100)->nullable()->after('payment_status'); // número de operación / token pasarela
            $table->string('payment_gateway', 30)->nullable()->after('payment_reference'); // 'culqi' | 'mercadopago' | 'manual'
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'payment_reference', 'payment_gateway']);
        });
    }
};
