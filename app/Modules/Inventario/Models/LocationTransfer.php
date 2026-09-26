<?php

namespace App\Modules\Inventario\Models;

use App\Models\User;
use App\Modules\Catalogo\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un traslado: tantas unidades salieron de un sitio y entraron en otro.
 *
 * Es inmutable, como el resto de historiales del sistema. Si un traslado se
 * anoto mal, se hace el traslado contrario: reescribirlo borraria la pista de
 * por que la mercaderia acabo donde acabo.
 */
class LocationTransfer extends Model
{
    protected $table = 'location_transfers';

    protected $fillable = [
        'project_id', 'product_id', 'user_id', 'desde_id', 'hasta_id',
        'cantidad', 'entre_sedes', 'guia_referencia', 'nota',
    ];

    protected $casts = ['cantidad' => 'integer', 'entre_sedes' => 'boolean'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function desde(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'desde_id');
    }

    public function hasta(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'hasta_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function update(array $attributes = [], array $options = [])
    {
        throw new \RuntimeException('Un traslado no se modifica: para corregirlo se hace el traslado contrario.');
    }

    public function delete()
    {
        throw new \RuntimeException('Un traslado no se borra: para corregirlo se hace el traslado contrario.');
    }
}
