<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Employee extends Model {
    protected $fillable = [
        'project_id', 'name', 'role', 'spatie_role', 'area', 'phone', 'email',
        'hire_date', 'is_active', 'user_id',
        'commission_rate', 'work_start', 'work_end',
    ];
    protected $casts = [
        'is_active'       => 'boolean',
        'hire_date'       => 'date',
        'commission_rate' => 'decimal:2',
    ];
    public function project()    { return $this->belongsTo(Project::class); }
    public function user()       { return $this->belongsTo(User::class); }
    public function attendances(){ return $this->hasMany(Attendance::class); }
    public function schedules()  { return $this->hasMany(WorkSchedule::class); }

    /** ¿El empleado puede acceder ahora según su horario laboral? */
    public function canAccessNow(): bool
    {
        if (!$this->work_start || !$this->work_end) return true;
        $now = now()->format('H:i:s');
        return $now >= $this->work_start && $now <= $this->work_end;
    }
}
