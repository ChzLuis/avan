<?php

use App\Support\Access;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Crea los 36 permisos canónicos — FASE 4 del plan de Perfiles y Accesos.
 *
 * 12 áreas × 3 niveles, con la convención única `area.nivel` en español.
 * Los nombres salen de `App\Support\Access`, que es la fuente de verdad.
 *
 * Es ADITIVO: no toca ningún permiso existente ni ningún rol. Hasta que las
 * rutas empiecen a usarlos (Fase 6), estos permisos no cambian nada de lo que
 * hoy puede hacer nadie.
 *
 * `agenda.ver` y `caja.ver` ya existían y son exactamente el mismo concepto:
 * se reutilizan en vez de duplicarse.
 */
return new class extends Migration
{
    public function up(): void
    {
        $ahora = now();

        foreach (Access::permisos() as $nombre) {
            $existe = DB::table('permissions')
                ->where('name', $nombre)
                ->where('guard_name', 'web')
                ->exists();

            if (!$existe) {
                DB::table('permissions')->insert([
                    'name'       => $nombre,
                    'guard_name' => 'web',
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }
        }

        app()['cache']->forget('spatie.permission.cache');
    }

    public function down(): void
    {
        // Solo se retiran los que NINGÚN rol esté usando: si la Fase 6 ya migró
        // roles a estos permisos, revertir a ciegas dejaría a gente sin acceso.
        $ids = DB::table('permissions')
            ->whereIn('name', Access::permisos())
            ->where('guard_name', 'web')
            ->pluck('id');

        $enUso = DB::table('role_has_permissions')->whereIn('permission_id', $ids)->pluck('permission_id');

        DB::table('permissions')->whereIn('id', $ids->diff($enUso))->delete();

        app()['cache']->forget('spatie.permission.cache');
    }
};
