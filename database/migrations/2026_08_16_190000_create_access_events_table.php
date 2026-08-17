<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoría de accesos — FASE 12 del plan de Perfiles y Accesos.
 *
 * Quién cambió qué acceso, a quién y cuándo. Debe ser información estructurada
 * y consultable, no un rastro suelto en los logs del servidor: el dueño de un
 * negocio tiene que poder responder «¿quién le dio acceso a Caja a Pedro?».
 *
 * Sigue el patrón que ya usa `order_events`: acción corta + `meta` en JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('access_events')) {
            return;
        }

        Schema::create('access_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->nullable();   // null = cambio global de plataforma
            $table->unsignedBigInteger('actor_id')->nullable();     // quién lo hizo
            $table->unsignedBigInteger('target_user_id')->nullable(); // a quién le afecta
            $table->string('role_name', 120)->nullable();           // perfil implicado
            $table->string('action', 40);
            $table->json('meta')->nullable();                       // antes/después
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['project_id', 'created_at'], 'ae_proyecto_fecha_idx');
            $table->index('target_user_id', 'ae_afectado_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_events');
    }
};
