<?php
namespace App\Models;

use App\Modules\Catalogo\Models\Service;
use Illuminate\Database\Eloquent\Model;
class Appointment extends Model {
    protected $fillable = [
        'project_id', 'service_id', 'client_id', 'client_name', 'client_phone',
        'date', 'start_time', 'end_time', 'status', 'notes',
        'table_number', 'zone', 'guests', 'occasion', 'source',
        'reminder_sent', 'confirmed_at', 'arrived_at',
    ];
    protected $casts = [
        'date'         => 'date',
        'confirmed_at' => 'datetime',
        'arrived_at'   => 'datetime',
        'reminder_sent'=> 'boolean',
    ];
    public function project() { return $this->belongsTo(Project::class); }
    public function service() { return $this->belongsTo(Service::class); }
    public function client()  { return $this->belongsTo(Client::class); }
}
