<?php

namespace App\Modules\Inventario\Models;

use App\Models\User;
use App\Modules\Catalogo\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Una toma de inventario: el acto de ir al almacen y contar lo que hay.
 *
 * Mientras esta `abierta` no cambia nada del stock; es un borrador. Al
 * cerrarla, cada diferencia se aplica con `InventoryLedger`, que es el unico
 * escritor del stock.
 */
class InventoryCount extends Model
{
    protected $table = 'inventory_counts';

    protected $fillable = [
        'project_id', 'user_id', 'nombre', 'estado', 'category_id', 'notas',
    ];

    protected $casts = [
        'cerrada_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InventoryCountItem::class, 'inventory_count_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function estaAbierta(): bool
    {
        return $this->estado === 'abierta';
    }

    /**
     * Resumen para la pantalla: cuanto se lleva contado y que descuadra.
     *
     * Solo cuentan como diferencia las lineas YA contadas: una linea sin
     * contar no es un faltante, es trabajo pendiente, y mezclarlas haria
     * parecer que falta todo el almacen al empezar.
     */
    public function resumen(): array
    {
        $items = $this->items;
        $contados = $items->whereNotNull('contado');

        return [
            'lineas' => $items->count(),
            'contadas' => $contados->count(),
            'pendientes' => $items->count() - $contados->count(),
            'con_diferencia' => $contados->filter(fn ($i) => $i->diferencia() !== 0)->count(),
            'sobrante' => (int) $contados->sum(fn ($i) => max(0, $i->diferencia())),
            'faltante' => (int) abs($contados->sum(fn ($i) => min(0, $i->diferencia()))),
        ];
    }
}
