<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flujos visuales del CONSTRUCTOR de bots (el editor drag & drop nuevo).
 *
 * OJO: BIXO ya tiene tablas `bot_flows` y `bot_sessions` de un sistema de bots
 * previo (BotInstance/BotState). Para NO chocar con ellas, este constructor usa
 * el prefijo `bot_builder_*`. El motor FlowRunner ejecuta la `definicion` (JSON).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bot_builder_flows')) {
            Schema::create('bot_builder_flows', function (Blueprint $t) {
                $t->id();
                $t->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $t->string('nombre')->default('Nuevo bot');
                $t->boolean('activo')->default(false);
                $t->json('definicion')->nullable(); // { inicio, bloques:{...} }
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('bot_builder_sessions')) {
            Schema::create('bot_builder_sessions', function (Blueprint $t) {
                $t->id();
                $t->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $t->unsignedBigInteger('bot_builder_flow_id')->nullable();
                $t->string('telefono')->index();
                $t->json('estado')->nullable(); // { bloque, vars, esperando }
                $t->timestamp('ultima_at')->nullable();
                $t->timestamps();
                $t->unique(['project_id', 'telefono']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_builder_sessions');
        Schema::dropIfExists('bot_builder_flows');
    }
};
