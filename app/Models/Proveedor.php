<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model {
    protected $table = 'proveedores';
    protected $fillable = [
        'project_id','name','contact_name','phone','email',
        'address','category','notes','is_active',
    ];
    protected $casts = ['is_active' => 'boolean'];
    public function project() { return $this->belongsTo(Project::class); }
}
