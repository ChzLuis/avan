<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Precios propios del revendedor.
 *
 * Un revendedor NO edita products.price (eso cambiaría el precio para todos).
 * En su lugar guarda AQUÍ su propio precio por producto. El POS y su catálogo
 * usan este precio; si no existe, cae al price_suggested/price del producto.
 *
 * También guarda si el producto está incluido en SU catálogo compartible.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reseller_prices')) return;

        Schema::create('reseller_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();   // el revendedor
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 12, 2);            // el precio que puso el revendedor
            $table->boolean('in_catalog')->default(true); // si aparece en su catálogo compartible
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);  // un precio por revendedor y producto
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_prices');
    }
};
