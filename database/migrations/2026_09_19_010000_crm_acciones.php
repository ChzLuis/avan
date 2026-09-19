<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Acciones del CRM: la siguiente cosa que hay que hacer con un cliente, con fecha y recordatorio push. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_acciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->string('titulo', 160);
            $table->text('notas')->nullable();
            $table->string('tipo', 20)->default('tarea');              // tarea | llamada | whatsapp | reunion
            $table->timestamp('vence_at')->nullable()->index();
            $table->timestamp('hecho_at')->nullable();
            $table->timestamp('recordada_at')->nullable();             // push enviado (una sola vez)
            $table->unsignedBigInteger('asignado_a')->nullable()->index();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->unsignedBigInteger('wa_conversacion_id')->nullable()->index();
            $table->unsignedBigInteger('trato_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_acciones');
    }
};
