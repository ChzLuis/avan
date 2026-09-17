<?php
namespace App\Models;

use App\Support\FlowEngine\PlantillaComercial;
use Illuminate\Database\Eloquent\Model;

class BotFlow extends Model {
    /** Plantilla del bot predeterminado que tiene toda empresa. */
    public const COMERCIAL = 'comercial';

    protected $table = 'bot_builder_flows';
    protected $fillable = ['project_id','nombre','plantilla','activo','definicion'];
    protected $casts = ['definicion'=>'array','activo'=>'boolean'];
    public function project(){ return $this->belongsTo(Project::class); }

    /**
     * Definición del flujo.
     *
     * Un bot predeterminado sin copia propia sigue la plantilla EN VIVO: se
     * arma con los datos del proyecto en cada ejecución, así que las mejoras
     * del Bot Comercial le llegan sin migrar nada. El día que el dueño edita
     * un bloque se guarda su versión y a partir de ahí manda la suya.
     */
    public function getDefinicionAttribute($value)
    {
        $propia = is_array($value) ? $value : json_decode((string) $value, true);
        if (is_array($propia) && $propia !== []) {
            return $propia;
        }

        if ($this->plantilla === self::COMERCIAL && $this->project) {
            return PlantillaComercial::definicion($this->project);
        }

        return is_array($propia) ? $propia : null;
    }

    /** ¿Sigue la plantilla (sin ediciones propias)? */
    public function sigueLaPlantilla(): bool
    {
        return $this->plantilla === self::COMERCIAL
            && empty($this->getRawOriginal('definicion'));
    }

    /**
     * El bot predeterminado del proyecto, creándolo si aún no existe.
     *
     * Nace DESACTIVADO siempre: el webhook responde con el flujo activo más
     * reciente, así que encender uno solo porque sí desplazaría al bot que la
     * empresa ya tenga funcionando. Encenderlo es un clic, y es del dueño.
     */
    public static function comercialDe(Project $project): self
    {
        return static::firstOrCreate(
            ['project_id' => $project->id, 'plantilla' => self::COMERCIAL],
            ['nombre' => PlantillaComercial::NOMBRE, 'activo' => false, 'definicion' => null]
        );
    }
}
