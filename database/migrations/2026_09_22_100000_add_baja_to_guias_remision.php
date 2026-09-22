<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baja de guías de remisión ante SUNAT.
 *
 * Una guía aceptada no se borra: se comunica su baja. Hasta ahora el sistema
 * lo impedía con un aviso ("se anula ante SUNAT") pero no ofrecía cómo, y el
 * usuario tenía que entrar al portal de SUNAT con su clave SOL.
 *
 * Mismas columnas que `invoices` a propósito: el estado de la baja se lee y se
 * pinta igual en los dos sitios, y quien ya conoce una entiende la otra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guias_remision', function (Blueprint $table) {
            $table->string('baja_estado', 20)->nullable()->after('sunat_sent_at');
            $table->string('baja_ticket', 100)->nullable()->after('baja_estado');
            $table->string('baja_motivo', 250)->nullable()->after('baja_ticket');
            $table->text('baja_error')->nullable()->after('baja_motivo');
            $table->timestamp('baja_at')->nullable()->after('baja_error');
        });
    }

    public function down(): void
    {
        Schema::table('guias_remision', function (Blueprint $table) {
            $table->dropColumn(['baja_estado', 'baja_ticket', 'baja_motivo', 'baja_error', 'baja_at']);
        });
    }
};
