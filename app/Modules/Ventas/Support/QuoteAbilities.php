<?php

namespace App\Modules\Ventas\Support;

use App\Models\Project;
use App\Models\User;

/**
 * Capacidades del usuario sobre Cotizaciones, resueltas en UN solo sitio.
 *
 * Espejo de OrderAbilities, con una diferencia importante: 'convertir' depende
 * ademas del PROYECTO, porque crear un pedido exige que el proyecto tenga el
 * modulo 'orders' activo. Por eso para() recibe el proyecto y devuelve UNA
 * sola clave 'convertir' ya resuelta: dos fuentes de verdad (un permiso por un
 * lado y un $puedeConvertir por otro) acaban desincronizandose.
 *
 * Esto NO sustituye la autorizacion del servidor, que sigue siendo la unica
 * barrera real: su proposito es no enseñar botones que terminan en 403/422.
 */
class QuoteAbilities
{
    /** Mismos pares A|B que los middleware de routes/web.php. */
    private const MAPA = [
        'ver'      => ['quotes.ver',      'view-quotes'],
        'crear'    => ['quotes.crear',    'manage-quotes'],
        'editar'   => ['quotes.editar',   'manage-quotes'],
        'eliminar' => ['quotes.eliminar', 'manage-quotes'],
    ];

    /** Permiso que gobierna la conversion (el mismo que exige la ruta). */
    private const PERMISO_CONVERTIR = ['quotes.editar', 'manage-quotes'];

    public static function para(?User $user, ?Project $project = null): array
    {
        $capacidades = [];
        foreach (self::MAPA as $accion => $alternativas) {
            $capacidades[$accion] = self::alguno($user, $alternativas);
        }

        // Convertir = permiso de edicion Y modulo de pedidos activo en el
        // proyecto. Sin el modulo, el pedido no tendria donde vivir.
        $capacidades['convertir'] = self::alguno($user, self::PERMISO_CONVERTIR)
            && $project !== null
            && $project->hasModule('orders');

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
