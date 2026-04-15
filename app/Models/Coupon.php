<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'project_id', 'code', 'type', 'value',
        'min_order', 'max_uses', 'uses_count', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'value'      => 'decimal:2',
        'min_order'  => 'decimal:2',
        'uses_count' => 'integer',
        'max_uses'   => 'integer',
        'is_active'  => 'boolean',
        'expires_at' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) return false;
        return true;
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($subtotal < (float) $this->min_order) return 0;
        if ($this->type === 'percent') {
            return round($subtotal * (float) $this->value / 100, 2);
        }
        return min((float) $this->value, $subtotal);
    }
}
