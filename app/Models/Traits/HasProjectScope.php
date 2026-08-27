<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasProjectScope
{
    public static function bootHasProjectScope(): void
    {
        static::addGlobalScope('project', function (Builder $builder) {
            $projectId = session('active_project_id') ?? session('comercial_project_id');
            if (! $projectId && app()->bound('active_project')) {
                $projectId = app('active_project')?->id;
            }

            if ($projectId) {
                $builder->where($builder->getModel()->getTable() . '.project_id', $projectId);
                return;
            }

            /* TD-001 — politica ante la AUSENCIA de contexto de proyecto:
               antes el scope abria (devolvia TODO); ahora:
               - consola y colas: neutro — el codigo de sistema filtra
                 explicito con allProjects()/forProject(). Los tests corren
                 "en consola", por eso se distingue runningUnitTests().
               - peticion sin usuario (webhooks del bot, paginas publicas):
                 neutro — es el contrato del que dependen los bots.
               - usuario autenticado que NO es superadmin: CERRADO. Un
                 gerente sin proyecto en sesion no ve nada, en vez de verlo
                 todo. */
            if (app()->runningInConsole() && ! app()->runningUnitTests()) {
                return;
            }
            if (! auth()->check() || auth()->user()->is_superadmin) {
                return;
            }
            $builder->whereRaw('1 = 0');
        });
    }

    /** Escapar el scope global cuando se necesite acceso cruzado (solo superadmin/sistema) */
    public static function allProjects(): Builder
    {
        return static::withoutGlobalScope('project');
    }

    /** Scope explícito por proyecto */
    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where($this->getTable() . '.project_id', $projectId);
    }
}
