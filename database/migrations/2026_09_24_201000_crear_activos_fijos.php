<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Activos fijos: lo que la empresa POSEE, no lo que vende.
 *
 * Una laptop, un mostrador, un taladro de la cuadrilla. No es un `Product`
 * aunque se le parezca: un producto se vende y se repone, un activo se
 * asigna a alguien, se deprecia y algun dia se da de baja. Meterlos en el
 * catalogo ensuciaria la tienda y el kardex.
 *
 * Por eso vive aparte, con lo unico que de verdad se pregunta cuando algo se
 * pierde: quien lo tiene y donde deberia estar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            // Lo que va impreso en el QR pegado al equipo.
            $table->string('codigo', 40);
            $table->string('nombre', 150);
            $table->string('categoria', 60)->nullable();
            $table->string('marca', 80)->nullable();
            $table->string('modelo', 80)->nullable();
            $table->string('serie', 80)->nullable();

            // Quien responde por el equipo. Es texto libre y no una relacion
            // con `users`: casi siempre el responsable es alguien que no tiene
            // cuenta en el sistema (un tecnico, un vigilante, un cliente).
            $table->string('responsable', 120)->nullable();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();

            // operativo | en_reparacion | prestado | baja | extraviado
            $table->string('estado', 20)->default('operativo');

            $table->date('fecha_compra')->nullable();
            $table->decimal('valor_compra', 12, 2)->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'codigo'], 'activo_codigo_unico');
            $table->index(['project_id', 'estado']);
        });

        // Historial: sin esto, "quien tenia la laptop en marzo" no se puede
        // responder, que es justo para lo que sirve tener activos fijos.
        Schema::create('fixed_asset_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // asignacion | devolucion | estado | ubicacion | alta | baja | revision
            $table->string('tipo', 20);
            $table->string('desde', 150)->nullable();
            $table->string('hasta', 150)->nullable();
            $table->text('nota')->nullable();
            $table->timestamps();

            $table->index('fixed_asset_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_asset_events');
        Schema::dropIfExists('fixed_assets');
    }
};
