<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Notificaciones push del CRM: claves VAPID (una vez) y suscripciones por usuario y negocio. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_claves', function (Blueprint $table) {
            $table->id();
            $table->string('publica', 120);
            $table->text('privada');
            $table->timestamps();
        });
        Schema::create('push_suscripciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('project_id')->index();
            $table->string('endpoint', 500);
            $table->string('p256dh', 200);
            $table->string('auth', 60);
            $table->string('agente', 200)->nullable();
            $table->timestamp('ultimo_ok_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'endpoint'], 'push_susc_user_endpoint_unq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_suscripciones');
        Schema::dropIfExists('push_claves');
    }
};
