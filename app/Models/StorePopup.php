<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorePopup extends Model
{
    protected $fillable = ['project_id', 'title', 'description', 'image_path', 'button_text', 'button_url', 'starts_at', 'ends_at', 'delay_seconds', 'frequency', 'show_desktop', 'show_mobile', 'is_enabled'];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_enabled' => 'boolean', 'show_desktop' => 'boolean', 'show_mobile' => 'boolean'];
    public function project() { return $this->belongsTo(Project::class); }
}
