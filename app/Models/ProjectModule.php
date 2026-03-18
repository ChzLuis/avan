<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProjectModule extends Model {
    protected $fillable = ['project_id', 'module_id', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
