<?php

namespace App\Modules\Inventario\Models;

use App\Modules\Ventas\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un bulto: la caja fisica en la que viaja parte de un pedido.
 */
class OrderPackage extends Model
{
    protected $table = 'order_packages';

    protected $fillable = [
        'project_id', 'order_id', 'codigo', 'descripcion', 'peso_kg',
        'estado', 'despachado_at', 'entregado_at', 'recibido_por', 'notas',
    ];

    protected $casts = [
        'despachado_at' => 'datetime',
        'entregado_at' => 'datetime',
        'peso_kg' => 'decimal:2',
    ];

    public const ESTADOS = [
        'preparado' => 'Preparado',
        'despachado' => 'Despachado',
        'entregado' => 'Entregado',
    ];

    public const COLORES = [
        'preparado' => 'bg-gray-100 text-gray-700',
        'despachado' => 'bg-blue-100 text-blue-800',
        'entregado' => 'bg-emerald-100 text-emerald-800',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function etiquetaEstado(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }

    public function colorEstado(): string
    {
        return self::COLORES[$this->estado] ?? 'bg-gray-100 text-gray-700';
    }

    /** El siguiente paso logico del bulto, o null si ya llego. */
    public function siguienteEstado(): ?string
    {
        return match ($this->estado) {
            'preparado' => 'despachado',
            'despachado' => 'entregado',
            default => null,
        };
    }
}
