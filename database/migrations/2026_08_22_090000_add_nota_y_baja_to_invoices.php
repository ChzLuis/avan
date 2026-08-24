<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lo que hace falta para corregir un comprobante ya emitido.
 *
 * Hasta ahora "eliminar" una factura borraba la fila de la base de datos. Ante
 * SUNAT ese comprobante seguía existiendo: el negocio quedaba debiendo el IGV
 * de una venta que en su sistema ya no aparecía. La forma legal de deshacerlo
 * es una nota de crédito —o una comunicación de baja, si es del mismo día— y
 * las dos necesitan saber a QUÉ documento afectan y POR QUÉ.
 *
 * `invoices.type` ya admitía 'nota_credito' y 'nota_debito', pero sin estos
 * campos la nota no se podía ni construir: el XML exige el tipo y el número
 * del documento afectado y un código de motivo del catálogo 09 o 10.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // ── Documento al que afecta la nota ───────────────────────────
            // Se guarda el id (para navegar) y también el tipo y el número
            // textuales: la nota tiene que seguir siendo válida aunque el
            // comprobante original se archive o venga de otro sistema.
            if (! Schema::hasColumn('invoices', 'afecta_invoice_id')) {
                $table->foreignId('afecta_invoice_id')->nullable()->after('quote_id')
                    ->constrained('invoices')->nullOnDelete();
            }
            if (! Schema::hasColumn('invoices', 'afecta_tipo')) {
                $table->string('afecta_tipo', 2)->nullable()->after('afecta_invoice_id');  // 01 | 03
            }
            if (! Schema::hasColumn('invoices', 'afecta_numero')) {
                $table->string('afecta_numero', 30)->nullable()->after('afecta_tipo');       // F001-00000123
            }

            // ── Por qué se emite (catálogo 09 para crédito, 10 para débito) ─
            if (! Schema::hasColumn('invoices', 'motivo_codigo')) {
                $table->string('motivo_codigo', 2)->nullable()->after('afecta_numero');
            }
            if (! Schema::hasColumn('invoices', 'motivo_descripcion')) {
                $table->string('motivo_descripcion', 250)->nullable()->after('motivo_codigo');
            }

            // ── Comunicación de baja ──────────────────────────────────────
            // Una factura del mismo día se da de baja; una boleta va en el
            // resumen diario. En ambos casos SUNAT devuelve un ticket que hay
            // que consultar después: sin guardarlo, la baja queda en el aire.
            if (! Schema::hasColumn('invoices', 'baja_estado')) {
                $table->string('baja_estado', 20)->nullable()->after('sunat_sent_at'); // pending|accepted|rejected
            }
            if (! Schema::hasColumn('invoices', 'baja_ticket')) {
                $table->string('baja_ticket', 100)->nullable()->after('baja_estado');
            }
            if (! Schema::hasColumn('invoices', 'baja_motivo')) {
                $table->string('baja_motivo', 250)->nullable()->after('baja_ticket');
            }
            if (! Schema::hasColumn('invoices', 'baja_error')) {
                $table->text('baja_error')->nullable()->after('baja_motivo');
            }
            if (! Schema::hasColumn('invoices', 'baja_at')) {
                $table->timestamp('baja_at')->nullable()->after('baja_error');
            }

            // Quién emitió el comprobante: para una auditoría, un comprobante
            // sin responsable es un comprobante sin dueño.
            if (! Schema::hasColumn('invoices', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('baja_at')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            foreach (['afecta_invoice_id', 'created_by'] as $fk) {
                if (Schema::hasColumn('invoices', $fk)) {
                    $table->dropConstrainedForeignId($fk);
                }
            }

            $sueltas = array_filter(
                ['afecta_tipo', 'afecta_numero', 'motivo_codigo', 'motivo_descripcion',
                 'baja_estado', 'baja_ticket', 'baja_motivo', 'baja_error', 'baja_at'],
                fn ($c) => Schema::hasColumn('invoices', $c)
            );

            if ($sueltas !== []) {
                $table->dropColumn(array_values($sueltas));
            }
        });
    }
};
