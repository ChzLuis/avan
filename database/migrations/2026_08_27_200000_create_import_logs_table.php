<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La tabla que el modelo esperaba desde siempre.
 *
 * ImportLog::create() corre tras cada importacion de productos y servicios,
 * pero la tabla nunca existio: un try/catch vacio se tragaba el error y el
 * rastro de "quien importo que y cuando" se perdia en silencio. Esta
 * migracion restaura la auditoria de importaciones que el codigo ya escribe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('import_logs')) {
            return; // deriva: si alguien la creo a mano, no se pisa
        }
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);              // products | services | clients...
            $table->string('filename')->default('');
            $table->unsignedInteger('created')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->json('errors')->nullable();
            $table->boolean('has_errors')->default(false);
            $table->timestamps();
            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
