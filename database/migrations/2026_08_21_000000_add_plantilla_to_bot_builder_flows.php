<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca de qué plantilla nació un flujo.
 *
 * Sirve para el bot predeterminado: mientras `plantilla` es 'comercial' y
 * `definicion` está vacía, el flujo NO guarda una copia propia — se resuelve
 * desde la plantilla en cada ejecución. Así toda mejora del Bot Comercial llega
 * sola a las empresas que no lo han tocado, en vez de quedarse congelada en la
 * copia del día que se creó. En cuanto el dueño edita algo, se materializa su
 * versión y manda la suya.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('bot_builder_flows', 'plantilla')) {
            return;
        }

        Schema::table('bot_builder_flows', function (Blueprint $table) {
            $table->string('plantilla', 30)->nullable()->after('nombre');
            $table->index(['project_id', 'plantilla']);
        });
    }

    public function down(): void
    {
        Schema::table('bot_builder_flows', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'plantilla']);
            $table->dropColumn('plantilla');
        });
    }
};
