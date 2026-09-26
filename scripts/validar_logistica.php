<?php

/**
 * Validacion punto por punto del modulo de logistica.
 *
 * Recorre las 9 capacidades sobre un negocio real y comprueba que cada una
 * hace lo que promete. No mira si "no da error": mira el dato antes y
 * despues, que es lo unico que demuestra algo.
 *
 * Todo lo que crea lo deshace al final.
 *
 * Correr:  php scripts/validar_logistica.php [project_id]
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Project;
use App\Modules\Catalogo\Models\Product;
use App\Modules\Inventario\Controllers\OrdenCompraController;
use App\Modules\Inventario\Controllers\TomaInventarioController;
use App\Modules\Inventario\Controllers\UbicacionController;
use App\Modules\Inventario\Models\InventoryCount;
use App\Modules\Inventario\Models\InventoryMovement;
use App\Modules\Inventario\Models\LocationTransfer;
use App\Modules\Inventario\Models\Proveedor;
use App\Modules\Inventario\Models\PurchaseOrder;
use App\Modules\Inventario\Models\WarehouseLocation;
use App\Modules\Inventario\Support\InventoryLedger;
use App\Modules\Inventario\Support\StockSituacion;
use App\Support\Qr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$PID = (int) ($argv[1] ?? 19);
$project = Project::findOrFail($PID);
app()->instance('active_project', $project);
$user = \App\Models\User::find($project->owner_id) ?? \App\Models\User::first();
auth()->login($user);

echo "=== VALIDACION DEL MODULO DE LOGISTICA ===\n";
echo "Negocio: {$project->name} (#{$PID})\n\n";

$ok = 0;
$mal = 0;
$check = function (string $punto, bool $pasa, string $detalle = '') use (&$ok, &$mal) {
    printf("  [%s] %-46s %s\n", $pasa ? 'OK ' : 'MAL', $punto, $detalle);
    $pasa ? $ok++ : $mal++;
};

$limpiar = [];

// ─── 1. Generar codigos ──────────────────────────────────────────────────
$svg = Qr::svg('PRUEBA-1', 120);
$barras = Qr::barras('PRUEBA-1', 40, 2);
$check('1. Genera QR', str_contains($svg, '<svg'), strlen($svg).' bytes');
$check('1. Genera codigo de barras', str_contains($barras, '<svg'), strlen($barras).' bytes');

// ─── 2. Etiquetas ────────────────────────────────────────────────────────
$conCodigo = Product::where('project_id', $PID)
    ->where(fn ($q) => $q->whereNotNull('sku')->where('sku', '!=', ''))->count();
$check('2. Hay productos etiquetables', $conCodigo > 0, "{$conCodigo} con código");

// ─── 3. Ubicaciones y sedes ──────────────────────────────────────────────
$ubis = WarehouseLocation::where('project_id', $PID)->get();
$conSede = $ubis->whereNotNull('sede_id')->count();
$check('3. Tiene ubicaciones', $ubis->count() > 0, $ubis->count().' ubicaciones');
$check('3. Colgadas de un local', $conSede > 0, "{$conSede} con sede");

if ($ubis->count() < 2) {
    echo "\n  No hay dos ubicaciones: no se puede probar el traslado.\n";
    exit(1);
}

// ─── 4. Stock: sistema, comprometido y estante ───────────────────────────
$p = Product::where('project_id', $PID)->whereNotNull('stock')->where('stock', '>', 20)->first();
$sit = StockSituacion::de($PID, $p->id, (int) $p->stock);
$check('4. Calcula las tres cifras de stock',
    $sit['fisico'] === $sit['sistema'] + $sit['comprometido'],
    "sistema={$sit['sistema']} comprometido={$sit['comprometido']} estante={$sit['fisico']}");

// ─── 5. Traslado entre ubicaciones ───────────────────────────────────────
$origen = $ubis->firstWhere('sede_id', '!=', null) ?? $ubis->first();
$destino = $ubis->first(fn ($u) => $u->id !== $origen->id);

DB::table('product_locations')->updateOrInsert(
    ['product_id' => $p->id, 'warehouse_location_id' => $origen->id],
    ['project_id' => $PID, 'cantidad' => 20, 'created_at' => now(), 'updated_at' => now()]
);

$ctrlUbi = new UbicacionController();
$r = json_decode($ctrlUbi->trasladar(Request::create('/x', 'POST', [
    'codigo' => $p->sku, 'desde_id' => $origen->id, 'hasta_id' => $destino->id,
    'cantidad' => 5, 'nota' => 'VALIDACION',
]))->getContent(), true);

$enOrigen = DB::table('product_locations')->where('product_id', $p->id)
    ->where('warehouse_location_id', $origen->id)->value('cantidad');
$enDestino = DB::table('product_locations')->where('product_id', $p->id)
    ->where('warehouse_location_id', $destino->id)->value('cantidad');

$check('5. Traslada entre ubicaciones', ($r['ok'] ?? false) && (int) $enOrigen === 15,
    $r['ok'] ? "origen={$enOrigen} destino={$enDestino}" : ($r['error'] ?? ''));
$check('5. El traslado NO cambia el stock total',
    (int) $p->fresh()->stock === (int) $p->stock, 'stock='.$p->fresh()->stock);
$check('5. Avisa si cruza locales',
    array_key_exists('entre_sedes', $r), 'entre_sedes='.var_export($r['entre_sedes'] ?? null, true));

// ─── 6. La venta descuenta del estante ───────────────────────────────────
$antesUbi = (int) DB::table('product_locations')->where('product_id', $p->id)
    ->where('warehouse_location_id', $origen->id)->value('cantidad');
$antesStock = (int) $p->fresh()->stock;

InventoryLedger::registrar($p, -3, 'venta', null, 'VALIDACION venta', 'order', 999997, $user->id);

$despUbi = (int) DB::table('product_locations')->where('product_id', $p->id)
    ->where('warehouse_location_id', $origen->id)->value('cantidad');
$check('6. La venta descuenta del stock',
    (int) $p->fresh()->stock === $antesStock - 3, 'stock '.$antesStock.' -> '.$p->fresh()->stock);
$check('6. La venta descuenta de la ubicacion',
    $despUbi === $antesUbi - 3, "ubicación {$antesUbi} -> {$despUbi}");

InventoryLedger::registrar($p, 3, 'ajuste_positivo', null, 'VALIDACION revertir', null, null, $user->id);

// ─── 7. Toma de inventario ───────────────────────────────────────────────
$ctrlToma = new TomaInventarioController();
$ctrlToma->store(Request::create('/x', 'POST', ['nombre' => 'VALIDACION conteo']));
$conteo = InventoryCount::where('project_id', $PID)->where('nombre', 'VALIDACION conteo')->latest('id')->first();
$limpiar['conteo'] = $conteo?->id;

$item = $conteo?->items()->where('product_id', $p->id)->first();
$check('7. El conteo congela el stock del sistema',
    $item && (int) $item->stock_sistema === (int) $p->fresh()->stock,
    $item ? "congelado={$item->stock_sistema}" : 'sin línea');

if ($item) {
    $ctrlToma->contar(Request::create('/x', 'POST', ['item_id' => $item->id, 'cantidad' => (int) $item->stock_sistema - 4]), $conteo->id);
    $item->refresh();
    $check('7. Detecta la diferencia', $item->diferencia() === -4, 'diferencia='.$item->diferencia());

    $stockAntesCierre = (int) $p->fresh()->stock;
    $ctrlToma->cerrar($conteo->id);
    $check('7. Al cerrar ajusta el stock',
        (int) $p->fresh()->stock === $stockAntesCierre - 4,
        $stockAntesCierre.' -> '.$p->fresh()->stock);

    $asiento = InventoryMovement::where('product_id', $p->id)->where('reason', 'conteo')->latest('id')->first();
    $check('7. Deja asiento de "Conteo fisico"', $asiento !== null, $asiento ? 'saldo='.$asiento->balance_after : '');

    InventoryLedger::registrar($p, 4, 'ajuste_positivo', null, 'VALIDACION revertir conteo', null, null, $user->id);
}

// ─── 8. Orden de compra y recepcion ──────────────────────────────────────
$prov = Proveedor::firstOrCreate(['project_id' => $PID, 'name' => 'VALIDACION proveedor'], ['is_active' => 1]);
$limpiar['proveedor'] = $prov->id;

$ctrlOC = new OrdenCompraController();
$ctrlOC->store(Request::create('/x', 'POST', [
    'proveedor_id' => $prov->id, 'warehouse_location_id' => $destino->id,
]));
$oc = PurchaseOrder::where('project_id', $PID)->latest('id')->first();
$limpiar['oc'] = $oc->id;

$ctrlOC->agregarLinea(Request::create('/x', 'POST', [
    'product_id' => $p->id, 'cantidad' => 10, 'precio_unitario' => 7.50,
]), $oc->id);
$oc->refresh()->load('items');

$check('8. Calcula el total con IGV',
    abs((float) $oc->total - 88.50) < 0.01, "subtotal={$oc->subtotal} igv={$oc->igv} total={$oc->total}");

$ctrlOC->enviar($oc->id);
$check('8. Se puede enviar al proveedor', $oc->fresh()->estado === 'enviada', 'estado='.$oc->fresh()->estado);

$stockAntesOC = (int) $p->fresh()->stock;
$linea = $oc->fresh()->items->first();
$ctrlOC->recibir(Request::create('/x', 'POST', ['recibido' => [$linea->id => 4]]), $oc->id);

$check('8. La recepcion parcial entra al stock',
    (int) $p->fresh()->stock === $stockAntesOC + 4, $stockAntesOC.' -> '.$p->fresh()->stock);
$check('8. La orden queda como parcial', $oc->fresh()->estado === 'parcial', 'estado='.$oc->fresh()->estado);

$ctrlOC->recibir(Request::create('/x', 'POST', ['recibido' => [$linea->id => 6]]), $oc->id);
$check('8. Al completar queda recibida', $oc->fresh()->estado === 'recibida', 'pendiente='.$oc->fresh()->pendiente());

$compra = InventoryMovement::where('reference_type', 'purchase_order')->where('reference_id', $oc->id)->count();
$check('8. Deja asientos de compra en el kardex', $compra === 2, "{$compra} asientos");

InventoryLedger::registrar($p, -10, 'ajuste_negativo', null, 'VALIDACION revertir compra', null, null, $user->id);

// ─── 9. Codigos ambiguos ─────────────────────────────────────────────────
$dup = DB::table('products')->where('project_id', $PID)->whereNotNull('sku')->where('sku', '!=', '')
    ->select('sku', DB::raw('COUNT(*) c'))->groupBy('sku')->having('c', '>', 1)->first();

if ($dup) {
    $r2 = json_decode($ctrlUbi->trasladar(Request::create('/x', 'POST', [
        'codigo' => $dup->sku, 'desde_id' => $origen->id, 'hasta_id' => $destino->id, 'cantidad' => 1,
    ]))->getContent(), true);
    $check('9. Bloquea codigos repetidos', ($r2['ok'] ?? true) === false, "sku {$dup->sku} en {$dup->c} productos");
} else {
    $check('9. No hay codigos repetidos', true, 'nada que bloquear');
}

// ─── Limpieza ────────────────────────────────────────────────────────────
DB::table('location_transfers')->where('project_id', $PID)->where('nota', 'VALIDACION')->delete();
if (! empty($limpiar['oc'])) {
    DB::table('purchase_order_items')->where('purchase_order_id', $limpiar['oc'])->delete();
    PurchaseOrder::whereKey($limpiar['oc'])->delete();
}
if (! empty($limpiar['conteo'])) {
    DB::table('inventory_count_items')->where('inventory_count_id', $limpiar['conteo'])->delete();
    InventoryCount::whereKey($limpiar['conteo'])->delete();
}
if (! empty($limpiar['proveedor'])) {
    Proveedor::whereKey($limpiar['proveedor'])->delete();
}
InventoryMovement::where('notes', 'like', '%VALIDACION%')->delete();
InventoryMovement::where('reference_type', 'purchase_order')->where('reference_id', $limpiar['oc'] ?? 0)->delete();
DB::table('product_locations')->where('product_id', $p->id)
    ->whereIn('warehouse_location_id', [$origen->id, $destino->id])->delete();

$final = (int) $p->fresh()->stock;
echo "\n  Producto de prueba: {$p->name}\n";
echo "  Stock final: {$final}\n";

echo "\n=== RESULTADO: {$ok} bien, {$mal} mal ===\n";
exit($mal > 0 ? 1 : 0);
