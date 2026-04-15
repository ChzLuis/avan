<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivery_address')) $table->string('delivery_address')->nullable()->after('notes');
            if (!Schema::hasColumn('orders', 'shipping_cost')) $table->decimal('shipping_cost', 10, 2)->nullable()->after('delivery_address');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $cols = array_filter(['delivery_address','shipping_cost'], fn($c) => Schema::hasColumn('orders', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};
