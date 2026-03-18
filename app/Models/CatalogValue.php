<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogValue extends Model
{
    protected $fillable = ['catalog_list_id', 'label', 'code', 'is_active', 'sort_order'];
    protected $casts = ['is_active' => 'boolean'];

    public function catalogList(): BelongsTo { return $this->belongsTo(CatalogList::class); }
}
