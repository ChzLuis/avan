<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Employee extends Model {
    protected $fillable = ['project_id', 'name', 'role', 'area', 'phone', 'email', 'hire_date', 'is_active'];
    protected $casts = ['is_active' => 'boolean', 'hire_date' => 'date'];
    public function project() { return $this->belongsTo(Project::class); }
}
