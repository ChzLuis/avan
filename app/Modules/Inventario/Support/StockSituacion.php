<?php

namespace App\Modules\Inventario\Support;

use Illuminate\Support\Facades\DB;

/**
 * Las tres cifras de stock que un almacen necesita distinguir.
 *
 * En este sistema la venta descuenta el stock EN EL MOMENTO de crear el
 * pedido, no cuando la mercaderia sale por la puerta. Eso es correcto para
 * vender (no se puede prometer dos veces lo mismo), pero crea un desfase que
 * confunde a quien esta en el almacen:
 *
 *   - `sistema`      lo que queda por vender. Es `products.stock`.
 *   - `comprometido` vendido y AUN EN EL ESTANTE, esperando despacho.
 *   - `fisico`       lo que deberia encontrarse al contar = sistema + comprometido.
 *
 * Sin esta distincion, cada toma de inventario marca como SOBRANTE todo lo
 * que esta vendido y sin despachar, y el encargado termina desconfiando del
 * sistema o, peor, "corrigiendo" un stock que estaba bien.
 *
 * No hay tabla nueva: se calcula de los pedidos. Guardarlo seria una segunda
 * verdad que tarde o temprano se desincroniza de la primera.
 */
class StockSituacion
{
    /**
     * Estados de pedido que ya descontaron stock pero todavia no salieron.
     *
     * `done` y `completed` ya se entregaron; `cancelled` devolvio el stock.
     */
    public const PENDIENTES_DE_SALIDA = ['pending', 'pagado', 'preparando', 'listo'];

    /**
     * Unidades comprometidas por producto: [product_id => cantidad].
     *
     * Una sola consulta para todo el catalogo, no una por producto: en un
     * negocio con miles de referencias, preguntar de uno en uno tumba la
     * pantalla de inventario.
     */
    public static function comprometidoPorProducto(int $projectId): array
    {
        // El SUM va con alias en el select, no dentro de pluck(): pluck no
        // resuelve una expresion cruda como clave y devolvia todo a cero.
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.project_id', $projectId)
            ->whereIn('orders.status', self::PENDIENTES_DE_SALIDA)
            ->whereNotNull('order_items.product_id')
            ->groupBy('order_items.product_id')
            ->select('order_items.product_id', DB::raw('SUM(order_items.quantity) AS unidades'))
            ->pluck('unidades', 'product_id')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /** Las tres cifras de un producto concreto. */
    public static function de(int $projectId, int $productId, ?int $stockSistema): array
    {
        $comprometido = self::comprometidoPorProducto($projectId)[$productId] ?? 0;

        return self::componer($stockSistema, $comprometido);
    }

    /**
     * Arma las tres cifras a partir del stock y lo comprometido.
     *
     * Un producto sin control de stock (`null`) devuelve null en todo: no se
     * le inventa un saldo, igual que hace InventoryLedger.
     */
    public static function componer(?int $stockSistema, int $comprometido): array
    {
        if ($stockSistema === null) {
            return ['sistema' => null, 'comprometido' => $comprometido, 'fisico' => null];
        }

        return [
            'sistema' => $stockSistema,
            'comprometido' => $comprometido,
            'fisico' => $stockSistema + $comprometido,
        ];
    }

    /**
     * Resumen del negocio, para la cabecera del inventario.
     *
     * `sobreventa` son los productos con stock negativo: se vendio mas de lo
     * que habia. Es el aviso mas urgente de una pantalla de inventario, porque
     * significa que hay un cliente esperando algo que no existe.
     */
    public static function resumen(int $projectId, $productos): array
    {
        $comprometidos = self::comprometidoPorProducto($projectId);

        $totalComprometido = 0;
        $conCompromiso = 0;
        $sobreventa = 0;

        foreach ($productos as $p) {
            $n = $comprometidos[$p->id] ?? 0;
            if ($n > 0) {
                $totalComprometido += $n;
                $conCompromiso++;
            }
            if ($p->stock !== null && (int) $p->stock < 0) {
                $sobreventa++;
            }
        }

        return [
            'comprometido' => $totalComprometido,
            'referencias_comprometidas' => $conCompromiso,
            'sobreventa' => $sobreventa,
        ];
    }
}
