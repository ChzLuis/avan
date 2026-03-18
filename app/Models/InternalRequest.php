<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InternalRequest extends Model {
    protected $fillable = ['project_id', 'requester_id', 'responsible_id', 'type', 'area', 'title', 'description', 'status', 'comments'];
    public function project()     { return $this->belongsTo(Project::class); }
    public function requester()   { return $this->belongsTo(User::class, 'requester_id'); }
    public function responsible() { return $this->belongsTo(User::class, 'responsible_id'); }
}
