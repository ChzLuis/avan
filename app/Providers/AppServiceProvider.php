<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        RedirectIfAuthenticated::redirectUsing(fn () => route('workspace'));

        // Equipo por defecto = 0, «sin proyecto».
        //
        // Con los teams de Spatie activos, `team_id` forma parte de la clave
        // primaria de model_has_roles y no admite nulos. Fuera de una petición
        // con negocio activo —comandos, colas, seeders— no hay equipo que fijar,
        // y sin este valor por defecto cualquier asignación de rol reventaría.
        // SetActiveProject lo sustituye por el proyecto real en cada petición.
        setPermissionsTeamId(0);

        // Durante la transición, los PERFILES siguen siendo globales.
        //
        // Spatie asigna al rol recién creado el equipo activo, así que un perfil
        // creado dentro de un negocio quedaría atado a él y dejaría de existir
        // para los demás — justo lo contrario de lo que hay hoy, donde los 13
        // roles son globales (`team_id` nulo) y funcionan en todas partes.
        //
        // Lo que YA es por proyecto es la ASIGNACIÓN: quién tiene qué perfil en
        // qué negocio. Las copias de perfil por negocio llegan en la Fase 9, y
        // entonces se retira esta línea.
        \Spatie\Permission\Models\Role::creating(function ($rol) {
            $rol->team_id = null;
        });

        // Superadmin y owner del proyecto activo tienen todos los permisos
        Gate::before(function ($user, $ability) {
            if ($user->is_superadmin) {
                return true;
            }
            $project = app()->bound('active_project') ? app('active_project') : null;
            if ($project && $project->owner_id === $user->id) {
                return true;
            }

            // Herencia de niveles: Administrar incluye Trabajar, y Trabajar
            // incluye Ver. Se resuelve aquí y no guardando los tres permisos en
            // la base, para que un perfil tenga UNA fila por área y no puedan
            // existir combinaciones imposibles como «editar sin ver».
            //
            // Devolver null (no false) es deliberado: así la comprobación sigue
            // su curso normal y este Gate solo puede CONCEDER, nunca denegar.
            // El bucle no llega a ejecutarse para los permisos no canónicos:
            // equivalentesSuperiores() devuelve [] y no hay coste añadido.
            foreach (\App\Support\Access::equivalentesSuperiores((string) $ability) as $superior) {
                try {
                    if ($user->hasPermissionTo($superior, 'web')) {
                        return true;
                    }
                } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
                    // Entorno sin sembrar: no concede nada y sigue su curso.
                }
            }

            return null;
        });
    }
}
