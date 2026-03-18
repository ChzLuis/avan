<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Appointment extends Model {
    protected $fillable = ['project_id', 'service_id', 'client_id', 'client_name', 'client_phone', 'date', 'start_time', 'end_time', 'status', 'notes'];
    protected $casts = ['date' => 'date'];
    public function project() { return $this->belongsTo(Project::class); }
    public function service() { return $this->belongsTo(Service::class); }
    public function client()  { return $this->belongsTo(Client::class); }
}
