<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'project_id', 'code', 'type', 'value',
        'min_order', 'max_uses', 'uses_count', 'expires_at', 'is_active',
        'first_purchase_only',
    ];

    protected $casts = [
        'value'               => 'decimal:2',
        'min_order'           => 'decimal:2',
        'uses_count'          => 'integer',
        'max_uses'            => 'integer',
        'is_active'           => 'boolean',
        'first_purchase_only' => 'boolean',
        'expires_at'          => 'date',
    ];

    public static array $types = [
        'percent'        => '% Descuento',
        'fixed'          => 'Monto fijo',
        'free_shipping'  => 'Envío gratis',
        'first_purchase' => 'Primera compra',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @param float|null $subtotal  Para validar monto mínimo
     * @param string|null $phone    Para verificar si es primera compra
     */
    public function isValid(float $subtotal = null, string $phone = null): bool
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) return false;
        if ($subtotal !== null && $subtotal < (float) $this->min_order) return false;

        // Cupón de primera compra: verificar que el teléfono no tenga pedidos previos
        if ($this->type === 'first_purchase' || $this->first_purchase_only) {
            if ($phone) {
                $hasOrders = Order::allProjects()
                    ->where('project_id', $this->project_id)
                    ->where('client_phone', $phone)
                    ->whereNotIn('status', ['cancelled'])
                    ->exists();
                if ($hasOrders) return false;
            }
        }

        return true;
    }

    /**
     * Calcula el descuento. Para free_shipping devuelve el costo de envío completo.
     */
    public function calculateDiscount(float $subtotal, float $shipping = 0): float
    {
        if ($subtotal < (float) $this->min_order) return 0;

        return match($this->type) {
            'percent'        => round($subtotal * (float) $this->value / 100, 2),
            'fixed'          => min((float) $this->value, $subtotal),
            'free_shipping'  => $shipping,
            'first_purchase' => $this->value > 0
                                    ? ($this->value <= 100
                                        ? round($subtotal * (float) $this->value / 100, 2)
                                        : min((float) $this->value, $subtotal))
                                    : 0,
            default          => min((float) $this->value, $subtotal),
        };
    }
}
