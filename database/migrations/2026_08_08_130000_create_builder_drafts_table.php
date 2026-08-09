<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// La tabla ya existe en producción (se creó fuera de una migración);
// esta migración la formaliza para entornos nuevos. Idempotente.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('builder_drafts')) {
            return;
        }
        Schema::create('builder_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('resource_type', 40);
            $table->string('resource_key', 120);
            $table->json('payload');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'resource_type', 'resource_key'], 'builder_drafts_unique');
            $table->index(['project_id', 'resource_type']);
        });
    }

    public function down(): void
    {
        // Producción conserva los borradores: rollback intencionalmente vacío.
    }
};
