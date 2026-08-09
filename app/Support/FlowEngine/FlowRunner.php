<?php

namespace App\Support\FlowEngine;

use App\Ia\IA;
use App\Models\Project;
use App\Support\ProjectContext;
use App\Support\FlowEngine\Carrito;

/**
 * MOTOR DE EJECUCIÓN DE FLUJOS.
 *
 * Lee un flujo (JSON de bloques + conexiones que dibuja el usuario en el
 * constructor visual) y lo ejecuta para UNA conversación. Cada bloque puede
 * consultar la info real del proyecto vía ProjectContext.
 *
 * El motor NO habla WhatsApp: recibe el mensaje del cliente y su "estado"
 * (en qué bloque quedó), y devuelve la respuesta + el nuevo estado. Quien
 * conecta con WhatsApp (Baileys) solo pasa mensajes y guarda el estado.
 *
 * Un "flujo" es: { "inicio": "b1", "bloques": { "b1": {...}, ... } }
 * Cada bloque: { "tipo": "...", ...datos..., "siguiente": "idBloque" }
 */
class FlowRunner
{
    private ProjectContext $ctx;

    public function __construct(
        private Project $project,
        private array $flow,
    ) {
        $this->ctx = ProjectContext::for($project);
    }

    /**
     * Procesa un mensaje entrante del cliente.
     *
     * @param  string  $mensaje   Texto que envió el cliente.
     * @param  array   $estado    ['bloque' => idActual|null, 'vars' => [...]]
     * @param  string  $telefono  Teléfono del cliente (para contexto).
     * @return array   ['respuestas' => [string...], 'estado' => [...], 'fin' => bool]
     */
    public function procesar(string $mensaje, array $estado, string $telefono = ''): array
    {
        $bloques = $this->flow['bloques'] ?? [];
        $vars = $estado['vars'] ?? [];
        $respuestas = [];

        // ¿Dónde estamos? Si no hay estado, empezamos por el bloque inicial.
        $actualId = $estado['bloque'] ?? ($this->flow['inicio'] ?? null);

        // COMANDO GLOBAL: el cliente puede cambiar de intención en cualquier momento
        // (menú, cancelar, ver productos…). Esto interrumpe el paso pendiente y salta
        // al bloque correspondiente — la conversación tiene prioridad sobre el flujo.
        $salto = $this->comandoGlobal($mensaje, $bloques, $estado);
        if ($salto !== null) {
            $actualId = $salto;
            $estado['esperando'] = false;   // cancelamos el paso pendiente
        }
        // Si veníamos esperando una respuesta (pregunta/opciones), este mensaje
        // es esa respuesta: la procesamos y avanzamos.
        elseif (!empty($estado['esperando'])) {
            $bloque = $bloques[$actualId] ?? null;
            if ($bloque) $bloque['_id'] = $actualId;   // para bloques que se repiten (asistente)
            $sig = $this->resolverRespuesta($bloque, $mensaje, $vars, $respuestas, $telefono);
            // Dato inválido → nos quedamos esperando el MISMO bloque (sin avanzar).
            if ($sig === '__ESPERAR__') {
                return [
                    'respuestas' => $respuestas,
                    'estado' => ['bloque' => $actualId, 'vars' => $vars, 'esperando' => true],
                    'fin' => false,
                    'acciones' => ['registrar' => null, 'agendar' => null],
                ];
            }
            $actualId = $sig;
        }

        // Acciones sobre el CRM que el webhook ejecuta (registrar lead, agendar, crear pedido).
        $acciones = ['registrar' => null, 'agendar' => null, 'pedido' => null];

        // Ejecutar bloques en cadena hasta que uno pida esperar o se acabe.
        $guardas = 0;
        while ($actualId !== null && isset($bloques[$actualId]) && $guardas++ < 50) {
            $bloque = $bloques[$actualId];
            $r = $this->ejecutar($bloque, $mensaje, $vars, $telefono);

            foreach ($r['respuestas'] as $txt) $respuestas[] = $txt;
            if (!empty($r['registrar'])) $acciones['registrar'] = $r['registrar'];
            if (!empty($r['agendar']))   $acciones['agendar']   = $r['agendar'];
            if (!empty($r['pedido']))    $acciones['pedido']    = $r['pedido'];

            if ($r['esperar']) {
                return [
                    'respuestas' => $respuestas,
                    'estado' => ['bloque' => $actualId, 'vars' => $vars, 'esperando' => true],
                    'fin' => false,
                    'acciones' => $acciones,
                ];
            }
            $actualId = $r['siguiente'] ?? ($bloque['siguiente'] ?? null);
        }

        return [
            'respuestas' => $respuestas,
            'estado' => ['bloque' => null, 'vars' => $vars, 'esperando' => false],
            'fin' => true,
            'acciones' => $acciones,
        ];
    }

    /** Ejecuta un bloque. Devuelve respuestas + si debe esperar + siguiente. */
    private function ejecutar(array $bloque, string $mensaje, array &$vars, string $telefono): array
    {
        $tipo = $bloque['tipo'] ?? 'mensaje';
        $out = ['respuestas' => [], 'esperar' => false, 'siguiente' => null];

        switch ($tipo) {
            case 'mensaje':
                $out['respuestas'][] = $this->interpolar($bloque['texto'] ?? '', $vars);
                break;

            case 'pregunta':
                $out['respuestas'][] = $this->interpolar($bloque['texto'] ?? '', $vars);
                $out['esperar'] = true; // esperamos la respuesta del cliente
                break;

            case 'opciones':
                $out['respuestas'][] = $this->textoOpciones($bloque, $vars);
                $out['esperar'] = true;
                break;

            case 'lista':
                // Lista nativa de WhatsApp (menú desplegable "Ver opciones").
                // El conector Baileys la envía como listMessage; si no puede, cae a texto.
                $secciones = [];
                foreach (($bloque['secciones'] ?? []) as $sec) {
                    $filas = [];
                    foreach (($sec['filas'] ?? $sec['opciones'] ?? []) as $f) {
                        $filas[] = [
                            'titulo'      => $this->interpolar($f['titulo'] ?? $f['texto'] ?? '', $vars),
                            'descripcion' => $this->interpolar($f['descripcion'] ?? '', $vars),
                            'id'          => $f['id'] ?? \Illuminate\Support\Str::slug($f['titulo'] ?? $f['texto'] ?? 'op'),
                        ];
                    }
                    $secciones[] = ['titulo' => $this->interpolar($sec['titulo'] ?? '', $vars), 'filas' => $filas];
                }
                $out['respuestas'][] = [
                    'tipo'    => 'lista',
                    'titulo'  => $this->interpolar($bloque['titulo'] ?? '', $vars),
                    'cuerpo'  => $this->interpolar($bloque['texto'] ?? $bloque['cuerpo'] ?? 'Elige una opción:', $vars),
                    'pie'     => $this->interpolar($bloque['pie'] ?? '', $vars),
                    'boton'   => $this->interpolar($bloque['boton'] ?? 'Ver opciones', $vars),
                    'secciones' => $secciones,
                    // Fallback en texto por si el canal no soporta listas
                    'fallback'  => $this->textoLista($bloque, $vars),
                ];
                $out['esperar'] = true;
                break;

            case 'buscar_producto':
                // Busca en el catálogo real usando lo que dijo el cliente (o un campo).
                $q = $this->interpolar($bloque['consulta'] ?? $mensaje, $vars);
                $prods = $this->ctx->buscarProducto($q);
                if ($prods->isEmpty()) {
                    $out['respuestas'][] = $bloque['no_encontrado'] ?? "No encontré \"$q\" en el catálogo.";
                } else {
                    $lineas = $prods->map(fn ($p) => "• {$p['nombre']} — S/ " . number_format($p['precio'], 2)
                        . ($p['stock'] !== null ? " (stock: {$p['stock']})" : ''))->implode("\n");
                    $out['respuestas'][] = ($bloque['encabezado'] ?? "Esto encontré:") . "\n" . $lineas;
                }
                break;

            case 'ia':
                // La IA responde con TODO el contexto del negocio + la conversación.
                $out['respuestas'][] = $this->respuestaIa($bloque, $mensaje, $telefono, $vars);
                break;

            case 'imagen':
                // Envía una imagen (por URL) con caption opcional.
                $out['respuestas'][] = [
                    'tipo' => 'imagen',
                    'url' => $this->interpolar($bloque['url'] ?? '', $vars),
                    'caption' => $this->interpolar($bloque['caption'] ?? '', $vars),
                ];
                break;

            case 'archivo':
                // Envía un documento (por URL).
                $out['respuestas'][] = [
                    'tipo' => 'archivo',
                    'url' => $this->interpolar($bloque['url'] ?? '', $vars),
                    'nombre' => $bloque['nombre'] ?? 'documento',
                ];
                break;

            case 'condicion':
                // Ramifica según si el mensaje contiene alguna palabra clave.
                $texto = mb_strtolower($mensaje);
                $rama = null;
                foreach (($bloque['reglas'] ?? []) as $regla) {
                    $palabras = array_map('trim', explode(',', mb_strtolower($regla['contiene'] ?? '')));
                    foreach ($palabras as $pal) {
                        if ($pal !== '' && str_contains($texto, $pal)) { $rama = $regla['siguiente'] ?? null; break 2; }
                    }
                }
                $out['siguiente'] = $rama ?? ($bloque['si_no'] ?? $bloque['siguiente'] ?? null);
                break;

            case 'webhook':
                // Llama a una URL externa (integración). Guarda la respuesta en una var.
                $out['respuestas'] = array_merge($out['respuestas'], $this->ejecutarWebhook($bloque, $vars, $telefono, $mensaje));
                break;

            case 'espera':
                // Marca de pausa (el conector físico decide cuánto). Aquí solo continúa.
                if (!empty($bloque['texto'])) $out['respuestas'][] = $this->interpolar($bloque['texto'], $vars);
                break;

            // ─── BLOQUES CON DATOS REALES (el diferenciador de BIXO) ───

            case 'catalogo':
                // Envía el catálogo REAL del negocio (productos + servicios con precio).
                $cat = $this->ctx->catalogo(($bloque['limite'] ?? 15));
                if ($cat->isEmpty()) {
                    $out['respuestas'][] = $bloque['vacio'] ?? 'Aún no tenemos productos publicados.';
                } else {
                    $enc = $bloque['encabezado'] ?? '📋 *Nuestro catálogo:*';
                    $lineas = $cat->map(fn ($p) => "▪️ {$p['nombre']} — S/ " . number_format($p['precio'], 2))->implode("\n");
                    $out['respuestas'][] = "$enc\n$lineas";
                }
                break;

            case 'estado_pedido':
                // Consulta el estado del último pedido del cliente (por su teléfono).
                $peds = $this->ctx->pedidosDe($telefono, 1);
                if ($peds->isEmpty()) {
                    $out['respuestas'][] = $bloque['sin_pedido'] ?? 'No encontré pedidos asociados a tu número.';
                } else {
                    $p = $peds->first();
                    $out['respuestas'][] = "📦 Tu pedido *#{$p['id']}* está: *{$p['estado']}*\nTotal: S/ " . number_format($p['total'], 2);
                }
                break;

            case 'cotizar':
                // Genera una cotización con lo que el cliente pidió (usa vars o catálogo).
                $q = $this->interpolar($bloque['consulta'] ?? $mensaje, $vars);
                $prods = $this->ctx->buscarProducto($q, 5);
                if ($prods->isEmpty()) {
                    $out['respuestas'][] = $bloque['no_encontrado'] ?? "No encontré \"$q\" para cotizar.";
                } else {
                    $tot = $prods->sum('precio');
                    $lineas = $prods->map(fn ($p) => "▪️ {$p['nombre']} — S/ " . number_format($p['precio'], 2))->implode("\n");
                    $out['respuestas'][] = "📋 *Cotización*\n$lineas\n─────\n💰 Total ref.: S/ " . number_format($tot, 2);
                }
                break;

            case 'registrar_crm':
                // Marca al cliente en el CRM: etapa y/o etiqueta (lo hace el webhook al persistir).
                $out['registrar'] = [
                    'etapa'    => $bloque['etapa'] ?? null,
                    'etiqueta' => $bloque['etiqueta'] ?? null,
                ];
                // Si hay carrito, el webhook crea el PEDIDO real (visible en CRM/extensión/POS).
                if (!empty($vars['carrito'])) {
                    $out['pedido'] = [
                        'items'      => $vars['carrito'],
                        'direccion'  => $vars['direccion'] ?? null,
                        'pago'       => $bloque['etiqueta'] ?? ($vars['pago'] ?? null),
                        'total'      => Carrito::subtotal($vars['carrito']),
                    ];
                }
                if (!empty($bloque['texto'])) $out['respuestas'][] = $this->interpolar($bloque['texto'], $vars);
                break;

            case 'agendar':
                // Registra una intención de cita/seguimiento (el webhook la persiste).
                $out['agendar'] = ['nota' => $this->interpolar($bloque['nota'] ?? 'Cita solicitada por bot', $vars)];
                $out['respuestas'][] = $bloque['texto'] ?? '✅ ¡Listo! Registré tu solicitud, te contactaremos pronto.';
                break;

            // ─── CARRITO DE COMPRAS (tienda virtual sin IA) ───

            case 'categorias':
                // Muestra las categorías como LISTA para navegar catálogos grandes.
                $cats = $this->ctx->categoriasConProductos();
                if ($cats->isEmpty()) {
                    $out['respuestas'][] = $bloque['vacio'] ?? 'Aún no hay productos publicados.';
                } else {
                    $filas = $cats->take(10)->map(fn ($c) => [
                        'titulo' => $c['nombre'], 'descripcion' => $c['total'] . ' productos',
                        'id' => 'cat_' . $c['id'], 'siguiente' => $bloque['siguiente'] ?? null,
                    ])->all();
                    $out['respuestas'][] = [
                        'tipo' => 'lista',
                        'titulo' => $bloque['titulo'] ?? '🛍️ Categorías',
                        'cuerpo' => $bloque['texto'] ?? 'Elige una categoría para ver productos:',
                        'boton' => 'Ver categorías',
                        'secciones' => [['titulo' => 'Categorías', 'filas' => $filas]],
                        'fallback' => "🛍️ *Categorías:*\n" . $cats->take(10)->map(fn ($c, $i) => ($i + 1) . ". {$c['nombre']} ({$c['total']})")->implode("\n"),
                    ];
                    $out['esperar'] = true;
                    $vars['_modo'] = 'categorias';
                }
                break;

            case 'buscar_agregar':
                // El destino al finalizar la compra (checkout) se recuerda para todo el carrito.
                $vars['_checkout'] = $bloque['finalizar_siguiente'] ?? $bloque['siguiente'] ?? ($vars['_checkout'] ?? null);
                // Busca (tolerante) y ofrece agregar al carrito con botones.
                $q = trim($this->interpolar($bloque['consulta'] ?? $mensaje, $vars));
                // Sin término de búsqueda todavía: esperamos a que el cliente escriba.
                if ($q === '' || $this->esComando($q)) {
                    $out['esperar'] = true;
                    $vars['_modo'] = 'buscando';
                    break;
                }
                $prods = $this->ctx->buscarTolerante($q, 5);
                if ($prods->isEmpty()) {
                    $out['respuestas'][] = "No encontré \"$q\" 😕. Escríbeme otro nombre o revisa las categorías.";
                    $out['esperar'] = true;
                    $vars['_modo'] = 'buscando';
                } elseif ($prods->count() === 1) {
                    // Un solo resultado: mostrarlo con botón de agregar
                    $p = $prods->first();
                    $vars['_ultimo_prod'] = $p['id'];
                    $out['respuestas'][] = [
                        'tipo' => 'lista',
                        'titulo' => $p['nombre'],
                        'cuerpo' => "*{$p['nombre']}*\n💵 S/ " . number_format($p['precio'], 2)
                            . ($p['stock'] !== null && $p['stock'] <= 0 ? "\n⚠️ Sin stock" : ''),
                        'boton' => '¿Qué hago?',
                        'secciones' => [['titulo' => 'Acciones', 'filas' => [
                            ['titulo' => '➕ Agregar al carrito', 'id' => 'add_' . $p['id']],
                            ['titulo' => '🔎 Buscar otro', 'id' => 'buscar'],
                            ['titulo' => '🛒 Ver carrito', 'id' => 'vercarrito'],
                        ]]],
                        'fallback' => "*{$p['nombre']}* — S/ " . number_format($p['precio'], 2) . "\nResponde: agregar / buscar / carrito",
                    ];
                    $out['esperar'] = true;
                    $vars['_modo'] = 'producto';
                } else {
                    // Varios: mostrar lista para elegir
                    $filas = $prods->map(fn ($p) => [
                        'titulo' => mb_substr($p['nombre'], 0, 24),
                        'descripcion' => 'S/ ' . number_format($p['precio'], 2),
                        'id' => 'add_' . $p['id'],
                    ])->all();
                    $out['respuestas'][] = [
                        'tipo' => 'lista',
                        'titulo' => 'Resultados',
                        'cuerpo' => "Encontré esto para \"$q\". Toca para agregar:",
                        'boton' => 'Ver productos',
                        'secciones' => [['titulo' => 'Productos', 'filas' => $filas]],
                        'fallback' => "Resultados:\n" . $prods->map(fn ($p, $i) => ($i + 1) . ". {$p['nombre']} — S/ " . number_format($p['precio'], 2))->implode("\n"),
                    ];
                    $out['esperar'] = true;
                    $vars['_modo'] = 'resultados';
                    $vars['_resultados'] = $prods->pluck('id')->all();
                }
                break;

            case 'ver_carrito':
                $vars['_checkout'] = $bloque['finalizar_siguiente'] ?? $bloque['siguiente'] ?? ($vars['_checkout'] ?? null);
                $carrito = $vars['carrito'] ?? [];
                $out['respuestas'][] = Carrito::resumen($carrito, (float) ($bloque['envio'] ?? 0));
                if (!empty($carrito)) {
                    $out['respuestas'][] = [
                        'tipo' => 'lista',
                        'titulo' => '🛒 Tu carrito',
                        'cuerpo' => '¿Qué deseas hacer?',
                        'boton' => 'Opciones',
                        'secciones' => [['titulo' => 'Carrito', 'filas' => [
                            ['titulo' => '➕ Agregar más productos', 'id' => 'seguir'],
                            ['titulo' => '➖ Quitar un producto', 'id' => 'quitar'],
                            ['titulo' => '🗑️ Vaciar carrito', 'id' => 'vaciar'],
                            ['titulo' => '🚚 Finalizar compra', 'id' => 'finalizar'],
                        ]]],
                        'fallback' => "Responde: agregar / quitar / vaciar / finalizar",
                    ];
                    $out['esperar'] = true;
                    $vars['_modo'] = 'carrito';
                } else {
                    // Carrito VACÍO: nunca avanzar al checkout. Ofrecer empezar a comprar.
                    $out['respuestas'][] = [
                        'tipo' => 'lista',
                        'titulo' => 'Empecemos 🛍️',
                        'cuerpo' => 'Aún no tienes productos. ¿Qué deseas hacer?',
                        'boton' => 'Ver opciones',
                        'secciones' => [['titulo' => 'Opciones', 'filas' => [
                            ['titulo' => '🔎 Buscar un producto', 'id' => 'buscar'],
                            ['titulo' => '🛍️ Ver categorías', 'id' => 'vercategorias'],
                            ['titulo' => '🏠 Volver al menú', 'id' => 'menu'],
                        ]]],
                        'fallback' => "Escribe *buscar* para encontrar un producto, *categorías* para explorar o *menú* para volver.",
                    ];
                    $out['esperar'] = true;
                    $vars['_modo'] = 'carrito_vacio';
                }
                break;

            case 'asistente':
                // ASISTENTE VIRTUAL: la IA lleva TODA la conversación (no flujo rígido).
                // Mantiene memoria, entiende intención, califica y agenda.
                $historial = $this->historialTexto($vars);
                $ctxNegocio = trim(($bloque['contexto'] ?? '') . "\n\n" . $this->ctx->briefParaIa($telefono));

                // modo 'asesor' = venta consultiva (servicios) | 'pedidos' = tomar pedidos
                $esAsesor = ($bloque['modo'] ?? 'pedidos') === 'asesor';

                // Primer contacto del cliente: Valeria se presenta con el mensaje de
                // bienvenida completo (fijo, no lo redacta la IA) antes de conversar.
                // Igual que hace el consultor humano: saluda + ambos catálogos + demo.
                if ($esAsesor && empty($vars['_estado_ia'])) {
                    $out['respuestas'][] = "Hola, buenas noches. 👋\nLe saluda Valeria, consultora de proyectos de Eskala";
                    $out['respuestas'][] = "🌐 Tiendas virtuales y páginas web\n"
                        . "https://compuciber.com/\n"
                        . "https://mercadosmayoristas.com.pe/\n"
                        . "https://www.gcsac.com.pe/\n"
                        . "https://markethuachoexpress.arindg.com/";
                    $out['respuestas'][] = "💼 Sistemas de gestión empresarial\n\n"
                        . "Banco Pichincha\nhttps://pichinchaperu.arandasoft.com/asmsspecialist/index.html#/\n\n"
                        . "BanBif\nhttps://banbif.arandasoft.com/asmsspecialist/index.html#/\n\n"
                        . "Gestión Humana\nhttps://gestionhumana.arandasoft.com/asmscustomer/index.html#/\n\n"
                        . "Mesa de Soporte\nhttps://soporte.arandasoft.com/asmsspecialist/index.html#/";
                    $out['respuestas'][] = "Si desea, también podemos prepararle una demo personalizada "
                        . "según el rubro de su negocio, para que vea cómo quedaría su propia tienda virtual.\n\n"
                        . "Quedo atento a cualquier consulta. 😊";
                    // Estado especial: ya se presentó y mostró todo. La IA no debe
                    // volver a saludar, solo preguntar el rubro cuando el cliente escriba.
                    $vars['_estado_ia'] = 'YA_PRESENTADA';
                    $out['esperar'] = true;
                    break;
                }

                $r = $esAsesor
                    ? IA::asesorComercial($mensaje, $historial, $ctxNegocio,
                        $vars['_estado_ia'] ?? 'INICIO', $vars['_datos_ia'] ?? [],
                        $vars['_lista_pendiente'] ?? null)
                    : IA::asistenteBot($mensaje, $historial, $ctxNegocio,
                        $vars['_estado_ia'] ?? 'MENU', $vars['_datos_ia'] ?? []);

                if (!empty($r['sin_ia'])) {
                    // Sin IA disponible: no dejamos al cliente sin respuesta.
                    $out['respuestas'][] = $bloque['fallback'] ?? 'En un momento te atiende un asesor. 🙏';
                } else {
                    // La IA responde en VARIOS globos cortos (como una persona en
                    // WhatsApp). Se envían por separado; la lista va con el último.
                    $globos = !empty($r['mensajes']) ? $r['mensajes'] : [$r['respuesta']];
                    $ultimo = array_pop($globos);
                    foreach ($globos as $g) {
                        if (trim($g) !== '') $out['respuestas'][] = $g;
                    }

                    // Si la IA decidió mostrar opciones, el último globo va como LISTA.
                    if (!empty($r['lista']['opciones'])) {
                        $opciones = collect($r['lista']['opciones'])->take(6);
                        $out['respuestas'][] = [
                            'tipo'   => 'lista',
                            'titulo' => $r['lista']['titulo'] ?? '',
                            'cuerpo' => $ultimo,
                            'boton'  => $r['lista']['boton'] ?? 'Ver opciones',
                            'secciones' => [[
                                'titulo' => '',
                                'filas' => $opciones->map(fn ($o, $i) => [
                                    'titulo'      => mb_substr($o['titulo'] ?? ('Opción ' . ($i + 1)), 0, 24),
                                    'descripcion' => mb_substr($o['descripcion'] ?? '', 0, 60),
                                    'id'          => $o['titulo'] ?? ('op' . ($i + 1)),
                                ])->all(),
                            ]],
                            'fallback' => $ultimo . "\n\n" . $opciones
                                ->map(fn ($o, $i) => ($i + 1) . '. ' . ($o['titulo'] ?? ''))->implode("\n"),
                        ];
                        // Recordar que hay una lista pendiente: si el cliente responde con
                        // solo un número, el router no debe confundirlo con "cantidad de
                        // productos" — es la selección de esta lista.
                        $vars['_lista_pendiente'] = $opciones->pluck('titulo')->values()->all();
                    } else {
                        $vars['_lista_pendiente'] = null;
                        if (trim((string) $ultimo) !== '') $out['respuestas'][] = $ultimo;
                    }
                    $vars['_estado_ia'] = $r['estado'];
                    $vars['_datos_ia']  = $r['datos'];
                    // Guardar el intercambio en la memoria de la conversación.
                    $vars['_hist'] = array_slice(array_merge($vars['_hist'] ?? [], [
                        ['de' => 'cliente', 'txt' => $mensaje],
                        ['de' => 'bot', 'txt' => $r['respuesta']],
                    ]), -16);   // últimos 8 intercambios

                    // Si la IA pide humano o ya tiene los datos, marcamos el lead.
                    if (!empty($r['escalar'])) {
                        $out['registrar'] = ['etapa' => 'contactado', 'etiqueta' => 'pide-asesor'];
                    } elseif (($r['estado'] ?? '') === 'CONFIRMACION') {
                        $out['registrar'] = ['etapa' => 'contactado', 'etiqueta' => 'lead-calificado'];
                    }
                }
                $out['esperar'] = true;   // el asistente siempre espera la siguiente respuesta
                break;

            case 'pago_qr':
                // Envía el QR de Yape/Plin + datos para pagar, y espera el comprobante.
                $pago = $this->ctx->datosPago();
                $total = !empty($vars['carrito']) ? Carrito::subtotal($vars['carrito']) : 0;

                $l = [];
                $l[] = $bloque['texto'] ?? '📲 *Pago por Yape / Plin*';
                if ($total > 0) $l[] = "💰 Monto a pagar: *S/ " . number_format($total, 2) . "*";
                if (!empty($pago['yape_numero'])) $l[] = "📱 Yape: *{$pago['yape_numero']}*"
                    . (!empty($pago['yape_nombre']) ? " ({$pago['yape_nombre']})" : '');
                if (!empty($pago['plin_numero'])) $l[] = "📱 Plin: *{$pago['plin_numero']}*";
                if (!empty($pago['instrucciones'])) $l[] = $pago['instrucciones'];
                $l[] = "";
                $l[] = $bloque['pedir_comprobante'] ?? "📸 Cuando pagues, *envíame la captura del comprobante* para validar tu pedido.";
                $textoPago = implode("\n", $l);

                if (!empty($pago['qr_url'])) {
                    // Imagen del QR con el texto como caption
                    $out['respuestas'][] = ['url' => $pago['qr_url'], 'caption' => $textoPago];
                } else {
                    $out['respuestas'][] = $textoPago;
                }
                $out['esperar'] = true;
                $vars['_modo'] = 'esperando_comprobante';
                break;

            case 'fin':
                if (!empty($bloque['texto'])) $out['respuestas'][] = $this->interpolar($bloque['texto'], $vars);
                $out['siguiente'] = null;
                break;

            default:
                $out['respuestas'][] = "[bloque desconocido: $tipo]";
        }

        return $out;
    }

    /** Palabras que son COMANDOS del sistema (nunca se guardan como dato). */
    private const COMANDOS = [
        'menu', 'menú', 'inicio', 'volver', 'cancelar', 'ver productos', 'productos',
        'catalogo', 'catálogo', 'hacer pedido', 'pedido', 'promociones', 'promos',
        'pago', 'zonas de entrega', 'zonas', 'hablar con asesor', 'asesor', 'ayuda',
    ];

    /**
     * Detecta si el mensaje es un COMANDO GLOBAL que cambia de intención.
     * Devuelve el id del bloque destino, o null si no es comando.
     * Busca en las opciones/filas de menús y listas del flujo un texto que coincida.
     */
    private function comandoGlobal(string $mensaje, array $bloques, array $estado): ?string
    {
        $m = mb_strtolower(trim($mensaje));
        if ($m === '') return null;

        // "menú"/"inicio"/"volver"/"cancelar" → regresar al bloque inicial.
        if (in_array($m, ['menu', 'menú', 'inicio', 'volver', 'cancelar'], true)) {
            return $this->flow['inicio'] ?? null;
        }

        // Buscar en menús (opciones) y listas (filas) una opción cuyo texto/id coincida.
        foreach ($bloques as $id => $b) {
            $tipo = $b['tipo'] ?? '';
            if ($tipo === 'opciones') {
                foreach (($b['opciones'] ?? []) as $op) {
                    if ($this->coincideComando($m, $op['texto'] ?? '') && !empty($op['siguiente'])) {
                        return $op['siguiente'];
                    }
                }
            }
            if ($tipo === 'lista') {
                foreach (($b['secciones'] ?? []) as $sec) {
                    foreach (($sec['filas'] ?? []) as $f) {
                        $etq = ($f['titulo'] ?? '') . ' ' . ($f['id'] ?? '');
                        if ($this->coincideComando($m, $etq) && !empty($f['siguiente'])) {
                            return $f['siguiente'];
                        }
                    }
                }
            }
        }
        return null;
    }

    /** ¿El mensaje coincide con una opción de menú (ignorando emojis/acentos)? */
    private function coincideComando(string $msg, string $opcion): bool
    {
        $limpia = fn ($s) => trim(preg_replace('/[^\p{L}\p{N} ]/u', '', mb_strtolower($s)));
        $a = $limpia($msg); $b = $limpia($opcion);
        if ($a === '' || $b === '') return false;
        return $a === $b || (mb_strlen($a) >= 4 && str_contains($b, $a)) || (mb_strlen($b) >= 4 && str_contains($a, $b));
    }

    /** ¿El texto es un comando del sistema? (no debe guardarse como dato) */
    private function esComando(string $texto): bool
    {
        $t = trim(preg_replace('/[^\p{L}\p{N} ]/u', '', mb_strtolower($texto)));
        foreach (self::COMANDOS as $c) {
            if ($t === $c || str_contains($t, $c)) return true;
        }
        return false;
    }

    /** Validación simple: ¿parece una dirección? (tiene calle+número o varias palabras) */
    private function pareceDireccion(string $texto): bool
    {
        if ($this->esComando($texto)) return false;
        $t = trim($texto);
        if (mb_strlen($t) < 6) return false;
        if ($this->esRuido($t)) return false;
        // Tiene un número (típico de dirección) o al menos 3 palabras
        return (bool) preg_match('/\d/', $t) || str_word_count($t) >= 3;
    }

    /** ¿Parece un pedido? (no es comando, saludo ni ruido) */
    private function parecePedido(string $texto): bool
    {
        $t = trim($texto);
        if (mb_strlen($t) < 3) return false;
        if ($this->esComando($t) || $this->esRuido($t)) return false;
        return true;
    }

    /** Saludos, agradecimientos y muletillas que NO son un dato válido. */
    private function esRuido(string $texto): bool
    {
        $t = trim(preg_replace('/[^\p{L}\p{N} ]/u', '', mb_strtolower($texto)));
        $ruido = ['hola', 'buenas', 'buenos dias', 'buenas tardes', 'buenas noches',
                  'gracias', 'muchas gracias', 'ok', 'okay', 'si', 'sí', 'no', 'ya',
                  'que tal', 'hey', 'holi', 'adios', 'chau', 'listo', 'bien', 'perfecto',
                  'ahorita', 'mañana', 'despues', 'después', 'ahora', 'aja', 'ajá'];
        return in_array($t, $ruido, true);
    }

    /** Procesa la respuesta del cliente a un bloque pregunta/opciones. */
    /**
     * Responde con naturalidad a cortesías/ruido (gracias, hola, emojis) sin
     * romper el flujo: reconoce el mensaje y orienta sobre qué puede hacer.
     */
    private function respuestaCortesia(string $msg, array $carrito): string
    {
        $t = trim(preg_replace('/[^\p{L}\p{N} ]/u', '', mb_strtolower($msg)));
        $tieneCarrito = !empty($carrito);
        $guia = $tieneCarrito
            ? "Puedes escribir *carrito* para ver tu pedido, *agregar* para seguir comprando o *finalizar* para cerrar la compra. 😊"
            : "Escribe *buscar* para encontrar un producto o *menú* para ver las opciones. 😊";

        if (in_array($t, ['gracias', 'muchas gracias'], true))            return "¡Con gusto! 🙌 $guia";
        if (in_array($t, ['ok', 'okay', 'listo', 'bien', 'perfecto', 'ya', 'aja', 'ajá'], true)) return "¡Genial! 👌 $guia";
        if (in_array($t, ['hola', 'buenas', 'buenos dias', 'buenas tardes', 'buenas noches', 'hey', 'holi', 'que tal'], true))
            return "¡Hola! 👋 $guia";
        if (in_array($t, ['adios', 'chau'], true))                        return "¡Hasta pronto! 👋 Aquí estaré cuando quieras pedir.";
        if (in_array($t, ['si', 'sí'], true))                             return "👍 $guia";
        if (in_array($t, ['no'], true))                                   return "Entendido. $guia";
        if (in_array($t, ['mañana', 'despues', 'después', 'ahorita', 'ahora'], true))
            return "Sin problema, cuando gustes. 🙂 $guia";
        return "😊 $guia";
    }

    /** Convierte la memoria de la conversación en texto para la IA. */
    private function historialTexto(array $vars): string
    {
        $h = $vars['_hist'] ?? [];
        if (empty($h)) return '(inicio de la conversación)';
        return collect($h)
            ->map(fn ($m) => ($m['de'] === 'cliente' ? 'Cliente: ' : 'Asesor: ') . $m['txt'])
            ->implode("\n");
    }

    /** Destino del checkout: el 'siguiente' del bloque ver_carrito del flujo (pedir dirección). */
    private function destinoCheckout(): ?string
    {
        foreach (($this->flow['bloques'] ?? []) as $b) {
            if (($b['tipo'] ?? '') === 'ver_carrito') {
                return $b['finalizar_siguiente'] ?? $b['siguiente'] ?? null;
            }
        }
        return null;
    }

    /**
     * Interpreta la respuesta del cliente dentro del flujo de CARRITO.
     * Devuelve el id del bloque siguiente, '__ESPERAR__' para re-mostrar, o null
     * si no era una acción de carrito (para que siga la lógica normal).
     */
    private function resolverCarrito(array $bloque, string $mensaje, array &$vars, array &$respuestas): ?string
    {
        $m = trim($mensaje);
        $ml = mb_strtolower($m);
        $carrito = $vars['carrito'] ?? [];

        // Destino del checkout (pedir dirección/pago): el 'siguiente' del bloque ver_carrito del flujo.
        $checkout = $this->destinoCheckout();

        // Agregar producto: id "add_123" (de botón) o número de la lista de resultados
        if (preg_match('/^add[_ ]?(\w+)/i', $m, $mm)) {
            $prod = $this->ctx->producto((int) $mm[1]);
            if ($prod) {
                $vars['carrito'] = Carrito::agregar($carrito, $prod, 1);
                $respuestas[] = "✅ Agregué *{$prod['nombre']}* a tu carrito.";
                $respuestas[] = Carrito::resumen($vars['carrito']);
                $respuestas[] = [
                    'tipo' => 'lista', 'titulo' => '¿Algo más?', 'cuerpo' => '¿Deseas seguir?',
                    'boton' => 'Opciones',
                    'secciones' => [['titulo' => ' ', 'filas' => [
                        ['titulo' => '➕ Agregar más', 'id' => 'seguir'],
                        ['titulo' => '🛒 Ver carrito', 'id' => 'vercarrito'],
                        ['titulo' => '🚚 Finalizar compra', 'id' => 'finalizar'],
                    ]]],
                    'fallback' => "Responde: agregar / carrito / finalizar",
                ];
                return '__ESPERAR__';
            }
        }

        // Elegir por número (lista de resultados previa)
        if (ctype_digit($m) && !empty($vars['_resultados'])) {
            $idx = (int) $m - 1;
            $ids = $vars['_resultados'];
            if (isset($ids[$idx])) {
                $prod = $this->ctx->producto((int) $ids[$idx]);
                if ($prod) {
                    $vars['carrito'] = Carrito::agregar($carrito, $prod, 1);
                    $respuestas[] = "✅ Agregué *{$prod['nombre']}*.";
                    $respuestas[] = Carrito::resumen($vars['carrito']);
                    return '__ESPERAR__';
                }
            }
        }

        // Navegar categoría: id "cat_5"
        if (preg_match('/^cat[_ ]?(\d+)/i', $m, $mm)) {
            $prods = $this->ctx->productosDeCategoria((int) $mm[1], 10);
            if ($prods->isNotEmpty()) {
                $filas = $prods->map(fn ($p) => [
                    'titulo' => mb_substr($p['nombre'], 0, 24),
                    'descripcion' => 'S/ ' . number_format($p['precio'], 2),
                    'id' => 'add_' . $p['id'],
                ])->all();
                $respuestas[] = [
                    'tipo' => 'lista', 'titulo' => 'Productos', 'cuerpo' => 'Toca para agregar:',
                    'boton' => 'Ver', 'secciones' => [['titulo' => 'Productos', 'filas' => $filas]],
                    'fallback' => $prods->map(fn ($p, $i) => ($i + 1) . ". {$p['nombre']} — S/ " . number_format($p['precio'], 2))->implode("\n"),
                ];
                $vars['_resultados'] = $prods->pluck('id')->all();
                return '__ESPERAR__';
            }
        }

        // Comandos de carrito
        if (in_array($ml, ['vercarrito', 'ver carrito', 'carrito', '🛒 ver carrito'], true)) {
            $respuestas[] = Carrito::resumen($carrito);
            return '__ESPERAR__';
        }
        if (in_array($ml, ['vaciar', '🗑️ vaciar carrito', 'vaciar carrito'], true)) {
            $vars['carrito'] = Carrito::vaciar();
            $respuestas[] = "🗑️ Carrito vaciado. Empecemos de nuevo cuando quieras.";
            return '__ESPERAR__';
        }
        if (in_array($ml, ['seguir', '➕ agregar más', 'agregar mas', 'agregar más', 'seguir comprando', '➕ agregar más productos'], true)) {
            $respuestas[] = "🔎 Escríbeme el nombre del producto que buscas (ej: quinua, miel).";
            $vars['_modo'] = 'buscando';
            return '__ESPERAR__';
        }
        if (in_array($ml, ['buscar', '🔎 buscar otro', 'buscar otro', '🔎 buscar un producto', 'buscar un producto'], true)) {
            $respuestas[] = "🔎 ¿Qué producto buscas?";
            $vars['_modo'] = 'buscando';
            return '__ESPERAR__';
        }
        // Ver categorías desde cualquier punto del carrito
        if (in_array($ml, ['vercategorias', 'categorias', 'categorías', '🛍️ ver categorías', 'ver categorias', 'ver categorías'], true)) {
            $cats = $this->ctx->categoriasConProductos();
            if ($cats->isEmpty()) {
                $respuestas[] = 'Aún no hay categorías disponibles. Escribe *buscar* para encontrar un producto.';
            } else {
                $filas = $cats->take(10)->map(fn ($c) => [
                    'titulo' => mb_substr($c['nombre'], 0, 24),
                    'descripcion' => $c['total'] . ' productos',
                    'id' => 'cat_' . $c['id'],
                ])->all();
                $respuestas[] = [
                    'tipo' => 'lista', 'titulo' => '🛍️ Categorías', 'cuerpo' => 'Elige una categoría:',
                    'boton' => 'Ver categorías', 'secciones' => [['titulo' => 'Categorías', 'filas' => $filas]],
                    'fallback' => "🛍️ *Categorías:*\n" . $cats->take(10)->map(fn ($c, $i) => ($i + 1) . ". {$c['nombre']}")->implode("\n"),
                ];
            }
            return '__ESPERAR__';
        }
        if (in_array($ml, ['quitar', '➖ quitar un producto', 'eliminar', '➖ eliminar producto'], true)) {
            if (empty($carrito)) { $respuestas[] = "Tu carrito está vacío."; return '__ESPERAR__'; }
            $filas = array_map(fn ($i) => [
                'titulo' => mb_substr($i['nombre'], 0, 24) . " x{$i['cantidad']}",
                'id' => 'del_' . $i['id'],
            ], $carrito);
            $respuestas[] = [
                'tipo' => 'lista', 'titulo' => 'Quitar producto', 'cuerpo' => '¿Cuál quito?',
                'boton' => 'Elegir', 'secciones' => [['titulo' => 'En tu carrito', 'filas' => $filas]],
                'fallback' => 'Escribe el nombre del producto a quitar.',
            ];
            return '__ESPERAR__';
        }
        if (preg_match('/^del[_ ]?(\w+)/i', $m, $mm)) {
            $vars['carrito'] = Carrito::eliminar($carrito, $mm[1]);
            $respuestas[] = "➖ Producto eliminado.";
            $respuestas[] = Carrito::resumen($vars['carrito']);
            return '__ESPERAR__';
        }
        if (in_array($ml, ['finalizar', '🚚 finalizar compra', 'finalizar compra', 'pagar'], true)) {
            if (empty($carrito)) {
                $respuestas[] = "Tu carrito está vacío. Agrega algún producto primero. 🛒";
                return '__ESPERAR__';
            }
            // Avanza al checkout (pedir dirección): destino global del flujo.
            return $checkout ?? $bloque['finalizar_siguiente'] ?? $bloque['siguiente'] ?? null;
        }

        // Cortesías en medio del flujo: responder natural sin romper nada.
        if ($this->esRuido($m)) {
            $respuestas[] = $this->respuestaCortesia($m, $carrito);
            return '__ESPERAR__';
        }

        // Modo búsqueda: cualquier texto libre se interpreta como búsqueda de producto
        if (($vars['_modo'] ?? '') === 'buscando' && $m !== '' && !$this->esComando($m)) {
            $prods = $this->ctx->buscarTolerante($m, 5);
            if ($prods->isEmpty()) {
                $respuestas[] = "No encontré \"$m\" 😕. Prueba con otro nombre.";
            } else {
                $filas = $prods->map(fn ($p) => [
                    'titulo' => mb_substr($p['nombre'], 0, 24),
                    'descripcion' => 'S/ ' . number_format($p['precio'], 2),
                    'id' => 'add_' . $p['id'],
                ])->all();
                $respuestas[] = [
                    'tipo' => 'lista', 'titulo' => 'Resultados', 'cuerpo' => "Para \"$m\":",
                    'boton' => 'Ver', 'secciones' => [['titulo' => 'Productos', 'filas' => $filas]],
                    'fallback' => $prods->map(fn ($p, $i) => ($i + 1) . ". {$p['nombre']} — S/ " . number_format($p['precio'], 2))->implode("\n"),
                ];
                $vars['_resultados'] = $prods->pluck('id')->all();
            }
            return '__ESPERAR__';
        }

        return null; // no era acción de carrito
    }

    private function resolverRespuesta(?array $bloque, string $mensaje, array &$vars, array &$respuestas, string $telefono): ?string
    {
        if (!$bloque) return null;
        $tipo = $bloque['tipo'] ?? '';

        // ASISTENTE: cada mensaje del cliente vuelve a ejecutar el MISMO bloque,
        // para que la IA responda siempre manteniendo la conversación.
        if ($tipo === 'asistente') {
            return $bloque['_id'] ?? null;
        }

        // ── Respuestas de bloques de CARRITO (según el modo activo) ──
        if (in_array($tipo, ['categorias', 'buscar_agregar', 'ver_carrito'], true)) {
            $r = $this->resolverCarrito($bloque, $mensaje, $vars, $respuestas);
            if ($r !== null) return $r;

            // No se reconoció la acción: NUNCA avanzar al checkout desde el carrito.
            // Orientamos y nos quedamos donde estamos (evita pedir dirección sin pedido).
            $tieneCarrito = !empty($vars['carrito']);
            $respuestas[] = $tieneCarrito
                ? "No entendí 🤔. Puedes escribir *carrito* para ver tu pedido, *agregar* para seguir comprando, *quitar* para eliminar algo o *finalizar* para cerrar la compra."
                : "No entendí 🤔. Escribe *buscar* para encontrar un producto, *categorías* para explorar o *menú* para volver al inicio.";
            return '__ESPERAR__';
        }

        // Esperando comprobante de pago (Yape/Plin): cualquier mensaje/imagen avanza
        // dejando el pedido "en revisión" para que el vendedor lo apruebe.
        if ($tipo === 'pago_qr') {
            $vars['pago_reportado'] = trim($mensaje);
            $respuestas[] = $bloque['recibido']
                ?? "✅ ¡Gracias! Recibimos tu comprobante. 🕵️ Un asesor validará el pago y te confirmamos en breve.";
            return $bloque['siguiente'] ?? null;
        }

        if ($tipo === 'pregunta') {
            $campo = $bloque['guardar_en'] ?? '';
            $val = trim($mensaje);

            // Nunca guardar un COMANDO del sistema como si fuera un dato.
            if ($campo && $this->esComando($val)) {
                $respuestas[] = $bloque['reintentar'] ?? 'No entendí tu respuesta. Por favor escríbeme el dato que te pedí. 🙏';
                return '__ESPERAR__';
            }

            // Validación por tipo de campo (dirección / pedido).
            $nombreCampo = mb_strtolower($campo);
            if (str_contains($nombreCampo, 'direccion') || str_contains($nombreCampo, 'dirección')) {
                if (!$this->pareceDireccion($val)) {
                    $respuestas[] = 'Eso no parece una dirección 📍. Escríbeme calle, número y una referencia (ej: Av. Perú 123, cerca al parque).';
                    return '__ESPERAR__';
                }
            }
            if (str_contains($nombreCampo, 'pedido') || str_contains($nombreCampo, 'producto')) {
                if (!$this->parecePedido($val)) {
                    $respuestas[] = 'Cuéntame *qué productos* quieres y la cantidad (ej: 2 quinua, 1 miel). 🛒';
                    return '__ESPERAR__';
                }
            }

            // Dato válido: guardar y avanzar.
            if ($campo) $vars[$campo] = $val;
            return $bloque['siguiente'] ?? null;
        }

        if ($tipo === 'opciones') {
            $opciones = $bloque['opciones'] ?? [];
            $elegido = trim($mensaje);
            // Match por número (1,2,3…) o por texto de la opción.
            foreach ($opciones as $i => $op) {
                $num = (string) ($i + 1);
                if ($elegido === $num || mb_strtolower($elegido) === mb_strtolower($op['texto'] ?? '')) {
                    return $op['siguiente'] ?? null;
                }
            }
            // No coincide: orientar mostrando las opciones (nunca un mensaje seco).
            $ops = array_map(fn ($op, $i) => ($i + 1) . '. ' . ($op['texto'] ?? ''), $opciones, array_keys($opciones));
            $respuestas[] = $bloque['reintentar']
                ?? "No estoy seguro de qué necesitas 🤔. Elige una opción:\n" . implode("\n", $ops);
            return '__ESPERAR__'; // se queda en el mismo (esperando de nuevo)
        }

        if ($tipo === 'lista') {
            $elegido = mb_strtolower(trim($mensaje));
            $n = 0;
            foreach (($bloque['secciones'] ?? []) as $sec) {
                foreach (($sec['filas'] ?? $sec['opciones'] ?? []) as $f) {
                    $n++;
                    $titulo = mb_strtolower($f['titulo'] ?? $f['texto'] ?? '');
                    $id     = mb_strtolower($f['id'] ?? '');
                    // Baileys devuelve el id o el título de la fila; también aceptamos el número.
                    if ($elegido === $titulo || ($id && $elegido === $id) || $elegido === (string) $n) {
                        return $f['siguiente'] ?? null;
                    }
                }
            }
            // Mensaje orientador (nunca seco): recuerda las opciones disponibles.
            $ops = [];
            foreach (($bloque['secciones'] ?? []) as $sec) {
                foreach (($sec['filas'] ?? []) as $f) $ops[] = $f['titulo'] ?? '';
            }
            $lista = implode("\n", array_map(fn ($o, $i) => ($i + 1) . ". $o", $ops, array_keys($ops)));
            $respuestas[] = $bloque['reintentar']
                ?? "No estoy seguro de qué necesitas 🤔. Puedes elegir una de estas opciones:\n$lista\n\n(Responde con el número o toca una opción)";
            return '__ESPERAR__';
        }

        return $bloque['siguiente'] ?? null;
    }

    /** Fallback en texto de una lista (si el canal no soporta listas nativas). */
    private function textoLista(array $bloque, array $vars): string
    {
        $txt = $this->interpolar($bloque['texto'] ?? $bloque['cuerpo'] ?? 'Elige una opción:', $vars) . "\n";
        $n = 0;
        foreach (($bloque['secciones'] ?? []) as $sec) {
            if (!empty($sec['titulo'])) $txt .= "\n*" . $this->interpolar($sec['titulo'], $vars) . "*\n";
            foreach (($sec['filas'] ?? $sec['opciones'] ?? []) as $f) {
                $n++;
                $txt .= $n . '. ' . $this->interpolar($f['titulo'] ?? $f['texto'] ?? '', $vars) . "\n";
            }
        }
        return rtrim($txt);
    }

    /** Genera la respuesta de un bloque IA con el contexto del proyecto. */
    private function respuestaIa(array $bloque, string $mensaje, string $telefono, array $vars): string
    {
        try {
            $brief = $this->ctx->briefParaIa($telefono);
            $instruccion = $bloque['instruccion'] ?? 'Eres un asistente de ventas del negocio. Responde breve, en español, usando SOLO la información del contexto. Si no sabes algo, dilo y ofrece ayuda humana.';
            $system = $instruccion . "\n\n--- CONTEXTO DEL NEGOCIO ---\n" . $brief;

            return IA::provider()->chat([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $mensaje],
            ], ['temperature' => 0.4, 'max_tokens' => 400]);
        } catch (\Throwable $e) {
            return $bloque['fallback'] ?? 'En un momento te atiende un asesor.';
        }
    }

    /** Llama a una URL externa (bloque webhook). Puede guardar la respuesta en una variable. */
    private function ejecutarWebhook(array $bloque, array &$vars, string $telefono, string $mensaje): array
    {
        $url = $this->interpolar($bloque['url'] ?? '', $vars);
        if ($url === '') return [];
        try {
            $payload = ['telefono' => $telefono, 'mensaje' => $mensaje, 'vars' => $vars];
            $metodo = strtolower($bloque['metodo'] ?? 'post');
            $resp = ($metodo === 'get')
                ? \Illuminate\Support\Facades\Http::timeout(15)->get($url, $payload)
                : \Illuminate\Support\Facades\Http::timeout(15)->post($url, $payload);

            // Guardar la respuesta (texto) en una variable si el bloque lo pide.
            if (!empty($bloque['guardar_en'])) {
                $vars[$bloque['guardar_en']] = $resp->body();
            }
            // Si el webhook devolvió un texto para el cliente, enviarlo.
            if (!empty($bloque['responder_body'])) {
                return [$resp->body()];
            }
        } catch (\Throwable $e) {
            if (!empty($bloque['fallback'])) return [$bloque['fallback']];
        }
        return [];
    }

    private function textoOpciones(array $bloque, array $vars): string
    {
        $txt = $this->interpolar($bloque['texto'] ?? 'Elige una opción:', $vars) . "\n";
        foreach (($bloque['opciones'] ?? []) as $i => $op) {
            $txt .= ($i + 1) . '. ' . ($op['texto'] ?? '') . "\n";
        }
        return rtrim($txt);
    }

    /** Reemplaza {{variable}} por su valor en un texto. */
    private function interpolar(string $texto, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', fn ($m) => $vars[$m[1]] ?? '', $texto);
    }
}
