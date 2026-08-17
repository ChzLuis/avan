<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F2b — Vencimientos de cobro.
 *
 * Hasta ahora NO existia ninguna fecha de vencimiento en la base, asi que
 * Cuentas por Cobrar calculaba la antiguedad desde `created_at` y llamaba
 * vencido a todo lo que pasara de 15 dias: una venta a 30 dias de credito
 * aparecia morosa el dia 16. El importe era exacto, el semaforo no.
 *
 * Una fila por cuota (`numero` 1..n). Un documento al contado tiene una sola
 * fila con vencimiento el mismo dia: la misma estructura sirve para los dos
 * casos, sin ramas especiales.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('receivable_terms')) {
            return;
        }

        Schema::create('receivable_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            $table->enum('payable_type', ['order', 'quote']);
            $table->unsignedBigInteger('payable_id');

            $table->unsignedSmallInteger('numero')->default(1);
            $table->date('due_date');
            $table->unsignedBigInteger('amount_cents');

            $table->timestamps();

            $table->unique(['payable_type', 'payable_id', 'numero'], 'terms_documento_cuota_unica');
            // El listado de morosidad filtra por vencimiento dentro del proyecto.
            $table->index(['project_id', 'due_date'], 'terms_vencimiento_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receivable_terms');
    }
};
