<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Historial de cada ejecución de sincronización, con contadores para auditoría y diagnóstico. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained('catalog_integrations')->cascadeOnDelete();

            $table->string('mode', 20); // full|incremental
            $table->string('trigger', 30); // manual|scheduler|webhook|first_connection|retry|reconciliation
            $table->string('status', 30)->default('pending'); // pending|running|completed|completed_with_errors|failed

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->json('cursor_start')->nullable();
            $table->json('cursor_end')->nullable();

            $table->unsignedInteger('pages_processed')->default(0);
            $table->unsignedInteger('items_received')->default(0);
            $table->unsignedInteger('items_created')->default(0);
            $table->unsignedInteger('items_updated')->default(0);
            $table->unsignedInteger('items_unchanged')->default(0);
            $table->unsignedInteger('items_skipped')->default(0);
            $table->unsignedInteger('items_failed')->default(0);
            $table->unsignedInteger('items_deactivated')->default(0);

            $table->text('error_summary')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['integration_id', 'status']);
            $table->index(['integration_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_sync_runs');
    }
};
