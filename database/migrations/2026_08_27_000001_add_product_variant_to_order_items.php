<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('order_items', 'product_variant_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->foreignId('product_variant_id')->nullable()->after('product_id')
                    ->constrained('product_variants')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('order_items', 'variant_snapshot')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->json('variant_snapshot')->nullable()->after('name');
            });
        }

        if (! Schema::hasIndex('order_items', ['product_id', 'product_variant_id'])) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->index(['product_id', 'product_variant_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('order_items', 'product_variant_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                if (Schema::hasIndex('order_items', ['product_id', 'product_variant_id'])) {
                    $table->dropIndex(['product_id', 'product_variant_id']);
                }
                $table->dropConstrainedForeignId('product_variant_id');
            });
        }

        if (Schema::hasColumn('order_items', 'variant_snapshot')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('variant_snapshot');
            });
        }
    }
};
