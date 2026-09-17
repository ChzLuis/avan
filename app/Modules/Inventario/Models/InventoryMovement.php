<?php
namespace App\Modules\Inventario\Models;

use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Models\Traits\HasProjectScope;
use Illuminate\Database\Eloquent\Model;
class InventoryMovement extends Model {
    use HasProjectScope;
    protected $fillable = [
        'project_id', 'product_id', 'user_id', 'type', 'reason', 'quantity',
        'unit_cost', 'balance_after', 'reference_type', 'reference_id', 'notes',
    ];
    protected $casts = ['unit_cost' => 'decimal:2', 'quantity' => 'integer', 'balance_after' => 'integer'];

    public function project() { return $this->belongsTo(Project::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function user()    { return $this->belongsTo(User::class); }

    /** Motivo legible ("Compra a proveedor", "Venta"…). */
    public function getMotivoAttribute(): string
    {
        return \App\Support\InventoryLedger::etiqueta($this->reason);
    }

    /** Valor del movimiento (cantidad × costo unitario), siempre positivo. */
    public function getValorAttribute(): float
    {
        return abs((int) $this->quantity) * (float) ($this->unit_cost ?? 0);
    }
}
