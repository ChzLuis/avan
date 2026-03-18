<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class QuoteItem extends Model {
    protected $fillable = ['quote_id', 'description', 'price', 'quantity'];
    protected $casts = ['price' => 'decimal:2'];
    public function quote() { return $this->belongsTo(Quote::class); }
}
