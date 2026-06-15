<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ComboItem extends Model {
    protected $fillable = ['combo_id','item_type','product_id','custom_name','quantity'];
    public function combo()   { return $this->belongsTo(Combo::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function getDisplayNameAttribute(): string {
        return $this->product?->name ?? $this->custom_name ?? '—';
    }
}
