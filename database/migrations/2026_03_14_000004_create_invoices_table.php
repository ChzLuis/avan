<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de comprobantes de pago (facturas y boletas).
 *
 * Diseñada para integrarse con SUNAT/OSE en el futuro.
 * Los campos con prefijo sunat_ se usan cuando se conecte
 * a un emisor electrónico (Nubefact, Facturapi, Efact, etc.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            // Origen (puede venir de un pedido, cotización o crearse directo)
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();

            // ── Tipo y numeración ──────────────────────────────────────────
            // type: 'factura' | 'boleta' | 'nota_credito' | 'nota_debito'
            $table->string('type', 20)->default('boleta');
            // serie: F001 (factura) | B001 (boleta) — configurable por negocio
            $table->string('serie', 10)->default('B001');
            // correlativo numérico: 1, 2, 3... (se formatea como 00000001)
            $table->unsignedInteger('correlativo')->default(1);
            // número completo formateado: B001-00000001
            $table->string('numero', 30)->nullable();

            // ── Emisor (datos del negocio al momento de emisión) ───────────
            $table->string('emisor_razon_social', 200)->nullable();
            $table->string('emisor_ruc', 11)->nullable();
            $table->string('emisor_direccion', 300)->nullable();

            // ── Receptor (datos del cliente) ──────────────────────────────
            $table->string('client_name', 200);
            $table->string('client_phone', 30)->nullable();
            $table->string('client_email', 150)->nullable();
            // doc_type: 'DNI' | 'RUC' | 'CE' | 'pasaporte'
            $table->string('client_doc_type', 20)->nullable();
            $table->string('client_doc_number', 15)->nullable();
            $table->string('client_address', 300)->nullable();

            // ── Importes ──────────────────────────────────────────────────
            $table->decimal('subtotal', 12, 2)->default(0);   // sin IGV
            $table->decimal('igv', 12, 2)->default(0);        // 18%
            $table->decimal('total', 12, 2)->default(0);      // con IGV
            $table->string('currency', 3)->default('PEN');
            // igv_enabled: false = precios con IGV incluido (boleta normal)
            $table->boolean('igv_included')->default(true);

            // ── Pago ──────────────────────────────────────────────────────
            $table->string('payment_method', 80)->nullable();
            // paid_at: fecha en que se cobró
            $table->timestamp('paid_at')->nullable();

            // ── Estado ────────────────────────────────────────────────────
            // status: 'draft' | 'issued' | 'sent' | 'cancelled'
            $table->string('status', 20)->default('draft');

            // ── Notas y observaciones ─────────────────────────────────────
            $table->text('notes')->nullable();
            $table->date('issue_date')->nullable();   // fecha de emisión
            $table->date('due_date')->nullable();      // fecha de vencimiento

            // ── SUNAT / OSE (para integración futura) ─────────────────────
            // sunat_status: null | 'pending' | 'accepted' | 'rejected' | 'cancelled'
            $table->string('sunat_status', 20)->nullable();
            // hash/CDR de respuesta del OSE
            $table->string('sunat_hash', 100)->nullable();
            $table->text('sunat_cdr')->nullable();        // XML CDR completo
            $table->string('sunat_ticket', 100)->nullable(); // ticket consulta
            $table->text('sunat_error')->nullable();      // mensaje de error
            $table->timestamp('sunat_sent_at')->nullable();

            $table->timestamps();

            // Índices para búsqueda rápida
            $table->index(['project_id', 'type', 'serie', 'correlativo']);
            $table->index(['project_id', 'status']);
            $table->unique(['project_id', 'type', 'serie', 'correlativo']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 300);
            $table->string('unit', 20)->default('NIU'); // NIU = unidad (código SUNAT)
            $table->decimal('quantity', 10, 3)->default(1);
            $table->decimal('unit_price', 12, 2);       // precio unitario sin IGV
            $table->decimal('igv_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);             // quantity * unit_price
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
