<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reconciliacion de deriva: store_publications y builder_drafts_descartados
 * existian SOLO en ARIN (creadas a mano); en local el Constructor no podia
 * publicar. Estructura copiada de SHOW CREATE TABLE de produccion, con
 * guardas para que la migracion sea inocua donde las tablas ya existen.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('store_publications')) {
            Schema::create('store_publications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('version');
                $table->json('snapshot');
                $table->json('checklist')->nullable();
                $table->foreignId('published_by')->nullable()
                    ->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['project_id', 'version']);
            });
        }

        if (! Schema::hasTable('builder_drafts_descartados')) {
            Schema::create('builder_drafts_descartados', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id')->index();
                $table->string('resource_type', 64)->nullable();
                $table->string('resource_key', 191)->nullable();
                $table->longText('payload')->nullable();
                $table->timestamp('descartado_en')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Solo se revierte donde esta migracion las creo; en ARIN las tablas
        // son anteriores a ella y no deben tocarse desde aqui.
        Schema::dropIfExists('builder_drafts_descartados');
        Schema::dropIfExists('store_publications');
    }
};
