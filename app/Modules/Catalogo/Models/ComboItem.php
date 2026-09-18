<?php
namespace App\Modules\Catalogo\Models;
use Illuminate\Database\Eloquent\Model;

class ComboItem extends Model {
    protected $fillable = ['combo_id','item_type','product_id','service_id','custom_name','quantity'];

    public function combo()   { return $this->belongsTo(Combo::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function service() { return $this->belongsTo(Service::class); }

    /** Nombre a mostrar según el tipo de ítem */
    public function getDisplayNameAttribute(): string {
        return match($this->item_type) {
            'service' => $this->service?->name ?? $this->custom_name ?? '—',
            'product' => $this->product?->name ?? $this->custom_name ?? '—',
            default   => $this->custom_name ?? '—',
        };
    }

    /** Precio unitario del ítem referenciado */
    public function getUnitPriceAttribute(): float {
        return (float) match($this->item_type) {
            'service' => $this->service?->price ?? 0,
            'product' => $this->product?->price ?? 0,
            default   => 0,
        };
    }

    /** Ícono según tipo */
    public function getIconAttribute(): string {
        return match($this->item_type) {
            'service' => '🔧',
            'product' => '📦',
            default   => '➕',
        };
    }
}
