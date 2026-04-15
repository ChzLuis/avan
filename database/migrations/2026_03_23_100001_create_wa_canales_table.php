<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_canales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('nombre', 80);                    // "AVAN Soluciones" | "AVAN Academy"
            $table->string('tipo', 30);                      // "bixo" | "academy" | "partners"
            $table->string('telefono', 30)->nullable();      // Número WhatsApp Business
            $table->string('phone_number_id', 80)->nullable(); // Meta Phone Number ID
            $table->text('access_token')->nullable();         // Token Meta API
            $table->string('verify_token', 100)->nullable(); // Token verificación webhook
            $table->string('color', 20)->default('#1D9E75'); // Color badge en UI
            $table->boolean('activo')->default(true);
            $table->text('mensaje_bienvenida')->nullable();
            $table->text('mensaje_ausencia')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_canales');
    }
};
