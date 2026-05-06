<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $fillable = ['project_id','user_id','type','filename','created','updated','skipped','errors','has_errors'];
    protected $casts    = ['errors' => 'array', 'has_errors' => 'boolean'];

    public function project() { return $this->belongsTo(Project::class); }
    public function user()    { return $this->belongsTo(User::class); }
}
