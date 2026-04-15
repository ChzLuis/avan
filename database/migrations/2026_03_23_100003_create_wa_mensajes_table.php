<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wa_conversacion_id')->constrained('wa_conversaciones')->cascadeOnDelete();
            $table->string('wa_message_id', 120)->nullable()->unique(); // ID de WhatsApp
            $table->string('direccion', 10);               // "entrante" | "saliente"
            $table->string('tipo', 20)->default('texto'); // texto|imagen|audio|documento|sticker
            $table->text('contenido');
            $table->string('media_url', 500)->nullable();
            $table->string('estado', 20)->default('enviado'); // enviado|entregado|leido|fallido
            $table->timestamp('leido_at')->nullable();
            $table->timestamp('entregado_at')->nullable();
            $table->timestamps();

            $table->index(['wa_conversacion_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_mensajes');
    }
};
