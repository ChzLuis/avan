<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rifa_ventas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->string('wa_number');
            $table->tinyInteger('plan')->comment('1-4');
            $table->string('plan_nombre');
            $table->integer('tickets');
            $table->decimal('monto', 8, 2);
            $table->string('nombre')->nullable();
            $table->string('dni', 20)->nullable();
            $table->string('payment_proof')->nullable();
            $table->string('ticket_code')->nullable();
            $table->enum('status', ['pendiente','pagado','enviado','cancelado'])->default('pendiente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rifa_ventas');
    }
};
