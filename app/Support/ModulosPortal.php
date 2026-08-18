<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Support\Facades\Cache;

/**
 * Qué módulos del portal comercial se le ofrecen a un negocio.
 *
 * Regla: "ten activo solo lo que funciona al 100%; lo que vamos mejorando lo
 * vamos liberando". El nucleo —vender, cobrar, ver— se ofrece siempre que el
 * usuario tenga permiso. Los demas solo aparecen si HAY DATO de que el negocio
 * los usa, o si alguien los enciende a proposito con el ajuste
 * `modulo_<clave>` = 1 en el proyecto.
 *
 * Por que por dato y no por opinion: un menu lleno de modulos vacios no es
 * funcionalidad, es ruido con el que tropezar — y "Pedidos del bot" ademas
 * devuelve 500 fuera de un proyecto de rifas, asi que ofrecerlo era mandar al
 * usuario a un error.
 *
 * Vive aqui porque la misma pregunta se hace en tres sitios (menu lateral,
 * atajos del cajon derecho y accesos rapidos del panel) y tres copias del
 * mismo criterio acaban discrepando.
 */
final class ModulosPortal
{
    /** @return array<string,bool> */
    public static function liberados(Project $project, ?int $userId = null): array
    {
        $uso = Cache::remember("portal.uso.{$project->id}", 60, fn () => [
            'caja'     => \App\Models\Caja::where('project_id', $project->id)->exists(),
            'facturas' => \App\Models\Invoice::where('project_id', $project->id)->exists(),
            'reservas' => \App\Models\Appointment::where('project_id', $project->id)->exists(),
            // Reparto se retira del menu hasta terminarlo: tener pedidos de
            // tipo 'delivery' ya no basta para publicarlo. Se enciende a mano
            // con el ajuste `modulo_reparto` del proyecto, igual que el resto,
            // asi que el negocio que lo pruebe no pierde el acceso.
            'reparto'  => false,
            'bot'      => \App\Models\WaCanal::where('project_id', $project->id)->exists(),
        ]);

        // Los precios propios son del usuario, no del negocio: no se cachean
        // con el proyecto porque cambian de una persona a otra.
        $uso['revendedor'] = $userId !== null
            && \App\Models\ResellerPrice::where('project_id', $project->id)
                ->where('user_id', $userId)->exists();

        $salida = [];
        foreach ($uso as $clave => $usa) {
            $salida[$clave] = $usa || (int) $project->setting('modulo_' . $clave, 0) === 1;
        }

        return $salida;
    }
}
