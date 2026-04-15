<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BotFlow extends Model
{
    protected $fillable = ['project_id', 'name', 'bot_type', 'is_active'];
    protected $casts    = ['is_active' => 'boolean'];

    public function project() { return $this->belongsTo(Project::class); }
    public function states()  { return $this->hasMany(BotState::class, 'flow_id')->orderBy('sort_order'); }
}
