<?php

namespace App\Modules\Crm\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Etapa del embudo de tratos de un negocio. Ver la migracion crm_tratos_y_etapas.
 */
class CrmEtapa extends Model
{
    protected $table = 'crm_etapas';

    protected $fillable = ['project_id', 'nombre', 'orden', 'color', 'probabilidad', 'es_ganado', 'es_perdido'];

    protected $casts = ['es_ganado' => 'boolean', 'es_perdido' => 'boolean'];

    /** Embudo por defecto para un negocio nuevo: se puede cambiar despues. */
    public const POR_DEFECTO = [
        ['nombre' => 'Nuevo',        'color' => '#64748b', 'probabilidad' => 10],
        ['nombre' => 'Contactado',   'color' => '#0ea5e9', 'probabilidad' => 25],
        ['nombre' => 'Cotizado',     'color' => '#6366f1', 'probabilidad' => 50],
        ['nombre' => 'Negociación',  'color' => '#f59e0b', 'probabilidad' => 75],
        ['nombre' => 'Ganado',       'color' => '#16a34a', 'probabilidad' => 100, 'es_ganado' => true],
        ['nombre' => 'Perdido',      'color' => '#dc2626', 'probabilidad' => 0,   'es_perdido' => true],
    ];

    /** Siembra el embudo por defecto si el negocio aun no tiene etapas. */
    public static function asegurar(Project $project): void
    {
        if (static::where('project_id', $project->id)->exists()) {
            return;
        }
        foreach (self::POR_DEFECTO as $i => $def) {
            static::create($def + ['project_id' => $project->id, 'orden' => $i]);
        }
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tratos(): HasMany
    {
        return $this->hasMany(CrmTrato::class, 'etapa_id');
    }
}
