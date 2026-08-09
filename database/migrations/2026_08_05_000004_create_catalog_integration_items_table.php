<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de correspondencia externo ↔ local. Única fuente de verdad de qué
 * entidad de BIXO fue creada/es actualizada por qué integración. El
 * identificador principal de una entidad externa es (integration_id,
 * entity_type, external_id) — NUNCA el SKU solo, porque el SKU puede
 * repetirse entre proveedores o cambiar del lado del ERP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_integration_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained('catalog_integrations')->cascadeOnDelete();
            $table->string('entity_type', 20); // product|service|category

            $table->string('external_id', 120);
            $table->string('external_parent_id', 120)->nullable(); // ej. categoría padre en el ERP
            $table->string('external_sku', 120)->nullable();

            $table->string('local_type', 60)->nullable(); // App\Models\Product, App\Models\Category...
            $table->unsignedBigInteger('local_id')->nullable();

            $table->timestamp('external_updated_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_hash', 64)->nullable(); // hash del NormalizedProduct para saltar sin cambios

            $table->string('sync_status', 20)->default('pending'); // synced|pending|conflict|failed|orphaned
            $table->text('sync_error')->nullable();
            $table->json('raw_metadata')->nullable(); // opcional, saneado, sin secretos — solo para depuración

            $table->timestamps();

            $table->unique(['integration_id', 'entity_type', 'external_id'], 'cii_integration_entity_external_unique');
            $table->index(['local_type', 'local_id']);
            $table->index(['sync_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_integration_items');
    }
};
