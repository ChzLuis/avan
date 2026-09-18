<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Service;
use App\Models\Combo;
use App\Models\ComboItem;
use App\Modules\Tienda\Models\Promotion;
use App\Models\Category;

class TecsistSeeder extends Seeder
{
    /** Proyecto Tecsist Solution */
    private const PROJECT_ID = 11;

    public function run(): void
    {
        $pid = self::PROJECT_ID;

        // ── Mapa de categorías por nombre ──
        $cats = Category::where('project_id', $pid)->pluck('id', 'name');
        $cat = fn(string $n) => $cats[$n] ?? null;

        // ═══════════════════════════════════════════════
        //  PRODUCTOS
        // ═══════════════════════════════════════════════
        $productos = [
            // Laptops
            ['Laptop HP ProBook 450 G10',      'Laptops', 3299.00, 3699.00, 'Intel Core i5-1335U, 16GB RAM, 512GB SSD, 15.6" FHD', 12, 'LP-HP-450'],
            ['Laptop Lenovo ThinkPad E14',     'Laptops', 3899.00, 4299.00, 'Intel Core i7-1355U, 16GB RAM, 1TB SSD, 14" FHD', 8, 'LP-LN-E14'],
            ['Laptop ASUS TUF Gaming F15',     'Laptops', 4599.00, 4999.00, 'Intel Core i7, RTX 4060 8GB, 16GB RAM, 512GB SSD, 144Hz', 6, 'LP-AS-TUF'],
            ['Laptop Dell Inspiron 15',        'Laptops', 2499.00, 2799.00, 'Intel Core i5, 8GB RAM, 512GB SSD, 15.6" FHD', 15, 'LP-DL-I15'],
            ['Ultrabook Apple MacBook Air M2', 'Laptops', 5299.00, 5799.00, 'Chip Apple M2, 8GB RAM, 256GB SSD, 13.6" Retina', 5, 'LP-AP-M2'],

            // Computadoras
            ['PC Escritorio Intel i5 Oficina', 'Computadoras', 2199.00, 2499.00, 'Intel Core i5-12400, 16GB RAM, 512GB SSD, Windows 11 Pro', 10, 'PC-I5-OF'],
            ['PC Gamer Ryzen 7 + RTX 4060',    'Computadoras', 4899.00, 5499.00, 'AMD Ryzen 7 5700X, RTX 4060 8GB, 16GB RAM, 1TB SSD', 4, 'PC-RY7-G'],
            ['All in One HP 24"',              'Computadoras', 2799.00, 3099.00, 'Intel Core i5, 8GB RAM, 512GB SSD, Pantalla 23.8" FHD', 7, 'PC-HP-AIO'],
            ['PC Workstation Xeon',            'Computadoras', 7999.00, 8999.00, 'Intel Xeon, 32GB RAM ECC, 1TB NVMe, Quadro T1000', 2, 'PC-WS-XN'],

            // Monitores
            ['Monitor LG 24" IPS Full HD',     'Monitores', 549.00, 649.00, '24" IPS, 1920x1080, 75Hz, HDMI/VGA', 20, 'MN-LG-24'],
            ['Monitor Samsung 27" Curvo',      'Monitores', 899.00, 1049.00, '27" Curvo VA, 1920x1080, 75Hz, FreeSync', 12, 'MN-SM-27C'],
            ['Monitor Gamer ASUS 27" 165Hz',   'Monitores', 1299.00, 1499.00, '27" IPS, 2560x1440, 165Hz, 1ms, G-Sync', 8, 'MN-AS-G27'],
            ['Monitor Dell 32" 4K',            'Monitores', 1899.00, 2199.00, '32" IPS, 3840x2160 4K, USB-C, HDR', 5, 'MN-DL-32K'],

            // Impresoras
            ['Impresora HP LaserJet Pro M404', 'Impresoras', 1299.00, 1499.00, 'Láser monocromática, 38 ppm, red y USB, dúplex', 10, 'IM-HP-M404'],
            ['Multifuncional Epson L5590',     'Impresoras', 1099.00, 1299.00, 'EcoTank, imprime/escanea/copia/fax, WiFi, ADF', 14, 'IM-EP-5590'],
            ['Impresora Canon PIXMA G3110',    'Impresoras', 699.00, 799.00, 'Sistema continuo, WiFi, imprime/escanea/copia', 18, 'IM-CN-3110'],
            ['Multifuncional Brother DCP-L2540DW', 'Impresoras', 1499.00, 1699.00, 'Láser mono, dúplex automático, WiFi, ADF 50 hojas', 6, 'IM-BR-2540'],
        ];

        foreach ($productos as $i => [$name, $catName, $price, $compare, $desc, $stock, $sku]) {
            Product::updateOrCreate(
                ['project_id' => $pid, 'sku' => $sku],
                [
                    'category_id'   => $cat($catName),
                    'name'          => $name,
                    'description'   => $desc,
                    'price'         => $price,
                    'compare_price' => $compare,
                    'cost'          => round($price * 0.72, 2),
                    'stock'         => $stock,
                    'stock_min'     => 2,
                    'unit'          => 'unidad',
                    'is_available'  => true,
                    'sort_order'    => $i,
                ]
            );
        }

        // ═══════════════════════════════════════════════
        //  SERVICIOS
        // ═══════════════════════════════════════════════
        $servicios = [
            // Soporte Técnico
            ['Soporte Remoto (1 hora)',           'Soporte Técnico', 60.00,  60,  'remoto',     'Asistencia remota para configuración, instalación o resolución de fallas.'],
            ['Soporte Presencial (visita)',       'Soporte Técnico', 120.00, 90,  'presencial', 'Visita técnica a domicilio u oficina dentro de la ciudad.'],
            ['Diagnóstico de Equipo',             'Soporte Técnico', 45.00,  45,  'presencial', 'Revisión completa de hardware y software con informe de estado.'],
            ['Recuperación de Datos',             'Soporte Técnico', 250.00, 240, 'presencial', 'Recuperación de archivos de discos dañados o formateados.'],

            // Mantenimiento
            ['Mantenimiento Preventivo PC',       'Mantenimiento Preventivo', 90.00,  60, 'presencial', 'Limpieza física, cambio de pasta térmica, optimización del sistema.'],
            ['Mantenimiento Preventivo Laptop',   'Mantenimiento Preventivo', 110.00, 75, 'presencial', 'Limpieza interna, ventiladores, pasta térmica y optimización.'],
            ['Mantenimiento Correctivo',          'Mantenimiento Correctivo', 150.00, 120, 'presencial', 'Reparación de fallas de hardware o software detectadas.'],
            ['Formateo e Instalación de Windows', 'Mantenimiento Correctivo', 80.00,  90, 'presencial', 'Formateo, instalación de Windows 11, drivers y programas base.'],
            ['Instalación de Antivirus + Licencia','Mantenimiento Preventivo', 70.00, 30, 'remoto',     'Instalación y configuración de antivirus con licencia anual.'],

            // Capacitación
            ['Curso Excel Básico-Intermedio',     'Cursos de Software', 250.00, 480, 'virtual',    'Curso de 8 horas: fórmulas, tablas dinámicas y gráficos.'],
            ['Curso Excel Avanzado',              'Cursos de Software', 350.00, 600, 'virtual',    'Macros, Power Query, dashboards y automatización.'],
            ['Capacitación Empresarial (grupo)',  'Capacitación Empresarial', 890.00, 960, 'presencial', 'Capacitación in-house hasta 10 personas, temario a medida.'],
            ['Curso de Ofimática Completo',       'Cursos de Software', 420.00, 720, 'virtual',    'Word, Excel, PowerPoint y herramientas de Google.'],

            // Instalación / Configuración
            ['Instalación de Red LAN',            'Soporte Presencial', 350.00, 180, 'presencial', 'Cableado, configuración de router/switch y puntos de red.'],
            ['Configuración de Impresora en Red', 'Soporte Presencial', 65.00,  45, 'presencial', 'Instalación, drivers y compartir impresora en red local.'],
            ['Migración de Datos',                'Soporte Presencial', 130.00, 120, 'presencial', 'Traslado de archivos, correo y programas a equipo nuevo.'],
        ];

        foreach ($servicios as $i => [$name, $catName, $price, $dur, $mod, $desc]) {
            Service::updateOrCreate(
                ['project_id' => $pid, 'name' => $name],
                [
                    'category_id'  => $cat($catName),
                    'description'  => $desc,
                    'price'        => $price,
                    'duration_min' => $dur,
                    'modality'     => $mod,
                    'is_available' => true,
                    'sort_order'   => $i,
                ]
            );
        }

        // Recargar IDs recién creados
        $P = Product::where('project_id', $pid)->pluck('id', 'name');
        $S = Service::where('project_id', $pid)->pluck('id', 'name');

        // ═══════════════════════════════════════════════
        //  COMBOS — 3 tipos de combinación
        // ═══════════════════════════════════════════════
        $combos = [
            // ── 1) MIXTO: Producto + Servicio ──
            [
                'name'        => '💻 Laptop Empresarial + Configuración Completa',
                'description' => 'Laptop HP ProBook lista para trabajar: incluye formateo, Windows 11 Pro, Office, antivirus y capacitación básica.',
                'price'       => 3549.00,
                'compare'     => 3519.00,
                'items'       => [
                    ['product', 'Laptop HP ProBook 450 G10', 1],
                    ['service', 'Formateo e Instalación de Windows', 1],
                    ['service', 'Instalación de Antivirus + Licencia', 1],
                    ['service', 'Soporte Remoto (1 hora)', 1],
                ],
            ],
            [
                'name'        => '🖨️ Impresora + Instalación en Red',
                'description' => 'Multifuncional Epson EcoTank con instalación profesional, configuración en red y capacitación de uso.',
                'price'       => 1119.00,
                'compare'     => 1229.00,
                'items'       => [
                    ['product', 'Multifuncional Epson L5590', 1],
                    ['service', 'Configuración de Impresora en Red', 1],
                    ['service', 'Soporte Remoto (1 hora)', 1],
                ],
            ],
            [
                'name'        => '🖥️ PC Gamer + Mantenimiento Anual',
                'description' => 'PC Gamer Ryzen 7 con RTX 4060 + 2 mantenimientos preventivos durante el año.',
                'price'       => 4999.00,
                'compare'     => 5079.00,
                'items'       => [
                    ['product', 'PC Gamer Ryzen 7 + RTX 4060', 1],
                    ['service', 'Mantenimiento Preventivo PC', 2],
                ],
            ],

            // ── 2) SOLO PRODUCTOS (2 o más) ──
            [
                'name'        => '📦 Pack Oficina: PC + Monitor',
                'description' => 'PC de escritorio Intel i5 con monitor LG 24" IPS. Todo listo para tu oficina.',
                'price'       => 2599.00,
                'compare'     => 2748.00,
                'items'       => [
                    ['product', 'PC Escritorio Intel i5 Oficina', 1],
                    ['product', 'Monitor LG 24" IPS Full HD', 1],
                ],
            ],
            [
                'name'        => '📦 Pack Gamer: Monitor 165Hz + Laptop TUF',
                'description' => 'Laptop ASUS TUF Gaming + Monitor gamer 27" 165Hz para setup dual.',
                'price'       => 5599.00,
                'compare'     => 5898.00,
                'items'       => [
                    ['product', 'Laptop ASUS TUF Gaming F15', 1],
                    ['product', 'Monitor Gamer ASUS 27" 165Hz', 1],
                ],
            ],
            [
                'name'        => '📦 Pack Home Office: Laptop + Monitor + Impresora',
                'description' => 'Todo lo que necesitas para trabajar desde casa: Laptop Dell, monitor y multifuncional.',
                'price'       => 4199.00,
                'compare'     => 4597.00,
                'items'       => [
                    ['product', 'Laptop Dell Inspiron 15', 1],
                    ['product', 'Monitor LG 24" IPS Full HD', 1],
                    ['product', 'Impresora Canon PIXMA G3110', 1],
                ],
            ],

            // ── 3) SOLO SERVICIOS (2 o más) ──
            [
                'name'        => '🔧 Plan Mantenimiento Total',
                'description' => 'Mantenimiento preventivo + correctivo + diagnóstico completo de tu equipo.',
                'price'       => 249.00,
                'compare'     => 285.00,
                'items'       => [
                    ['service', 'Mantenimiento Preventivo PC', 1],
                    ['service', 'Mantenimiento Correctivo', 1],
                    ['service', 'Diagnóstico de Equipo', 1],
                ],
            ],
            [
                'name'        => '🔧 Pack Formateo + Antivirus + Soporte',
                'description' => 'Deja tu equipo como nuevo: formateo, Windows, antivirus con licencia y 1 hora de soporte.',
                'price'       => 179.00,
                'compare'     => 210.00,
                'items'       => [
                    ['service', 'Formateo e Instalación de Windows', 1],
                    ['service', 'Instalación de Antivirus + Licencia', 1],
                    ['service', 'Soporte Remoto (1 hora)', 1],
                ],
            ],
            [
                'name'        => '🔧 Pack Capacitación Excel Completo',
                'description' => 'Curso de Excel básico-intermedio + avanzado. 18 horas de formación virtual.',
                'price'       => 499.00,
                'compare'     => 600.00,
                'items'       => [
                    ['service', 'Curso Excel Básico-Intermedio', 1],
                    ['service', 'Curso Excel Avanzado', 1],
                ],
            ],
        ];

        foreach ($combos as $i => $c) {
            $combo = Combo::updateOrCreate(
                ['project_id' => $pid, 'name' => $c['name']],
                [
                    'description'   => $c['description'],
                    'price'         => $c['price'],
                    'compare_price' => $c['compare'],
                    'is_available'  => true,
                    'sort_order'    => $i,
                ]
            );

            // Rehacer los ítems del combo
            ComboItem::where('combo_id', $combo->id)->delete();
            foreach ($c['items'] as [$tipo, $nombre, $qty]) {
                ComboItem::create([
                    'combo_id'   => $combo->id,
                    'item_type'  => $tipo,
                    'product_id' => $tipo === 'product' ? ($P[$nombre] ?? null) : null,
                    'service_id' => $tipo === 'service' ? ($S[$nombre] ?? null) : null,
                    'quantity'   => $qty,
                ]);
            }
        }

        // ═══════════════════════════════════════════════
        //  PROMOCIONES
        // ═══════════════════════════════════════════════
        $promos = [
            [
                'name'        => '🎁 10% en Packs de Productos',
                'description' => 'Llevando 2 o más productos juntos obtienes 10% de descuento.',
                'type'        => 'percentage',
                'value'       => 10,
                'applies_to'  => 'all',
                'min_order'   => 1500,
                'coupon_code' => 'PACK10',
            ],
            [
                'name'        => '🔧 15% en Servicios Combinados',
                'description' => 'Contrata 2 o más servicios y ahorra 15%.',
                'type'        => 'percentage',
                'value'       => 15,
                'applies_to'  => 'all',
                'min_order'   => 150,
                'coupon_code' => 'SERV15',
            ],
            [
                'name'        => '💻🔧 Equipo + Servicio: S/ 100 de descuento',
                'description' => 'Compra cualquier equipo y contrata un servicio: S/ 100 menos.',
                'type'        => 'fixed',
                'value'       => 100,
                'applies_to'  => 'all',
                'min_order'   => 2000,
                'coupon_code' => 'EQUIPO100',
            ],
            [
                'name'        => '🚀 Bienvenida: 5% primera compra',
                'description' => 'Descuento de bienvenida para nuevos clientes.',
                'type'        => 'percentage',
                'value'       => 5,
                'applies_to'  => 'all',
                'min_order'   => 500,
                'coupon_code' => 'BIENVENIDO5',
            ],
        ];

        foreach ($promos as $p) {
            Promotion::updateOrCreate(
                ['project_id' => $pid, 'coupon_code' => $p['coupon_code']],
                [
                    'name'        => $p['name'],
                    'description' => $p['description'],
                    'type'        => $p['type'],
                    'value'       => $p['value'],
                    'applies_to'  => $p['applies_to'],
                    'min_order'   => $p['min_order'],
                    'is_active'   => true,
                    'starts_at'   => now(),
                    'ends_at'     => now()->addMonths(6),
                ]
            );
        }

        $this->command->info('✅ Tecsist Solution:');
        $this->command->info('   ' . Product::where('project_id', $pid)->count() . ' productos');
        $this->command->info('   ' . Service::where('project_id', $pid)->count() . ' servicios');
        $this->command->info('   ' . Combo::where('project_id', $pid)->count() . ' combos (mixtos, productos, servicios)');
        $this->command->info('   ' . Promotion::where('project_id', $pid)->count() . ' promociones');
    }
}
