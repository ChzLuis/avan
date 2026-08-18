<?php
/**
 * Backfill del Kardex: documenta el origen de la existencia que ya estaba.
 *
 * 753 productos tienen stock sin un solo movimiento, porque entraron por la
 * importacion masiva antes de que esa puerta pasara por el libro. No se
 * inventa nada: se anota UN movimiento de existencia inicial por producto,
 * con la cantidad que el producto ya tiene.
 *
 * INVARIANTE: la suma de `products.stock` es identica antes y despues. Este
 * script NO escribe en `products`. Si la suma cambia, algo va mal y se
 * revierte la transaccion entera.
 *
 * Sin `user_id`: nadie registro esas entradas y no se le atribuyen a quien
 * ejecuta el backfill. `reference_type='backfill'` las identifica para
 * siempre como lo que son.
 */
$base = '/home/arindg/htdocs/arindg.com';

// `parse_ini_file` no sirve aqui: el .env lleva valores con '=' y caracteres
// que el formato INI no admite. Se leen las claves necesarias a mano.
$env = [];
foreach (file($base . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
    if ($linea === '' || $linea[0] === '#' || ! str_contains($linea, '=')) {
        continue;
    }
    [$k, $v] = explode('=', $linea, 2);
    $env[trim($k)] = trim($v, " 	\"'");
}

$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$sumaAntes  = (int) $pdo->query("SELECT COALESCE(SUM(stock),0) FROM products WHERE stock IS NOT NULL")->fetchColumn();
$movsAntes  = (int) $pdo->query("SELECT COUNT(*) FROM inventory_movements")->fetchColumn();

$objetivo = $pdo->query("
    SELECT p.id, p.project_id, p.stock, p.cost
    FROM products p
    WHERE p.stock IS NOT NULL AND p.stock <> 0
      AND NOT EXISTS (SELECT 1 FROM inventory_movements m WHERE m.product_id = p.id)
    ORDER BY p.id
")->fetchAll(PDO::FETCH_ASSOC);

echo "productos a regularizar: " . count($objetivo) . PHP_EOL;
echo "suma de stock ANTES: {$sumaAntes}" . PHP_EOL;
echo "movimientos ANTES: {$movsAntes}" . PHP_EOL;

if (! $objetivo) {
    echo "NADA QUE HACER" . PHP_EOL;
    exit(0);
}

$ins = $pdo->prepare("
    INSERT INTO inventory_movements
        (project_id, product_id, user_id, type, reason, quantity, unit_cost,
         balance_after, reference_type, reference_id, notes, created_at, updated_at)
    VALUES
        (:project_id, :product_id, NULL, 'in', 'inicial', :quantity, :unit_cost,
         :balance_after, 'backfill', NULL, :notes, NOW(), NOW())
");

$pdo->beginTransaction();
try {
    $hechos = 0;
    $manifiesto = [];

    foreach ($objetivo as $p) {
        $cantidad = (int) $p['stock'];
        $ins->execute([
            ':project_id'    => $p['project_id'],
            ':product_id'    => $p['id'],
            ':quantity'      => $cantidad,
            ':unit_cost'     => $p['cost'] !== null ? $p['cost'] : null,
            ':balance_after' => $cantidad,
            ':notes'         => 'Existencia inicial regularizada: el producto ya tenia esta cantidad antes de que el Kardex registrara las entradas.',
        ]);

        // Verificacion por fila: una insercion que no afecta a 1 fila aborta
        // el backfill entero.
        if ($ins->rowCount() !== 1) {
            throw new RuntimeException("producto {$p['id']}: rowCount = " . $ins->rowCount());
        }

        $hechos++;
        $manifiesto[] = ['product_id' => (int) $p['id'], 'project_id' => (int) $p['project_id'], 'cantidad' => $cantidad];
    }

    $sumaDespues = (int) $pdo->query("SELECT COALESCE(SUM(stock),0) FROM products WHERE stock IS NOT NULL")->fetchColumn();

    if ($sumaDespues !== $sumaAntes) {
        throw new RuntimeException("INVARIANTE ROTO: stock {$sumaAntes} -> {$sumaDespues}");
    }

    $pdo->commit();

    file_put_contents(
        $base . '/storage/app/backfill-kardex-manifiesto.json',
        json_encode([
            'fecha'        => date('c'),
            'productos'    => $hechos,
            'suma_stock'   => $sumaAntes,
            'movs_antes'   => $movsAntes,
            'detalle'      => $manifiesto,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    $movsDespues = (int) $pdo->query("SELECT COUNT(*) FROM inventory_movements")->fetchColumn();
    $huerfanos   = (int) $pdo->query("
        SELECT COUNT(*) FROM products p
        WHERE p.stock IS NOT NULL AND p.stock <> 0
          AND NOT EXISTS (SELECT 1 FROM inventory_movements m WHERE m.product_id = p.id)
    ")->fetchColumn();

    echo "OK regularizados: {$hechos}" . PHP_EOL;
    echo "suma de stock DESPUES: {$sumaDespues} (invariante intacto)" . PHP_EOL;
    echo "movimientos DESPUES: {$movsDespues}" . PHP_EOL;
    echo "productos con stock SIN kardex: {$huerfanos}" . PHP_EOL;
    echo "manifiesto: storage/app/backfill-kardex-manifiesto.json" . PHP_EOL;
} catch (Throwable $e) {
    $pdo->rollBack();
    echo "REVERTIDO: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
