<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ExternalRequest extends Model {
    protected $fillable = ['project_id', 'client_id', 'client_name', 'client_phone', 'client_email', 'type', 'title', 'description', 'status', 'response'];
    public function project() { return $this->belongsTo(Project::class); }
    public function client()  { return $this->belongsTo(Client::class); }
}
