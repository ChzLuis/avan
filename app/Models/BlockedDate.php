<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BlockedDate extends Model {
    protected $fillable = ['project_id', 'date', 'reason'];
    protected $casts = ['date' => 'date'];
    public function project() { return $this->belongsTo(Project::class); }
}
