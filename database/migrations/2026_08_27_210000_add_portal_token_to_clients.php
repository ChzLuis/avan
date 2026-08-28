<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El enlace personal del cliente (Fase 11, Portal del Cliente).
 *
 * Mismo patrón que las cotizaciones: un token en la URL, sin contraseñas.
 * Nulo = el negocio aún no le generó portal a ese cliente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('clients', 'portal_token')) {
            return;
        }
        Schema::table('clients', function (Blueprint $table) {
            $table->string('portal_token', 64)->nullable()->unique()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('portal_token');
        });
    }
};
