<?php
/**
 * Reemplaza imágenes demo con fotos reales de productos (Wikimedia + Rappi)
 * Ejecutar: php fix_images.php (en VPS)
 */

$host = '127.0.0.1';
$db   = 'dbarin';
$user = 'userarin';
$pass = 'qsgrMtiMKPhQ3gMnNg9G';
$pdo  = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);

$storageDir = '/home/arindg/htdocs/arindg.com/storage/app/public/products';

// URLs reales por tipo de producto
// Fuentes: Wikimedia Commons (libre uso) + Rappi PE CDN
$realImages = [

    // ===== CERVEZAS BOTELLA GRANDE =====
    // Cristal 620ml botella
    'cristal_botella' => 'https://upload.wikimedia.org/wikipedia/commons/d/db/Cerveza_Cristal_%2839189049012%29.jpg',
    // Pilsen Callao botella
    'pilsen_botella'  => 'https://upload.wikimedia.org/wikipedia/commons/3/3d/Pilsencallao.jpg',
    // Golden botella — usamos Cusqueña dorada que es el mismo grupo (AB InBev Peru)
    'golden_botella'  => 'https://images.rappi.pe/products/21716dec-fd0a-4009-89d5-60da3551e2a9.jpg',
    // Cusqueña Dorada botella
    'cusquena_dorada' => 'https://upload.wikimedia.org/wikipedia/commons/2/26/Cusque%C3%B1aDorada.jpg',
    // Cusqueña Negra botella
    'cusquena_negra'  => 'https://upload.wikimedia.org/wikipedia/commons/e/ed/Cuzque%C3%B1a_Malta_%285879091003%29.jpg',
    // Cusqueña Trigo botella
    'cusquena_trigo'  => 'https://images.rappi.pe/products/eae9f24f-6432-4578-a15a-ae97e1f54f63.png',
    // Cusqueña Quinua — lata, usamos imagen de lata Cusqueña
    'cusquena_quinua' => 'https://upload.wikimedia.org/wikipedia/commons/3/31/Cusque%C3%B1a_lata_%283135888301%29.jpg',
    // Cusqueña Cero (sin alcohol)
    'cusquena_cero'   => 'https://images.rappi.pe/products/b7349819-e49e-4b0d-bd0b-710f1d4bf03f.png',

    // ===== LATAS 475ml =====
    'cristal_lata'    => 'https://upload.wikimedia.org/wikipedia/commons/d/db/Cerveza_Cristal_%2839189049012%29.jpg',
    'pilsen_lata'     => 'https://upload.wikimedia.org/wikipedia/commons/3/3d/Pilsencallao.jpg',
    'golden_lata'     => 'https://images.rappi.pe/products/21716dec-fd0a-4009-89d5-60da3551e2a9.jpg',

    // ===== LICORES =====
    'jack_daniels'        => 'https://upload.wikimedia.org/wikipedia/commons/6/67/Jack_Daniels_bottle.jpg',
    'johnnie_walker_red'  => 'https://upload.wikimedia.org/wikipedia/commons/d/dd/Johnnie_Walker_Red_Label.jpg',
    'johnnie_walker_black'=> 'https://upload.wikimedia.org/wikipedia/commons/c/c5/Johnnie_Walker_Gold_Label_18-Year.jpg',
    'ballantines'         => 'https://upload.wikimedia.org/wikipedia/commons/b/b8/2017_Ballantine%27s_Finest.jpg',
    'chivas_12'           => 'https://upload.wikimedia.org/wikipedia/commons/f/f6/Chivas_regal_12yo.jpg',
    'chivas_15'           => 'https://upload.wikimedia.org/wikipedia/commons/6/67/Chivas_Regal_The_Icon_Blended_Scotch_Whisky_%28700mL%29.jpg',
    'ron_zacapa'          => 'https://upload.wikimedia.org/wikipedia/commons/0/00/Ron_Zacapa_23_year_rum.jpg',

    // ===== BEBIDAS =====
    'four_loko'       => 'https://upload.wikimedia.org/wikipedia/commons/1/18/Four_Loko_Beverage_Large_Can.jpg',
    'mikes'           => 'https://upload.wikimedia.org/wikipedia/commons/3/32/Mikes_Hard_Lemonade_Bottle._330ml_Canada_Old7_and_new_5percent_alc_Liquor3620.jpg',
];

// Mapeo product_id => tipo de imagen
// Solo los que aún tienen imagen demo (product_XXX_demo.jpg)
$map = [
    // Cusqueña dorada botella grande
    75  => 'cusquena_trigo',   // "Cusqueña trigo unidad" - botella grande
    76  => 'cusquena_trigo',   // "Cusqueña trigo 1 caja"
    77  => 'cusquena_dorada',  // "Cusqueña dorada unidad"
    78  => 'cusquena_dorada',  // "Cusqueña dorada 1 caja"
    79  => 'cusquena_negra',   // "Cusqueña negra unidad"
    80  => 'cusquena_negra',   // "Cusqueña negra 1 caja"
    81  => 'cristal_botella',  // "Cristal unidad"
    82  => 'cristal_botella',  // "Cristal 1 caja"
    83  => 'pilsen_botella',   // "Pilsen unidad"
    84  => 'pilsen_botella',   // "Pilsen 1 caja"
    85  => 'golden_botella',   // "Golden unidad"
    86  => 'golden_botella',   // "Golden 1 caja"
    87  => 'cusquena_negra',   // "Cusqueña negra latón 475ml unidad"
    88  => 'cusquena_negra',   // "Cusqueña negra latón 475ml 1 six"
    89  => 'cusquena_trigo',   // "Cusqueña trigo latón 475ml unidad"
    90  => 'cusquena_trigo',   // "Cusqueña trigo latón 475ml 1 six"
    91  => 'cusquena_quinua',  // "Cusqueña de quinua latón 475ml unidad"
    92  => 'cusquena_quinua',  // "Cusqueña de quinua latón 475ml 1 six"
    93  => 'pilsen_lata',      // "Pilsen latón 475ml unidad"
    94  => 'pilsen_lata',      // "Pilsen latón 475ml 1 six"
    95  => 'cristal_lata',     // "Cristal latón 475ml unidad"
    96  => 'cristal_lata',     // "Cristal latón 475ml 1 six"
    97  => 'golden_lata',      // "Golden latón 475ml unidad"
    98  => 'golden_lata',      // "Golden latón 475ml 1 six"
    99  => 'pilsen_lata',      // "Pilsen lata pequeña unidad"
    100 => 'pilsen_lata',      // "Pilsen lata pequeña 1 six"
    101 => 'pilsen_lata',      // "Pilsen lata pequeña pack 12"
    102 => 'cristal_lata',     // "Cristal lata pequeña unidad"
    103 => 'cristal_lata',     // "Cristal lata pequeña 1 six"
    104 => 'golden_lata',      // "Golden lata pequeña unidad"
    105 => 'golden_lata',      // "Golden lata pequeña 1 six"
    106 => 'cusquena_cero',    // "Cusqueña cero lata pequeña unidad"
    107 => 'cusquena_trigo',   // "Cusqueña trigo botella pequeña unidad"
    108 => 'cusquena_trigo',   // "Cusqueña trigo botella pequeña 1 six"
    109 => 'cusquena_negra',   // "Cusqueña negra botella pequeña unidad"
    110 => 'cusquena_negra',   // "Cusqueña negra botella pequeña 1 six"
    111 => 'cusquena_dorada',  // "Cusqueña dorada botella pequeña unidad"
    112 => 'cusquena_dorada',  // "Cusqueña dorada botella pequeña 1 six"
    113 => 'cusquena_cero',    // "Cusqueña cero botella pequeña unidad"
    114 => 'cusquena_cero',    // "Cusqueña cero botella pequeña 1 six"
    115 => 'pilsen_botella',   // "Pilsen botella pequeña unidad"
    116 => 'pilsen_botella',   // "Pilsen botella pequeña 1 six"
    117 => 'pilsen_botella',   // "Pilsen botella pequeña caja x24"
    118 => 'mikes',            // "Mike fresa unidad"
    119 => 'mikes',            // "Mike fresa six pack"
    121 => 'mikes',            // "Mike maracuyá six pack"
    124 => 'mikes',            // "Mike manzana unidad"
    125 => 'mikes',            // "Mike manzana six pack"
    131 => 'four_loko',        // "Four Loko Maracuyá unidad"
    132 => 'four_loko',        // "Four Loko Ponche de Frutas unidad"
    133 => 'four_loko',        // "Four Loko Purple Uva unidad"
    134 => 'four_loko',        // "Four Loko Sandía unidad"
    136 => 'chivas_15',        // "Chivas Regal XV 15 años 750ml"
    137 => 'chivas_12',        // "Chivas Regal 12 años 750ml"
    138 => 'ballantines',      // "Ballantine's 1L"
    140 => 'ron_zacapa',       // "Ron Zacapa Centenario 750ml"
    141 => 'johnnie_walker_red',   // "Johnnie Walker Red Label 750ml"
    142 => 'johnnie_walker_black', // "Johnnie Walker Black Label 750ml"
    143 => 'johnnie_walker_black', // "Johnnie Walker Double Black 750ml"
];

$ctx = stream_context_create(['http' => [
    'timeout'         => 20,
    'user_agent'      => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120.0',
    'follow_location' => true,
    'max_redirects'   => 5,
], 'ssl' => [
    'verify_peer'      => false,
    'verify_peer_name' => false,
]]);

$cache = [];
$ok = 0; $fail = 0; $skip = 0;

foreach ($map as $productId => $tipo) {
    // Verificar que aún tiene imagen demo (no real)
    $stmt = $pdo->prepare("SELECT id, url FROM product_images WHERE product_id=? AND is_main=1 LIMIT 1");
    $stmt->execute([$productId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo "NO_IMG [$productId] - sin registro, insertando\n";
    } elseif (strpos($row['url'], '_demo.jpg') === false) {
        echo "SKIP   [$productId] ya tiene imagen real: {$row['url']}\n";
        $skip++;
        continue;
    }

    $url      = $realImages[$tipo] ?? null;
    if (!$url) { echo "NOURL  [$productId] tipo=$tipo sin URL definida\n"; $fail++; continue; }

    $ext      = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
    $ext      = strtolower(explode('?', $ext)[0]);
    if (!in_array($ext, ['jpg','jpeg','png','webp'])) $ext = 'jpg';

    $filename = "product_{$productId}_real.{$ext}";
    $filepath = "$storageDir/$filename";
    $dbPath   = "products/$filename";

    // Usar caché si ya descargamos este tipo
    if (isset($cache[$tipo])) {
        if ($cache[$tipo] !== $filepath) {
            copy($cache[$tipo], $filepath);
        }
        echo "CACHE  [$productId] $tipo\n";
    } else {
        echo "DL     [$productId] $tipo -> $url\n";
        $data = @file_get_contents($url, false, $ctx);
        if (!$data || strlen($data) < 3000) {
            echo "FAIL   [$productId] descarga fallida (bytes=" . strlen((string)$data) . ")\n";
            $fail++;
            continue;
        }
        file_put_contents($filepath, $data);
        $cache[$tipo] = $filepath;
        echo "       guardado: $filename (" . round(strlen($data)/1024) . " KB)\n";
    }

    // Actualizar o insertar registro en DB
    if ($row) {
        $pdo->prepare("UPDATE product_images SET url=?, updated_at=NOW() WHERE id=?")
            ->execute([$dbPath, $row['id']]);
    } else {
        $pdo->prepare("INSERT INTO product_images (product_id,url,is_main,sort_order,created_at,updated_at) VALUES (?,?,1,0,NOW(),NOW())")
            ->execute([$productId, $dbPath]);
    }
    $ok++;
}

echo "\n=== $ok actualizados | $fail fallidos | $skip ya tenían imagen real ===\n";
