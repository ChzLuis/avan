<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreMenu extends Model
{
    protected $fillable = ['project_id', 'name', 'location', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function project() { return $this->belongsTo(Project::class); }
    public function items() { return $this->hasMany(StoreMenuItem::class)->orderBy('sort_order')->orderBy('id'); }
    public function rootItems() { return $this->items()->whereNull('parent_id'); }
}
