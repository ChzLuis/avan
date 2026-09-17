<?php

namespace App\Support;

/**
 * Qué negocio se está viendo, según la cara por la que se entró.
 *
 * BIXO tiene dos caras que conviven en la misma sesión del navegador:
 *   · Configuración (`/bixoadmin`, panel)  -> `active_project_id`
 *   · Operación     (`/bixosales`)         -> `comercial_project_id`
 *
 * Son independientes a propósito: en Operación se entra eligiendo el negocio
 * en su propio login. Antes los tres sitios que resolvían el contexto hacían
 * `active_project_id ?? comercial_project_id` (o al revés), así que cambiar
 * de proyecto en una cara arrastraba a la otra: elegías un negocio en
 * Configuración y Operación te lo cambiaba por debajo.
 *
 * Aquí la cara la decide la RUTA, y solo se cae a la otra clave cuando la
 * propia no existe —para no dejar sin contexto a quien aún no ha entrado por
 * su login—.
 */
final class ContextoProyecto
{
    /** True si la petición viene de la cara de Operación. */
    public static function esOperacion(): bool
    {
        if (! app()->bound('request')) {
            return false;
        }

        $request = request();
        $ruta    = $request?->route();

        if ($ruta && $ruta->getName()) {
            if (str_starts_with($ruta->getName(), 'bixosales.')
                || str_starts_with($ruta->getName(), 'comercial.')) {
                return true;
            }
        }

        // Al resolver el scope el enrutador puede no haber corrido todavía:
        // la URL es la fuente que siempre está.
        return $request !== null
            && ($request->is('bixosales*') || $request->is('comercial*'));
    }

    /**
     * Id del negocio activo para ESTA petición. Null si no hay ninguno.
     */
    public static function id(): ?int
    {
        $propia = self::esOperacion() ? 'comercial_project_id' : 'active_project_id';
        $otra   = self::esOperacion() ? 'active_project_id' : 'comercial_project_id';

        $id = session($propia);

        return $id ? (int) $id : (session($otra) ? (int) session($otra) : null);
    }
}
