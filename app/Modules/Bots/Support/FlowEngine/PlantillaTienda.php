<?php

namespace App\Modules\Bots\Support\FlowEngine;

use App\Models\Project;

/**
 * Bot estándar de tienda: la plantilla que se aplica a cualquier negocio.
 *
 * Lo que hace un cliente con este bot, de principio a fin:
 *
 *   saluda → elige en el menú → busca o navega categorías → agrega al carrito
 *   → confirma → deja su dirección → elige cómo paga → **queda un PEDIDO REAL**
 *
 * El último paso es el que importa: `registrar_crm` con carrito no solo etiqueta
 * al cliente, crea el pedido y aparece en el CRM y en el punto de venta. Sin él
 * el bot solo sería un folleto que contesta.
 *
 * Se apoya solo en bloques que el motor ya ejecuta (`FlowRunner`), y los textos
 * salen de los datos del negocio, no están escritos a mano por proyecto.
 */
class PlantillaTienda
{
    public const NOMBRE = 'Tienda — pedido por WhatsApp';

    /**
     * Definición lista para guardar en `bot_builder_flows.definicion`.
     *
     * Las coordenadas x/y colocan los bloques en el lienzo del constructor de
     * forma legible: el camino principal baja por la izquierda y las ramas se
     * abren a la derecha. Sin ellas, el dueño abre el constructor y ve una pila
     * de cajas superpuestas.
     */
    public static function definicion(Project $project): array
    {
        $negocio = $project->name;

        return [
            // Por dónde empieza la conversación. El motor lee esta clave de
            // primer nivel, no el orden de los bloques: sin ella el bot arranca
            // en ningún sitio y el cliente no recibe nada.
            'inicio' => 'inicio',

            // El bot solo entra si el cliente dice algo de esto. Sin disparos
            // contestaría a cualquier mensaje, incluido el de alguien que solo
            // quiere hablar con una persona.
            'disparos' => [
                ['palabras' => [
                    'hola', 'buenas', 'buenos dias', 'buenas tardes',
                    'pedido', 'pedir', 'comprar', 'quiero', 'precio', 'precios',
                    'catalogo', 'catálogo', 'delivery', 'envio', 'envío', 'menu', 'menú',
                ]],
            ],

            'bloques' => [

                // ── Entrada ──────────────────────────────────────────────
                'inicio' => [
                    'tipo' => 'mensaje', 'x' => 60, 'y' => 40,
                    'texto' => "¡Hola! 👋 Bienvenido a *{$negocio}*.\nEstoy aquí para ayudarte a hacer tu pedido.",
                    'siguiente' => 'menu',
                ],

                'menu' => [
                    'tipo' => 'lista', 'x' => 60, 'y' => 170,
                    'titulo' => '¿Qué deseas hacer?',
                    'texto'  => 'Elige una opción para empezar 👇',
                    'boton'  => 'Ver opciones',
                    'secciones' => [[
                        'titulo' => 'Menú',
                        'filas'  => [
                            ['titulo' => '🔎 Buscar un producto', 'descripcion' => 'Escribe lo que necesitas', 'siguiente' => 'buscar'],
                            ['titulo' => '🛍️ Ver categorías',     'descripcion' => 'Explora todo el catálogo',  'siguiente' => 'categorias'],
                            ['titulo' => '🛒 Ver mi carrito',      'descripcion' => 'Revisa lo que llevas',      'siguiente' => 'carrito'],
                            ['titulo' => '📦 Estado de mi pedido', 'descripcion' => 'Consulta tu último pedido', 'siguiente' => 'estado'],
                            ['titulo' => '💬 Hablar con un asesor', 'descripcion' => 'Te atiende una persona',   'siguiente' => 'asesor'],
                        ],
                    ]],
                ],

                // ── Comprar ──────────────────────────────────────────────
                'buscar' => [
                    'tipo' => 'buscar_agregar', 'x' => 380, 'y' => 120,
                    'texto' => '🔎 Escríbeme el nombre del producto que buscas.',
                    'siguiente' => 'carrito',
                    'finalizar_siguiente' => 'direccion',
                ],

                'categorias' => [
                    'tipo' => 'categorias', 'x' => 380, 'y' => 260,
                    'titulo' => '🛍️ Nuestras categorías',
                    'texto'  => 'Elige una categoría para ver sus productos:',
                    'vacio'  => 'Todavía estamos publicando el catálogo. Escríbeme qué necesitas y te ayudo. 🙂',
                    'siguiente' => 'buscar',
                ],

                'carrito' => [
                    'tipo' => 'ver_carrito', 'x' => 60, 'y' => 420,
                    'siguiente' => 'buscar',
                    'finalizar_siguiente' => 'direccion',
                ],

                // ── Cierre del pedido ────────────────────────────────────
                'direccion' => [
                    'tipo' => 'pregunta', 'x' => 60, 'y' => 570,
                    'texto' => "📍 Perfecto. ¿A qué *dirección* llevamos tu pedido?\nIndícame calle, número y una referencia.",
                    'guardar_en' => 'direccion',
                    'siguiente' => 'pago',
                ],

                'pago' => [
                    'tipo' => 'opciones', 'x' => 60, 'y' => 710,
                    'texto' => '💳 ¿Cómo prefieres *pagar*?',
                    'opciones' => [
                        ['texto' => '💵 Al recibir el pedido', 'siguiente' => 'cierre_contraentrega'],
                        ['texto' => '📲 Yape o Plin',          'siguiente' => 'cierre_digital'],
                    ],
                ],

                // Las dos ramas crean el pedido real; solo cambia qué se le dice
                // al cliente y con qué etiqueta queda en el CRM.
                'cierre_contraentrega' => [
                    'tipo' => 'registrar_crm', 'x' => 380, 'y' => 660,
                    'etapa' => 'cotizado', 'etiqueta' => 'contra-entrega',
                    'texto' => "✅ ¡Listo! Tu pedido quedó registrado.\n\n📍 Entrega en: {{direccion}}\n💵 Pagas cuando lo recibas.\n\nUn asesor te confirmará en breve. ¡Gracias por comprar en *{$negocio}*! 🛒",
                    'siguiente' => 'fin',
                ],

                'cierre_digital' => [
                    'tipo' => 'registrar_crm', 'x' => 380, 'y' => 810,
                    'etapa' => 'cotizado', 'etiqueta' => 'yape-plin',
                    'texto' => "✅ ¡Listo! Tu pedido quedó registrado.\n\n📍 Entrega en: {{direccion}}\n📲 Te enviamos los datos de Yape/Plin para completar el pago.\n\nUn asesor te confirmará en breve. ¡Gracias! 🛒",
                    'siguiente' => 'fin',
                ],

                // ── Ramas de consulta ────────────────────────────────────
                'estado' => [
                    'tipo' => 'estado_pedido', 'x' => 700, 'y' => 120,
                    'sin_pedido' => 'No encuentro pedidos con este número todavía. Si acabas de pedir, dame unos minutos. 🙂',
                    'siguiente' => 'fin',
                ],

                'asesor' => [
                    'tipo' => 'registrar_crm', 'x' => 700, 'y' => 260,
                    'etapa' => 'contactado', 'etiqueta' => 'pide-asesor',
                    'texto' => '💬 Enseguida te atiende una persona del equipo. Cuéntame mientras tanto en qué te ayudamos.',
                    'siguiente' => 'fin',
                ],

                'fin' => [
                    'tipo' => 'fin', 'x' => 380, 'y' => 960,
                    'texto' => '🙌 Gracias por escribirnos. Si necesitas algo más, escribe *hola* y volvemos a empezar.',
                ],
            ],

            // El bot se calla cuando ya hay una persona atendiendo: nada peor que
            // un cliente hablando con un asesor y que el bot le interrumpa.
            'reglas' => [
                ['tipo' => 'tiene_vendedor', 'accion' => 'silenciar'],
            ],
        ];
    }
}
