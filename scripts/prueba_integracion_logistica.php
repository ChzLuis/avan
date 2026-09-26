<?php

/**
 * Prueba de integracion del modulo de logistica.
 *
 * No prueba piezas sueltas: sigue una unidad de producto por toda la cadena y
 * comprueba que las cuatro verdades del sistema dicen lo mismo en cada paso:
 * el stock, la ubicacion, el kardex y lo comprometido.
 */

use App\Modules\Catalogo\Models\Product;
use App\Modules\Inventario\Models\InventoryMovement;
use App\Modules\Inventario\Models\WarehouseLocation;
use App\Modules\Inventario\Support\InventoryLedger;
use App\Modules\Inventario\Support\StockSituacion;
use App\Modules\Ventas\Models\Order;
use Illuminate\Support\Facades\DB;

$PID = 36;
$project = App\Models\Project::find($PID);
app()->instance('active_project', $project);
$u = App\Models\User::find(4);
auth()->login($u);

$p = Product::where('project_id', $PID)->where('sku', 'AGR-007')->first();
$ubi = WarehouseLocation::where('project_id', $PID)->where('codigo', 'A-02')->first();

$foto = function (string $paso) use ($p, $ubi, $PID) {
    $p->refresh();
    $enUbi = DB::table('product_locations')
        ->where('product_id', $p->id)->where('warehouse_location_id', $ubi->id)->value('cantidad');
    $comp = StockSituacion::comprometidoPorProducto($PID)[$p->id] ?? 0;
    $mov = InventoryMovement::where('product_id', $p->id)->count();
    printf("%-26s stock=%-5s ubicacion=%-5s comprometido=%-4s asientos=%d\n",
        $paso, $p->stock, $enUbi ?? '-', $comp, $mov);

    return ['stock' => (int) $p->stock, 'ubi' => $enUbi, 'comp' => $comp, 'mov' => $mov];
};

echo "=== CADENA COMPLETA: ".$p->name." ===\n";
$a = $foto('0. inicio');

// --- 1) Venta por el punto de venta -------------------------------------
$orden = Order::create([
    'project_id' => $PID, 'client_name' => 'PRUEBA integracion',
    'status' => 'pending', 'total' => 0, 'created_by' => 4,
]);
DB::table('order_items')->insert([
    'order_id' => $orden->id, 'product_id' => $p->id, 'name' => $p->name,
    'price' => $p->price, 'quantity' => 6, 'created_at' => now(), 'updated_at' => now(),
]);
$b = $foto('1. pedido creado');

InventoryLedger::registrar($p, -6, 'venta', null, 'PRUEBA integracion', 'order', $orden->id, 4);
$c = $foto('2. stock descontado');

// --- 2) Anulacion: el stock vuelve --------------------------------------
$orden->update(['status' => 'cancelled']);
InventoryLedger::registrar($p, 6, 'anulacion', null, 'PRUEBA integracion devolucion', 'order_cancel', $orden->id, 4);
$d = $foto('3. pedido anulado');

// --- Veredicto -----------------------------------------------------------
echo "\n=== COMPROBACIONES ===\n";
$check = fn ($ok, $txt) => printf("  [%s] %s\n", $ok ? 'OK ' : 'FALLA', $txt);

$check($b['comp'] === $a['comp'] + 6, 'el pedido pendiente suma 6 al comprometido');
$check($c['stock'] === $a['stock'] - 6, 'la venta descuenta 6 del stock');
$check($c['ubi'] !== null && (int) $c['ubi'] === (int) $b['ubi'] - 6, 'la venta descuenta 6 de la ubicacion');
$check($c['mov'] === $b['mov'] + 1, 'la venta deja asiento en el kardex');
$check($d['stock'] === $a['stock'], 'la anulacion devuelve el stock');
$check($d['comp'] === $a['comp'], 'la anulacion libera lo comprometido');
$check((int) $c['stock'] === (int) $c['ubi'], 'stock y ubicacion coinciden tras vender');

// LIMITACION CONOCIDA, no un fallo: la salida sabe de que estante quitar, pero
// una ENTRADA no sabe donde la van a guardar. Tras una devolucion el stock
// sube y la ubicacion no, hasta que alguien diga donde se puso. Igual que en
// la orden de compra, que por eso pide ubicacion de destino.
printf("  [i  ] tras la anulacion el stock sube a %d y la ubicacion queda en %s:
"
    ."         la mercaderia devuelta hay que ubicarla a mano o con un traslado.
",
    $d['stock'], $d['ubi']);

// --- Limpieza ------------------------------------------------------------
DB::table('order_items')->where('order_id', $orden->id)->delete();
$orden->forceDelete();
InventoryMovement::where('notes', 'like', '%PRUEBA integracion%')->delete();
DB::table('product_locations')->where('product_id', $p->id)
    ->where('warehouse_location_id', $ubi->id)->update(['cantidad' => $a['ubi']]);
$p->update(['stock' => $a['stock']]);

$fin = $foto("\nfin (restaurado)");
echo ($fin['stock'] === $a['stock'] && $fin['mov'] === $a['mov'])
    ? "  [OK ] la prueba no dejo rastro\n"
    : "  [FALLA] quedo rastro de la prueba\n";
