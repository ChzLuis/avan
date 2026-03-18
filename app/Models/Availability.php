<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Availability extends Model {
    protected $fillable = ['project_id', 'day_of_week', 'start_time', 'end_time', 'is_available'];
    protected $casts = ['is_available' => 'boolean'];
    public function project() { return $this->belongsTo(Project::class); }
}
