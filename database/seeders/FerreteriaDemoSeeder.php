<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CatalogList;
use App\Models\CatalogValue;
use App\Models\Module;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FerreteriaDemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Usuario demo ─────────────────────────────────────────────
        $user = User::firstOrCreate(
            ['email' => 'ferreteriademo@avan.com'],
            [
                'name'     => 'Demo Ferretería',
                'password' => bcrypt('password'),
            ]
        );

        // ── 2. Proyecto ─────────────────────────────────────────────────
        $project = Project::firstOrCreate(
            ['slug' => 'ferreteria-san-pedro'],
            [
                'owner_id'    => $user->id,
                'name'        => 'Ferretería San Pedro',
                'description' => 'Tu ferretería de confianza. Herramientas, materiales de construcción, electricidad, plomería y más. Atención personalizada y precios competitivos.',
                'phone'       => '+51 984 123 456',
                'whatsapp'    => '+51984123456',
                'address'     => 'Av. Los Constructores 245, Lima',
                'category'    => 'Ferretería',
                'is_active'   => true,
            ]
        );

        // Activar todos los módulos disponibles
        $modules = Module::all();
        foreach ($modules as $module) {
            $project->modules()->syncWithoutDetaching([$module->id => ['is_active' => true]]);
        }

        // Settings de diseño
        $settings = [
            'primary_color'   => '#f97316',   // naranja ferretería
            'hero_bg_color'   => '#1c1917',
            'hero_title'      => 'Todo para tu obra y hogar',
            'hero_subtitle'   => 'Herramientas, materiales y acabados al mejor precio. Asesoría personalizada.',
            'hero_badge'      => '¡Envío a todo Lima!',
            'banner1_title'   => 'Herramientas Pro',
            'banner1_sub'     => 'Marca Stanley, Bosch y más',
            'banner2_title'   => 'Ofertas de temporada',
            'banner2_sub'     => 'Hasta 30% de descuento',
            'store_mode'      => 'direct',
            'whatsapp_msg'    => 'Hola Ferretería San Pedro, vi su catálogo y me interesa hacer un pedido.',
            'quote_whatsapp'  => '51984123456',
            'quote_wa_msg'    => 'Hola, quisiera cotizar los siguientes materiales:',
            'seo_title'       => 'Ferretería San Pedro — Herramientas y Materiales en Lima',
            'seo_description' => 'Ferretería San Pedro: herramientas, materiales de construcción, electricidad, plomería y más. Los mejores precios en Lima.',
            'seo_keywords'    => 'ferretería lima, herramientas, materiales construcción, plomería, electricidad, pinturas',
        ];
        foreach ($settings as $key => $value) {
            $project->settings()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Seed catálogos del sistema para este proyecto
        DefaultCatalogsSeeder::seedForProject($project);

        // ── 3. Actualizar catálogos de sistema con valores ferretería ───
        $this->seedCatalogValues($project);

        // ── 4. Categorías ───────────────────────────────────────────────
        $cats = $this->seedCategories($project);

        // ── 5. Productos ────────────────────────────────────────────────
        $this->seedProducts($project, $cats);
    }

    private function seedCatalogValues(Project $project): void
    {
        $data = [
            'brand' => [
                ['label' => 'Stanley',    'code' => 'STANLEY'],
                ['label' => 'Bosch',      'code' => 'BOSCH'],
                ['label' => 'Truper',     'code' => 'TRUPER'],
                ['label' => 'Makita',     'code' => 'MAKITA'],
                ['label' => 'DeWalt',     'code' => 'DEWALT'],
                ['label' => 'Pretul',     'code' => 'PRETUL'],
                ['label' => 'Voltech',    'code' => 'VOLTECH'],
                ['label' => 'Genérico',   'code' => 'GEN'],
            ],
            'unit' => [
                ['label' => 'Unidad',     'code' => 'UND'],
                ['label' => 'Caja',       'code' => 'CJA'],
                ['label' => 'Bolsa',      'code' => 'BLS'],
                ['label' => 'Metro',      'code' => 'MT'],
                ['label' => 'Rollo',      'code' => 'RLL'],
                ['label' => 'Par',        'code' => 'PAR'],
                ['label' => 'Galón',      'code' => 'GAL'],
                ['label' => 'Litro',      'code' => 'LT'],
                ['label' => 'Kilo',       'code' => 'KG'],
            ],
            'payment_method' => [
                ['label' => 'Efectivo',          'code' => 'cash'],
                ['label' => 'Yape',              'code' => 'yape'],
                ['label' => 'Plin',              'code' => 'plin'],
                ['label' => 'Transferencia',     'code' => 'transfer'],
                ['label' => 'Tarjeta débito',    'code' => 'debit'],
                ['label' => 'Tarjeta crédito',   'code' => 'credit'],
            ],
            'payment_condition' => [
                ['label' => 'Al contado',        'code' => 'cash'],
                ['label' => 'Crédito 15 días',   'code' => '15d'],
                ['label' => 'Crédito 30 días',   'code' => '30d'],
                ['label' => 'Contra entrega',    'code' => 'cod'],
            ],
            'sales_channel' => [
                ['label' => 'Tienda física',     'code' => 'store'],
                ['label' => 'WhatsApp',          'code' => 'whatsapp'],
                ['label' => 'Catálogo online',   'code' => 'catalog'],
                ['label' => 'Teléfono',          'code' => 'phone'],
            ],
            'client_type' => [
                ['label' => 'Persona natural',   'code' => 'natural'],
                ['label' => 'Empresa',           'code' => 'empresa'],
                ['label' => 'Constructor',       'code' => 'constructor'],
                ['label' => 'Revendedor',        'code' => 'revendedor'],
            ],
            'lead_source' => [
                ['label' => 'Boca a boca',       'code' => 'referido'],
                ['label' => 'Google',            'code' => 'google'],
                ['label' => 'WhatsApp',          'code' => 'whatsapp'],
                ['label' => 'Redes sociales',    'code' => 'redes'],
                ['label' => 'Pasaba por aquí',   'code' => 'walk-in'],
            ],
            'department' => [
                ['label' => 'Ventas',            'code' => 'ventas'],
                ['label' => 'Almacén',           'code' => 'almacen'],
                ['label' => 'Despacho',          'code' => 'despacho'],
                ['label' => 'Administración',    'code' => 'admin'],
            ],
            'job_title' => [
                ['label' => 'Vendedor',          'code' => 'vendedor'],
                ['label' => 'Almacenero',        'code' => 'almacenero'],
                ['label' => 'Despachador',       'code' => 'despachador'],
                ['label' => 'Cajero',            'code' => 'cajero'],
                ['label' => 'Gerente',           'code' => 'gerente'],
            ],
            'general_status' => [
                ['label' => 'Activo',            'code' => 'active'],
                ['label' => 'Inactivo',          'code' => 'inactive'],
                ['label' => 'Pendiente',         'code' => 'pending'],
                ['label' => 'Cancelado',         'code' => 'cancelled'],
            ],
        ];

        foreach ($data as $type => $values) {
            $catalog = CatalogList::where('project_id', $project->id)
                ->where('type', $type)
                ->first();
            if (!$catalog) continue;

            // Solo agregar si no tiene valores aún
            if ($catalog->values()->count() === 0) {
                foreach ($values as $i => $v) {
                    CatalogValue::create([
                        'catalog_list_id' => $catalog->id,
                        'label'      => $v['label'],
                        'code'       => $v['code'],
                        'is_active'  => true,
                        'sort_order' => $i,
                    ]);
                }
            }
        }
    }

    private function seedCategories(Project $project): array
    {
        $names = [
            'Herramientas manuales',
            'Herramientas eléctricas',
            'Electricidad',
            'Plomería',
            'Construcción y acabados',
            'Pinturas y accesorios',
            'Fijaciones y tornillería',
            'Seguridad y EPP',
        ];

        $cats = [];
        foreach ($names as $i => $name) {
            $cats[$name] = Category::firstOrCreate(
                ['project_id' => $project->id, 'name' => $name],
                ['sort_order' => $i, 'is_active' => true]
            );
        }
        return $cats;
    }

    private function seedProducts(Project $project, array $cats): void
    {
        $products = [

            // ── Herramientas manuales ────────────────────────────────────
            ['cat' => 'Herramientas manuales', 'name' => 'Martillo de acero 20oz Stanley',           'sku' => 'HM-001', 'price' => 45.90,  'compare_price' => 55.00,  'cost' => 28.00, 'stock' => 40, 'desc' => 'Martillo de orejas con mango de fibra de vidrio antivibración. Ideal para clavado y extracción de clavos.'],
            ['cat' => 'Herramientas manuales', 'name' => 'Alicate universal 8" Stanley',             'sku' => 'HM-002', 'price' => 32.50,  'compare_price' => null,   'cost' => 18.00, 'stock' => 55, 'desc' => 'Alicate universal de acero al cromo vanadio con mangos ergonómicos aislados.'],
            ['cat' => 'Herramientas manuales', 'name' => 'Destornillador set 6 piezas Truper',       'sku' => 'HM-003', 'price' => 28.90,  'compare_price' => 35.00,  'cost' => 15.00, 'stock' => 60, 'desc' => 'Set de 6 destornilladores de punta plana y estrella con mango ergonómico bicolor.'],
            ['cat' => 'Herramientas manuales', 'name' => 'Llave inglesa 12" ajustable Pretul',       'sku' => 'HM-004', 'price' => 38.00,  'compare_price' => null,   'cost' => 22.00, 'stock' => 30, 'desc' => 'Llave ajustable de boca ancha graduable hasta 35mm. Acero forjado resistente.'],
            ['cat' => 'Herramientas manuales', 'name' => 'Nivel de burbuja 60cm aluminio',           'sku' => 'HM-005', 'price' => 22.00,  'compare_price' => null,   'cost' => 12.00, 'stock' => 45, 'desc' => 'Nivel profesional de aluminio con 3 burbujas (horizontal, vertical y 45°).'],
            ['cat' => 'Herramientas manuales', 'name' => 'Sierra de arco 12" con hoja bimetálica',   'sku' => 'HM-006', 'price' => 29.90,  'compare_price' => 36.00,  'cost' => 16.00, 'stock' => 25, 'desc' => 'Sierra de arco tensada con hoja bimetálica de 18 TPI. Marco de acero tubular.'],
            ['cat' => 'Herramientas manuales', 'name' => 'Cinta métrica 5m Stanley FatMax',          'sku' => 'HM-007', 'price' => 35.00,  'compare_price' => null,   'cost' => 20.00, 'stock' => 70, 'desc' => 'Cinta métrica con hoja ancha de 25mm y freno automático. Resistente a impactos.'],
            ['cat' => 'Herramientas manuales', 'name' => 'Juego de llaves combinadas 12 piezas',     'sku' => 'HM-008', 'price' => 89.00,  'compare_price' => 110.00, 'cost' => 52.00, 'stock' => 18, 'desc' => 'Set de 12 llaves combinadas de acero cromo vanadio (8 al 22mm). Con estuche.'],

            // ── Herramientas eléctricas ──────────────────────────────────
            ['cat' => 'Herramientas eléctricas', 'name' => 'Taladro percutor 600W Bosch',            'sku' => 'HE-001', 'price' => 289.00, 'compare_price' => 350.00, 'cost' => 190.00,'stock' => 12, 'desc' => 'Taladro percutor con mandril de 13mm, 600W, reversa y 2 velocidades. Ideal para concreto y metal.'],
            ['cat' => 'Herramientas eléctricas', 'name' => 'Amoladora angular 4.5" 850W Bosch',      'sku' => 'HE-002', 'price' => 245.00, 'compare_price' => 295.00, 'cost' => 160.00,'stock' => 10, 'desc' => 'Amoladora con 850W, disco de 115mm y protector de chispa ajustable.'],
            ['cat' => 'Herramientas eléctricas', 'name' => 'Sierra circular 7-1/4" 1400W',           'sku' => 'HE-003', 'price' => 320.00, 'compare_price' => null,   'cost' => 210.00,'stock' => 8,  'desc' => 'Sierra circular con profundidad de corte de 65mm, guía láser y base biselable hasta 45°.'],
            ['cat' => 'Herramientas eléctricas', 'name' => 'Atornillador inalámbrico 12V Bosch',     'sku' => 'HE-004', 'price' => 189.00, 'compare_price' => 220.00, 'cost' => 120.00,'stock' => 15, 'desc' => 'Atornillador inalámbrico compacto con batería de ion litio 12V y 2 velocidades.'],
            ['cat' => 'Herramientas eléctricas', 'name' => 'Lijadora orbital 240W Makita',           'sku' => 'HE-005', 'price' => 175.00, 'compare_price' => null,   'cost' => 110.00,'stock' => 9,  'desc' => 'Lijadora orbital de plato 125mm con bolsa colectora de polvo y velocidad variable.'],

            // ── Electricidad ─────────────────────────────────────────────
            ['cat' => 'Electricidad', 'name' => 'Cable THW 14 AWG rollo 100m Indeco',                'sku' => 'EL-001', 'price' => 89.00,  'compare_price' => null,   'cost' => 60.00, 'stock' => 30, 'desc' => 'Cable eléctrico THW calibre 14 AWG en rollo de 100 metros. Apto para instalaciones interiores.'],
            ['cat' => 'Electricidad', 'name' => 'Interruptor termomagnético 20A Schneider',          'sku' => 'EL-002', 'price' => 28.00,  'compare_price' => 33.00,  'cost' => 16.00, 'stock' => 50, 'desc' => 'Interruptor automático termomagnético 20A 220V, 1 polo. Protección contra sobrecargas.'],
            ['cat' => 'Electricidad', 'name' => 'Tomacorriente doble con tierra Bticino',            'sku' => 'EL-003', 'price' => 15.50,  'compare_price' => null,   'cost' => 8.00,  'stock' => 80, 'desc' => 'Tomacorriente doble con polo a tierra, color blanco, 15A 220V.'],
            ['cat' => 'Electricidad', 'name' => 'Lámpara LED 18W luz fría Philips',                  'sku' => 'EL-004', 'price' => 22.90,  'compare_price' => 27.00,  'cost' => 13.00, 'stock' => 100,'desc' => 'Lámpara LED circular 18W, 6500K luz blanca fría, base E27. Equivale a 150W incandescente.'],
            ['cat' => 'Electricidad', 'name' => 'Cinta aislante 3M Temflex negro x 5m',              'sku' => 'EL-005', 'price' => 5.50,   'compare_price' => null,   'cost' => 2.50,  'stock' => 150,'desc' => 'Cinta aislante de vinilo, resistente a la intemperie. Apta hasta 600V.'],
            ['cat' => 'Electricidad', 'name' => 'Tablero eléctrico 6 circuitos',                     'sku' => 'EL-006', 'price' => 75.00,  'compare_price' => 90.00,  'cost' => 48.00, 'stock' => 20, 'desc' => 'Tablero de distribución empotrable para 6 circuitos con barra de neutro y tierra.'],

            // ── Plomería ─────────────────────────────────────────────────
            ['cat' => 'Plomería', 'name' => 'Tubo PVC 1/2" x 3m clase 10 Pavco',                    'sku' => 'PL-001', 'price' => 9.80,   'compare_price' => null,   'cost' => 5.50,  'stock' => 100,'desc' => 'Tubo de PVC rígido para agua fría, 1/2" diámetro, 3m de longitud, clase 10.'],
            ['cat' => 'Plomería', 'name' => 'Codo PVC 1/2" x 90° (bolsa x 10)',                     'sku' => 'PL-002', 'price' => 8.50,   'compare_price' => null,   'cost' => 4.50,  'stock' => 60, 'desc' => 'Codos de PVC de 1/2" a 90° para agua fría. Vienen en bolsa de 10 unidades.'],
            ['cat' => 'Plomería', 'name' => 'Llave de paso 1/2" esfera latón',                      'sku' => 'PL-003', 'price' => 12.90,  'compare_price' => 16.00,  'cost' => 7.00,  'stock' => 45, 'desc' => 'Llave de paso de esfera en latón cromado, 1/2", con palanca de acero inoxidable.'],
            ['cat' => 'Plomería', 'name' => 'Pegamento PVC Oatey 118ml',                             'sku' => 'PL-004', 'price' => 18.00,  'compare_price' => null,   'cost' => 10.00, 'stock' => 40, 'desc' => 'Pegamento solvente para uniones de PVC. Fragua rápida. Apto para agua potable.'],
            ['cat' => 'Plomería', 'name' => 'Cinta teflón 12mm x 10m (pack x 5)',                   'sku' => 'PL-005', 'price' => 7.50,   'compare_price' => null,   'cost' => 3.50,  'stock' => 120,'desc' => 'Pack de 5 cintas teflón para sellar rosca de tuberías. 12mm x 10m cada una.'],
            ['cat' => 'Plomería', 'name' => 'Válvula flotadora 1/2" para tanque',                   'sku' => 'PL-006', 'price' => 14.50,  'compare_price' => 18.00,  'cost' => 8.00,  'stock' => 30, 'desc' => 'Válvula de flotador para cisterna y tanque de agua. PVC reforzado.'],

            // ── Construcción y acabados ──────────────────────────────────
            ['cat' => 'Construcción y acabados', 'name' => 'Cemento Portland 42.5kg Sol',            'sku' => 'CA-001', 'price' => 32.00,  'compare_price' => null,   'cost' => 24.00, 'stock' => 200,'desc' => 'Cemento Portland tipo I en bolsa de 42.5kg. Uso en obras civiles, acabados y prefabricados.'],
            ['cat' => 'Construcción y acabados', 'name' => 'Arena gruesa bolsa 40kg',                'sku' => 'CA-002', 'price' => 12.00,  'compare_price' => null,   'cost' => 7.00,  'stock' => 150,'desc' => 'Arena lavada gruesa en bolsa de 40kg. Para mezclas de concreto y mortero.'],
            ['cat' => 'Construcción y acabados', 'name' => 'Ladrillo King Kong 18 huecos x 1000',    'sku' => 'CA-003', 'price' => 850.00, 'compare_price' => null,   'cost' => 620.00,'stock' => 15, 'desc' => 'Millar de ladrillo King Kong 18 huecos para muro estructural. Entrega incluida.'],
            ['cat' => 'Construcción y acabados', 'name' => 'Porcelanato 60x60 gris antideslizante m²','sku' => 'CA-004', 'price' => 68.00,  'compare_price' => 80.00,  'cost' => 44.00, 'stock' => 80, 'desc' => 'Porcelanato rectificado 60x60cm gris antideslizante, acabado mate. Por m².'],
            ['cat' => 'Construcción y acabados', 'name' => 'Fragua blanca Celima 1kg',               'sku' => 'CA-005', 'price' => 8.50,   'compare_price' => null,   'cost' => 4.50,  'stock' => 90, 'desc' => 'Fragua en polvo color blanco para juntas de cerámico y porcelanato. Bolsa 1kg.'],
            ['cat' => 'Construcción y acabados', 'name' => 'Perfil metálico 2x4 drywall 3m',         'sku' => 'CA-006', 'price' => 18.50,  'compare_price' => 22.00,  'cost' => 11.00, 'stock' => 60, 'desc' => 'Perfil metálico tipo canal de 2"x4" en 3m para estructura de tabiques drywall.'],

            // ── Pinturas y accesorios ────────────────────────────────────
            ['cat' => 'Pinturas y accesorios', 'name' => 'Pintura látex interior blanco 4L Vencedor', 'sku' => 'PT-001', 'price' => 65.00,  'compare_price' => 78.00,  'cost' => 42.00, 'stock' => 35, 'desc' => 'Pintura látex lavable para interiores, color blanco, 4 litros. Alto rendimiento y cobertura.'],
            ['cat' => 'Pinturas y accesorios', 'name' => 'Pintura anticorrosiva rojo 1L Tekno',       'sku' => 'PT-002', 'price' => 38.00,  'compare_price' => null,   'cost' => 23.00, 'stock' => 25, 'desc' => 'Pintura anticorrosiva al aceite para estructuras metálicas, color rojo minio. 1 litro.'],
            ['cat' => 'Pinturas y accesorios', 'name' => 'Rodillo de pintura 9" con marco',           'sku' => 'PT-003', 'price' => 18.50,  'compare_price' => 23.00,  'cost' => 10.00, 'stock' => 50, 'desc' => 'Rodillo de felpa 9" con marco y extensible incluido. Para pintura látex.'],
            ['cat' => 'Pinturas y accesorios', 'name' => 'Brocha cerda 4" multiusos',                 'sku' => 'PT-004', 'price' => 12.00,  'compare_price' => null,   'cost' => 6.00,  'stock' => 60, 'desc' => 'Brocha de 4" con cerdas naturales y mango de madera. Para pinturas al agua y aceite.'],
            ['cat' => 'Pinturas y accesorios', 'name' => 'Lija al agua grano 120 (pack x 10)',        'sku' => 'PT-005', 'price' => 14.00,  'compare_price' => null,   'cost' => 7.50,  'stock' => 80, 'desc' => 'Pack de 10 lijas al agua grano 120 para madera, metal y preparación de superficies.'],
            ['cat' => 'Pinturas y accesorios', 'name' => 'Espátula de empaste 6"',                    'sku' => 'PT-006', 'price' => 9.50,   'compare_price' => null,   'cost' => 5.00,  'stock' => 45, 'desc' => 'Espátula de acero inoxidable flexible de 6" para aplicar masilla y empaste.'],

            // ── Fijaciones y tornillería ─────────────────────────────────
            ['cat' => 'Fijaciones y tornillería', 'name' => 'Tornillos autoperforantes 1" caja x 200', 'sku' => 'FJ-001', 'price' => 15.00,  'compare_price' => null,   'cost' => 8.00,  'stock' => 70, 'desc' => 'Caja de 200 tornillos autoperforantes de 1" cabeza plana galvanizados. Para drywall.'],
            ['cat' => 'Fijaciones y tornillería', 'name' => 'Taco fisher 6mm caja x 100',              'sku' => 'FJ-002', 'price' => 12.00,  'compare_price' => null,   'cost' => 6.00,  'stock' => 90, 'desc' => 'Tacos de plástico 6mm para pared de concreto. Caja de 100 unidades.'],
            ['cat' => 'Fijaciones y tornillería', 'name' => 'Perno hexagonal 1/4" x 1" (caja x 50)',  'sku' => 'FJ-003', 'price' => 18.00,  'compare_price' => 22.00,  'cost' => 10.00, 'stock' => 40, 'desc' => 'Caja de 50 pernos hexagonales zincados 1/4" x 1" con tuerca y arandela incluidos.'],
            ['cat' => 'Fijaciones y tornillería', 'name' => 'Clavos de acero 2" caja 1kg',             'sku' => 'FJ-004', 'price' => 8.50,   'compare_price' => null,   'cost' => 4.50,  'stock' => 100,'desc' => 'Caja de 1kg de clavos de acero de 2" para madera y drywall.'],
            ['cat' => 'Fijaciones y tornillería', 'name' => 'Anclaje químico epoxi 300ml Hilti',       'sku' => 'FJ-005', 'price' => 95.00,  'compare_price' => 115.00, 'cost' => 62.00, 'stock' => 15, 'desc' => 'Anclaje químico de resina epoxi para fijaciones en concreto y mampostería. 300ml.'],

            // ── Seguridad y EPP ──────────────────────────────────────────
            ['cat' => 'Seguridad y EPP', 'name' => 'Casco de seguridad blanco clase A',               'sku' => 'EP-001', 'price' => 25.00,  'compare_price' => 30.00,  'cost' => 14.00, 'stock' => 30, 'desc' => 'Casco de protección clase A, color blanco, con ajuste ratchet. Norma ANSI Z89.1.'],
            ['cat' => 'Seguridad y EPP', 'name' => 'Guantes de cuero soldador par',                   'sku' => 'EP-002', 'price' => 18.00,  'compare_price' => null,   'cost' => 9.50,  'stock' => 40, 'desc' => 'Guantes de cuero largo para soldadura y trabajos de calor. Talla L.'],
            ['cat' => 'Seguridad y EPP', 'name' => 'Lentes de seguridad transparentes Uvex',          'sku' => 'EP-003', 'price' => 15.00,  'compare_price' => 19.00,  'cost' => 8.00,  'stock' => 55, 'desc' => 'Gafas de seguridad con lente transparente antiempañante. Protección UV 99%.'],
            ['cat' => 'Seguridad y EPP', 'name' => 'Respirador media cara N95 3M',                    'sku' => 'EP-004', 'price' => 32.00,  'compare_price' => null,   'cost' => 19.00, 'stock' => 35, 'desc' => 'Mascarilla respiratoria media cara N95. Protección contra polvo fino y partículas.'],
            ['cat' => 'Seguridad y EPP', 'name' => 'Botas de seguridad punta de acero 42',            'sku' => 'EP-005', 'price' => 89.00,  'compare_price' => 110.00, 'cost' => 55.00, 'stock' => 20, 'desc' => 'Botas de cuero con punta de acero, suela antideslizante y resistente a aceites. Talla 42.'],
            ['cat' => 'Seguridad y EPP', 'name' => 'Chaleco reflectivo naranja talla L',              'sku' => 'EP-006', 'price' => 22.00,  'compare_price' => null,   'cost' => 12.00, 'stock' => 25, 'desc' => 'Chaleco de seguridad naranja con cintas reflectivas plateadas. Talla L.'],
        ];

        foreach ($products as $i => $data) {
            $cat = $cats[$data['cat']] ?? null;
            if (!$cat) continue;

            Product::firstOrCreate(
                ['project_id' => $project->id, 'sku' => $data['sku']],
                [
                    'category_id'   => $cat->id,
                    'name'          => $data['name'],
                    'description'   => $data['desc'],
                    'price'         => $data['price'],
                    'compare_price' => $data['compare_price'],
                    'cost'          => $data['cost'],
                    'stock'         => $data['stock'],
                    'unit'          => 'UND',
                    'is_available'  => true,
                    'sort_order'    => $i,
                ]
            );
        }
    }
}
