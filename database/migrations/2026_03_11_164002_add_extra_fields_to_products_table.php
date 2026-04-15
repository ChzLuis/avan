<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'sku')) $table->string('sku', 80)->nullable()->after('name');
            if (!Schema::hasColumn('products', 'compare_price')) $table->decimal('compare_price', 10, 2)->nullable()->after('price');
            if (!Schema::hasColumn('products', 'cost')) $table->decimal('cost', 10, 2)->nullable()->after('compare_price');
            if (!Schema::hasColumn('products', 'unit')) $table->string('unit', 30)->nullable()->after('cost');
            if (!Schema::hasColumn('products', 'barcode')) $table->string('barcode', 80)->nullable()->after('sku');
            if (!Schema::hasColumn('products', 'brand_catalog_id')) $table->unsignedBigInteger('brand_catalog_id')->nullable()->after('category_id');
            if (!Schema::hasColumn('products', 'notes')) $table->text('notes')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(array_filter(['sku','compare_price','cost','unit','barcode','brand_catalog_id','notes'], fn($c) => Schema::hasColumn('products', $c)));
        });
    }
};
