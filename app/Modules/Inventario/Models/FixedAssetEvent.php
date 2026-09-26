<?php

namespace App\Modules\Inventario\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un hecho en la vida de un activo: se asigno, volvio, cambio de estado.
 *
 * Es inmutable, igual que el historial de los pedidos: si alguien pudiera
 * reescribirlo, dejaria de servir como prueba de quien tenia que responder
 * por un equipo perdido. Un apunte mal hecho se corrige con otro apunte.
 */
class FixedAssetEvent extends Model
{
    protected $table = 'fixed_asset_events';

    protected $fillable = ['fixed_asset_id', 'user_id', 'tipo', 'desde', 'hasta', 'nota'];

    public const ETIQUETAS = [
        'alta' => 'Alta',
        'asignacion' => 'Asignado',
        'devolucion' => 'Devuelto',
        'estado' => 'Cambio de estado',
        'ubicacion' => 'Cambio de ubicación',
        'revision' => 'Revisión',
        'baja' => 'Baja',
    ];

    public function activo(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function etiqueta(): string
    {
        return self::ETIQUETAS[$this->tipo] ?? ucfirst((string) $this->tipo);
    }

    public function update(array $attributes = [], array $options = [])
    {
        throw new \RuntimeException('El historial del activo no se modifica: es su registro de auditoría.');
    }

    public function delete()
    {
        throw new \RuntimeException('El historial del activo no se borra: es su registro de auditoría.');
    }
}
