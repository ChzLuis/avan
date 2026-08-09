<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BotFlow extends Model {
    protected $table = 'bot_builder_flows';
    protected $fillable = ['project_id','nombre','activo','definicion'];
    protected $casts = ['definicion'=>'array','activo'=>'boolean'];
    public function project(){ return $this->belongsTo(Project::class); }
}
