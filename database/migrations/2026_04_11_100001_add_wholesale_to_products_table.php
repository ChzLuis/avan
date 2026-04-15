<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'wholesale_price')) $table->decimal('wholesale_price', 12, 2)->nullable()->after('compare_price');
            if (!Schema::hasColumn('products', 'wholesale_min_qty')) $table->unsignedInteger('wholesale_min_qty')->nullable()->after('wholesale_price');
        });
    }
    public function down(): void {
        Schema::table('products', function (Blueprint $table) {
            $cols = array_filter(['wholesale_price','wholesale_min_qty'], fn($c) => Schema::hasColumn('products', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};
