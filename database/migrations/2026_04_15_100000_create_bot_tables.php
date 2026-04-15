<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Flujos del bot por proyecto
        Schema::create('bot_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');                          // ej: "Flujo principal"
            $table->string('bot_type', 30)->default('main'); // main | rifa
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Estados del flujo
        Schema::create('bot_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('bot_flows')->cascadeOnDelete();
            $table->string('key', 60);                       // ej: menu, pedir, pago
            $table->string('label');                         // nombre visible
            $table->text('message');                         // mensaje que envía el bot
            $table->string('input_type', 20)->default('text'); // text|number|image|location|option
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Transiciones entre estados
        Schema::create('bot_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_state_id')->constrained('bot_states')->cascadeOnDelete();
            $table->foreignId('to_state_id')->constrained('bot_states')->cascadeOnDelete();
            $table->string('trigger', 100)->nullable();      // texto que activa (null = cualquier input)
            $table->string('action', 60)->nullable();        // save_name|create_order|save_payment|none
            $table->string('action_param', 100)->nullable(); // parámetro extra para la acción
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Config personalizable por proyecto
        Schema::create('bot_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('bot_type', 30)->default('main');
            $table->string('key', 80);
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'bot_type', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_transitions');
        Schema::dropIfExists('bot_states');
        Schema::dropIfExists('bot_flows');
        Schema::dropIfExists('bot_configs');
    }
};
