<?php
namespace App\Modules\Operaciones\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
class BlockedDate extends Model {
    protected $fillable = ['project_id', 'date', 'reason'];
    protected $casts = ['date' => 'date'];
    public function project() { return $this->belongsTo(Project::class); }
}
