<?php

namespace App\Modules\Inventario\Models;

use App\Modules\Catalogo\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una linea del conteo: que decia el sistema y que habia en el estante.
 */
class InventoryCountItem extends Model
{
    protected $table = 'inventory_count_items';

    protected $fillable = [
        'inventory_count_id', 'product_id', 'stock_sistema', 'contado', 'contado_at',
    ];

    protected $casts = [
        'contado_at' => 'datetime',
        'stock_sistema' => 'integer',
        'contado' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function count(): BelongsTo
    {
        return $this->belongsTo(InventoryCount::class, 'inventory_count_id');
    }

    /**
     * Positivo = sobra en el almacen; negativo = falta.
     *
     * Una linea sin contar devuelve 0, no el stock en negativo: todavia no se
     * sabe nada de ese producto.
     */
    public function diferencia(): int
    {
        if ($this->contado === null) {
            return 0;
        }

        return (int) $this->contado - (int) $this->stock_sistema;
    }

    public function fueContada(): bool
    {
        return $this->contado !== null;
    }
}
