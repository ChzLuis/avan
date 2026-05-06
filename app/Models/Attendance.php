<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'project_id', 'employee_id', 'date',
        'check_in', 'check_out', 'minutes_worked', 'status', 'notes',
    ];

    protected $casts = [
        'date'           => 'date',
        'minutes_worked' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function calcularMinutos(): int
    {
        if (!$this->check_in || !$this->check_out) return 0;
        $in  = \Carbon\Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->check_in);
        $out = \Carbon\Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->check_out);
        return max(0, (int) $in->diffInMinutes($out));
    }

    public static function statusLabel(string $status): string
    {
        return match($status) {
            'present'  => 'Presente',
            'absent'   => 'Ausente',
            'late'     => 'Tardanza',
            'half_day' => 'Medio día',
            default    => $status,
        };
    }
}
