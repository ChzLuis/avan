<?php

namespace App\Modules\Inventario\Support;

use App\Modules\Inventario\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Kardex: punto ÚNICO por donde debe pasar cualquier cambio de stock.
 *
 * La regla es simple: nadie más toca products.stock. Si algún sitio lo cambia
 * por su cuenta (un decrement suelto), el saldo del Kardex deja de cuadrar con
 * la existencia real y el historial pierde todo valor.
 *
 * El movimiento y el stock se escriben en una transacción con el producto
 * bloqueado, porque dos ventas simultáneas del mismo producto podrían leer el
 * mismo saldo y guardar un balance_after equivocado.
 */
class InventoryLedger
{
    /** Motivos de ENTRADA (suman stock). */
    public const ENTRADAS = ['compra', 'devolucion_cliente', 'ajuste_positivo', 'inicial', 'importacion'];

    /** Motivos de SALIDA (restan stock). */
    public const SALIDAS = ['venta', 'devolucion_proveedor', 'merma', 'ajuste_negativo', 'consumo'];

    /** Etiquetas en español para el historial. */
    public const ETIQUETAS = [
        'compra'               => 'Compra a proveedor',
        'devolucion_cliente'   => 'Devolución de cliente',
        'ajuste_positivo'      => 'Ajuste (suma)',
        'inicial'              => 'Stock inicial',
        'importacion'          => 'Carga por Excel',
        'venta'                => 'Venta',
        'devolucion_proveedor' => 'Devolución a proveedor',
        'merma'                => 'Merma / rotura',
        'ajuste_negativo'      => 'Ajuste (resta)',
        'consumo'              => 'Consumo interno',
        'conteo'               => 'Conteo físico',
        'sincronizacion'       => 'Sincronización con proveedor',
        'anulacion'            => 'Anulación de pedido',
    ];

    /**
     * Registra un movimiento y deja el stock del producto coherente.
     *
     * @param  int  $delta  Cantidad con signo: positiva entra, negativa sale.
     * @return InventoryMovement|null  null si el producto no lleva control de stock.
     */
    public static function registrar(
        Product $product,
        int $delta,
        string $reason,
        ?float $unitCost = null,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
    ): ?InventoryMovement {
        // stock null = el producto no lleva inventario (servicios, bajo pedido).
        // No se le inventa un saldo: simplemente no genera Kardex.
        if ($product->stock === null || $delta === 0) {
            return null;
        }

        return DB::transaction(function () use ($product, $delta, $reason, $unitCost, $notes, $referenceType, $referenceId, $userId) {
            $fresco = Product::whereKey($product->getKey())->lockForUpdate()->first();
            if (!$fresco || $fresco->stock === null) {
                return null;
            }

            $saldo = (int) $fresco->stock + $delta;

            $fresco->stock = $saldo;
            $fresco->save();
            $product->stock = $saldo;   // el objeto en memoria queda al día

            return InventoryMovement::create([
                'project_id'     => $fresco->project_id,
                'product_id'     => $fresco->id,
                'user_id'        => $userId ?? auth()->id(),
                'type'           => $delta > 0 ? 'in' : 'out',
                'reason'         => $reason,
                'quantity'       => $delta,
                'unit_cost'      => $unitCost ?? $fresco->cost,
                'balance_after'  => $saldo,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'notes'          => $notes,
            ]);
        });
    }

    /**
     * Deja el stock en una cantidad exacta (edición manual o conteo físico) y
     * registra la diferencia. Devuelve null si no hubo cambio real.
     */
    public static function ajustarA(
        Product $product,
        int $cantidadFinal,
        string $reason = 'conteo',
        ?string $notes = null,
        ?int $userId = null,
    ): ?InventoryMovement {
        if ($product->stock === null) {
            return null;
        }

        $delta = $cantidadFinal - (int) $product->stock;

        return $delta === 0
            ? null
            : self::registrar($product, $delta, $reason, null, $notes, 'ajuste', null, $userId);
    }

    /** Etiqueta legible de un motivo. */
    public static function etiqueta(?string $reason): string
    {
        return self::ETIQUETAS[$reason] ?? ucfirst(str_replace('_', ' ', (string) $reason));
    }
}
