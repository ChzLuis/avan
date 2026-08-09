<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Integración de catálogo configurada por un proyecto con un proveedor
 * externo (SISKOTE, y a futuro cualquier otro). Un proyecto puede tener
 * varias integraciones activas a la vez (una por proveedor, o varias del
 * mismo proveedor con settings distintos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 60); // clave del Registry, ej. "siskote"
            $table->string('name', 120); // etiqueta elegida por el usuario, ej. "Catálogo tienda principal"

            // Credenciales SIEMPRE cifradas (cast encrypted:array en el modelo). Nunca texto plano.
            $table->text('credentials')->nullable();
            // Configuración NO sensible declarada por el esquema del proveedor (lista de precios, timeout, etc.).
            $table->json('settings')->nullable();
            // Snapshot de ProviderCapabilities al momento de la última prueba de conexión (para pintar UI sin instanciar el proveedor).
            $table->json('capabilities_cache')->nullable();

            $table->string('sync_mode', 20)->default('full'); // full|incremental
            $table->unsignedInteger('sync_interval_minutes')->default(60);
            $table->boolean('active')->default(true);

            $table->timestamp('last_connection_test_at')->nullable();
            $table->boolean('last_connection_ok')->nullable();
            $table->timestamp('last_sync_started_at')->nullable();
            $table->timestamp('last_sync_completed_at')->nullable();
            $table->timestamp('last_successful_sync_at')->nullable();
            $table->string('last_sync_status', 30)->nullable();
            $table->text('last_sync_error')->nullable(); // mensaje saneado, nunca payload crudo con secretos

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'active']);
            $table->index(['provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_integrations');
    }
};
