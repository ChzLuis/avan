<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbandonedCart extends Model
{
    protected $fillable = [
        'project_id', 'client_phone', 'client_email', 'client_name',
        'items', 'subtotal', 'coupon_code',
        'reminder_sent', 'reminder_sent_at', 'recovered_at',
    ];

    protected $casts = [
        'items'             => 'array',
        'subtotal'          => 'decimal:2',
        'reminder_sent'     => 'boolean',
        'reminder_sent_at'  => 'datetime',
        'recovered_at'      => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function isRecovered(): bool
    {
        return $this->recovered_at !== null;
    }
}
