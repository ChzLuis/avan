<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Sede extends Model {
    protected $fillable = ['project_id','name','address','phone','email','manager','is_active','notes'];
    protected $casts = ['is_active' => 'boolean'];
    public function project() { return $this->belongsTo(Project::class); }
}
