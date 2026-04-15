<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaRespuestaRapida extends Model
{
    protected $table = 'wa_respuestas_rapidas';

    protected $fillable = ['project_id', 'canal', 'nombre', 'texto', 'orden'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
