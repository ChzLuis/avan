<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasProjectScope;
class Promotion extends Model {
    use HasProjectScope;
    protected $fillable = ['project_id','name','description','type','value','applies_to','applies_to_id','coupon_code','max_uses','uses_count','min_order','is_active','starts_at','ends_at'];
    protected $casts    = ['value'=>'decimal:2','min_order'=>'decimal:2','is_active'=>'boolean','starts_at'=>'datetime','ends_at'=>'datetime'];
    public function project() { return $this->belongsTo(Project::class); }
    public function isActive(): bool {
        if (!$this->is_active) return false;
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) return false;
        if ($this->ends_at   && $now->gt($this->ends_at))   return false;
        if ($this->max_uses  && $this->uses_count >= $this->max_uses) return false;
        return true;
    }
}
