<?php

namespace App\Modules\Bots\Support\FlowEngine;

use App\Modules\Bots\Ia\IA;

/**
 * Carrito de compras del bot (sin IA). Vive en las variables de la sesión
 * (vars['carrito']) como una lista de líneas: [{id,nombre,precio,cantidad}].
 * Métodos puros que reciben y devuelven el array del carrito.
 */
class Carrito
{
    /** Agrega un producto. Si ya existe, suma la cantidad (no duplica línea). */
    public static function agregar(array $carrito, array $prod, int $cant = 1): array
    {
        $cant = max(1, $cant);
        foreach ($carrito as &$item) {
            if ((string) $item['id'] === (string) $prod['id']) {
                $item['cantidad'] += $cant;
                return $carrito;
            }
        }
        unset($item);
        $carrito[] = [
            'id'       => $prod['id'],
            'nombre'   => $prod['nombre'] ?? $prod['name'] ?? 'Producto',
            'precio'   => (float) ($prod['precio'] ?? $prod['price'] ?? 0),
            'cantidad' => $cant,
        ];
        return $carrito;
    }

    /** Fija la cantidad de un producto (0 = eliminar). */
    public static function fijarCantidad(array $carrito, $id, int $cant): array
    {
        $out = [];
        foreach ($carrito as $item) {
            if ((string) $item['id'] === (string) $id) {
                if ($cant > 0) { $item['cantidad'] = $cant; $out[] = $item; }
                // cant<=0 → se omite (eliminado)
            } else {
                $out[] = $item;
            }
        }
        return $out;
    }

    /** Elimina un producto por id. */
    public static function eliminar(array $carrito, $id): array
    {
        return array_values(array_filter($carrito, fn ($i) => (string) $i['id'] !== (string) $id));
    }

    public static function vaciar(): array
    {
        return [];
    }

    public static function subtotal(array $carrito): float
    {
        return array_reduce($carrito, fn ($s, $i) => $s + ($i['precio'] * $i['cantidad']), 0.0);
    }

    public static function cantidadTotal(array $carrito): int
    {
        return array_reduce($carrito, fn ($s, $i) => $s + $i['cantidad'], 0);
    }

    /** Texto bonito del carrito para WhatsApp. */
    public static function resumen(array $carrito, float $envio = 0): string
    {
        if (empty($carrito)) return "🛒 Tu carrito está vacío.";
        $l = ["🛒 *Tu carrito:*"];
        foreach ($carrito as $i) {
            $sub = $i['precio'] * $i['cantidad'];
            $l[] = "• {$i['nombre']} x{$i['cantidad']} — S/ " . number_format($sub, 2);
        }
        $sub = self::subtotal($carrito);
        $l[] = "━━━━━━━━━━━━";
        $l[] = "Subtotal: S/ " . number_format($sub, 2);
        if ($envio > 0) {
            $l[] = "Envío: S/ " . number_format($envio, 2);
            $l[] = "*TOTAL: S/ " . number_format($sub + $envio, 2) . "*";
        }
        return implode("\n", $l);
    }
}
