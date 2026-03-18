<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Client extends Model {
    protected $fillable = ['project_id', 'name', 'phone', 'email', 'notes'];
    public function project()      { return $this->belongsTo(Project::class); }
    public function orders()       { return $this->hasMany(Order::class); }
    public function quotes()       { return $this->hasMany(Quote::class); }
    public function appointments() { return $this->hasMany(Appointment::class); }
}
