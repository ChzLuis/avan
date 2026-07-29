<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorePage extends Model
{
    protected $fillable = ['project_id', 'key', 'title', 'content', 'is_enabled'];
    protected $casts = ['content' => 'array', 'is_enabled' => 'boolean'];
    public function project() { return $this->belongsTo(Project::class); }
}
