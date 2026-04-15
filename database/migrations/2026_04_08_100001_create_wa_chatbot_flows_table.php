<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_chatbot_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wa_canal_id')->constrained('wa_canales')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->json('trigger_keywords');   // ["hola","info","precio"] — vacío = comodín
            $table->text('response_text');       // Mensaje a enviar
            $table->boolean('es_bienvenida')->default(false); // Se dispara en el primer mensaje del contacto
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        Schema::table('wa_conversaciones', function (Blueprint $table) {
            $table->boolean('bot_activo')->default(true)->after('asignado_a');
        });
    }

    public function down(): void
    {
        Schema::table('wa_conversaciones', function (Blueprint $table) {
            $table->dropColumn('bot_activo');
        });
        Schema::dropIfExists('wa_chatbot_flows');
    }
};
