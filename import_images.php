<?php
$host = '127.0.0.1';
$db   = 'dbarin';
$user = 'userarin';
$pass = 'qsgrMtiMKPhQ3gMnNg9G';
$pdo  = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);

$storageDir = '/home/arindg/htdocs/arindg.com/storage/app/public/products';
if (!is_dir($storageDir)) mkdir($storageDir, 0775, true);

// Imágenes reales por tipo de producto (Unsplash - libres de derechos para presentación)
// Cada tipo tiene una URL única para que los productos se distingan visualmente
$byType = [
    'cerveza_botella'  => 'https://images.unsplash.com/photo-1608270586620-248524c67de9?w=600&h=600&fit=crop&q=80',
    'cerveza_lata'     => 'https://images.unsplash.com/photo-1625272974585-7a5d4a7e2e37?w=600&h=600&fit=crop&q=80',
    'cerveza_negra'    => 'https://images.unsplash.com/photo-1535958636474-b021ee887b13?w=600&h=600&fit=crop&q=80',
    'whisky'           => 'https://images.unsplash.com/photo-1527281400683-1aae777175f8?w=600&h=600&fit=crop&q=80',
    'whisky_dark'      => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&h=600&fit=crop&q=80',
    'ron'              => 'https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?w=600&h=600&fit=crop&q=80',
    'bebida_frutal'    => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=600&h=600&fit=crop&q=80',
    'bebida_energetica'=> 'https://images.unsplash.com/photo-1622543925917-763c34d1a86e?w=600&h=600&fit=crop&q=80',
];

// product_id => tipo
$map = [
    // Cristal (botella)
    81=>'cerveza_botella', 82=>'cerveza_botella',
    // Cristal (latón)
    95=>'cerveza_lata', 96=>'cerveza_lata',
    // Cristal (lata pequeña)
    102=>'cerveza_lata', 103=>'cerveza_lata',

    // Cusqueña dorada
    75=>'cerveza_botella', 76=>'cerveza_botella',
    77=>'cerveza_botella', 78=>'cerveza_botella',
    111=>'cerveza_botella', 112=>'cerveza_botella',
    // Cusqueña negra
    79=>'cerveza_negra', 80=>'cerveza_negra',
    87=>'cerveza_lata', 88=>'cerveza_lata',
    109=>'cerveza_negra', 110=>'cerveza_negra',
    // Cusqueña trigo
    89=>'cerveza_lata', 90=>'cerveza_lata',
    107=>'cerveza_botella', 108=>'cerveza_botella',
    // Cusqueña quinua
    91=>'cerveza_lata', 92=>'cerveza_lata',
    // Cusqueña cero
    106=>'cerveza_lata', 113=>'cerveza_botella', 114=>'cerveza_botella',

    // Golden
    85=>'cerveza_botella', 86=>'cerveza_botella',
    97=>'cerveza_lata', 98=>'cerveza_lata',
    104=>'cerveza_lata', 105=>'cerveza_lata',

    // Pilsen
    83=>'cerveza_botella', 84=>'cerveza_botella',
    93=>'cerveza_lata', 94=>'cerveza_lata',
    99=>'cerveza_lata', 100=>'cerveza_lata', 101=>'cerveza_lata',
    115=>'cerveza_botella', 116=>'cerveza_botella', 117=>'cerveza_botella',

    // Whiskeys
    136=>'whisky', 137=>'whisky', 138=>'whisky',
    141=>'whisky', 142=>'whisky_dark', 143=>'whisky_dark',
    144=>'whisky', 145=>'whisky', 146=>'whisky', 147=>'whisky',

    // Ron Zacapa
    140=>'ron',

    // Four Loko
    131=>'bebida_energetica', 132=>'bebida_energetica',
    133=>'bebida_energetica', 134=>'bebida_energetica',

    // Mike's
    118=>'bebida_frutal', 119=>'bebida_frutal',
    120=>'bebida_frutal', 121=>'bebida_frutal',
    124=>'bebida_frutal', 125=>'bebida_frutal',

    // Ballantine's
    139=>'whisky',
];

$ctx = stream_context_create(['http'=>[
    'timeout'=>15,
    'user_agent'=>'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
    'follow_location'=>true,
]]);

// Cachear imágenes descargadas para no re-descargar el mismo tipo
$cache = [];
$ok=0; $fail=0; $skip=0;

foreach ($map as $productId => $tipo) {
    // Saltar si ya tiene imagen
    $stmt = $pdo->prepare("SELECT id FROM product_images WHERE product_id=? AND is_main=1 LIMIT 1");
    $stmt->execute([$productId]);
    if ($stmt->fetch()) { echo "SKIP [$productId]\n"; $skip++; continue; }

    $filename = "product_{$productId}_demo.jpg";
    $filepath = "$storageDir/$filename";
    $dbPath   = "products/$filename";
    $url      = $byType[$tipo];

    // Usar caché si ya descargamos este tipo
    if (isset($cache[$tipo])) {
        copy($cache[$tipo], $filepath);
    } else {
        $data = @file_get_contents($url, false, $ctx);
        if (!$data || strlen($data) < 5000) {
            echo "FAIL [$productId] tipo=$tipo\n"; $fail++; continue;
        }
        file_put_contents($filepath, $data);
        $cache[$tipo] = $filepath;
    }

    $ins = $pdo->prepare("INSERT INTO product_images (product_id,url,is_main,sort_order,created_at,updated_at) VALUES (?,?,1,0,NOW(),NOW())");
    $ins->execute([$productId, $dbPath]);
    echo "OK   [$productId] $tipo -> $filename\n";
    $ok++;
}

echo "\n=== $ok OK | $fail fallidos | $skip ya tenían imagen ===\n";
