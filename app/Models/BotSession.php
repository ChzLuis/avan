<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BotSession extends Model {
    protected $table = 'bot_builder_sessions';
    protected $fillable = ['project_id','bot_builder_flow_id','telefono','estado','ultima_at'];
    protected $casts = ['estado'=>'array','ultima_at'=>'datetime'];
}
