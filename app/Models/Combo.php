<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasProjectScope;

class Combo extends Model {
    use HasProjectScope;
    protected $fillable = ['project_id','name','description','price','compare_price','image_url','is_available','sort_order'];
    protected $casts    = ['price'=>'decimal:2','compare_price'=>'decimal:2','is_available'=>'boolean'];
    public function project() { return $this->belongsTo(Project::class); }
    public function items()   { return $this->hasMany(ComboItem::class); }
    public function getDiscountPercentAttribute(): int {
        if (!$this->compare_price || $this->compare_price <= $this->price) return 0;
        return round((1 - $this->price / $this->compare_price) * 100);
    }
}
