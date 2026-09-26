<?php

/**
 * Deja todo el catalogo de MegaHogar con stock 100 para probar la logistica.
 *
 * Dos avisos importantes sobre como esta hecho:
 *
 * 1. ANTES de tocar nada guarda el stock actual en un JSON. MegaHogar es un
 *    cliente real con 825 movimientos de kardex; sin respaldo, esto seria
 *    irreversible.
 *
 * 2. El stock NO se escribe a pelo: pasa por `InventoryLedger`, que es el
 *    unico escritor. Asi cada carga queda como un asiento con su motivo y se
 *    puede deshacer o auditar igual que cualquier otro movimiento. Un
 *    `update(['stock' => 100])` masivo dejaria el kardex mintiendo.
 *
 * Revertir:  php scripts/megahogar_stock_pruebas.php --revertir
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Project;
use App\Modules\Catalogo\Models\Product;
use App\Modules\Inventario\Support\InventoryLedger;
use Illuminate\Support\Facades\DB;

const STOCK_PRUEBA = 100;

$project = Project::where('name', 'like', '%MegaHogar%')->firstOrFail();
$pid = $project->id;
$respaldo = storage_path('app/stock_respaldo_'.$pid.'.json');

$revertir = in_array('--revertir', $argv ?? [], true);

// ----------------------------------------------------------------- revertir
if ($revertir) {
    if (! file_exists($respaldo)) {
        echo "No hay respaldo en {$respaldo}. No se puede revertir.\n";
        exit(1);
    }

    $previo = json_decode(file_get_contents($respaldo), true);
    $vueltos = 0;

    foreach ($previo as $id => $stock) {
        $p = Product::find($id);
        if (! $p) {
            continue;
        }
        // Se restaura tal cual estaba, incluido el `null` de los que no
        // llevaban control de inventario.
        $p->stock = $stock === null ? null : (int) $stock;
        $p->save();
        $vueltos++;
    }

    // Se borran los asientos que genero esta carga, no el historial real.
    $borrados = DB::table('inventory_movements')
        ->where('project_id', $pid)
        ->where('notes', 'like', '%Carga de prueba de logistica%')
        ->delete();

    echo "Revertidos: {$vueltos} productos | asientos de prueba borrados: {$borrados}\n";
    exit(0);
}

// ------------------------------------------------------------------ aplicar
$productos = Product::where('project_id', $pid)->get(['id', 'stock']);

if (! file_exists($respaldo)) {
    $mapa = [];
    foreach ($productos as $p) {
        $mapa[$p->id] = $p->stock;
    }
    file_put_contents($respaldo, json_encode($mapa));
    echo "Respaldo guardado: {$respaldo} (".count($mapa)." productos)\n";
} else {
    echo "Ya existia respaldo, no se pisa: {$respaldo}\n";
}

$activados = 0;   // no llevaban control de inventario
$ajustados = 0;   // ya lo llevaban
$sinCambio = 0;

foreach ($productos as $p) {
    $producto = Product::find($p->id);

    if ($producto->stock === null) {
        // Empieza a llevar inventario: primero en cero, y el Ledger asienta
        // las 100 como entrada. Asi el kardex cuenta la historia completa.
        $producto->stock = 0;
        $producto->save();
        InventoryLedger::registrar(
            $producto, STOCK_PRUEBA, 'inicial', null,
            'Carga de prueba de logistica', null, null, null
        );
        $activados++;

        continue;
    }

    if ((int) $producto->stock === STOCK_PRUEBA) {
        $sinCambio++;

        continue;
    }

    InventoryLedger::ajustarA(
        $producto, STOCK_PRUEBA, 'ajuste_positivo',
        'Carga de prueba de logistica', null
    );
    $ajustados++;
}

echo "Activados (no llevaban control): {$activados}\n";
echo "Ajustados a ".STOCK_PRUEBA.": {$ajustados}\n";
echo "Ya estaban en ".STOCK_PRUEBA.": {$sinCambio}\n";

$con = Product::where('project_id', $pid)->where('stock', STOCK_PRUEBA)->count();
$total = Product::where('project_id', $pid)->count();
echo "\nResultado: {$con} de {$total} productos con stock ".STOCK_PRUEBA."\n";
echo "Para deshacerlo:  php scripts/megahogar_stock_pruebas.php --revertir\n";
