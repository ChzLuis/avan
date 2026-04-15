<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Certificado extends Model
{
    protected $table = 'certificados';

    protected $fillable = [
        'project_id', 'codigo', 'alumno_nombre', 'alumno_dni',
        'alumno_email', 'curso_nombre', 'curso_horas', 'curso_nivel',
        'fecha_emision', 'fecha_vencimiento', 'instructor',
        'institucion', 'estado', 'notas',
    ];

    protected $casts = [
        'fecha_emision'     => 'date',
        'fecha_vencimiento' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public static function generarCodigo(): string
    {
        do {
            $codigo = 'CERT-' . strtoupper(Str::random(8));
        } while (self::where('codigo', $codigo)->exists());

        return $codigo;
    }

    public function isValido(): bool
    {
        return $this->estado === 'emitido'
            && ($this->fecha_vencimiento === null || $this->fecha_vencimiento->isFuture());
    }
}
