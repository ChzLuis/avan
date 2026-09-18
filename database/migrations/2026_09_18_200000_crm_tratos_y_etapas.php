<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tratos (oportunidades) del CRM con etapas configurables por negocio.
 *
 * Un trato es "esto se puede vender a esta persona": nace casi siempre de una
 * conversacion de WhatsApp, lleva un valor estimado, una etapa del embudo,
 * un asesor y una fecha de cierre. Las etapas son por negocio (una ferreteria
 * y una consultora no venden igual); al entrar por primera vez se siembran
 * unas por defecto que se pueden renombrar, reordenar o borrar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_etapas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('nombre', 60);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->string('color', 20)->default('#6366f1');
            $table->unsignedTinyInteger('probabilidad')->default(50); // % de cierre tipico de la etapa (prevision ponderada)
            $table->boolean('es_ganado')->default(false);
            $table->boolean('es_perdido')->default(false);
            $table->timestamps();
            $table->index(['project_id', 'orden']);
        });

        Schema::create('crm_tratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('etapa_id')->constrained('crm_etapas')->cascadeOnDelete();
            $table->string('titulo', 120);
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('wa_conversacion_id')->nullable()->constrained('wa_conversaciones')->nullOnDelete();
            $table->string('contacto_nombre', 100)->nullable();
            $table->string('contacto_telefono', 30)->nullable();
            $table->decimal('valor', 12, 2)->default(0);
            $table->string('moneda', 3)->default('PEN');
            $table->foreignId('asesor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('cierre_estimado')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);        // posicion dentro de la columna
            $table->timestamp('etapa_desde')->nullable();               // para "dias en etapa"
            $table->timestamp('ganado_at')->nullable();
            $table->timestamp('perdido_at')->nullable();
            $table->string('motivo_perdida', 160)->nullable();
            $table->text('notas')->nullable();
            $table->string('origen', 20)->default('manual');            // manual | whatsapp
            $table->timestamps();
            $table->index(['project_id', 'etapa_id', 'orden']);
            $table->index(['project_id', 'contacto_telefono']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_tratos');
        Schema::dropIfExists('crm_etapas');
    }
};
