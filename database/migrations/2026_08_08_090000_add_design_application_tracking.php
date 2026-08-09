<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trazabilidad de aplicaciones de plantillas de diseño: qué plantilla/versión
 * se aplicó a qué proyecto, qué elementos CREÓ y el estado previo de los
 * modificados — para descartar con restauración exacta. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('design_template_applications')) {
            Schema::create('design_template_applications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id')->index();
                $table->unsignedBigInteger('design_template_id')->nullable();
                $table->unsignedInteger('template_version')->nullable();
                $table->unsignedBigInteger('applied_by')->nullable();
                $table->string('status', 20)->default('draft');   // draft | published | discarded
                $table->longText('meta')->nullable();             // JSON: created_popup_ids, previous_states…
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('store_popups', 'design_application_id')) {
            Schema::table('store_popups', function (Blueprint $table) {
                $table->unsignedBigInteger('design_application_id')->nullable()->index()
                    ->comment('Aplicación de plantilla que creó este popup (null = propio del proyecto)');
            });
        }
    }

    public function down(): void
    {
        // Vacío a propósito: la trazabilidad no se destruye por rollback.
    }
};
