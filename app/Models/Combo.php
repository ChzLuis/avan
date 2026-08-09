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

    /** Suma de los precios individuales de los ítems (para comparar con el precio del combo) */
    public function getItemsTotalAttribute(): float {
        return $this->items->sum(fn($i) => $i->unit_price * $i->quantity);
    }

    /** Ahorro real en soles */
    public function getSavingsAttribute(): float {
        $total = $this->compare_price ?: $this->items_total;
        return max(0, $total - (float) $this->price);
    }

    /**
     * Tipo de combinación del combo:
     * 'mixto'     → productos + servicios
     * 'productos' → solo productos
     * 'servicios' → solo servicios
     */
    public function getComboTypeAttribute(): string {
        $tipos = $this->items->pluck('item_type')->unique();
        $tieneProd = $tipos->contains('product');
        $tieneServ = $tipos->contains('service');
        if ($tieneProd && $tieneServ) return 'mixto';
        if ($tieneServ) return 'servicios';
        if ($tieneProd) return 'productos';
        return 'otro';
    }

    /** Etiqueta bonita del tipo de combo */
    public function getComboTypeLabelAttribute(): string {
        return match($this->combo_type) {
            'mixto'     => '📦🔧 Producto + Servicio',
            'productos' => '📦 Pack de Productos',
            'servicios' => '🔧 Pack de Servicios',
            default     => '🎁 Combo',
        };
    }
}
