<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ubicaciones del almacen: donde esta guardada cada cosa.
 *
 * El problema que resuelve es el de siempre en una bodega grande: el sistema
 * dice que hay 40 cajas pero nadie sabe en que estante, y se pierde mas
 * tiempo buscando que despachando.
 *
 * `product_locations` es una tabla aparte y no una columna en `products`
 * por dos razones: `Product` es propiedad del modulo Catalogo y no se toca
 * desde Inventario, y en un almacen real el mismo producto esta repartido en
 * varios sitios (dos pallets, un estante de picking y el sobrante arriba).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            // El codigo es lo que se imprime en el QR del estante.
            $table->string('codigo', 40);
            $table->string('nombre', 120);
            $table->string('zona', 60)->nullable();
            $table->string('tipo', 20)->default('estante');
            $table->text('notas')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Dos estantes no pueden llamarse igual dentro del mismo negocio:
            // al escanear habria que adivinar cual de los dos es.
            $table->unique(['project_id', 'codigo'], 'ubicacion_codigo_unico');
            $table->index(['project_id', 'is_active']);
        });

        Schema::create('product_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_location_id')->constrained('warehouse_locations')->cascadeOnDelete();
            // Cuanto hay EN ESE SITIO. Es orientativo: el saldo que manda
            // sigue siendo `products.stock`, escrito solo por InventoryLedger.
            $table->integer('cantidad')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_location_id'], 'producto_ubicacion_unico');
            $table->index(['project_id', 'warehouse_location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_locations');
        Schema::dropIfExists('warehouse_locations');
    }
};
