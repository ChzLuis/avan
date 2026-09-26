<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ordenes de compra: el documento con el que entra la mercaderia.
 *
 * Hasta ahora el stock solo podia subir con un "ajuste manual", que no dice
 * a quien se le compro, a que precio ni si llego todo. Sin eso no se puede
 * reclamar un faltante al proveedor ni saber si el costo subio.
 *
 * NO hay tabla de recepciones. Cada entrada se anota en el kardex a traves de
 * `InventoryLedger` con motivo `compra` y referencia a esta orden, que ya es
 * el historial del sistema: una tabla paralela seria una segunda verdad que
 * tarde o temprano se contradice con la primera.
 *
 * Las lineas guardan `cantidad_recibida` porque una orden se recibe a trozos:
 * llegan 8 de 10 hoy y 2 la semana que viene, y hasta que no llegue todo la
 * orden sigue abierta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Correlativo visible: OC-0001. Se numera por negocio.
            $table->string('numero', 20);

            // borrador -> enviada -> parcial -> recibida | anulada
            $table->string('estado', 20)->default('borrador');

            $table->date('fecha_emision')->nullable();
            $table->date('fecha_esperada')->nullable();

            // Donde se espera la mercaderia. Al recibir, entra a esta ubicacion.
            $table->foreignId('warehouse_location_id')->nullable()
                ->constrained('warehouse_locations')->nullOnDelete();

            $table->string('moneda', 8)->default('PEN');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('igv', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->text('notas')->nullable();
            $table->timestamp('recibida_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'numero'], 'oc_numero_unico');
            $table->index(['project_id', 'estado']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->integer('cantidad');
            $table->integer('cantidad_recibida')->default(0);
            $table->decimal('precio_unitario', 12, 2)->default(0);

            $table->timestamps();

            // Un producto no se repite en la misma orden: si se pide mas, se
            // sube la cantidad de su linea. Dos lineas del mismo producto
            // harian imposible saber cuanto falta por recibir.
            $table->unique(['purchase_order_id', 'product_id'], 'oc_producto_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
