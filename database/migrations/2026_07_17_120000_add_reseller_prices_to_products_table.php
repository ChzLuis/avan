<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Precios para el Modo Revendedor.
 *
 * El revendedor piensa en "cuánto quiero cobrar y cuánto gano", no en
 * "precio unitario referencial con descuento". Para eso el producto necesita:
 *   - price_suggested: el precio que el negocio sugiere (arranca = price actual)
 *   - price_min: nunca puede vender por debajo (el sistema lo bloquea)
 *   - price_max: tope superior opcional
 *   (el costo `cost` ya existe → con él se calcula la ganancia real)
 *
 * Solo AÑADE columnas. No borra ni modifica datos existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'price_suggested')) {
                $table->decimal('price_suggested', 12, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('products', 'price_min')) {
                $table->decimal('price_min', 12, 2)->nullable()->after('price_suggested');
            }
            if (!Schema::hasColumn('products', 'price_max')) {
                $table->decimal('price_max', 12, 2)->nullable()->after('price_min');
            }
        });

        // Sembrar: si no hay precio sugerido, usar el precio actual como sugerido.
        if (Schema::hasColumn('products', 'price_suggested')) {
            \DB::table('products')->whereNull('price_suggested')->update([
                'price_suggested' => \DB::raw('price'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (['price_suggested', 'price_min', 'price_max'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
