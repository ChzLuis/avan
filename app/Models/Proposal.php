<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proposal extends Model
{
    protected $fillable = [
        'project_id', 'number', 'client_name', 'client_phone', 'client_email',
        'business_name', 'rubro', 'city', 'price', 'price_first', 'price_second',
        'valid_days', 'plat3_name', 'plat3_features', 'plat4_name', 'plat4_features',
        'extra_notes', 'status', 'sent_at',
    ];

    protected $casts = [
        'plat3_features' => 'array',
        'plat4_features' => 'array',
        'sent_at'        => 'datetime',
        'price'          => 'decimal:2',
        'price_first'    => 'decimal:2',
        'price_second'   => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
