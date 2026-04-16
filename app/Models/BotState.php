<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BotState extends Model
{
    protected $fillable = ['flow_id', 'key', 'label', 'message', 'images', 'input_type', 'sort_order', 'is_active', 'validation_pattern', 'validation_min', 'validation_max', 'validation_error'];
    protected $casts    = ['is_active' => 'boolean', 'images' => 'array', 'validation_min' => 'integer', 'validation_max' => 'integer'];

    public function flow()        { return $this->belongsTo(BotFlow::class, 'flow_id'); }
    public function transitions() { return $this->hasMany(BotTransition::class, 'from_state_id')->orderBy('sort_order'); }
    public function incomings()   { return $this->hasMany(BotTransition::class, 'to_state_id'); }
}
