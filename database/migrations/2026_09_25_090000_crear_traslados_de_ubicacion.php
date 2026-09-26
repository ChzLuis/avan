<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traslados entre ubicaciones: mover mercaderia de un sitio a otro.
 *
 * Un traslado NO cambia el stock del negocio: los 40 sacos siguen siendo 40,
 * solo que ahora estan en otro estante. Por eso no pasa por `InventoryLedger`
 * ni aparece en el kardex: mezclarlo alli llenaria el historial de asientos
 * que no mueven el saldo y harian ilegible lo que si importa.
 *
 * Aun asi queda registrado, porque la pregunta que se hace un almacen cuando
 * algo no esta donde deberia es "quien lo movio y cuando".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // El origen puede ser nulo: a veces se coloca mercaderia que
            // todavia no estaba asignada a ningun sitio (una compra que entra
            // directo al estante).
            $table->foreignId('desde_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('hasta_id')->constrained('warehouse_locations')->cascadeOnDelete();

            $table->integer('cantidad');
            $table->text('nota')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_transfers');
    }
};
