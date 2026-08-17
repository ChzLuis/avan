<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * products.options guarda las variantes del producto (tallas, colores...).
 *
 * La columna existía en producción pero NUNCA tuvo migración: se creó a mano.
 * Resultado: el catálogo daba 500 en cualquier entorno nuevo o local, porque la
 * autodetección de variantes consulta `options like '%"sizes"%'`. Esta migración
 * la declara para que el esquema sea reproducible; en producción no hace nada
 * porque la columna ya está.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'options')) {
                $table->json('options')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'options')) {
                $table->dropColumn('options');
            }
        });
    }
};
