<?php

namespace App\Modules\Ventas\Support;

use App\Models\User;

/**
 * Capacidades del usuario sobre Pedidos, resueltas en UN solo sitio.
 *
 * El backend protege las rutas con project.can:permisoB|permisoLegacy, que es
 * un OR (semantica ANY) entre el universo objetivo (orders.editar) y el
 * heredado (manage-orders). Si la vista repitiera esa equivalencia con @can
 * sueltos, acabaria desincronizada en cuanto cambie una sola ruta.
 *
 * Aqui se declara una vez y la usan Blade y Alpine. El objetivo es no enseñar
 * botones que terminan en 403: NO sustituye la autorizacion del servidor, que
 * sigue siendo la unica barrera real.
 */
class OrderAbilities
{
    /** Mismos pares que los middleware de routes/web.php. */
    private const MAPA = [
        'ver'       => ['orders.ver',      'view-orders'],
        'crear'     => ['orders.crear',    'manage-orders'],
        'editar'    => ['orders.editar',   'manage-orders'],
        'eliminar'  => ['orders.eliminar', 'manage-orders'],
        // Preparacion y entrega: tambien para logistica/almacen, que cambian
        // estado sin poder tocar datos comerciales.
        'logistica' => ['orders.editar',   'manage-logistics'],
    ];

    public static function para(?User $user): array
    {
        $capacidades = [];
        foreach (self::MAPA as $accion => $alternativas) {
            $capacidades[$accion] = self::alguno($user, $alternativas);
        }

        return $capacidades;
    }

    /**
     * ANY sobre las alternativas. Se usa can() y no hasPermissionTo() porque
     * ante un permiso inexistente can() devuelve false en vez de lanzar
     * PermissionDoesNotExist, y los permisos legacy pueden no estar sembrados
     * en todos los entornos. can() ademas respeta el Gate::before que da acceso
     * total a superadmin y al dueño del proyecto.
     */
    private static function alguno(?User $user, array $permisos): bool
    {
        if (! $user) {
            return false;
        }

        foreach ($permisos as $permiso) {
            if ($user->can($permiso)) {
                return true;
            }
        }

        return false;
    }
}
