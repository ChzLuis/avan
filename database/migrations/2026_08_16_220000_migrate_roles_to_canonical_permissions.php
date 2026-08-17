<?php

use App\Support\Access;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migra los perfiles al modelo canónico — FASE 6 del plan.
 *
 * A cada rol se le AÑADEN los permisos canónicos equivalentes a los que ya
 * tiene. **No se le quita ninguno de los antiguos.** Durante la transición cada
 * perfil tiene las dos generaciones, así que:
 *
 *   - las rutas siguen exigiendo los permisos viejos y todo funciona igual;
 *   - cuando una ruta pase a exigir el canónico, quien podía seguirá pudiendo.
 *
 * Retirar los antiguos es la Fase 13, y solo cuando nada los use.
 *
 * La equivalencia se queda con el nivel MÁS ALTO de cada área: quien tenía
 * `catalog.ver` y `catalog.eliminar` acaba con `catalogo.administrar`, porque la
 * herencia le devuelve Trabajar y Ver.
 */
return new class extends Migration
{
    public function up(): void
    {
        $canonicos = DB::table('permissions')
            ->whereIn('name', Access::permisos())
            ->where('guard_name', 'web')
            ->pluck('id', 'name');

        foreach (DB::table('roles')->get() as $rol) {
            $antiguos = DB::table('role_has_permissions as rp')
                ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                ->where('rp.role_id', $rol->id)
                ->pluck('p.name')
                ->all();

            foreach (Access::traducir($antiguos) as $nuevo) {
                $permisoId = $canonicos[$nuevo] ?? null;
                if (!$permisoId) {
                    continue;
                }

                $yaLoTiene = DB::table('role_has_permissions')
                    ->where('role_id', $rol->id)
                    ->where('permission_id', $permisoId)
                    ->exists();

                if (!$yaLoTiene) {
                    DB::table('role_has_permissions')->insert([
                        'role_id'       => $rol->id,
                        'permission_id' => $permisoId,
                    ]);
                }
            }
        }

        app('cache')->forget(config('permission.cache.key', 'spatie.permission.cache'));
    }

    /**
     * Revertir retira SOLO los canónicos de los roles. Los permisos siguen
     * existiendo (los creó la Fase 4) y los antiguos nunca se tocaron, así que
     * el sistema vuelve exactamente al estado previo.
     */
    public function down(): void
    {
        $ids = DB::table('permissions')
            ->whereIn('name', Access::permisos())
            ->where('guard_name', 'web')
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();

        app('cache')->forget(config('permission.cache.key', 'spatie.permission.cache'));
    }
};
