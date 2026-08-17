<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F2b — Libro mayor de cobros (append-only).
 *
 * Hasta ahora un cobro era una mutacion de `orders.advance_amount`: no se podia
 * enumerar, ni revertir, ni conciliar. Aqui cada cobro es un ASIENTO. Nunca se
 * hace UPDATE ni DELETE sobre esta tabla: corregir es insertar otra fila que
 * revierte a la anterior (`reverses_id`).
 *
 * El dinero va en CENTAVOS ENTEROS, no en decimal: sumar decimales a lo largo
 * de decenas de cobros es exactamente como un saldo acaba distinto segun quien
 * lo mire (misma disciplina que App\Support\LineMath).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            return;
        }

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            // A que documento abona. No se usa relacion polimorfica de Eloquent
            // a proposito: el vocabulario queda explicito y acotado a dos.
            $table->enum('payable_type', ['order', 'quote']);
            $table->unsignedBigInteger('payable_id');

            // SIEMPRE positivo. El signo lo aporta la reversion, no el importe:
            // asi una suma mal escrita no puede fabricar dinero negativo.
            $table->unsignedBigInteger('amount_cents');

            $table->string('method', 40)->nullable();      // yape, efectivo, transferencia...
            $table->string('reference', 120)->nullable();  // n.o de operacion o voucher
            $table->dateTime('received_at');               // cuando entro el dinero (!= created_at)
            $table->string('source', 30)->default('panel'); // panel, bot, extension, pos, portal, backfill
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Reversion: si viene informado, este asiento anula al indicado.
            $table->foreignId('reverses_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('reversal_reason', 200)->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'payable_type', 'payable_id'], 'payments_documento_idx');
            $table->index(['project_id', 'received_at'], 'payments_fecha_idx');
            // Un asiento solo puede revertirse UNA vez: sin esto, dos
            // reversiones del mismo cobro descuadran el saldo en silencio.
            $table->unique('reverses_id', 'payments_reversion_unica');
        });
    }

    public function down(): void
    {
        // Sin down() destructivo: esta tabla es el libro contable. Si hiciera
        // falta revertir la migracion, se decide a mano y con respaldo.
    }
};
