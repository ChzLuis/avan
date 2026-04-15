<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Employee extends Model {
    protected $fillable = ['project_id', 'name', 'role', 'area', 'phone', 'email', 'hire_date', 'is_active', 'user_id'];
    protected $casts = ['is_active' => 'boolean', 'hire_date' => 'date'];
    public function project() { return $this->belongsTo(Project::class); }
    public function user()    { return $this->belongsTo(User::class); }
}
