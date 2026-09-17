<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial del lector de comprobantes.
 *
 * Una lectura cuesta dinero (llamada a un modelo de visión) y puede acabar
 * en un comprobante que se declara a SUNAT. Sin rastro no habría forma de
 * responder a "¿de dónde salió esta factura?" ni de detectar que alguien
 * subió dos veces el mismo papel.
 *
 * Guarda el resultado, no el archivo: la imagen solo se conserva mientras se
 * revisa, y el dato que importa después es qué se leyó y en qué acabó.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lecturas_comprobantes')) {
            return;
        }

        Schema::create('lecturas_comprobantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // El comprobante que salió de esta lectura, si el usuario llegó a emitirlo.
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();

            $table->string('estado', 20)->default('procesando');
            $table->string('archivo', 255)->nullable();
            $table->string('mime', 60)->nullable();
            $table->unsignedInteger('bytes')->default(0);
            $table->string('motor', 30)->nullable();

            // Lo leído, ya normalizado. Sirve para reabrir una revisión y para
            // auditar qué vio el modelo frente a lo que acabó emitiéndose.
            $table->json('resultado')->nullable();
            $table->json('avisos')->nullable();
            $table->string('mensaje_error', 255)->nullable();

            // Identidad del comprobante leído: con esto se detecta el duplicado
            // sin volver a abrir el JSON.
            $table->string('doc_tipo', 20)->nullable();
            $table->string('doc_serie', 10)->nullable();
            $table->string('doc_numero', 20)->nullable();

            $table->timestamps();

            $table->index(['project_id', 'created_at']);
            $table->index(['project_id', 'doc_serie', 'doc_numero'], 'lecturas_doc_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturas_comprobantes');
    }
};
