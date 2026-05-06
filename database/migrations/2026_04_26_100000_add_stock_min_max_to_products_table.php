<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'stock_min')) {
                $table->unsignedInteger('stock_min')->nullable()->after('stock');
            }
            if (!Schema::hasColumn('products', 'stock_max')) {
                $table->unsignedInteger('stock_max')->nullable()->after('stock_min');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $cols = array_filter(['stock_min', 'stock_max'], fn($c) => Schema::hasColumn('products', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};
