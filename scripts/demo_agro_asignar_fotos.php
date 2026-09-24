<?php

/**
 * Asigna a cada producto de la demo agricola la foto que YA se reviso a ojo.
 *
 * La regla que manda aqui: un producto se queda SIN foto antes que con una
 * foto que no le corresponde. En las tres rondas de busqueda automatica
 * salieron un oso polar para "maiz gigante", vainitas verdes para "frijol
 * canario" y una flor para "camote"; asignar eso es peor que no poner nada,
 * porque el cliente lo nota y deja de creerle al catalogo.
 *
 * Por eso el mapa de abajo es explicito y corto: solo entra lo verificado.
 *
 * Correr:  php scripts/demo_agro_asignar_fotos.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Project;
use App\Modules\Catalogo\Models\Product;
use Illuminate\Support\Facades\DB;

$project = Project::where('slug', 'demoagro')->firstOrFail();
$pid = $project->id;
$base = 'https://arindg.com/uploads/demoagro/';

/**
 * SKU => archivo. El archivo vive en public/uploads/demoagro/.
 * Las papas comerciales comparten la foto del saco a proposito: es lo que
 * el comprador recibe, y son la misma papa en distinto calibre.
 */
$mapa = [
    // Papas nativas: foto de papas de colores, que es lo que las distingue.
    'AGR-001' => 'papa-amarilla.jpg',      // Amarilla Tumbay
    'AGR-002' => 'papa-saco.jpg',          // Huayro
    'AGR-003' => 'papa-nativa.jpg',        // Peruanita
    'AGR-004' => 'papa-nativa.jpg',        // Huamantanga
    'AGR-005' => 'papa-nativa.jpg',        // Negra Andina
    'AGR-006' => 'papa-nativa.jpg',        // Tornillo

    // Papas comerciales: el saco.
    'AGR-007' => 'papa-saco.jpg',
    'AGR-008' => 'papa-saco.jpg',
    'AGR-009' => 'papa-saco.jpg',
    'AGR-010' => 'papa-saco.jpg',
    'AGR-011' => 'papa-saco.jpg',
    'AGR-012' => 'papa-saco.jpg',
    'AGR-013' => 'papa-saco.jpg',

    // Tuberculos andinos
    'AGR-014' => 'olluco.jpg',             // Olluco entero
    'AGR-015' => 'olluco.jpg',             // Olluco picado
    'AGR-016' => 'oca-amarilla.jpg',
    'AGR-017' => 'oca-roja.jpg',
    'AGR-018' => 'mashua.jpg',
    // Chuno y moraya se quedan sin foto: no se encontro ninguna fiable.

    // Raices y camotes
    'AGR-021' => 'camote-amarillo.jpg',
    'AGR-023' => 'camote-naranja.jpg',
    'AGR-024' => 'yuca.jpg',
    'AGR-025' => 'yuca.jpg',
    'AGR-026' => 'kion.jpg',
    'AGR-028' => 'arracacha.jpg',

    // Granos andinos
    'AGR-029' => 'quinua-blanca.jpg',
    'AGR-032' => 'kiwicha.jpg',
    'AGR-034' => 'maiz-morado.jpg',
    'AGR-035' => 'maiz-cancha.jpg',
    'AGR-038' => 'trigo.jpg',

    // Menestras
    'AGR-041' => 'frijol-panamito.jpg',
    'AGR-043' => 'lenteja.jpg',
    'AGR-045' => 'arveja.jpg',
    'AGR-046' => 'habas.jpg',

    // Betarraga (AGR-027) y Cebolla Roja (AGR-048) se quedaron sin foto a
    // proposito: las unicas disponibles eran de un mercado europeo con el
    // cartel de precios en euros a la vista. Una tienda peruana mostrando
    // el precio de otra tienda en otra moneda resta credibilidad.

    // Hortalizas
    'AGR-050' => 'ajo.jpg',
    'AGR-051' => 'zanahoria.jpg',
    'AGR-052' => 'choclo.jpg',
];

$puestas = 0;
$faltan = [];

foreach (Product::where('project_id', $pid)->get() as $producto) {
    $archivo = $mapa[$producto->sku] ?? null;
    if (! $archivo) {
        $faltan[] = $producto->sku.' '.$producto->name;
        continue;
    }

    $ruta = public_path('uploads/demoagro/'.$archivo);
    if (! file_exists($ruta)) {
        $faltan[] = $producto->sku.' (falta el archivo '.$archivo.')';
        continue;
    }

    DB::table('product_images')->updateOrInsert(
        ['product_id' => $producto->id, 'is_main' => 1],
        ['url' => $base.$archivo, 'sort_order' => 0, 'updated_at' => now(), 'created_at' => now()]
    );
    $puestas++;
}

echo "Con foto: {$puestas}\n";
echo 'Sin foto: '.count($faltan)."\n";
foreach ($faltan as $f) {
    echo '  - '.$f."\n";
}
