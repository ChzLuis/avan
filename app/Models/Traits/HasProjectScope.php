<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasProjectScope
{
    public static function bootHasProjectScope(): void
    {
        static::addGlobalScope('project', function (Builder $builder) {
            $projectId = session('active_project_id') ?? session('comercial_project_id');
            if ($projectId) {
                $builder->where($builder->getModel()->getTable() . '.project_id', $projectId);
            }
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
