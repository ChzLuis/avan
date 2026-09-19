<?php

namespace App\Modules\Crm\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Estado de una conversacion del CRM ("Nuevo", "Propuesta", "No responde"...).
 * Cada negocio tiene los suyos y los edita desde Configuracion; antes estaban
 * escritos a mano en cuatro vistas y no se podian cambiar.
 */
class CrmEstado extends Model
{
    protected $table = 'crm_estados';

    protected $fillable = ['project_id', 'clave', 'nombre', 'color', 'orden', 'es_inicial', 'es_final'];

    protected $casts = ['es_inicial' => 'boolean', 'es_final' => 'boolean', 'orden' => 'integer'];

    /** Los de fabrica: los siete de siempre mas los que pidio el usuario. */
    public const SEMILLA = [
        ['clave' => 'nuevo',        'nombre' => 'Nuevo',        'color' => '#16a34a', 'es_inicial' => true],
        ['clave' => 'contactado',   'nombre' => 'Contactado',   'color' => '#2563eb'],
        ['clave' => 'seguimiento',  'nombre' => 'Seguimiento',  'color' => '#0891b2'],
        ['clave' => 'demo_enviada', 'nombre' => 'Demo enviada', 'color' => '#d97706'],
        ['clave' => 'propuesta',    'nombre' => 'Propuesta',    'color' => '#7c3aed'],
        ['clave' => 'negociacion',  'nombre' => 'Negociación',  'color' => '#c026d3'],
        ['clave' => 'no_responde',  'nombre' => 'No responde',  'color' => '#a16207'],
        ['clave' => 'proyecto',     'nombre' => 'Proyecto',     'color' => '#0284c7'],
        ['clave' => 'venta',        'nombre' => 'Venta',        'color' => '#059669', 'es_final' => true],
        ['clave' => 'perdido',      'nombre' => 'Perdido',      'color' => '#dc2626', 'es_final' => true],
    ];

    /** Siembra los estados de fabrica la primera vez que el negocio entra al CRM. */
    public static function asegurar(Project $project): void
    {
        if (static::where('project_id', $project->id)->exists()) {
            return;
        }
        foreach (self::SEMILLA as $i => $e) {
            static::create($e + ['project_id' => $project->id, 'orden' => $i]);
        }
    }

    /** Estados del negocio, ordenados, como array listo para las vistas. */
    public static function delProyecto(int $projectId): array
    {
        return static::where('project_id', $projectId)->orderBy('orden')->orderBy('id')->get()
            ->map(fn ($e) => [
                'clave' => $e->clave, 'nombre' => $e->nombre, 'color' => $e->color,
                'es_inicial' => $e->es_inicial, 'es_final' => $e->es_final,
            ])->values()->all();
    }

    /** Clave unica a partir de un nombre escrito por el usuario. */
    public static function claveDesde(string $nombre, int $projectId, ?int $ignorarId = null): string
    {
        $base = Str::slug($nombre, '_') ?: 'estado';
        $clave = $base;
        $i = 2;
        while (static::where('project_id', $projectId)->where('clave', $clave)->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))->exists()) {
            $clave = $base . '_' . $i++;
        }

        return mb_substr($clave, 0, 40);
    }
}
