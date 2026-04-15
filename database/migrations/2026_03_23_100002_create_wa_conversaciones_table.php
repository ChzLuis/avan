<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_conversaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wa_canal_id')->constrained('wa_canales')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('cliente_nombre', 100)->nullable();
            $table->string('cliente_telefono', 30);
            $table->string('cliente_sector', 80)->nullable();
            $table->string('cliente_distrito', 80)->nullable();
            $table->string('origen_anuncio', 50)->nullable(); // "anuncio_bixo" | "anuncio_academy" | "organico"
            $table->string('estado', 30)->default('nuevo');   // nuevo|contactado|demo_enviada|propuesta|cerrado|perdido
            $table->text('notas')->nullable();
            $table->boolean('archivado')->default(false);
            $table->integer('no_leidos')->default(0);
            $table->timestamp('ultimo_mensaje_at')->nullable();
            $table->string('asignado_a')->nullable();        // para cuando haya equipo
            $table->timestamps();

            $table->index(['wa_canal_id', 'archivado', 'ultimo_mensaje_at']);
            $table->index('cliente_telefono');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_conversaciones');
    }
};
