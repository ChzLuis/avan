<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Diseños guardados del constructor: plantillas reutilizables como DATOS
 * (nunca vistas nuevas). Idempotente; down() no destruye en producción.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('design_templates')) {
            Schema::create('design_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('owner_id')->index();
                $table->string('name', 120);
                $table->string('description', 500)->nullable();
                $table->string('category', 60)->nullable();      // rubro sugerido
                $table->string('thumbnail_path', 500)->nullable();
                $table->unsignedBigInteger('source_project_id')->nullable();
                $table->boolean('is_favorite')->default(false);
                $table->boolean('is_default')->default(false);
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('design_template_versions')) {
            Schema::create('design_template_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('design_template_id')->index();
                $table->unsignedInteger('version');
                $table->longText('payload');                      // JSON schema v1
                $table->string('notes', 300)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->unique(['design_template_id', 'version'], 'design_tpl_version_unique');
            });
        }
    }

    public function down(): void
    {
        // Intencionalmente vacío: en producción nunca se debe perder una
        // biblioteca de diseños por un rollback accidental.
    }
};
