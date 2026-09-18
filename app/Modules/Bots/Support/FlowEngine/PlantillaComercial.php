<?php

namespace App\Modules\Bots\Support\FlowEngine;

use App\Modules\Bots\Ia\IA;

use App\Models\Project;
use App\Support\ProjectContext;

/**
 * Bot Comercial Informativo: la primera línea de atención de cualquier negocio.
 *
 * Informa, orienta y busca en el catálogo con datos REALES del sistema; no
 * vende solo. Todo lo que huela a negociación (comprar, cotizar, cantidades,
 * descuentos especiales, reclamos) se deriva a una persona y el bot se calla.
 *
 * Modelo híbrido: menú con lista nativa + lenguaje natural. Si el cliente
 * escribe su consulta en vez de tocar el menú, el bloque `intencion` la
 * clasifica por reglas (sin IA) y salta a la rama correcta.
 *
 * Los textos de bienvenida, guías y despedida son bloques normales: el dueño
 * los edita en el constructor visual, por empresa, sin tocar código.
 */
class PlantillaComercial
{
    public const NOMBRE = 'Bot Comercial Informativo';

    public static function definicion(Project $project): array
    {
        $negocio = $project->name;

        // Rutas del clasificador: intención → bloque. Una sola fuente para el
        // enrutador inicial y para el "no coincide" del menú.
        $rutas = [
            'producto'     => 'consultar',
            'promociones'  => 'promos',
            'pagos'        => 'pagos',
            'direccion'    => 'direccion',
            'horario'      => 'horario',
            'contacto'     => 'contacto',
            'empresa'      => 'empresa',
            'web'          => 'web',
            'guia'         => 'guia_comprar',
            'faq'          => 'faq',
            'recomendacion'=> 'recomendar',
            'comparacion'  => 'consultar',
            'asesor'       => 'asesor',
            'fallback'     => 'no_entendi',
        ];

        return [
            'inicio' => 'inicio',

            // Entra ante saludos y ante las consultas comerciales típicas; a
            // todo lo demás no responde (podría ser una conversación privada).
            'disparos' => [
                ['palabras' => [
                    'hola', 'buenas', 'buenos dias', 'buenas tardes', 'buenas noches', 'info', 'informacion', 'información',
                    'precio', 'precios', 'cuanto', 'cuánto', 'tienen', 'tienes', 'venden', 'busco',
                    'catalogo', 'catálogo', 'promocion', 'promoción', 'oferta', 'pagar', 'pago', 'yape', 'plin',
                    'direccion', 'dirección', 'ubicacion', 'ubicación', 'horario', 'menu', 'menú', 'asesor',
                ]],
            ],

            'bloques' => [

                // ── Flujo 1: bienvenida + menú híbrido ───────────────────
                // El inicio saluda Y ENCAMINA: si el primer mensaje del cliente
                // ya es la pregunta ("¿cuánto cuesta X?", "¿cómo pago?"), se le
                // responde de una vez; el menú solo sale cuando saluda sin más.
                'inicio' => [
                    'tipo' => 'intencion', 'x' => 40, 'y' => 40,
                    'texto' => "¡Hola! 👋 Soy el asistente virtual de *{$negocio}*.\nPuedo darte precios, promociones e información al instante.",
                    'texto_repetido' => 'Claro 👇 Elige una opción:',
                    'rutas' => ['fallback' => 'menu'] + $rutas,
                    'siguiente' => 'menu',
                ],

                'menu' => [
                    'tipo' => 'lista', 'x' => 40, 'y' => 240,
                    'titulo' => '¿En qué puedo ayudarte?',
                    'texto'  => 'Elige una opción o *escríbeme directamente* tu consulta 👇',
                    'boton'  => 'Ver opciones',
                    // Texto libre que no es una opción → clasificador de intención.
                    'no_coincide' => 'router',
                    'secciones' => [[
                        'titulo' => 'Menú',
                        'filas'  => [
                            ['titulo' => '🔎 Buscar un producto',   'descripcion' => 'Precios y características',   'siguiente' => 'consultar'],
                            ['titulo' => '🔥 Ver promociones',      'descripcion' => 'Ofertas vigentes',            'siguiente' => 'promos'],
                            ['titulo' => '💳 Métodos de pago',      'descripcion' => 'Yape, Plin, cuentas…',        'siguiente' => 'pagos'],
                            ['titulo' => '📍 Dirección y horarios', 'descripcion' => 'Dónde y cuándo atendemos',    'siguiente' => 'direccion'],
                            ['titulo' => '🌐 Ver nuestra tienda',   'descripcion' => 'Catálogo online completo',    'siguiente' => 'web'],
                            ['titulo' => '❓ Cómo comprar',         'descripcion' => 'Te explico los pasos',        'siguiente' => 'guia_comprar'],
                            ['titulo' => '💬 Preguntas frecuentes', 'descripcion' => 'Envíos, garantía y más',      'siguiente' => 'faq'],
                            ['titulo' => '👤 Hablar con un asesor', 'descripcion' => 'Te atiende una persona',      'siguiente' => 'asesor'],
                        ],
                    ]],
                ],

                // Clasificador por reglas: recibe el texto libre y decide.
                'router' => [
                    'tipo' => 'intencion', 'x' => 340, 'y' => 40,
                    'rutas' => $rutas,
                ],

                // ── Flujos 2 y 3: buscar producto / consultar precio ─────
                'consultar' => [
                    'tipo' => 'consultar_producto', 'x' => 340, 'y' => 240,
                    'texto' => '🔎 ¿Qué producto buscas? Puedes escribir el *nombre*, la *categoría* o el *código* (vale aproximado).',
                    'asesor_siguiente' => 'asesor',
                    'libre_siguiente'  => 'router',
                    'siguiente' => 'asesor',
                ],

                // ── Flujo 4: promociones reales ──────────────────────────
                'promos' => [
                    'tipo' => 'promociones', 'x' => 340, 'y' => 640,
                    'vacio' => 'Por ahora no tenemos promociones vigentes 🙂. Escríbeme qué producto te interesa y te paso su precio.',
                    'libre_siguiente' => 'router',
                    'asesor_siguiente' => 'asesor',
                    'siguiente' => null,
                ],

                // ── Flujo 5: métodos de pago reales ──────────────────────
                'pagos' => [
                    'tipo' => 'metodos_pago', 'x' => 640, 'y' => 40,
                    'siguiente' => 'algo_mas',
                ],

                // ── Flujos 6 y 7: dirección y horario ────────────────────
                'direccion' => [
                    'tipo' => 'info_negocio', 'dato' => 'direccion', 'x' => 640, 'y' => 240,
                    'siguiente' => 'horario',
                ],
                'horario' => [
                    'tipo' => 'info_negocio', 'dato' => 'horario', 'x' => 640, 'y' => 440,
                    'siguiente' => 'algo_mas',
                ],

                // ── Flujo 8: información de la empresa ───────────────────
                'empresa' => [
                    'tipo' => 'info_negocio', 'dato' => 'empresa', 'x' => 640, 'y' => 640,
                    'siguiente' => 'algo_mas',
                ],
                'contacto' => [
                    'tipo' => 'info_negocio', 'dato' => 'contacto', 'x' => 640, 'y' => 840,
                    'siguiente' => 'algo_mas',
                ],

                // ── Flujo 9: página / catálogo online ────────────────────
                'web' => [
                    'tipo' => 'info_negocio', 'dato' => 'web', 'x' => 940, 'y' => 40,
                    // Cierra el tema sin coletilla: el bot queda escuchando en
                    // silencio (una respuesta bien construida > dos burbujas).
                    'siguiente' => 'escucha',
                ],

                // Escucha muda: espera el siguiente mensaje sin decir nada.
                'escucha' => [
                    'tipo' => 'intencion', 'esperar' => true, 'x' => 1240, 'y' => 640,
                    'libre_siguiente' => 'router',
                    'rutas' => $rutas,
                ],

                // ── Guía configurable (el dueño la edita a su gusto) ─────
                'guia_comprar' => [
                    'tipo' => 'mensaje', 'x' => 940, 'y' => 240,
                    'texto' => "🛒 *¿Cómo comprar?*\n1. Escríbeme el producto que buscas.\n2. Revisa precio y características.\n3. Dime cuál te interesa y la cantidad.\n4. Un asesor confirma disponibilidad.\n5. Coordinamos pago y entrega.",
                    'siguiente' => 'algo_mas',
                ],

                // ── Preguntas frecuentes: las del constructor, no otras ──
                'faq' => [
                    'tipo' => 'faq', 'x' => 940, 'y' => 440,
                    'vacio' => 'Todavía no tengo preguntas frecuentes registradas 🙂. Escribe *asesor* y una persona te ayuda.',
                    'libre_siguiente' => 'router',
                    'siguiente' => 'algo_mas',
                ],

                // ── Recomendación con presupuesto real ──────────────
                'recomendar' => [
                    'tipo' => 'recomendar', 'x' => 340, 'y' => 440,
                    'asesor_siguiente' => 'asesor',
                    'libre_siguiente'  => 'router',
                    'siguiente' => 'asesor',
                ],

                // ── Flujos 10 y 12: asesor / derivación humana ───────────
                'asesor' => [
                    'tipo' => 'registrar_crm', 'x' => 1240, 'y' => 40,
                    'etapa' => 'contactado', 'etiqueta' => 'pide-asesor',
                    'texto' => "👤 Perfecto, te paso con una persona del equipo.\nCuéntame mientras tanto qué necesitas, así te atendemos más rápido. 🙂",
                    'siguiente' => 'fin_asesor',
                ],

                // ── Flujo 11: no entendido (sin inventar nada) ───────────
                'no_entendi' => [
                    'tipo' => 'mensaje', 'x' => 1240, 'y' => 240,
                    'texto' => "No tengo esa información registrada 😕.\n\nPuedo ayudarte con:\n• Precios y productos 🔎\n• Promociones 🔥\n• Métodos de pago 💳\n• Dirección y horarios 📍\n\nO escribe *asesor* para hablar con una persona.",
                    'siguiente' => 'algo_mas',
                ],

                // Cierre suave tras una respuesta informativa.
                'algo_mas' => [
                    'tipo' => 'intencion', 'esperar' => true, 'x' => 940, 'y' => 640,
                    'texto' => '¿Te ayudo con algo más? Escríbeme tu consulta o escribe *menú* para ver las opciones. 🙂',
                    'rutas' => $rutas,
                ],

                // El bot termina; el asesor toma la conversación (la regla
                // tiene_vendedor lo mantiene callado desde ese momento).
                'fin_asesor' => [
                    'tipo' => 'fin', 'x' => 1240, 'y' => 440,
                    'texto' => null,
                ],
            ],

            // Con un vendedor humano asignado el bot no interrumpe.
            'reglas' => [
                ['tipo' => 'tiene_vendedor', 'accion' => 'silenciar'],
            ],
        ];
    }

    /**
     * Checklist de configuración: qué datos reales tiene el negocio y qué le
     * falta para que el bot responda completo. Se enseña en el panel al
     * activar la plantilla.
     *
     * @return array<int, array{ok: bool, texto: string}>
     */
    public static function checklist(Project $project): array
    {
        $ctx = ProjectContext::for($project);
        $n = $ctx->negocio();
        $pago = $ctx->datosPago();
        $s = $project->settings()->pluck('value', 'key');

        // Un JSON "[]" pasa filled() estando vacio: hay que DECODIFICAR las
        // listas, no mirar si la cadena existe (el checklist decia "pagos OK"
        // a la vez que el bot respondia "no tengo metodos registrados").
        $manual = json_decode((string) ($s['payment_manual_methods'] ?? '[]'), true);
        $aceptados = json_decode((string) ($s['accepted_payments'] ?? '[]'), true);

        $hayPagos = $pago['yape_numero'] || $pago['plin_numero']
            || collect(['bcp', 'bbva', 'interbank', 'scotiabank', 'nacion'])
                ->contains(fn ($k) => filled($s["payment_bank_{$k}"] ?? null))
            || ! empty($manual) || ! empty($aceptados);

        $hayCatalogo = \App\Modules\Catalogo\Models\Product::where('project_id', $project->id)
            ->where('is_available', true)->where('price', '>', 0)->exists();

        return [
            ['ok' => $hayCatalogo,          'texto' => 'Catálogo con productos y precios'],
            ['ok' => (bool) $hayPagos,      'texto' => 'Métodos de pago'],
            ['ok' => (bool) $n['direccion'],'texto' => 'Dirección'],
            ['ok' => (bool) $n['horario'],  'texto' => 'Horario de atención'],
            ['ok' => (bool) $n['web'],      'texto' => 'Tienda online'],
            ['ok' => (bool) $n['whatsapp'] || (bool) $n['telefono'], 'texto' => 'Teléfono / WhatsApp'],
        ];
    }
}
