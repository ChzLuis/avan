<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Toma de inventario: el conteo fisico del almacen.
 *
 * Son dos tablas y no una porque el conteo tiene dos vidas distintas. La
 * cabecera (`inventory_counts`) dice quien contó, cuando y si ya se cerro;
 * las lineas (`inventory_count_items`) guardan, producto a producto, cuanto
 * decia el sistema y cuanto habia de verdad en el estante.
 *
 * Lo importante: esta tabla NO toca el stock. Cuando el conteo se cierra, la
 * diferencia se aplica con `InventoryLedger`, que es el unico escritor del
 * stock en todo el sistema. Asi el kardex sigue contando la historia completa
 * y un ajuste por conteo se ve igual que cualquier otro movimiento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nombre', 120);
            // abierta -> contando | cerrada -> ya se aplicaron los ajustes
            $table->string('estado', 20)->default('abierta');
            // Se guarda el alcance para poder repetir el mismo conteo el mes
            // siguiente sin volver a elegir a mano.
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->text('notas')->nullable();
            $table->timestamp('cerrada_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'estado']);
        });

        Schema::create('inventory_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_count_id')->constrained('inventory_counts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // Lo que decia el sistema cuando se conto esa linea. Se congela
            // aqui: si se mira al cerrar, una venta hecha mientras se contaba
            // haria aparecer una diferencia que nadie cometio.
            $table->integer('stock_sistema');
            $table->integer('contado')->nullable();
            $table->timestamp('contado_at')->nullable();
            $table->timestamps();

            // Un producto no puede aparecer dos veces en el mismo conteo: si
            // se escanea de nuevo, se actualiza la linea que ya existe.
            $table->unique(['inventory_count_id', 'product_id'], 'conteo_producto_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_count_items');
        Schema::dropIfExists('inventory_counts');
    }
};
