<?php

namespace App\Modules\Inventario\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un activo fijo: algo que la empresa posee y alguien tiene a su cargo.
 */
class FixedAsset extends Model
{
    protected $table = 'fixed_assets';

    protected $fillable = [
        'project_id', 'codigo', 'nombre', 'categoria', 'marca', 'modelo', 'serie',
        'responsable', 'warehouse_location_id', 'estado', 'fecha_compra', 'valor_compra', 'notas',
    ];

    protected $casts = [
        'fecha_compra' => 'date',
        'valor_compra' => 'decimal:2',
    ];

    public const ESTADOS = [
        'operativo' => 'Operativo',
        'en_reparacion' => 'En reparación',
        'prestado' => 'Prestado',
        'extraviado' => 'Extraviado',
        'baja' => 'Dado de baja',
    ];

    /** Colores de la etiqueta de estado, para que la lista se lea de un vistazo. */
    public const COLORES = [
        'operativo' => 'bg-emerald-100 text-emerald-800',
        'en_reparacion' => 'bg-amber-100 text-amber-800',
        'prestado' => 'bg-blue-100 text-blue-800',
        'extraviado' => 'bg-red-100 text-red-800',
        'baja' => 'bg-gray-200 text-gray-600',
    ];

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(FixedAssetEvent::class, 'fixed_asset_id')->orderByDesc('id');
    }

    public function etiquetaEstado(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }

    public function colorEstado(): string
    {
        return self::COLORES[$this->estado] ?? 'bg-gray-100 text-gray-700';
    }

    /**
     * Anota un hecho en el historial del activo.
     *
     * Todo cambio pasa por aqui: sin historial no se puede responder quien
     * tenia el equipo cuando se perdio, que es la razon de tener activos fijos.
     */
    public function anotar(string $tipo, ?string $desde = null, ?string $hasta = null, ?string $nota = null): FixedAssetEvent
    {
        return $this->eventos()->create([
            'user_id' => auth()->id(),
            'tipo' => $tipo,
            'desde' => $desde,
            'hasta' => $hasta,
            'nota' => $nota,
        ]);
    }
}
