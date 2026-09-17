<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ficha tecnica del producto: PDF propio o enlace del proveedor.
 *
 * Son dos vias para lo mismo y no se excluyen: hay proveedores que mandan el
 * PDF y otros que solo publican la ficha en su web. Si estan las dos, manda el
 * archivo subido, que es el que no se cae cuando el proveedor cambia su sitio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Ruta relativa dentro del disco publico (no URL completa: el
            // dominio de la tienda cambia y las rutas guardadas se romperian).
            $table->string('ficha_tecnica_archivo')->nullable()->after('description');
            $table->string('ficha_tecnica_url')->nullable()->after('ficha_tecnica_archivo');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['ficha_tecnica_archivo', 'ficha_tecnica_url']);
        });
    }
};
