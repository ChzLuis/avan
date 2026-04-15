<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'payment_status')) $table->string('payment_status', 30)->nullable()->after('status');
            if (!Schema::hasColumn('orders', 'payment_reference')) $table->string('payment_reference', 100)->nullable()->after('payment_status');
            if (!Schema::hasColumn('orders', 'payment_gateway')) $table->string('payment_gateway', 30)->nullable()->after('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $cols = array_filter(['payment_status','payment_reference','payment_gateway'], fn($c) => Schema::hasColumn('orders', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};
