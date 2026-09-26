<?php

namespace App\Modules\Inventario\Models;

use App\Models\Sede;
use App\Modules\Catalogo\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un sitio del almacen: un estante, un pasillo, una zona de un deposito.
 */
class WarehouseLocation extends Model
{
    protected $table = 'warehouse_locations';

    protected $fillable = [
        'project_id', 'sede_id', 'codigo', 'nombre', 'zona', 'responsable',
        'tipo', 'notas', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const TIPOS = [
        'estante' => 'Estante',
        'pasillo' => 'Pasillo',
        'zona' => 'Zona',
        'deposito' => 'Depósito',
        'vitrina' => 'Vitrina',
    ];

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_locations', 'warehouse_location_id', 'product_id')
            ->withPivot(['cantidad', 'project_id'])
            ->withTimestamps();
    }

    public function activos(): HasMany
    {
        return $this->hasMany(FixedAsset::class, 'warehouse_location_id');
    }

    public function etiquetaTipo(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst((string) $this->tipo);
    }

    /** Nombre completo para mostrar: "A-01 · Estante frente a caja". */
    public function titulo(): string
    {
        return trim($this->codigo.' · '.$this->nombre, ' ·');
    }

    /** Con el local delante: "Almacen Central › A-01 · Estante de papa". */
    public function tituloConSede(): string
    {
        $sede = $this->sede->name ?? null;

        return $sede ? $sede.' › '.$this->titulo() : $this->titulo();
    }
}
