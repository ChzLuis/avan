<?php

use App\Support\Access;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Recalcula los permisos canónicos de cada perfil con el mapeo corregido.
 *
 * La primera traducción (Fase 6) ensanchaba el acceso de roles reales, porque la
 * herencia hace que el nivel más alto de un área conceda los inferiores y tres
 * permisos estaban mal colocados:
 *
 *   attendance.fichar   -> personal.trabajar    El vendedor pasaba a poder crear
 *                                               y editar EMPLEADOS solo por fichar
 *                                               su propia entrada.
 *   roles.gestionar     -> configuracion.administrar
 *                                               El gerente, que tiene
 *                                               settings.pagos del mismo nivel,
 *                                               ganaba la gestión de perfiles:
 *                                               la escalada de privilegios que se
 *                                               cerró al principio de este trabajo.
 *   catalog-integrations.* -> catalogo.*        Cualquiera que pudiera VER el
 *                                               catálogo entraba a la pantalla de
 *                                               conexión con el ERP.
 *
 * Es seguro recalcular: **ninguna ruta exige todavía un permiso canónico**, así
 * que quitarlos y volver a ponerlos no cambia lo que nadie puede hacer hoy.
 */
return new class extends Migration
{
    public function up(): void
    {
        $canonicos = DB::table('permissions')
            ->whereIn('name', Access::permisos())
            ->where('guard_name', 'web')
            ->pluck('id', 'name');

        // Se leen los permisos de cada rol ANTES de borrar nada.
        //
        // `agenda.ver` y `caja.ver` se llaman igual en las dos generaciones: son
        // permiso antiguo Y canónico a la vez. Borrando primero, desaparecían del
        // origen de la traducción y los roles cuyo nivel máximo en esas áreas era
        // «ver» —contador, solo_lectura, vendedor— se quedaban sin acceso a Caja.
        $original = [];
        foreach (DB::table('roles')->get() as $rol) {
            $original[$rol->id] = DB::table('role_has_permissions as rp')
                ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                ->where('rp.role_id', $rol->id)
                ->pluck('p.name')
                ->all();
        }

        DB::table('role_has_permissions')->whereIn('permission_id', $canonicos->values())->delete();

        foreach ($original as $rolId => $antiguos) {
            foreach (Access::traducir($antiguos) as $nuevo) {
                if ($id = $canonicos[$nuevo] ?? null) {
                    DB::table('role_has_permissions')->insert([
                        'role_id'       => $rolId,
                        'permission_id' => $id,
                    ]);
                }
            }
        }

        app('cache')->forget(config('permission.cache.key', 'spatie.permission.cache'));
    }

    public function down(): void
    {
        // No se revierte a la versión ensanchada a propósito: era incorrecta.
    }
};
