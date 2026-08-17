<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * roles.description — la descripción del perfil.
 *
 * La pantalla de Roles ya tenía el campo, el navegador ya lo enviaba y el
 * controlador ya lo validaba, pero la columna no existía: se descartaba en cada
 * guardado y al recargar siempre salía vacío.
 *
 * Es la pieza que permite que un dueño de negocio entienda para qué sirve cada
 * perfil sin leer una lista de permisos técnicos. FASE 1 del plan de Perfiles y
 * Accesos: no cambia ningún permiso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'description')) {
                $table->string('description', 200)->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
