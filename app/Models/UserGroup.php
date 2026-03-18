<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class UserGroup extends Model {
    protected $fillable = ['project_id','name','type','description','color','is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function project() { return $this->belongsTo(Project::class); }
}
