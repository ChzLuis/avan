<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Estados de conversacion editables por negocio (antes estaban escritos a mano en las vistas). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_estados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->string('clave', 40);                    // lo que se guarda en wa_conversaciones.estado
            $table->string('nombre', 60);
            $table->string('color', 9)->default('#64748b'); // color del chip
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('es_inicial')->default(false);  // con el que nace una conversacion
            $table->boolean('es_final')->default(false);    // cuenta como "cerrada" en la bandeja
            $table->timestamps();
            $table->unique(['project_id', 'clave'], 'crm_estados_project_clave_unq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_estados');
    }
};
