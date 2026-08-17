<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class QuoteItem extends Model {
    protected $fillable = ['quote_id', 'description', 'price', 'discount', 'quantity'];
    protected $casts = [
        'discount' => 'decimal:2','price' => 'decimal:2'];
    public function quote() { return $this->belongsTo(Quote::class); }
}
