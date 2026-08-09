<?php
/**
 * Seeder de Ferretería para proyecto GABDE (project_id=3)
 * Ejecutar: C:\xampp\php\php.exe ferreteria_seeder.php
 * Desde: c:\xampp\htdocs\avan
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

$projectId = 3;

echo "Limpiando datos existentes...\n";
Product::where('project_id', $projectId)->delete();
Category::where('project_id', $projectId)->delete();

// ── Estructura: categorías padre → subcategorías → productos ─────────────────
$tree = [
    'Herramientas Manuales' => [
        'subs' => ['Martillos y Combos', 'Destornilladores', 'Llaves y Alicates', 'Sierras y Cortadoras'],
        'productos' => [
            ['Martillo de Uña 16oz', 'Martillo carpintero mango fibra de vidrio', 12.50, 18.00, 'und', 80],
            ['Martillo de Bola 1kg', 'Acero forjado, mango madera', 15.00, 22.00, 'und', 60],
            ['Combo Macho 3lb', 'Acero forjado, mango madera reforzado', 18.50, 26.00, 'und', 50],
            ['Combo Macho 5lb', 'Acero forjado, ideal para demolición', 24.00, 35.00, 'und', 40],
            ['Destornillador Estrella #2 6"', 'Punta magnética, mango ergonómico', 4.50, 7.00, 'und', 150],
            ['Destornillador Plano 1/4" 6"', 'Acero cromo-vanadio', 4.00, 6.50, 'und', 150],
            ['Juego Destornilladores x6', 'Set: estrella y plano, mango antideslizante', 18.00, 28.00, 'set', 40],
            ['Alicate de Corte 7"', 'Acero cromo-vanadio, corte limpio', 8.00, 13.00, 'und', 90],
            ['Alicate de Presión 10"', 'Vise-Grip tipo pinza locking', 22.00, 32.00, 'und', 45],
            ['Alicate Universal 8"', 'Cabeza cromada, mango aislado 1000V', 9.50, 15.00, 'und', 70],
            ['Llave Francesa 10"', 'Acero forjado, apertura 0-28mm', 14.00, 20.00, 'und', 55],
            ['Juego Llaves Corona 6-22mm x12', 'Acero cromo-vanadio, 12 piezas', 45.00, 68.00, 'set', 25],
            ['Serrucho 20" 8 dientes', 'Hoja templada, mango PP ergonómico', 15.00, 22.00, 'und', 40],
            ['Sierra Arco Ajustable', 'Para metal, ajustable 10"/12"', 12.00, 18.00, 'und', 35],
            ['Cúter Grande 25mm', 'Hoja segmentada, mango metálico', 5.50, 9.00, 'und', 100],
        ],
    ],
    'Herramientas Eléctricas' => [
        'subs' => ['Taladros y Rotomartillos', 'Amoladoras', 'Sierras Eléctricas', 'Lijadoras'],
        'productos' => [
            ['Taladro Percutor 1/2" 800W', 'Velocidad variable, reversa, con maletín', 95.00, 145.00, 'und', 20],
            ['Rotomartillo SDS 850W', 'Función percusión, rotación y cincel', 180.00, 260.00, 'und', 12],
            ['Taladro Inalámbrico 20V', 'Batería Li-ion 2Ah, cargador incluido', 150.00, 220.00, 'und', 15],
            ['Amoladora 4.5" 850W', 'Disco abrasivo incluido, 11,000 rpm', 65.00, 95.00, 'und', 25],
            ['Amoladora 7" 2200W', 'Motor potente para trabajos pesados', 155.00, 220.00, 'und', 10],
            ['Sierra Circular 7-1/4" 1400W', 'Guía paralela, hoja 24 dientes', 145.00, 210.00, 'und', 8],
            ['Sierra Caladora 600W', 'Velocidad variable, 3000 spm', 85.00, 125.00, 'und', 12],
            ['Lijadora Orbital 150W', 'Plato 115x228mm, incluye papel', 55.00, 80.00, 'und', 18],
            ['Lijadora de Banda 600W', 'Banda 75x533mm, velocidad variable', 120.00, 175.00, 'und', 8],
        ],
    ],
    'Electricidad' => [
        'subs' => ['Cables y Conductores', 'Interruptores y Tomacorrientes', 'Iluminación', 'Tableros y Breakers'],
        'productos' => [
            ['Cable THW 14 AWG Rojo x100m', 'Conductor cobre sólido 2.5mm², 600V', 68.00, 95.00, 'rollo', 20],
            ['Cable THW 12 AWG Negro x100m', 'Conductor cobre sólido 4mm², 600V', 95.00, 135.00, 'rollo', 15],
            ['Cable Gemelo 2x16 x100m', 'Para instalaciones domiciliarias 300V', 48.00, 70.00, 'rollo', 25],
            ['Cable Encauchetado 3x14 x50m', 'Flexible, para equipos y herramientas', 85.00, 120.00, 'rollo', 12],
            ['Tomacorriente Doble + USB', 'Universal, 16A 250V, con placa incluida', 8.50, 13.00, 'und', 80],
            ['Interruptor Simple', '10A 250V, empotrar, placa incluida', 4.50, 7.00, 'und', 120],
            ['Interruptor Doble', '10A 250V, empotrar, placa incluida', 7.00, 11.00, 'und', 80],
            ['Foco LED A60 9W', 'E27, luz fría 6500K, 810 lm, 25000h', 3.50, 5.50, 'und', 200],
            ['Tubo LED T8 18W 1.20m', 'Luz fría, reemplaza fluorescente 36W', 9.00, 14.00, 'und', 100],
            ['Foco Dicroico LED 7W', 'GU10, luz blanca 4000K, empotrable', 5.00, 8.00, 'und', 120],
            ['Tablero Metálico 12 polos', 'Con barra de cobre, empotrar o sobreponer', 65.00, 95.00, 'und', 15],
            ['Breaker Bipolar 20A', 'Riel DIN, capacidad interrupción 6kA', 18.00, 28.00, 'und', 50],
            ['Breaker Monopolar 16A', 'Riel DIN, enchufe directo', 8.00, 13.00, 'und', 80],
            ['Cinta Aislante 3/4" x18m', 'PVC, temperatura -10°C a 80°C, negro', 1.50, 2.50, 'und', 300],
            ['Canaleta Ranurada 40x25mm x2m', 'PVC rígido blanco, con tapa', 5.00, 8.00, 'var', 60],
        ],
    ],
    'Plomería' => [
        'subs' => ['Tuberías y Accesorios', 'Llaves y Válvulas', 'Herramientas de Plomería', 'Tanques y Desagüe'],
        'productos' => [
            ['Tubo PVC Agua 1/2" x5m', 'PN10, color crema, para agua fría', 6.50, 10.00, 'var', 100],
            ['Tubo PVC Agua 3/4" x5m', 'PN10, color crema, unión a presión', 9.00, 14.00, 'var', 80],
            ['Tubo PVC Agua 1" x5m', 'PN10, color crema', 14.00, 20.00, 'var', 60],
            ['Codo PVC 1/2" x 90°', 'Para instalaciones de agua fría', 0.50, 0.90, 'und', 500],
            ['Tee PVC 1/2"', 'Para instalaciones de agua fría', 0.60, 1.00, 'und', 400],
            ['Tubo PVC Desagüe 4" x3m', 'Serie 25, color naranja, desagüe', 18.00, 26.00, 'var', 50],
            ['Tubo PVC Desagüe 2" x3m', 'Serie 25, color naranja', 10.00, 15.00, 'var', 60],
            ['Llave de Paso Cromada 1/2"', 'Bola interna, latón niquelado', 8.00, 13.00, 'und', 60],
            ['Llave de Bola PVC 1/2"', 'Para agua fría, cierre rápido', 4.50, 7.00, 'und', 80],
            ['Llave de Paso Esférica 1"', 'Latón cromado, alta presión', 22.00, 33.00, 'und', 30],
            ['Cinta Teflón 3/4" x10m', 'PTFE, sello para roscas, blanco', 0.80, 1.50, 'und', 400],
            ['Pegamento PVC 1/4 galón', 'Cemento solv. para tuberías PVC', 14.00, 20.00, 'gln', 40],
            ['Inodoro One Piece Blanco', 'Bajo consumo 4.8L, incluye accesorios', 280.00, 380.00, 'und', 5],
            ['Lavatorio de Bañ 55x47cm', 'Loza blanca, incluye accesorios de instalación', 95.00, 140.00, 'und', 8],
        ],
    ],
    'Construcción y Acabados' => [
        'subs' => ['Cemento y Morteros', 'Ladrillos y Bloques', 'Fierro y Estructuras', 'Acabados e Interiores'],
        'productos' => [
            ['Cemento Portland Tipo I x42.5kg', 'Bolsa estándar, alta resistencia', 26.00, 32.00, 'bol', 200],
            ['Arena Fina x m³', 'Lavada, para tarrajeo y enlucido', 45.00, 60.00, 'm³', 30],
            ['Gravilla 3/4" x m³', 'Para concreto estructural', 55.00, 75.00, 'm³', 20],
            ['Ladrillo King Kong 18 huecos', 'Arcilla roja, 24x13x9cm, estándar', 0.85, 1.20, 'und', 5000],
            ['Ladrillo Pandereta', 'Arcilla, 24x12x9cm, para muros divisorios', 0.55, 0.85, 'und', 3000],
            ['Fierro 3/8" x9m', 'Corrugado, grado 60, 0.56 kg/m', 26.00, 35.00, 'var', 150],
            ['Fierro 1/2" x9m', 'Corrugado, grado 60, 1.00 kg/m', 48.00, 62.00, 'var', 100],
            ['Fierro 1/4" x9m', 'Corrugado, grado 60, 0.25 kg/m', 12.00, 17.00, 'var', 200],
            ['Alambre N°16 x kg', 'Galvanizado, para amarre de fierro', 3.50, 5.00, 'kg', 100],
            ['Porcelanato 60x60cm (caja)', 'Acabado mate, antideslizante, 1.44m²/caja', 65.00, 90.00, 'caj', 30],
            ['Porcelanato 45x45cm (caja)', 'Acabado brillante, 1.21m²/caja', 48.00, 68.00, 'caj', 40],
            ['Mayólica 25x40cm (caja)', '1.00m²/caja, colores variados', 32.00, 46.00, 'caj', 50],
            ['Fragua x1kg', 'Para juntas de cerámicos, varios colores', 4.50, 7.00, 'kg', 80],
            ['Pegamento Cerámico x25kg', 'Adhesivo mortero gris, interior/exterior', 22.00, 30.00, 'bol', 40],
            ['Yeso x25kg', 'Para tarrajeo interior, muy fino', 14.00, 20.00, 'bol', 50],
        ],
    ],
    'Pinturas y Accesorios' => [
        'subs' => ['Pinturas Látex', 'Esmaltes y Anticorrosivos', 'Accesorios de Pintura', 'Selladores y Masillas'],
        'productos' => [
            ['Pintura Látex Blanca x4L', 'Rendimiento 40m²/gln, lavable', 38.00, 55.00, 'gln', 30],
            ['Pintura Látex Blanca x18L', 'Balde, alto rendimiento, lavable', 145.00, 210.00, 'bld', 15],
            ['Pintura Látex Color x4L', 'Disponible en colores (consultar)', 42.00, 60.00, 'gln', 25],
            ['Esmalte Sintético Blanco x1L', 'Brillante, para madera y metal', 18.00, 27.00, 'lt', 40],
            ['Esmalte Sintético Color x1L', 'Brillante, para madera y metal', 19.00, 28.00, 'lt', 35],
            ['Anticorrosivo Rojo x1L', 'Base para metal, protección 3 años', 16.00, 24.00, 'lt', 30],
            ['Anticorrosivo x4L', 'Protección máxima, secado rápido', 55.00, 80.00, 'gln', 15],
            ['Rodillo Lana 9" con paleta', 'Lana natural, para látex', 8.00, 13.00, 'und', 50],
            ['Rodillo Felpa 9" con paleta', 'Para superficies rugosas', 9.00, 14.00, 'und', 40],
            ['Brocha 4"', 'Cerda natural, mango madera', 5.50, 9.00, 'und', 60],
            ['Brocha 2"', 'Cerda natural, mango madera', 3.50, 6.00, 'und', 80],
            ['Lija al Agua x pliego', 'Grano 120, 150, 220 (indicar)', 0.80, 1.50, 'und', 200],
            ['Thinner Acrílico x1L', 'Solvente para pinturas y lacas', 6.00, 9.50, 'lt', 60],
            ['Masilla Plástica x4kg', 'Para nivelar superficies de madera y metal', 18.00, 27.00, 'bld', 25],
            ['Sellador Madera x1L', 'Fondo aislante, cierra poros', 14.00, 21.00, 'lt', 30],
        ],
    ],
    'Fijaciones y Tornillería' => [
        'subs' => ['Tornillos y Tuercas', 'Clavos y Grapas', 'Anclajes y Tacos', 'Bisagras y Jaladores'],
        'productos' => [
            ['Tornillo Drywall 6x1" x200und', 'Fosfatado, cabeza bugle, punta fina', 3.50, 5.50, 'paq', 100],
            ['Tornillo Drywall 6x1-5/8" x200', 'Fosfatado, cabeza bugle', 4.00, 6.50, 'paq', 80],
            ['Tornillo Madera 8x2" x100und', 'Galvanizado, cabeza plana Phillips', 4.50, 7.00, 'paq', 80],
            ['Tornillo Madera 10x3" x50und', 'Galvanizado, cabeza plana Phillips', 5.00, 8.00, 'paq', 60],
            ['Tuerca Hex M10 x50und', 'Acero galvanizado', 3.00, 5.00, 'paq', 80],
            ['Perno Hex M10x80 + tuerca', 'Galvanizado completo', 0.90, 1.50, 'und', 200],
            ['Clavo con Cabeza 2.5" x kg', 'Acero, para madera y construcción', 3.50, 5.50, 'kg', 100],
            ['Clavo para Concreto 1.5" x100', 'Acero endurecido, para disparo o golpe', 3.00, 5.00, 'paq', 80],
            ['Grapa Galvanizada 3/8" x1000', 'Para grampas neumáticas o manuales', 5.50, 8.50, 'paq', 50],
            ['Taco Fisher S8 x10und', 'Nylon expansivo, con tornillo', 1.20, 2.00, 'paq', 200],
            ['Taco Expansivo M10x70', 'Anclaje mecánico para concreto', 0.80, 1.30, 'und', 300],
            ['Bisagra Acero 3"x3" x par', 'Acero galvanizado, reversible', 2.50, 4.00, 'par', 100],
            ['Jalador Barra 128mm', 'Aluminio anodizado, para muebles', 3.50, 6.00, 'und', 80],
            ['Cadena Acero 3/8" x metro', 'Galvanizada, resistencia 1500kg', 4.50, 7.00, 'mt', 200],
        ],
    ],
    'Seguridad y EPP' => [
        'subs' => ['Cascos y Protección Cabeza', 'Guantes de Trabajo', 'Botas de Seguridad', 'Arneses y Linternas'],
        'productos' => [
            ['Casco de Seguridad ABS', 'Tipo II, ratchet, amarillo - ANSI Z89.1', 18.00, 28.00, 'und', 30],
            ['Casco Dieléctrico', 'Para trabajo eléctrico, clase E, blanco', 25.00, 38.00, 'und', 20],
            ['Guante de Cuero Tipo C', 'Para trabajos pesados, talla M/L/XL', 8.50, 14.00, 'par', 60],
            ['Guante Látex Rugoso', 'Antideslizante, talla 9 y 10', 5.00, 8.00, 'par', 80],
            ['Guante Neoprene', 'Para líquidos y químicos, talla M', 12.00, 18.00, 'par', 40],
            ['Bota de Hule Industrial', 'PVC, puntera reforzada, talla 38-44', 28.00, 42.00, 'par', 25],
            ['Bota de Cuero con Punta Acero', 'Norma EN ISO 20345, talla 38-44', 75.00, 110.00, 'par', 15],
            ['Lentes de Seguridad Claro', 'Policarbonato, antirayadura, ANSI Z87.1', 4.50, 7.50, 'und', 80],
            ['Lentes de Seguridad Oscuro', 'Policarbonato UV400, para exteriores', 5.00, 8.00, 'und', 60],
            ['Mascarilla N95 c/ válvula', 'Filtración 95%, caja x10 unidades', 28.00, 40.00, 'caj', 20],
            ['Arnes 1 Punto con Amortiguador', 'Para trabajo en altura, certificado', 85.00, 125.00, 'und', 10],
            ['Linterna Led Recargable', '3W COB, 400 lm, batería Li-ion USB', 18.00, 28.00, 'und', 30],
            ['Cinta de Señalización Amarilla', 'Polietileno, 75mm x200m, "PELIGRO"', 12.00, 18.00, 'rollo', 20],
        ],
    ],
];

echo "Creando categorías y productos...\n";

$parentOrder = 1;
$totalCats = 0;
$totalProds = 0;

foreach ($tree as $parentName => $data) {
    // Categoría padre
    $parent = Category::create([
        'project_id' => $projectId,
        'name'       => $parentName,
        'slug'       => Str::slug($parentName) . '-' . $projectId,
        'parent_id'  => null,
        'is_active'  => true,
        'sort_order' => $parentOrder++,
    ]);
    $totalCats++;

    // Subcategorías
    $subOrder = 1;
    $subIds = [];
    foreach ($data['subs'] as $subName) {
        $sub = Category::create([
            'project_id' => $projectId,
            'name'       => $subName,
            'slug'       => Str::slug($subName) . '-' . $projectId,
            'parent_id'  => $parent->id,
            'is_active'  => true,
            'sort_order' => $subOrder++,
        ]);
        $subIds[] = $sub->id;
        $totalCats++;
    }

    // Productos bajo la categoría padre, repartidos entre subcategorías
    $prodOrder = 1;
    foreach ($data['productos'] as $idx => $p) {
        [$name, $desc, $price, $compare, $unit, $stock] = $p;

        // Asignar a subcategoría rotando
        $subCatId = count($subIds) > 0 ? $subIds[$idx % count($subIds)] : null;

        Product::create([
            'project_id'    => $projectId,
            'category_id'   => $subCatId ?? $parent->id,
            'name'          => $name,
            'description'   => $desc,
            'sku'           => strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 8)) . str_pad($idx + 1, 3, '0', STR_PAD_LEFT),
            'price'         => $price,
            'compare_price' => $compare,
            'unit'          => $unit,
            'stock'         => $stock,
            'is_available'  => true,
            'sort_order'    => $prodOrder++,
        ]);
        $totalProds++;
    }

    echo "  ✓ $parentName → {$parent->id} | " . count($subIds) . " subs | " . count($data['productos']) . " productos\n";
}

echo "\n✅ Listo: $totalCats categorías | $totalProds productos\n";
echo "URL: http://localhost/avan/public/gabde\n";
