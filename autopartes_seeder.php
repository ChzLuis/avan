<?php
/**
 * Seeder de 200 productos de AutoPartes
 * Ejecutar: php autopartes_seeder.php
 * Desde: /home/mercadosmayoristas-admin/htdocs/admin.mercadosmayoristas.com.pe
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

$projectId = 4;

// ── Categorías ──────────────────────────────────────────────────────────────
$cats = [
    'Motor y Transmisión',
    'Frenos y Suspensión',
    'Sistema Eléctrico',
    'Filtros y Lubricantes',
    'Carrocería y Accesorios',
    'Refrigeración y Aire',
    'Dirección y Ruedas',
    'Iluminación',
];

$catIds = [];
foreach ($cats as $catName) {
    $cat = Category::firstOrCreate(
        ['project_id' => $projectId, 'name' => $catName],
        ['slug' => Str::slug($catName) . '-' . $projectId, 'is_active' => true, 'sort_order' => 0]
    );
    $catIds[$catName] = $cat->id;
}

echo "Categorías creadas/verificadas: " . count($catIds) . "\n";

// ── Productos ────────────────────────────────────────────────────────────────
$productos = [
    // Motor y Transmisión (30)
    ['Motor y Transmisión', 'Filtro de Aceite Toyota Corolla',         'ACE-001', 18.50,  12.00,  22.00],
    ['Motor y Transmisión', 'Filtro de Aceite Nissan Sentra',          'ACE-002', 17.00,  11.00,  20.00],
    ['Motor y Transmisión', 'Bujía NGK Iridium (juego x4)',            'BUJ-001', 85.00,  55.00,  100.00],
    ['Motor y Transmisión', 'Bujía Bosch Silver (juego x4)',           'BUJ-002', 65.00,  42.00,  80.00],
    ['Motor y Transmisión', 'Correa de Distribución Gates 1.6L',       'COR-001', 120.00, 78.00,  145.00],
    ['Motor y Transmisión', 'Correa de Distribución Dayco 2.0L',       'COR-002', 135.00, 88.00,  160.00],
    ['Motor y Transmisión', 'Kit Correa Distribución + Tensor',        'KIT-001', 250.00, 165.00, 300.00],
    ['Motor y Transmisión', 'Empaque Tapa de Válvulas Toyota',         'EMP-001', 45.00,  28.00,  55.00],
    ['Motor y Transmisión', 'Empaque Colector de Escape',              'EMP-002', 38.00,  24.00,  48.00],
    ['Motor y Transmisión', 'Junta de Culata 1.8L',                   'JCU-001', 180.00, 115.00, 220.00],
    ['Motor y Transmisión', 'Junta de Culata 2.0L',                   'JCU-002', 210.00, 135.00, 250.00],
    ['Motor y Transmisión', 'Cadena de Distribución Hyundai',         'CAD-001', 95.00,  62.00,  115.00],
    ['Motor y Transmisión', 'Banda Serpentina Gates 6PK',              'BAN-001', 55.00,  36.00,  68.00],
    ['Motor y Transmisión', 'Tensor de Banda Serpentina',              'TEN-001', 78.00,  50.00,  95.00],
    ['Motor y Transmisión', 'Polea Tensora Motor',                     'POL-001', 65.00,  42.00,  80.00],
    ['Motor y Transmisión', 'Sello de Cigüeñal Delantero',            'SEL-001', 22.00,  14.00,  28.00],
    ['Motor y Transmisión', 'Sello de Cigüeñal Trasero',              'SEL-002', 25.00,  16.00,  32.00],
    ['Motor y Transmisión', 'Caja de Cambios Manual Reconstruida',     'CAJ-001', 1800.00,1200.00, 0],
    ['Motor y Transmisión', 'Kit Clutch Toyota Corolla 1.8',          'KCL-001', 320.00, 210.00, 390.00],
    ['Motor y Transmisión', 'Kit Clutch Nissan 2.0',                  'KCL-002', 350.00, 230.00, 420.00],
    ['Motor y Transmisión', 'Disco de Clutch 9 pulgadas',             'DCL-001', 95.00,  62.00,  115.00],
    ['Motor y Transmisión', 'Prensa de Clutch',                       'PCL-001', 145.00, 95.00,  175.00],
    ['Motor y Transmisión', 'Rodamiento de Empuje Clutch',            'ROD-001', 45.00,  28.00,  55.00],
    ['Motor y Transmisión', 'Eje Cardán Completo',                    'EJC-001', 420.00, 275.00, 0],
    ['Motor y Transmisión', 'Cruceta de Eje Cardán',                  'CRU-001', 38.00,  24.00,  48.00],
    ['Motor y Transmisión', 'Diferencial Trasero Reconstruido',       'DIF-001', 950.00, 620.00, 0],
    ['Motor y Transmisión', 'Semieje Derecho Toyota',                 'SEM-001', 280.00, 182.00, 340.00],
    ['Motor y Transmisión', 'Semieje Izquierdo Toyota',               'SEM-002', 280.00, 182.00, 340.00],
    ['Motor y Transmisión', 'Junta Homocinética Externa',             'JHO-001', 65.00,  42.00,  80.00],
    ['Motor y Transmisión', 'Junta Homocinética Interna',             'JHO-002', 55.00,  36.00,  68.00],

    // Frenos y Suspensión (30)
    ['Frenos y Suspensión', 'Pastillas de Freno Delanteras Brembo',   'PFD-001', 95.00,  62.00,  115.00],
    ['Frenos y Suspensión', 'Pastillas de Freno Traseras Brembo',     'PFT-001', 85.00,  55.00,  105.00],
    ['Frenos y Suspensión', 'Pastillas de Freno Delanteras TRW',      'PFD-002', 72.00,  47.00,  88.00],
    ['Frenos y Suspensión', 'Disco de Freno Delantero Ventilado',     'DFD-001', 145.00, 94.00,  175.00],
    ['Frenos y Suspensión', 'Disco de Freno Trasero Sólido',          'DFT-001', 98.00,  64.00,  120.00],
    ['Frenos y Suspensión', 'Kit Frenos Completo Delantero',          'KFD-001', 280.00, 182.00, 340.00],
    ['Frenos y Suspensión', 'Tambor de Freno Trasero',                'TAM-001', 120.00, 78.00,  148.00],
    ['Frenos y Suspensión', 'Zapatas de Freno Traseras',              'ZAP-001', 65.00,  42.00,  80.00],
    ['Frenos y Suspensión', 'Cilindro de Rueda Trasera',              'CIR-001', 45.00,  28.00,  55.00],
    ['Frenos y Suspensión', 'Bomba de Freno Toyota Corolla',          'BOM-001', 185.00, 120.00, 225.00],
    ['Frenos y Suspensión', 'Líquido de Frenos DOT4 (500ml)',         'LIQ-001', 18.00,  11.00,  22.00],
    ['Frenos y Suspensión', 'Manguera de Freno Delantera',            'MAN-001', 35.00,  22.00,  43.00],
    ['Frenos y Suspensión', 'Amortiguador Delantero Monroe',          'AMO-001', 185.00, 120.00, 225.00],
    ['Frenos y Suspensión', 'Amortiguador Trasero Monroe',            'AMO-002', 165.00, 107.00, 200.00],
    ['Frenos y Suspensión', 'Amortiguador Delantero KYB',             'AMO-003', 195.00, 127.00, 238.00],
    ['Frenos y Suspensión', 'Amortiguador Trasero KYB',               'AMO-004', 175.00, 114.00, 215.00],
    ['Frenos y Suspensión', 'Resorte Helicoidal Delantero',           'RES-001', 95.00,  62.00,  115.00],
    ['Frenos y Suspensión', 'Resorte Helicoidal Trasero',             'RES-002', 85.00,  55.00,  105.00],
    ['Frenos y Suspensión', 'Kit Suspension Delantera Completa',      'KSU-001', 450.00, 292.00, 550.00],
    ['Frenos y Suspensión', 'Brazo de Control Superior Derecho',      'BRA-001', 145.00, 94.00,  175.00],
    ['Frenos y Suspensión', 'Brazo de Control Inferior Izquierdo',    'BRA-002', 145.00, 94.00,  175.00],
    ['Frenos y Suspensión', 'Rotula Superior Derecha',                'ROT-001', 45.00,  28.00,  55.00],
    ['Frenos y Suspensión', 'Rotula Inferior Izquierda',              'ROT-002', 45.00,  28.00,  55.00],
    ['Frenos y Suspensión', 'Barra Estabilizadora Delantera',         'BAR-001', 220.00, 143.00, 268.00],
    ['Frenos y Suspensión', 'Buje de Barra Estabilizadora',          'BUJ-003', 18.00,  11.00,  22.00],
    ['Frenos y Suspensión', 'Terminal de Dirección Derecho',          'TER-001', 42.00,  27.00,  52.00],
    ['Frenos y Suspensión', 'Terminal de Dirección Izquierdo',        'TER-002', 42.00,  27.00,  52.00],
    ['Frenos y Suspensión', 'Rodamiento de Rueda Delantera',          'ROD-002', 85.00,  55.00,  105.00],
    ['Frenos y Suspensión', 'Rodamiento de Rueda Trasera',            'ROD-003', 78.00,  50.00,  95.00],
    ['Frenos y Suspensión', 'Perno Rueda M12x1.5 (juego x4)',        'PRU-001', 28.00,  18.00,  35.00],

    // Sistema Eléctrico (25)
    ['Sistema Eléctrico', 'Batería 60Ah Bosch',                       'BAT-001', 320.00, 208.00, 390.00],
    ['Sistema Eléctrico', 'Batería 70Ah Bosch',                       'BAT-002', 380.00, 247.00, 460.00],
    ['Sistema Eléctrico', 'Batería 45Ah Panasonic',                   'BAT-003', 280.00, 182.00, 340.00],
    ['Sistema Eléctrico', 'Alternador Reconstruido Toyota 90A',       'ALT-001', 320.00, 208.00, 0],
    ['Sistema Eléctrico', 'Alternador Nuevo Nissan 110A',             'ALT-002', 485.00, 315.00, 0],
    ['Sistema Eléctrico', 'Motor de Arranque Toyota',                 'ARR-001', 285.00, 185.00, 0],
    ['Sistema Eléctrico', 'Motor de Arranque Nissan',                 'ARR-002', 295.00, 192.00, 0],
    ['Sistema Eléctrico', 'Bobina de Encendido Toyota',               'BOB-001', 95.00,  62.00,  115.00],
    ['Sistema Eléctrico', 'Bobina de Encendido Bosch Universal',      'BOB-002', 85.00,  55.00,  105.00],
    ['Sistema Eléctrico', 'Sensor MAP Toyota',                        'SEN-001', 145.00, 94.00,  175.00],
    ['Sistema Eléctrico', 'Sensor TPS (Posición Acelerador)',         'SEN-002', 125.00, 81.00,  152.00],
    ['Sistema Eléctrico', 'Sensor de Temperatura de Refrigerante',   'SEN-003', 45.00,  28.00,  55.00],
    ['Sistema Eléctrico', 'Sensor de Oxígeno Bosch',                 'SEN-004', 185.00, 120.00, 225.00],
    ['Sistema Eléctrico', 'Sensor ABS Delantero',                    'SEN-005', 95.00,  62.00,  115.00],
    ['Sistema Eléctrico', 'Sensor ABS Trasero',                      'SEN-006', 95.00,  62.00,  115.00],
    ['Sistema Eléctrico', 'Fusible Blade 10A (caja x100)',           'FUS-001', 15.00,   9.00,  18.00],
    ['Sistema Eléctrico', 'Relé Universal 12V 40A',                  'REL-001', 12.00,   7.50,  15.00],
    ['Sistema Eléctrico', 'Cable de Bujía NGK',                      'CAB-001', 78.00,  50.00,  95.00],
    ['Sistema Eléctrico', 'Terminal de Batería Universal',           'TBA-001', 8.50,    5.00,  10.00],
    ['Sistema Eléctrico', 'Regulador de Voltaje Alternador',         'REG-001', 45.00,  28.00,  55.00],
    ['Sistema Eléctrico', 'Interruptor de Freno',                    'INT-001', 22.00,  14.00,  28.00],
    ['Sistema Eléctrico', 'Bocina Doble Tono 12V',                   'BOC-001', 35.00,  22.00,  43.00],
    ['Sistema Eléctrico', 'Motor Limpiaparabrisas Delantero',        'LIM-001', 145.00, 94.00,  175.00],
    ['Sistema Eléctrico', 'Plumilla Limpiaparabrisas 20"',           'PLU-001', 28.00,  18.00,  35.00],
    ['Sistema Eléctrico', 'Plumilla Limpiaparabrisas 16"',           'PLU-002', 24.00,  15.00,  30.00],

    // Filtros y Lubricantes (25)
    ['Filtros y Lubricantes', 'Aceite Motor 5W30 Sintético 1L Mobil', 'ACM-001', 32.00,  20.00,  39.00],
    ['Filtros y Lubricantes', 'Aceite Motor 10W40 Semisintético 1L',  'ACM-002', 24.00,  15.00,  30.00],
    ['Filtros y Lubricantes', 'Aceite Motor 20W50 Mineral 1L',        'ACM-003', 18.00,  11.00,  22.00],
    ['Filtros y Lubricantes', 'Aceite Caja Manual 75W90 GL4 1L',      'ACM-004', 28.00,  18.00,  35.00],
    ['Filtros y Lubricantes', 'Aceite Dirección Hidráulica 1L',       'ACM-005', 22.00,  14.00,  28.00],
    ['Filtros y Lubricantes', 'Aceite ATF Dexron III 1L',             'ACM-006', 26.00,  17.00,  32.00],
    ['Filtros y Lubricantes', 'Filtro de Aire Toyota Corolla',        'FIA-001', 25.00,  16.00,  31.00],
    ['Filtros y Lubricantes', 'Filtro de Aire Nissan Sentra',         'FIA-002', 23.00,  15.00,  29.00],
    ['Filtros y Lubricantes', 'Filtro de Aire Universal Redondo',     'FIA-003', 18.00,  11.00,  22.00],
    ['Filtros y Lubricantes', 'Filtro de Combustible Bencina',        'FCO-001', 28.00,  18.00,  35.00],
    ['Filtros y Lubricantes', 'Filtro de Combustible Diesel',         'FCO-002', 35.00,  22.00,  43.00],
    ['Filtros y Lubricantes', 'Filtro de Aceite Bosch Universal',     'FAC-001', 15.00,   9.50,  19.00],
    ['Filtros y Lubricantes', 'Filtro de Habitáculo Toyota',          'FHA-001', 22.00,  14.00,  28.00],
    ['Filtros y Lubricantes', 'Filtro de Habitáculo Nissan',          'FHA-002', 20.00,  13.00,  25.00],
    ['Filtros y Lubricantes', 'Grasa Multipropósito Mobilux 500g',    'GRA-001', 18.00,  11.00,  22.00],
    ['Filtros y Lubricantes', 'Grasa de Rodamientos 200g',            'GRA-002', 14.00,   9.00,  18.00],
    ['Filtros y Lubricantes', 'Líquido Refrigerante Verde 1L',        'LRE-001', 15.00,   9.50,  19.00],
    ['Filtros y Lubricantes', 'Líquido Refrigerante Rojo 1L',         'LRE-002', 16.00,  10.00,  20.00],
    ['Filtros y Lubricantes', 'Aditivo Limpia Inyectores 300ml',      'ADI-001', 22.00,  14.00,  28.00],
    ['Filtros y Lubricantes', 'Aditivo Sellagoteras Motor 300ml',     'ADI-002', 28.00,  18.00,  35.00],
    ['Filtros y Lubricantes', 'Sellador de Juntas Rojo Alta Temp.',   'SEJ-001', 18.00,  11.00,  22.00],
    ['Filtros y Lubricantes', 'Sellador de Juntas Gris Motor',        'SEJ-002', 16.00,  10.00,  20.00],
    ['Filtros y Lubricantes', 'Spray Limpiador Frenos 400ml',         'SPR-001', 22.00,  14.00,  28.00],
    ['Filtros y Lubricantes', 'Spray Afloja Todo WD40 400ml',         'SPR-002', 18.00,  11.00,  22.00],
    ['Filtros y Lubricantes', 'Kit Cambio Aceite (filtro+aceite 4L)', 'KCA-001', 95.00,  62.00,  115.00],

    // Carrocería y Accesorios (25)
    ['Carrocería y Accesorios', 'Espejo Retrovisor Derecho Toyota',   'ESP-001', 85.00,  55.00,  105.00],
    ['Carrocería y Accesorios', 'Espejo Retrovisor Izquierdo Toyota', 'ESP-002', 85.00,  55.00,  105.00],
    ['Carrocería y Accesorios', 'Parachoque Delantero Universal',     'PAR-001', 280.00, 182.00, 0],
    ['Carrocería y Accesorios', 'Manija Exterior Puerta Delantera',   'MAN-002', 35.00,  22.00,  43.00],
    ['Carrocería y Accesorios', 'Manija Interior Puerta',             'MAN-003', 22.00,  14.00,  28.00],
    ['Carrocería y Accesorios', 'Moldura Parabrisas Delantera',       'MOL-001', 45.00,  28.00,  55.00],
    ['Carrocería y Accesorios', 'Jalador de Puerta Interior',         'JAL-001', 18.00,  11.00,  22.00],
    ['Carrocería y Accesorios', 'Cerradura de Puerta Delantera',      'CER-001', 65.00,  42.00,  80.00],
    ['Carrocería y Accesorios', 'Bisagra de Capó Derecha',            'BIS-001', 38.00,  24.00,  48.00],
    ['Carrocería y Accesorios', 'Bisagra de Capó Izquierda',          'BIS-002', 38.00,  24.00,  48.00],
    ['Carrocería y Accesorios', 'Gas de Capó (amortiguador)',         'GAS-001', 28.00,  18.00,  35.00],
    ['Carrocería y Accesorios', 'Gas de Maletero',                   'GAS-002', 28.00,  18.00,  35.00],
    ['Carrocería y Accesorios', 'Goma de Puerta Universal 5m',        'GOM-001', 35.00,  22.00,  43.00],
    ['Carrocería y Accesorios', 'Goma de Capó',                      'GOM-002', 25.00,  16.00,  31.00],
    ['Carrocería y Accesorios', 'Antena Universal AM/FM',             'ANT-001', 22.00,  14.00,  28.00],
    ['Carrocería y Accesorios', 'Spoiler Universal Trasero',          'SPO-001', 120.00, 78.00,  0],
    ['Carrocería y Accesorios', 'Funda de Volante Cuero PU',         'FVO-001', 32.00,  20.00,  39.00],
    ['Carrocería y Accesorios', 'Alfombras Auto Universal (juego)',   'ALF-001', 45.00,  28.00,  55.00],
    ['Carrocería y Accesorios', 'Tapetes de Goma Universal (juego)',  'TAP-001', 38.00,  24.00,  48.00],
    ['Carrocería y Accesorios', 'Organizador de Maletero',           'ORG-001', 55.00,  36.00,  68.00],
    ['Carrocería y Accesorios', 'Cargador USB para Auto 2 puertos',  'CAR-001', 18.00,  11.00,  22.00],
    ['Carrocería y Accesorios', 'Soporte Celular Tablero Magnético', 'SOC-001', 22.00,  14.00,  28.00],
    ['Carrocería y Accesorios', 'Extintor Auto 1kg PQS',             'EXT-001', 48.00,  31.00,  58.00],
    ['Carrocería y Accesorios', 'Kit Herramientas Básicas Auto',     'KHE-001', 85.00,  55.00,  105.00],
    ['Carrocería y Accesorios', 'Triángulos de Emergencia (par)',    'TRI-001', 28.00,  18.00,  35.00],

    // Refrigeración y Aire (20)
    ['Refrigeración y Aire', 'Radiador Aluminio Toyota Corolla',      'RAD-001', 380.00, 247.00, 460.00],
    ['Refrigeración y Aire', 'Radiador Aluminio Nissan Sentra',       'RAD-002', 360.00, 234.00, 438.00],
    ['Refrigeración y Aire', 'Tapa de Radiador 1.1 Bar',             'TRA-001', 12.00,   7.50,  15.00],
    ['Refrigeración y Aire', 'Tapa de Radiador 1.3 Bar',             'TRA-002', 14.00,   9.00,  18.00],
    ['Refrigeración y Aire', 'Termostato Motor 82°C',                'TER-003', 22.00,  14.00,  28.00],
    ['Refrigeración y Aire', 'Termostato Motor 88°C',                'TER-004', 22.00,  14.00,  28.00],
    ['Refrigeración y Aire', 'Manguera Superior Radiador Toyota',    'MRA-001', 35.00,  22.00,  43.00],
    ['Refrigeración y Aire', 'Manguera Inferior Radiador Toyota',    'MRA-002', 32.00,  20.00,  39.00],
    ['Refrigeración y Aire', 'Electro Ventilador 12V Universal',     'EVE-001', 145.00, 94.00,  175.00],
    ['Refrigeración y Aire', 'Bomba de Agua Toyota 1.8L',            'BAG-001', 145.00, 94.00,  175.00],
    ['Refrigeración y Aire', 'Bomba de Agua Nissan 2.0L',            'BAG-002', 155.00, 101.00, 188.00],
    ['Refrigeración y Aire', 'Gas Refrigerante R134A 800g',          'GRE-001', 95.00,  62.00,  115.00],
    ['Refrigeración y Aire', 'Compresor A/C Toyota Corolla',         'COM-001', 850.00, 552.00, 0],
    ['Refrigeración y Aire', 'Condensador A/C Universal',            'CON-001', 280.00, 182.00, 0],
    ['Refrigeración y Aire', 'Filtro Deshidratador A/C',             'FDE-001', 65.00,  42.00,  80.00],
    ['Refrigeración y Aire', 'Manguera A/C Alta Presión',            'MAC-001', 95.00,  62.00,  115.00],
    ['Refrigeración y Aire', 'Manguera A/C Baja Presión',            'MAC-002', 85.00,  55.00,  105.00],
    ['Refrigeración y Aire', 'Válvula Expansión A/C',                'VAL-001', 75.00,  48.00,  92.00],
    ['Refrigeración y Aire', 'Embrague Magnético Compresor A/C',     'EMB-001', 185.00, 120.00, 225.00],
    ['Refrigeración y Aire', 'Kit Carga A/C con Manómetro',          'KAC-001', 120.00, 78.00,  148.00],

    // Dirección y Ruedas (25)
    ['Dirección y Ruedas', 'Cremallera de Dirección Toyota',          'CRE-001', 680.00, 442.00, 0],
    ['Dirección y Ruedas', 'Cremallera de Dirección Nissan',          'CRE-002', 720.00, 468.00, 0],
    ['Dirección y Ruedas', 'Bomba de Dirección Hidráulica Toyota',    'BDH-001', 380.00, 247.00, 460.00],
    ['Dirección y Ruedas', 'Columna de Dirección Universal',          'COD-001', 245.00, 159.00, 0],
    ['Dirección y Ruedas', 'Fuelle de Cremallera Derecho',            'FCR-001', 22.00,  14.00,  28.00],
    ['Dirección y Ruedas', 'Fuelle de Cremallera Izquierdo',          'FCR-002', 22.00,  14.00,  28.00],
    ['Dirección y Ruedas', 'Terminal de Dirección Externo',           'TDE-001', 38.00,  24.00,  48.00],
    ['Dirección y Ruedas', 'Terminal de Dirección Interno',           'TDI-001', 45.00,  28.00,  55.00],
    ['Dirección y Ruedas', 'Rotula de Muñón Derecha',                'RMU-001', 52.00,  34.00,  65.00],
    ['Dirección y Ruedas', 'Rotula de Muñón Izquierda',              'RMU-002', 52.00,  34.00,  65.00],
    ['Dirección y Ruedas', 'Manguera Dirección Hidráulica Alta',     'MDH-001', 95.00,  62.00,  115.00],
    ['Dirección y Ruedas', 'Depósito Dirección Hidráulica',          'DDH-001', 45.00,  28.00,  55.00],
    ['Dirección y Ruedas', 'Llantas 185/65 R15 (unidad)',            'LLA-001', 280.00, 182.00, 0],
    ['Dirección y Ruedas', 'Llantas 195/65 R15 (unidad)',            'LLA-002', 310.00, 202.00, 0],
    ['Dirección y Ruedas', 'Llantas 205/55 R16 (unidad)',            'LLA-003', 345.00, 224.00, 0],
    ['Dirección y Ruedas', 'Llantas 215/60 R16 (unidad)',            'LLA-004', 365.00, 237.00, 0],
    ['Dirección y Ruedas', 'Aro de Aluminio 15" (unidad)',           'ARO-001', 285.00, 185.00, 0],
    ['Dirección y Ruedas', 'Aro de Aluminio 16" (unidad)',           'ARO-002', 320.00, 208.00, 0],
    ['Dirección y Ruedas', 'Aro de Acero 15" (unidad)',              'ARO-003', 145.00, 94.00,  0],
    ['Dirección y Ruedas', 'Tuerca de Rueda M12x1.5 (unidad)',       'TUR-001', 4.50,   2.80,   5.50],
    ['Dirección y Ruedas', 'Perno de Rueda M12x1.5 (unidad)',        'PRU-002', 5.50,   3.50,   7.00],
    ['Dirección y Ruedas', 'Tapa de Aro 15" (juego x4)',             'TAR-001', 45.00,  28.00,  55.00],
    ['Dirección y Ruedas', 'Válvula de Neumático (unidad)',          'VAL-002', 3.50,   2.00,   4.50],
    ['Dirección y Ruedas', 'Parche Frío para Llanta (unidad)',       'PAR-002', 2.50,   1.50,   3.50],
    ['Dirección y Ruedas', 'Kit Pesas de Equilibrio 60g (10u)',      'PES-001', 8.00,   5.00,  10.00],

    // Iluminación (20)
    ['Iluminación', 'Faro Delantero Derecho Toyota Corolla',         'FAR-001', 185.00, 120.00, 225.00],
    ['Iluminación', 'Faro Delantero Izquierdo Toyota Corolla',       'FAR-002', 185.00, 120.00, 225.00],
    ['Iluminación', 'Faro Trasero Derecho Toyota',                   'FAR-003', 125.00, 81.00,  152.00],
    ['Iluminación', 'Faro Trasero Izquierdo Toyota',                 'FAR-004', 125.00, 81.00,  152.00],
    ['Iluminación', 'Faro Neblinero Universal',                      'FAR-005', 65.00,  42.00,  80.00],
    ['Iluminación', 'Bombillo H4 60/55W (par)',                      'BOM-002', 18.00,  11.00,  22.00],
    ['Iluminación', 'Bombillo H7 55W (par)',                         'BOM-003', 22.00,  14.00,  28.00],
    ['Iluminación', 'Bombillo H11 55W (par)',                        'BOM-004', 20.00,  13.00,  25.00],
    ['Iluminación', 'Kit LED H4 6000K (par)',                        'KLE-001', 65.00,  42.00,  80.00],
    ['Iluminación', 'Kit LED H7 6000K (par)',                        'KLE-002', 72.00,  47.00,  88.00],
    ['Iluminación', 'Kit LED H11 6000K (par)',                       'KLE-003', 68.00,  44.00,  83.00],
    ['Iluminación', 'Kit Xenón HID H4 6000K',                        'KXE-001', 145.00, 94.00,  175.00],
    ['Iluminación', 'Tira LED 12V 5m (luz de tablero)',              'TLE-001', 22.00,  14.00,  28.00],
    ['Iluminación', 'Luz de Marcha Atrás Universal',                 'LMA-001', 18.00,  11.00,  22.00],
    ['Iluminación', 'Luz Interior LED Domo',                         'LID-001', 12.00,   7.50,  15.00],
    ['Iluminación', 'Bombillo de Tablero T10 LED (par)',             'BTL-001', 8.00,    5.00,  10.00],
    ['Iluminación', 'Luz DRL Diurna Universal',                      'DRL-001', 85.00,  55.00,  105.00],
    ['Iluminación', 'Faro Auxiliar Redondo 4" LED',                  'FAU-001', 45.00,  28.00,  55.00],
    ['Iluminación', 'Barra LED 22" 120W Off-Road',                   'BAL-001', 185.00, 120.00, 225.00],
    ['Iluminación', 'Kit Luces Angel Eyes H8 (par)',                 'KAE-001', 55.00,  36.00,  68.00],
];

$creados = 0;
$errores = 0;

foreach ($productos as $p) {
    [$catName, $nombre, $sku, $precio, $costo, $compare] = $p;
    try {
        Product::create([
            'project_id'    => $projectId,
            'category_id'   => $catIds[$catName],
            'name'          => $nombre,
            'slug'          => Str::slug($nombre) . '-' . strtolower($sku),
            'sku'           => $sku,
            'price'         => $precio,
            'compare_price' => $compare > 0 ? $compare : null,
            'cost'          => $costo,
            'is_available'  => true,
            'description'   => 'Repuesto automotriz de alta calidad. Compatible con múltiples modelos. Garantía de fábrica.',
            'unit'          => 'und',
        ]);
        $creados++;
    } catch (\Exception $e) {
        $errores++;
        echo "Error en $nombre: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Productos creados: $creados\n";
echo "❌ Errores: $errores\n";
echo "📦 Total en DB: " . Product::where('project_id', $projectId)->count() . " productos\n";
