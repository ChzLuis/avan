<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BotTransition extends Model
{
    protected $fillable = ['from_state_id', 'to_state_id', 'trigger', 'action', 'action_param', 'sort_order'];

    public function fromState() { return $this->belongsTo(BotState::class, 'from_state_id'); }
    public function toState()   { return $this->belongsTo(BotState::class, 'to_state_id'); }
}
