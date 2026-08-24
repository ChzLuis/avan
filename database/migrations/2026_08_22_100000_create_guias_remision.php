<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guía de remisión electrónica del remitente.
 *
 * La factura dice qué se vendió; la guía dice cómo viajó la mercadería. Son
 * documentos distintos y SUNAT los exige por separado: mover bienes sin guía
 * expone a que se los retengan en un control de carretera, por muy bien
 * facturados que estén.
 *
 * No cabe en `invoices` aunque comparta numeración y emisor: una guía no tiene
 * importes ni IGV, y sí tiene peso, bultos, dos direcciones, un vehículo y un
 * conductor. Meterla ahí obligaría a dejar en blanco la mitad de la tabla y a
 * llenar la otra mitad de columnas que ninguna factura usa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guias_remision', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            // De dónde nace: casi siempre de una venta ya facturada.
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();

            // ── Numeración ────────────────────────────────────────────────
            // La serie de una guía de remitente empieza por T: T001-00000001.
            $table->string('serie', 10)->default('T001');
            $table->unsignedInteger('correlativo')->default(1);
            $table->string('numero', 30)->nullable();

            // ── Emisor, congelado al emitir ───────────────────────────────
            $table->string('emisor_razon_social', 200)->nullable();
            $table->string('emisor_ruc', 11)->nullable();

            // ── Destinatario ──────────────────────────────────────────────
            $table->string('destinatario_nombre', 200);
            $table->string('destinatario_doc_tipo', 2)->nullable();   // catálogo 06
            $table->string('destinatario_doc_numero', 15)->nullable();

            // ── Traslado ──────────────────────────────────────────────────
            $table->string('motivo_codigo', 2)->default('01');        // catálogo 20
            $table->string('motivo_descripcion', 120)->nullable();
            $table->date('fecha_traslado');                            // cuándo sale
            $table->string('modalidad', 2)->default('02');             // 01 público | 02 privado

            // Peso y bultos: en un control de carretera es lo primero que miran.
            $table->decimal('peso_total', 10, 3)->default(0);
            $table->string('peso_unidad', 5)->default('KGM');
            $table->unsignedInteger('bultos')->nullable();

            // ── Puntos de partida y llegada ───────────────────────────────
            $table->string('partida_ubigeo', 6)->nullable();
            $table->string('partida_direccion', 300);
            $table->string('llegada_ubigeo', 6)->nullable();
            $table->string('llegada_direccion', 300);

            // ── Transporte público: quién lo lleva ────────────────────────
            $table->string('transportista_ruc', 11)->nullable();
            $table->string('transportista_razon_social', 200)->nullable();
            $table->string('transportista_mtc', 20)->nullable();

            // ── Transporte privado: con qué y quién conduce ───────────────
            $table->string('vehiculo_placa', 10)->nullable();
            $table->string('conductor_doc_tipo', 2)->nullable();
            $table->string('conductor_doc_numero', 15)->nullable();
            $table->string('conductor_nombres', 120)->nullable();
            $table->string('conductor_apellidos', 120)->nullable();
            $table->string('conductor_licencia', 20)->nullable();

            // ── Estado propio y estado ante SUNAT ─────────────────────────
            $table->string('status', 20)->default('issued');           // issued | cancelled
            $table->string('sunat_status', 20)->nullable();
            $table->string('sunat_ticket', 100)->nullable();
            $table->string('sunat_hash', 100)->nullable();
            $table->text('sunat_cdr')->nullable();
            $table->text('sunat_error')->nullable();
            $table->timestamp('sunat_sent_at')->nullable();

            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            // Dos guías con el mismo número serían dos traslados idénticos ante
            // SUNAT: el índice lo impide aunque falle el cálculo del correlativo.
            $table->unique(['project_id', 'serie', 'correlativo']);
        });

        Schema::create('guia_remision_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guia_remision_id')->constrained('guias_remision')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('codigo', 60)->nullable();
            $table->string('description', 300);
            $table->string('unit', 5)->default('NIU');    // catálogo 03
            $table->decimal('quantity', 10, 3)->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guia_remision_items');
        Schema::dropIfExists('guias_remision');
    }
};
