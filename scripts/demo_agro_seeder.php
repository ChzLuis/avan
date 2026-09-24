<?php

/**
 * Demo del rubro agricola: distribuidora de papa, tuberculos y granos andinos.
 *
 * El rubro no se parece a las otras demos y por eso existe: aqui no se vende
 * una unidad suelta sino el saco, el precio cambia por calibre y lo primero
 * que pregunta el comprador es de que zona viene. Todo eso va en el catalogo,
 * no en un texto de adorno.
 *
 * Correr:  php scripts/demo_agro_seeder.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Catalogo\Models\Category;
use App\Modules\Catalogo\Models\Product;
use App\Models\Project;
use Illuminate\Support\Str;

const SLUG = 'demoagro';
const NEGOCIO = 'Agro Andino Distribuidora';

$project = Project::where('slug', SLUG)->first();
if (! $project) {
    $project = Project::create([
        'owner_id' => 4,
        'name' => NEGOCIO,
        'slug' => SLUG,
        'description' => 'Distribuidora mayorista de papa, tuberculos andinos, '
            .'granos y menestras. Acopio directo de Huanuco, Junin, Ayacucho y Puno. '
            .'Despacho por saco a mercados, restaurantes y bodegas de Lima.',
        'category' => 'agricola',
        'phone' => '016543210',
        'whatsapp' => '951234567',
        'address' => 'Mercado Mayorista de Santa Anita, Pabellon 3 - Puesto 142, Lima',
        'is_active' => true,
    ]);
    echo "Proyecto creado: {$project->id}\n";
} else {
    echo "Proyecto existente: {$project->id}\n";
}
$pid = $project->id;

// ---------------------------------------------------------------- categorias
$cats = [
    'Papas nativas',
    'Papas comerciales',
    'Tuberculos andinos',
    'Raices y camotes',
    'Granos andinos',
    'Menestras',
    'Hortalizas de campo',
];
$catIds = [];
foreach ($cats as $i => $name) {
    $c = Category::firstOrCreate(
        ['name' => $name, 'project_id' => $pid],
        ['slug' => Str::slug($name), 'is_active' => true, 'sort_order' => ($i + 1) * 10]
    );
    $catIds[$name] = $c->id;
}
echo 'Categorias: '.count($catIds)."\n";

/**
 * Cada fila: [categoria, nombre, precio x kg, precio del saco, kg del saco,
 *             procedencia, calibre, descripcion].
 * Los precios siguen el rango real del mayorista de Santa Anita.
 */
$productos = [
    // --- Papas nativas ---
    ['Papas nativas', 'Papa Amarilla Tumbay', 6.50, 280, 50, 'Huanuco', 'Primera',
        'La mas buscada para pure y causa. Pulpa amarilla intensa, se deshace al sancochar. Cosecha de Tumbay, Huanuco.'],
    ['Papas nativas', 'Papa Huayro', 5.20, 225, 50, 'Junin - Concepcion', 'Primera',
        'Piel rosada y pulpa cremosa. Aguanta el guiso sin deshacerse: la que piden las pollerias para el sancochado.'],
    ['Papas nativas', 'Papa Peruanita', 4.60, 200, 50, 'Ayacucho', 'Primera',
        'Piel bicolor y cascara delgada, se cocina con cascara. Rinde para sancochado y papa a la huancaina.'],
    ['Papas nativas', 'Papa Huamantanga', 6.80, 295, 50, 'Canta - Lima', 'Primera',
        'Harinosa y de sabor marcado. Temporada corta de mayo a agosto, se agota rapido.'],
    ['Papas nativas', 'Papa Negra Andina', 5.80, 250, 50, 'Huancavelica', 'Primera',
        'Piel oscura y pulpa firme. De altura, sobre los 3 800 msnm. Ideal para pachamanca.'],
    ['Papas nativas', 'Papa Tornillo', 5.40, 235, 50, 'Cusco', 'Segunda',
        'Nativa alargada de pulpa jaspeada. Se usa para sancochado y huatia.'],

    // --- Papas comerciales ---
    ['Papas comerciales', 'Papa Canchan', 2.90, 125, 50, 'Huanuco', 'Primera',
        'La de mayor rotacion del mercado. Piel rosada, sirve para todo: frita, guisada y sancochada.'],
    ['Papas comerciales', 'Papa Canchan Segunda', 2.30, 98, 50, 'Huanuco', 'Segunda',
        'Calibre menor al de primera, mismo sabor. Rinde mas para pure, sopa y relleno.'],
    ['Papas comerciales', 'Papa Yungay', 2.70, 118, 50, 'Junin - Jauja', 'Primera',
        'Piel crema y ojos superficiales. Pela facil, muy pedida por comedores y menus.'],
    ['Papas comerciales', 'Papa Blanca Unica', 2.60, 112, 50, 'Ica', 'Primera',
        'De costa, tamano parejo. La preferida para papa frita por su bajo contenido de agua.'],
    ['Papas comerciales', 'Papa Perricholi', 2.40, 105, 50, 'Lima - Huaral', 'Primera',
        'Rendidora y de precio estable todo el ano. Para pollerias y picanterias.'],
    ['Papas comerciales', 'Papa Unica para Frito', 3.10, 135, 50, 'Ica', 'Extra',
        'Seleccionada por calibre para corte en baston. Menos merma en cocina.'],
    ['Papas comerciales', 'Papa Chaucha', 4.20, 182, 50, 'Junin', 'Primera',
        'Cosecha temprana, cascara muy fina. Se cocina rapido, para sopas y cauche.'],

    // --- Tuberculos andinos ---
    ['Tuberculos andinos', 'Olluco Entero', 3.80, 165, 50, 'Cusco', 'Primera',
        'Firme y sin brotes. Para olluquito con charqui. Llega dos veces por semana.'],
    ['Tuberculos andinos', 'Olluco Picado', 4.40, 190, 50, 'Cusco', 'Primera',
        'Ya cortado en tiras, listo para cocina. Ahorra el picado en restaurantes.'],
    ['Tuberculos andinos', 'Oca Amarilla', 3.40, 148, 50, 'Puno', 'Primera',
        'Dulce despues del asoleado. Temporada de mayo a setiembre.'],
    ['Tuberculos andinos', 'Oca Roja', 3.60, 156, 50, 'Cusco', 'Primera',
        'Mas dulce que la amarilla. Para huatia, mazamorra y guarnicion.'],
    ['Tuberculos andinos', 'Mashua Amarilla', 3.20, 140, 50, 'Huancavelica', 'Primera',
        'De sabor picante suave. Muy pedida por su uso medicinal tradicional.'],
    ['Tuberculos andinos', 'Chuno Negro', 12.50, 545, 50, 'Puno', 'Primera',
        'Papa deshidratada al frio por el metodo andino. Se remoja la vispera. No se malogra.'],
    ['Tuberculos andinos', 'Moraya (Chuno Blanco)', 15.80, 690, 50, 'Puno', 'Primera',
        'Chuno lavado y secado al sol. Para chairo y sopas. Rinde el triple al hidratarse.'],

    // --- Raices y camotes ---
    ['Raices y camotes', 'Camote Amarillo', 2.70, 118, 50, 'Canete - Lima', 'Primera',
        'Dulce y de pulpa firme. El que acompana el ceviche y el chicharron.'],
    ['Raices y camotes', 'Camote Morado', 3.30, 143, 50, 'Barranca - Lima', 'Primera',
        'Pulpa morada intensa. Para pure, frito y reposteria.'],
    ['Raices y camotes', 'Camote Naranja', 3.10, 135, 50, 'Canete - Lima', 'Primera',
        'El mas dulce de los tres. Muy pedido para camote frito de polleria.'],
    ['Raices y camotes', 'Yuca Blanca', 2.90, 126, 50, 'Junin - Satipo', 'Primera',
        'De selva, llega fresca cada semana. Se quiebra al partir: senal de que esta tierna.'],
    ['Raices y camotes', 'Yuca Amarilla', 3.20, 140, 50, 'San Martin', 'Primera',
        'Mas mantecosa que la blanca. Para yuca frita y huancaina.'],
    ['Raices y camotes', 'Kion (Jengibre)', 6.40, 278, 50, 'Junin - Chanchamayo', 'Primera',
        'Rizoma fresco y aromatico. Para infusion, pollo al kion y jarabes.'],
    ['Raices y camotes', 'Betarraga', 2.50, 108, 50, 'Lima - Huaral', 'Primera',
        'Para ensalada y jugo. Llega con hoja cortada para que dure mas.'],
    ['Raices y camotes', 'Arracacha', 4.80, 208, 50, 'Cajamarca', 'Primera',
        'Raiz de sabor entre apio y zanahoria. Para sopas y pure de nino.'],

    // --- Granos andinos ---
    ['Granos andinos', 'Quinua Blanca Perlada', 9.80, 425, 50, 'Puno - Juliaca', 'Extra',
        'Lavada y sin saponina, lista para cocinar. Grano parejo, sin piedras.'],
    ['Granos andinos', 'Quinua Roja', 12.50, 545, 50, 'Puno', 'Extra',
        'Mas firme al cocinar, no se deshace. Para ensaladas y guarnicion.'],
    ['Granos andinos', 'Quinua Negra', 13.80, 600, 50, 'Ayacucho', 'Extra',
        'La de menor produccion de las tres. Sabor mas intenso y textura crocante.'],
    ['Granos andinos', 'Kiwicha', 10.60, 460, 50, 'Arequipa - Majes', 'Extra',
        'Grano menudo de alto valor proteico. Para mazamorra, granola y pop.'],
    ['Granos andinos', 'Canihua', 13.20, 575, 50, 'Puno', 'Extra',
        'Prima de la quinua, no necesita lavado. Se tuesta para canihuaco.'],
    ['Granos andinos', 'Maiz Morado', 6.20, 270, 50, 'Lima - Huaral', 'Primera',
        'Mazorca seca de color parejo. Para chicha morada y mazamorra. Se vende en coronta.'],
    ['Granos andinos', 'Maiz Cancha Serrano', 5.40, 235, 50, 'Ayacucho', 'Primera',
        'Grano que revienta parejo al tostar. El de la cancha salada de picanteria.'],
    ['Granos andinos', 'Maiz Mote Pelado', 5.80, 252, 50, 'Cusco', 'Primera',
        'Ya pelado con ceniza, solo se sancocha. Para mote con chicharron.'],
    ['Granos andinos', 'Maiz Gigante del Cusco', 11.40, 495, 50, 'Cusco - Urubamba', 'Extra',
        'Grano grande y blanco de Urubamba. Denominacion de origen. Para mote de fiesta.'],
    ['Granos andinos', 'Trigo Pelado', 4.20, 182, 50, 'Cajamarca', 'Primera',
        'Para trigo atamalado, sopa de trigo y shambar norteno.'],

    // --- Menestras ---
    ['Menestras', 'Frijol Canario', 8.40, 365, 50, 'Lambayeque', 'Extra',
        'Grano grande y de cocido rapido. El de mayor salida para menu.'],
    ['Menestras', 'Frijol Castilla', 7.20, 312, 50, 'Piura', 'Primera',
        'Para el tacu tacu y el frejol con seco. Cosecha del norte.'],
    ['Menestras', 'Frijol Panamito', 8.80, 382, 50, 'Ancash', 'Extra',
        'Blanco y pequeno, de cascara fina. Para frejolada y sopa.'],
    ['Menestras', 'Pallar Bebe', 11.50, 500, 50, 'Ica', 'Extra',
        'Pallar de grano chico, no necesita remojo largo. Para pallares a la iquena.'],
    ['Menestras', 'Lenteja Serrana', 6.40, 278, 50, 'Cajamarca', 'Primera',
        'Grano chico que cuece rapido. La mas pedida para menu diario.'],
    ['Menestras', 'Garbanzo', 9.60, 418, 50, 'Ica', 'Extra',
        'Calibre grande y parejo. Para garbanzada y hummus.'],
    ['Menestras', 'Arveja Partida', 5.80, 252, 50, 'Junin', 'Primera',
        'Ya partida, cuece en 20 minutos. Para crema y sopa espesa.'],
    ['Menestras', 'Habas Secas', 6.80, 295, 50, 'Puno', 'Primera',
        'Para sopa, habas tostadas y mote. Grano entero seleccionado.'],
    ['Menestras', 'Frijol Negro', 7.60, 330, 50, 'Amazonas', 'Primera',
        'De cascara oscura y caldo espeso. Para tacacho y frejol norteno.'],

    // --- Hortalizas de campo ---
    ['Hortalizas de campo', 'Cebolla Roja', 2.60, 112, 50, 'Arequipa - Camana', 'Primera',
        'Bulbo firme y de color parejo. La de la sarza y el ceviche.'],
    ['Hortalizas de campo', 'Cebolla Blanca', 3.20, 140, 50, 'Arequipa', 'Primera',
        'Mas suave que la roja. Para guisos y salsas.'],
    ['Hortalizas de campo', 'Ajo Entero', 12.80, 555, 50, 'Arequipa', 'Extra',
        'Cabeza grande y bien cerrada. De mayor rendimiento al pelar.'],
    ['Hortalizas de campo', 'Zanahoria', 2.20, 95, 50, 'Junin - Concepcion', 'Primera',
        'Lavada y de calibre parejo. Para menu, jugo y guarnicion.'],
    ['Hortalizas de campo', 'Choclo Serrano', 3.60, 156, 50, 'Junin - Jauja', 'Primera',
        'Mazorca de grano grande y lechoso. Llega desgranable, para choclo con queso.'],
];

$hechos = 0;
foreach ($productos as $i => $p) {
    [$cat, $nombre, $precioKg, $precioSaco, $kgSaco, $origen, $calibre, $desc] = $p;

    // El comprador de este rubro decide por zona y calibre: van en la ficha,
    // no escondidos en un atributo que nadie abre.
    $descripcion = $desc
        ."\n\nProcedencia: {$origen}."
        ."\nCalibre: {$calibre}."
        ."\nSaco de {$kgSaco} kg. Tambien al por menor desde 1 kg.";

    Product::updateOrCreate(
        ['project_id' => $pid, 'sku' => 'AGR-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT)],
        [
            'category_id' => $catIds[$cat],
            'name' => $nombre,
            'description' => $descripcion,
            'price' => $precioKg,
            'unit' => 'kg',
            'wholesale_price' => $precioSaco,
            'wholesale_min_qty' => 1,
            'wholesale_unit' => "saco {$kgSaco} kg",
            'stock' => rand(18, 240),
            'is_available' => true,
            'sort_order' => ($i + 1) * 10,
        ]
    );
    $hechos++;
}

echo "Productos: {$hechos}\n";
echo 'Total en catalogo: '.Product::where('project_id', $pid)->count()."\n";
