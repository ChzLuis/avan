<?php

return [
    'marca' => 'Eskala',

    // Datos comerciales que puede comunicar Valeria. Cambia esta sección cuando
    // cambien precios, alcance o casos de éxito; no hace falta editar el prompt.
    'datos' => [
        'PRECIO' => 'S/ 490, pago único, IGV incluido. Es la implementación completa de la tienda virtual.',
        'RENOVACION' => 'S/ 100 al año, recién a partir del segundo año. Cubre hosting, mantenimiento y continuidad del servicio.',
        'INCLUYE' => 'Diseño adaptado al rubro, catálogo inicial de hasta 200 productos con categorías y subcategorías, carrito de compras, sistema de cotizaciones, botón de WhatsApp, pasarela de pagos, configuración básica de envíos, panel administrativo, precios minorista y mayorista, SEO, código QR, Libro de Reclamaciones y capacitación.',
        'PANEL' => 'Un panel administrativo donde puede gestionar ventas, productos, proveedores y usuarios. La capacitación está incluida.',
        'PRODUCTOS' => 'La carga inicial incluye hasta 200 productos, con categorías y subcategorías. Si tiene más, se puede evaluar con el asesor.',
        'CATEGORIAS' => 'Se organizan en categorías y subcategorías, adaptadas a cómo ordena su negocio.',
        'PAGOS' => 'Se integra una pasarela de pagos y también queda el botón de WhatsApp para pedidos directos.',
        'ENVIOS' => 'Se hace una configuración básica de envíos según cómo trabaje el negocio.',
        'HOSTING' => 'El primer año está incluido. Desde el segundo año son S/ 100 anuales por hosting y mantenimiento.',
        'CAPACITACION' => 'Sí, la capacitación está incluida para que pueda administrar su tienda.',
        'SEO' => 'Se entrega con optimización SEO para que la tienda pueda aparecer en Google.',
        'WHATSAPP' => 'La tienda lleva botón de WhatsApp para que pedidos y consultas lleguen directo a su celular.',
        'QR' => 'Se entrega un código QR exclusivo de la tienda para compartirlo en el local, volantes o redes.',
        'RECLAMACIONES' => 'Incluye el Libro de Reclamaciones.',
        'BOT' => 'El bot para recepción automática de pedidos cuesta S/ 20 mensuales. Es opcional.',
        'META_ADS' => 'Hay asesoría de 2 horas por S/ 50 la sesión o administración mensual de campañas por S/ 200 al mes. No incluye el presupuesto publicitario.',
        'INSTITUCIONAL' => 'El módulo institucional cuesta S/ 150.',
        'TIEMPO' => 'Los tiempos de entrega se coordinan con el asesor según el tamaño del catálogo.',
        'FORMA_PAGO' => 'La forma de pago se coordina con el asesor en la llamada.',
        'VIDEO' => 'Video de cómo funciona la carga de productos: https://www.loom.com/share/abaeca8eccba4598b02dc756dd8b2263',
        'PLANTILLA' => 'Puede compartir un formato, diseño o plantilla de referencia que le guste; el equipo lo adapta a su tienda.',
        'RESPONSIVE' => 'La tienda funciona en celular, tablet y computadora.',
    ],

    'incluye_resumen' => [
        'Diseño personalizado', 'Catálogo organizado', 'Carga inicial de hasta 200 productos',
        'Carrito y pagos en línea', 'Pedidos por WhatsApp', 'Panel de administración', 'Capacitación',
    ],

    'casos_exito' => [
        ['nombre' => 'Compuciber', 'rubro' => 'Tecnología', 'url' => 'https://compuciber.com/', 'descripcion' => 'Ideal para catálogos amplios de tecnología.'],
        ['nombre' => 'Mercados Mayoristas', 'rubro' => 'Mayorista', 'url' => 'https://mercadosmayoristas.com.pe/', 'descripcion' => 'Tienda con muchas categorías y búsqueda rápida.'],
        ['nombre' => 'GC SAC', 'rubro' => 'Corporativo', 'url' => 'https://www.gcsac.com.pe/', 'descripcion' => 'Diseño corporativo para empresas.'],
        ['nombre' => 'Market Huacho Express', 'rubro' => 'Ventas rápidas', 'url' => 'https://markethuachoexpress.arindg.com/', 'descripcion' => 'Modelo pensado para ventas rápidas.'],
    ],

    // Estas listas se envían como listas tocables de WhatsApp; la IA no las inventa.
    'listas' => [
        'horarios' => [
            'titulo' => 'Coordinar llamada',
            'boton' => 'Ver horarios',
            'opciones' => [
                ['titulo' => 'Hoy en la mañana', 'descripcion' => 'Coordinar una llamada hoy'],
                ['titulo' => 'Hoy en la tarde', 'descripcion' => 'Coordinar una llamada hoy'],
                ['titulo' => 'Mañana temprano', 'descripcion' => 'Coordinar una llamada mañana'],
                ['titulo' => 'Mañana en la tarde', 'descripcion' => 'Coordinar una llamada mañana'],
            ],
        ],
        'siguiente_paso' => [
            'titulo' => '¿Cómo seguimos?',
            'boton' => 'Ver opciones',
            'opciones' => [
                ['titulo' => 'Sí, mostrar demo', 'descripcion' => 'Preparamos una demo para tu rubro'],
                ['titulo' => 'Quiero más información', 'descripcion' => 'Resolver dudas primero'],
            ],
        ],
        'cierre' => [
            'titulo' => '¿Qué prefieres?',
            'boton' => 'Ver opciones',
            'opciones' => [
                ['titulo' => 'Recibir propuesta', 'descripcion' => 'Propuesta personalizada'],
                ['titulo' => 'Agendar reunión', 'descripcion' => 'Reunión de 15 minutos'],
                ['titulo' => 'Tengo dudas', 'descripcion' => 'Resolver algunas consultas primero'],
            ],
        ],
    ],
];
