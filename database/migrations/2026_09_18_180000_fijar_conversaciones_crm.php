<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bandeja estilo WhatsApp: una conversacion se puede FIJAR arriba. Archivar y
 * "marcar como no leida" ya cabian en columnas existentes (archivado,
 * no_leidos); esta es la unica que faltaba.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_conversaciones', function (Blueprint $table) {
            $table->boolean('fijada')->default(false)->after('archivado');
        });
    }

    public function down(): void
    {
        Schema::table('wa_conversaciones', function (Blueprint $table) {
            $table->dropColumn('fijada');
        });
    }
};
