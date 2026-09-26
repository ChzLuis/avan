<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bultos de un pedido: las cajas fisicas en que viaja.
 *
 * Un pedido de 30 productos puede salir en 4 cajas, y lo que se escanea al
 * cargar el camion o al entregar es la caja, no el pedido. Sin bultos, la
 * pregunta "llegaron las cuatro?" no tiene respuesta.
 *
 * El rastro de quien lo despacho y cuando NO se guarda aqui: va a
 * `OrderEvent`, que ya existe, es inmutable y es donde el equipo ya mira la
 * historia del pedido. Aqui solo vive el estado actual del bulto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // Lo que se imprime en la etiqueta del bulto.
            $table->string('codigo', 40);
            $table->string('descripcion', 150)->nullable();
            $table->decimal('peso_kg', 8, 2)->nullable();
            // preparado -> despachado -> entregado
            $table->string('estado', 20)->default('preparado');
            $table->timestamp('despachado_at')->nullable();
            $table->timestamp('entregado_at')->nullable();
            $table->string('recibido_por', 120)->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'codigo'], 'bulto_codigo_unico');
            $table->index(['project_id', 'estado']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_packages');
    }
};
