<?php
namespace App\Modules\Tienda\Models;

use App\Models\Product;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasProjectScope;
class Promotion extends Model {
    use HasProjectScope;
    protected $fillable = ['project_id','name','description','image_url','label','type','value','applies_to','applies_to_id','coupon_code','max_uses','uses_count','min_order','is_active','starts_at','ends_at'];
    protected $casts    = ['value'=>'decimal:2','min_order'=>'decimal:2','is_active'=>'boolean','starts_at'=>'datetime','ends_at'=>'datetime'];
    public function project() { return $this->belongsTo(Project::class); }
    /**
     * Producto al que aplica (cuando `applies_to` = product). Es lo que la
     * tienda pinta en la tarjeta de promocion: foto, marca y "Agregar a
     * cotizacion" del articulo real, sin duplicarlo.
     */
    public function producto() { return $this->belongsTo(Product::class, 'applies_to_id'); }
    /** Vigentes hoy: activas y dentro de fechas. */
    public function scopeVigentes($q) {
        $now = now();
        return $q->where('is_active', true)
            ->where(fn ($w) => $w->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($w) => $w->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }
    public function isActive(): bool {
        if (!$this->is_active) return false;
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) return false;
        if ($this->ends_at   && $now->gt($this->ends_at))   return false;
        if ($this->max_uses  && $this->uses_count >= $this->max_uses) return false;
        return true;
    }
}
