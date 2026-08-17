<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perfiles por proyecto — FASE 7 del plan de Perfiles y Accesos.
 *
 * Activa el soporte de «teams» de spatie/laravel-permission usando el proyecto
 * como equipo. Añade `team_id` a `roles`, `model_has_roles` y
 * `model_has_permissions`.
 *
 * POR QUÉ ESTA MIGRACIÓN Y NO LA DEL PAQUETE
 * La que trae Spatie (`add_teams_fields.php.stub`) rellena `team_id` con el valor
 * fijo **1**, y aquí los proyectos son 7, 10, 16, 18, 19, 20 y 21: las 21
 * asignaciones existentes quedarían colgando de un equipo que no existe y todo el
 * mundo perdería su rol. Aquí se rellena con el proyecto real de cada persona.
 *
 * DECISIONES DE RELLENO
 *  - Los 13 roles actuales se quedan con `team_id = NULL`. Spatie trata el nulo
 *    como GLOBAL (Role.php:175) y los encuentra en cualquier proyecto, así que
 *    todo sigue funcionando igual. Las copias por negocio llegan en la Fase 9.
 *  - Cada asignación toma el proyecto de la ficha de empleado de esa persona y,
 *    si no la tiene, el de su membresía.
 *  - Las asignaciones que no pertenecen a ningún proyecto (10 revendedores y 2
 *    usuarios de prueba) se marcan con `team_id = 0`. No se borran: hoy tampoco
 *    conceden nada —esas personas no son miembros de ningún negocio— y borrar
 *    datos en una migración de infraestructura sería pasarse.
 */
return new class extends Migration
{
    private const SIN_PROYECTO = 0;

    public function up(): void
    {
        $esSqlite = DB::getDriverName() === 'sqlite';

        // ── roles: pueden pertenecer a un proyecto (o ser globales con NULL) ──
        if (!Schema::hasColumn('roles', 'team_id')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->unsignedBigInteger('team_id')->nullable()->after('id');
                $table->index('team_id', 'roles_team_foreign_key_index');
            });

            // El unico pasa a incluir el proyecto: así «Vendedor» puede existir
            // una vez por negocio sin chocar.
            Schema::table('roles', function (Blueprint $table) {
                try {
                    $table->dropUnique('roles_name_guard_name_unique');
                } catch (\Throwable) {
                    // Entornos donde el índice tiene otro nombre o no existe.
                }
                $table->unique(['team_id', 'name', 'guard_name'], 'roles_team_name_guard_unique');
            });
        }

        // ── model_has_roles ──
        if (!Schema::hasColumn('model_has_roles', 'team_id')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('team_id')->default(self::SIN_PROYECTO);
                $table->index('team_id', 'model_has_roles_team_foreign_key_index');
            });

            $this->rellenarProyectoDeCadaAsignacion();

            Schema::table('model_has_roles', function (Blueprint $table) use ($esSqlite) {
                if (!$esSqlite) {
                    try { $table->dropForeign(['role_id']); } catch (\Throwable) {}
                }
                $table->dropPrimary();
                $table->primary(['team_id', 'role_id', 'model_id', 'model_type'],
                    'model_has_roles_role_model_type_primary');
                if (!$esSqlite) {
                    $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
                }
            });
        }

        // ── model_has_permissions (vacía en producción, pero debe cuadrar) ──
        if (!Schema::hasColumn('model_has_permissions', 'team_id')) {
            Schema::table('model_has_permissions', function (Blueprint $table) use ($esSqlite) {
                $table->unsignedBigInteger('team_id')->default(self::SIN_PROYECTO);
                $table->index('team_id', 'model_has_permissions_team_foreign_key_index');
                if (!$esSqlite) {
                    try { $table->dropForeign(['permission_id']); } catch (\Throwable) {}
                }
                $table->dropPrimary();
                $table->primary(['team_id', 'permission_id', 'model_id', 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
                if (!$esSqlite) {
                    $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
                }
            });
        }

        app('cache')->forget(config('permission.cache.key', 'spatie.permission.cache'));
    }

    /**
     * Cada asignación al proyecto de esa persona: primero por su ficha de
     * empleado con ese mismo rol, luego por cualquier ficha suya, y por último
     * por su membresía. Lo que no se pueda atribuir queda en SIN_PROYECTO.
     */
    private function rellenarProyectoDeCadaAsignacion(): void
    {
        if (!Schema::hasTable('employees') || !Schema::hasTable('project_members')) {
            return;
        }

        foreach (DB::table('model_has_roles')->get() as $fila) {
            $rol = DB::table('roles')->where('id', $fila->role_id)->value('name');

            $proyecto = DB::table('employees')
                    ->where('user_id', $fila->model_id)
                    ->where('spatie_role', $rol)
                    ->value('project_id')
                ?? DB::table('employees')
                    ->where('user_id', $fila->model_id)
                    ->value('project_id')
                ?? DB::table('project_members')
                    ->where('user_id', $fila->model_id)
                    ->value('project_id')
                ?? self::SIN_PROYECTO;

            DB::table('model_has_roles')
                ->where('role_id', $fila->role_id)
                ->where('model_id', $fila->model_id)
                ->where('model_type', $fila->model_type)
                ->update(['team_id' => $proyecto]);
        }
    }

    public function down(): void
    {
        $esSqlite = DB::getDriverName() === 'sqlite';

        if (Schema::hasColumn('model_has_permissions', 'team_id')) {
            Schema::table('model_has_permissions', function (Blueprint $table) use ($esSqlite) {
                if (!$esSqlite) { try { $table->dropForeign(['permission_id']); } catch (\Throwable) {} }
                $table->dropPrimary();
                $table->dropIndex('model_has_permissions_team_foreign_key_index');
                $table->dropColumn('team_id');
                $table->primary(['permission_id', 'model_id', 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
                if (!$esSqlite) {
                    $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
                }
            });
        }

        if (Schema::hasColumn('model_has_roles', 'team_id')) {
            Schema::table('model_has_roles', function (Blueprint $table) use ($esSqlite) {
                if (!$esSqlite) { try { $table->dropForeign(['role_id']); } catch (\Throwable) {} }
                $table->dropPrimary();
                $table->dropIndex('model_has_roles_team_foreign_key_index');
                $table->dropColumn('team_id');
                $table->primary(['role_id', 'model_id', 'model_type'],
                    'model_has_roles_role_model_type_primary');
                if (!$esSqlite) {
                    $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
                }
            });
        }

        if (Schema::hasColumn('roles', 'team_id')) {
            Schema::table('roles', function (Blueprint $table) {
                try { $table->dropUnique('roles_team_name_guard_unique'); } catch (\Throwable) {}
                $table->dropIndex('roles_team_foreign_key_index');
                $table->dropColumn('team_id');
                $table->unique(['name', 'guard_name'], 'roles_name_guard_name_unique');
            });
        }

        app('cache')->forget(config('permission.cache.key', 'spatie.permission.cache'));
    }
};
