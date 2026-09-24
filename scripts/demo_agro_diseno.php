<?php

/**
 * Diseno de la demo agricola: ajustes del constructor y bloques de la portada.
 *
 * Lo que distingue a este rubro y por eso no se copia el diseno de las otras
 * demos: el precio se mueve cada manana, se compra por saco y no por unidad,
 * y el comprador decide por zona de acopio y calibre. La portada esta armada
 * alrededor de esas tres cosas.
 *
 * Correr:  php scripts/demo_agro_diseno.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Project;
use Illuminate\Support\Facades\DB;

$project = Project::where('slug', 'demoagro')->firstOrFail();
$pid = $project->id;
$wa = '951234567';

// --------------------------------------------------------------- ajustes
$ajustes = [
    // Identidad. Verde oliva de campo y tierra oscura: ninguna otra demo
    // usa esta paleta, que es justamente el punto.
    'primary_color' => '#4D7C0F',
    'secondary_color' => '#1A2E05',
    'currency' => 'PEN',
    'currency_symbol' => 'S/',
    'store_mode' => 'direct',

    // Contacto
    'contact_phone' => '016543210',
    'contact_email' => 'ventas@agroandino.pe',
    'contact_address' => 'Mercado Mayorista de Santa Anita, Pabellon 3 - Puesto 142, Lima',
    'contact_hours' => 'Lunes a sabado de 4:00 a.m. a 2:00 p.m.',
    'business_hours' => 'Lunes a sabado de 4:00 a.m. a 2:00 p.m.',
    'quote_whatsapp' => $wa,

    // Portada
    'announcement_text' => 'Precio de pizarra actualizado cada manana · Pedido minimo 1 saco',
    'hero_badge' => 'Atendemos desde las 4 a.m., a la hora del mercado',
    'hero_title' => 'Del productor andino a su negocio',
    'hero_subtitle' => 'Papa, tuberculos, granos y menestras por saco. Acopio directo en '
        .'Huanuco, Junin, Ayacucho y Puno, con despacho en Lima el mismo dia.',
    'banner1_title' => 'Papa de la sierra central',
    'banner1_sub' => 'Canchan, Huayro y Amarilla Tumbay, ingreso diario',
    'banner2_title' => 'Granos y menestras',
    'banner2_sub' => 'Quinua, kiwicha y frijol por saco de 50 kg',

    // Catalogo
    'catalog_cols_desktop' => 4,
    'wholesale_enabled' => 1,
    'catalog_template' => 'fresh',

    // Textos de la plantilla. Los de fabrica hablan de "sin conservantes" y
    // "empaque sostenible": lenguaje de verduleria fina que a un comprador
    // de mercado mayorista no le dice nada.
    'fresh_trust_1' => 'Acopio directo en chacra',
    'fresh_trust_2' => 'Despacho de madrugada',
    'fresh_trust_3' => 'Procedencia y calibre declarados',
    'fresh_trust_4' => 'Precio de pizarra del dia',
    'fresh_trust_icon_1' => '🌱',
    'fresh_trust_icon_2' => '🚛',
    'fresh_trust_icon_3' => '📍',
    'fresh_trust_icon_4' => '💵',
    'fresh_grid_title' => 'Nuestro catalogo por saco',
    'fresh_benefit_1' => 'Reparto a su puesto',
    'fresh_benefit_sub_1' => 'Sin recargo desde 10 sacos',
    'fresh_benefit_2' => 'Compra en chacra',
    'fresh_benefit_sub_2' => 'Sin intermediarios en el camino',
    'fresh_benefit_3' => 'Calibre garantizado',
    'fresh_benefit_sub_3' => 'Se cambia o se descuenta',
    'fresh_benefit_4' => 'Factura y guia',
    'fresh_benefit_sub_4' => 'Electronicas, al instante',
    'fresh_benefit_icon_1' => '🚛',
    'fresh_benefit_icon_2' => '🌱',
    'fresh_benefit_icon_3' => '⚖️',
    'fresh_benefit_icon_4' => '🧾',
    'btn_cart_text' => 'Agregar al pedido',
    'cart_title' => 'Mi pedido',

    // Pago y entrega. En este rubro se paga contra entrega o por
    // transferencia: el camion sale de madrugada y se cobra al descargar.
    'payment_manual_enabled' => 1,
    'payment_manual_methods' => json_encode(['yape', 'plin', 'transferencia', 'cash']),
    'payment_cash_enabled' => 1,
    'payment_cash_label' => 'Efectivo contra entrega',
    'payment_yape_number' => $wa,
    'payment_plin_number' => $wa,
    'shipping_enabled' => 1,
    'shipping_cost' => 25,
    'shipping_free_from' => 600,
    'pickup_enabled' => 1,
    'pickup_label' => 'Recojo en el puesto de Santa Anita',

    // Pie
    'footer_tagline' => 'Distribuidora mayorista de papa, tuberculos y granos andinos. '
        .'Acopio en chacra, despacho en Lima.',
    'footer_copyright' => '© 2026 Agro Andino Distribuidora · Tienda de demostracion',

    // SEO. El titulo no repite el nombre del negocio: describe lo que se vende.
    'seo_title' => 'Papa por saco, tuberculos y granos andinos al por mayor en Lima',
    'seo_description' => 'Venta mayorista de papa canchan, huayro y amarilla, olluco, oca, '
        .'chuno, quinua, kiwicha y menestras. Saco de 50 kg con procedencia y calibre '
        .'declarados. Despacho a mercados de Lima.',
];

foreach ($ajustes as $k => $v) {
    DB::table('project_settings')->updateOrInsert(
        ['project_id' => $pid, 'key' => $k],
        ['value' => (string) $v, 'updated_at' => now(), 'created_at' => now()]
    );
}
echo 'Ajustes: '.count($ajustes)."\n";

// ---------------------------------------------------------------- bloques
$bloques = [
    // Banda con las tres reglas del negocio. Va arriba del todo porque son
    // las preguntas que el comprador hace antes de mirar un solo producto.
    ['info_strip', 'cards', 10, [
        'items' => [
            ['title' => 'Precio de pizarra', 'description' => 'Se actualiza cada manana segun el ingreso al mercado.', 'enabled' => true, 'sort_order' => 10],
            ['title' => 'Venta por saco', 'description' => 'Desde 1 saco de 50 kg. Tambien al por menor desde 1 kg.', 'enabled' => true, 'sort_order' => 20],
            ['title' => 'Procedencia declarada', 'description' => 'Cada lote indica zona de acopio y calibre.', 'enabled' => true, 'sort_order' => 30],
            ['title' => 'Despacho en Lima', 'description' => 'El mismo dia si pide antes de las 10 a.m.', 'enabled' => true, 'sort_order' => 40],
        ],
    ]],

    ['featured_categories', 'images', 20, [
        'title' => 'Que despachamos',
        'subtitle' => 'Siete lineas con ingreso diario de chacra.',
    ]],

    ['benefits', 'cards', 30, [
        'title' => 'Por que comprarnos',
        'body' => 'Compramos en chacra y despachamos a la hora que trabaja el mercado.',
        'items' => [
            ['key' => 'chacra', 'icon' => 'store', 'title' => 'Acopio directo', 'text' => 'Compramos al agricultor en chacra, sin intermediarios en el camino.', 'enabled' => true, 'sort_order' => 10],
            ['key' => 'madrugada', 'icon' => 'clock', 'title' => 'Atencion de madrugada', 'text' => 'Desde las 4 a.m., cuando el mercado se mueve de verdad.', 'enabled' => true, 'sort_order' => 20],
            ['key' => 'calibre', 'icon' => 'check', 'title' => 'Calibre garantizado', 'text' => 'Si el lote no llega como se ofrecio, se cambia o se descuenta.', 'enabled' => true, 'sort_order' => 30],
            ['key' => 'reparto', 'icon' => 'truck', 'title' => 'Reparto a su puesto', 'text' => 'Sin recargo desde 10 sacos dentro de Lima y Callao.', 'enabled' => true, 'sort_order' => 40],
        ],
    ]],

    // Navegar por zona de acopio: en este rubro el comprador pide "papa de
    // Huanuco", no "papa". Ninguna otra demo necesita este corte.
    ['collection_showcase', 'cards', 40, [
        'title' => 'Por zona de acopio',
        'subtitle' => 'Cada region da una papa distinta. Elija por procedencia.',
        'columns' => 3,
        'items' => [
            ['title' => 'Huanuco', 'subtitle' => 'Canchan y Amarilla Tumbay', 'enabled' => true, 'sort_order' => 10],
            ['title' => 'Junin', 'subtitle' => 'Huayro, Yungay y Chaucha', 'enabled' => true, 'sort_order' => 20],
            ['title' => 'Puno', 'subtitle' => 'Chuno, moraya y quinua', 'enabled' => true, 'sort_order' => 30],
            ['title' => 'Ayacucho', 'subtitle' => 'Peruanita y maiz cancha', 'enabled' => true, 'sort_order' => 40],
            ['title' => 'Cusco', 'subtitle' => 'Olluco, oca y maiz gigante', 'enabled' => true, 'sort_order' => 50],
            ['title' => 'Arequipa', 'subtitle' => 'Ajo, cebolla y kiwicha', 'enabled' => true, 'sort_order' => 60],
        ],
    ]],

    ['featured_products', 'grid', 50, [
        'title' => 'Mayor salida esta semana',
        'subtitle' => 'Lo que mas se despacha a mercados y restaurantes.',
    ]],

    ['about_preview', 'split', 60, [
        'label' => 'Quienes somos',
        'title' => 'Dieciocho anos comprando en chacra',
        'body' => 'Empezamos con un puesto de papa en Santa Anita y hoy acopiamos en nueve '
            .'regiones. Trabajamos con las mismas familias productoras de siempre: por eso '
            .'podemos decir de donde viene cada saco y responder por el calibre.',
        'items' => [
            ['value' => '18', 'title' => 'anos en el mercado', 'enabled' => true, 'sort_order' => 10],
            ['value' => '240', 'title' => 'clientes en Lima', 'enabled' => true, 'sort_order' => 20],
            ['value' => '9', 'title' => 'regiones de acopio', 'enabled' => true, 'sort_order' => 30],
            ['value' => '6 t', 'title' => 'despachadas al dia', 'enabled' => true, 'sort_order' => 40],
        ],
    ]],

    ['testimonials', 'cards', 70, [
        'title' => 'Lo que dicen nuestros clientes',
        'items' => [
            ['name' => 'Rosa Quispe', 'role' => 'Puesto 318, Mercado de Caquetá', 'rating' => 5,
                'text' => 'Me dejan los sacos a las seis de la manana en el puesto. Antes tenia que ir yo a Santa Anita y perdia media manana.', 'enabled' => true, 'sort_order' => 10],
            ['name' => 'Julio Ramirez', 'role' => 'Picanteria El Fogon, Surquillo', 'rating' => 5,
                'text' => 'Pido amarilla de Tumbay y llega amarilla de Tumbay. Con otros proveedores me mandaban cualquier papa y la cocina se daba cuenta.', 'enabled' => true, 'sort_order' => 20],
            ['name' => 'Mercedes Ccahuana', 'role' => 'Bodega y menu, San Juan de Lurigancho', 'rating' => 5,
                'text' => 'Compro medio saco y no me ponen problema. Y el precio del dia esta en la pagina, ya no tengo que llamar para preguntar.', 'enabled' => true, 'sort_order' => 30],
        ],
    ]],

    ['faq', 'accordion', 80, [
        'title' => 'Preguntas frecuentes',
        'items' => [
            ['question' => 'Cual es el pedido minimo?', 'answer' => 'Un saco de 50 kg. Tambien vendemos al por menor desde 1 kg en el puesto. El despacho sin costo es desde 10 sacos dentro de Lima Metropolitana.'],
            ['question' => 'El precio del dia es fijo?', 'answer' => 'El precio de pizarra se actualiza cada manana con el ingreso al mayorista. Cuando usted confirma su pedido, ese precio le queda cerrado por 24 horas aunque el mercado suba.'],
            ['question' => 'Puedo pedir un calibre en especial?', 'answer' => 'Si: extra, primera o segunda. Indiquelo al hacer el pedido, porque el calibre cambia el precio y el rendimiento en cocina. La segunda rinde mas para pure y relleno.'],
            ['question' => 'Como se de donde viene el producto?', 'answer' => 'Cada ficha indica la zona de acopio y el calibre. En la guia de remision va el numero de lote, por si necesita rastrearlo.'],
            ['question' => 'Emiten factura?', 'answer' => 'Si, factura y guia de remision electronica. Necesitamos su RUC, razon social y la direccion exacta de entrega.'],
            ['question' => 'Despachan a provincia?', 'answer' => 'Dejamos la carga en la agencia de transporte que usted indique, en Lima. El flete a provincia corre por cuenta del comprador.'],
            ['question' => 'Que pasa si el producto llega malogrado?', 'answer' => 'Se revisa al descargar, delante del transportista. Lo que no este conforme se devuelve en el momento y no se cobra.'],
        ],
    ]],

    ['wa_advisory', 'band', 90, [
        'title' => 'Compra por volumen o camion completo?',
        'subtitle' => 'Desde 50 sacos trabajamos precio de acopio. Escribanos y le cotizamos el flete incluido.',
        'button_text' => 'Pedir cotizacion por WhatsApp',
        'message' => 'Hola, quiero cotizar una compra por volumen.',
        'phone' => $wa,
    ]],

    ['cta_banner', 'wide', 100, [
        'title' => 'Arme su pedido hoy y reciba manana temprano',
        'subtitle' => 'Cierre antes de las 10 a.m. y el camion sale de madrugada.',
        'button_text' => 'Ver el catalogo',
        'button_url' => '/'.$project->slug.'/tienda',
        'background_color' => '#1A2E05',
    ]],

    ['locations', 'cards', 110, [
        'title' => 'Donde estamos',
        'subtitle' => 'Venga al puesto o pida el despacho.',
        'items' => [
            [
                'name' => 'Puesto 142 - Santa Anita',
                'nombre' => 'Puesto 142 - Santa Anita',
                'address' => 'Mercado Mayorista de Santa Anita, Pabellon 3, Lima',
                'direccion' => 'Mercado Mayorista de Santa Anita, Pabellon 3, Lima',
                'phone' => '016543210',
                'hours' => 'Lunes a sabado de 4:00 a.m. a 2:00 p.m.',
                'enabled' => true, 'sort_order' => 10,
            ],
            [
                'name' => 'Almacen de acopio - Huanuco',
                'nombre' => 'Almacen de acopio - Huanuco',
                'address' => 'Carretera Central km 8, Amarilis, Huanuco',
                'direccion' => 'Carretera Central km 8, Amarilis, Huanuco',
                'phone' => '062345678',
                'hours' => 'Lunes a viernes de 7:00 a.m. a 5:00 p.m.',
                'enabled' => true, 'sort_order' => 20,
            ],
        ],
    ]],
];

foreach ($bloques as [$component, $variant, $orden, $content]) {
    DB::table('store_sections')->updateOrInsert(
        ['project_id' => $pid, 'component' => $component, 'page' => 'home'],
        [
            'variant' => $variant,
            'sort_order' => $orden,
            'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
            'created_at' => now(),
        ]
    );
}
echo 'Bloques: '.count($bloques)."\n";
echo "Listo: https://arindg.com/{$project->slug}\n";
