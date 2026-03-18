<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku', 80)->nullable()->after('name');
            $table->decimal('compare_price', 10, 2)->nullable()->after('price');
            $table->decimal('cost', 10, 2)->nullable()->after('compare_price');
            $table->string('unit', 30)->nullable()->after('cost');
            $table->string('barcode', 80)->nullable()->after('sku');
            $table->unsignedBigInteger('brand_catalog_id')->nullable()->after('category_id');
            $table->text('notes')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sku','compare_price','cost','unit','barcode','brand_catalog_id','notes']);
        });
    }
};
