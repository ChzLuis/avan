<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    protected $fillable = ['employee_id', 'weekday', 'start_time', 'end_time', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'weekday' => 'integer'];

    public static array $days = [
        0 => 'Lunes', 1 => 'Martes', 2 => 'Miércoles',
        3 => 'Jueves', 4 => 'Viernes', 5 => 'Sábado', 6 => 'Domingo',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /** ¿El horario de hoy está activo en este momento? */
    public function isWithinSchedule(): bool
    {
        if (!$this->is_active) return false;
        $now = now()->format('H:i:s');
        return $now >= $this->start_time && $now <= $this->end_time;
    }
}
