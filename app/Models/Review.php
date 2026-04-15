<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'project_id', 'product_id', 'author_name', 'author_email',
        'rating', 'comment', 'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'rating'      => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
