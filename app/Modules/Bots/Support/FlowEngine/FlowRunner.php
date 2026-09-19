<?php

namespace App\Modules\Bots\Support\FlowEngine;

use App\Modules\Tienda\Support\StorefrontNavigation;

use App\Modules\Bots\Ia\IA;
use App\Models\Project;
use App\Support\ProjectContext;
use App\Modules\Bots\Support\FlowEngine\Carrito;

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

        // CORTESÍA: "gracias", "ok", "vale" no son búsquedas. Sin esto, un
        // "gracias" de cierre devolvía una lista de colchones (paso real).
        // Se responde el gesto y la conversación se queda donde estaba.
        // Si el bloque en curso hizo una pregunta de si/no, un "claro" o un
        // "dale" son la RESPUESTA, no cortesia: tratarlos como cortesia dejaba
        // al cliente confirmando el asesor y recibiendo "si necesitas algo mas...".
        $bloqueActual = $bloques[$estado['bloque'] ?? ''] ?? null;
        $esperaSiONo = $bloqueActual
            && (! empty($bloqueActual['confirmacion']) || ! empty($bloqueActual['negacion']));
        $social = $esperaSiONo ? null : $this->respuestaSocial($mensaje, $estado['vars'] ?? []);
        if ($social !== null) {
            return [
                'respuestas' => [$social],
                'estado' => $estado,
                'fin' => false,
                'acciones' => ['registrar' => null, 'agendar' => null],
            ];
        }

        // COMANDO GLOBAL: el cliente puede cambiar de intención en cualquier momento
        // (menú, cancelar, ver productos…). Esto interrumpe el paso pendiente y salta
        // al bloque correspondiente — la conversación tiene prioridad sobre el flujo.
        // Con una pregunta de si/no pendiente, la respuesta manda sobre los
        // comandos globales: un "claro" confirmaba y acababa en el saludo.
        $salto = ($esperaSiONo && $this->siONo($mensaje) !== null)
            ? null
            : $this->comandoGlobal($mensaje, $bloques, $estado);
        if ($salto !== null) {
            $actualId = $salto;
            $estado['esperando'] = false;   // cancelamos el paso pendiente
            // El salto limpia el SUBFLUJO (modo de consulta, listas abiertas),
            // pero conserva lo util: _saludado (no re-presentarse) y
            // _ultimo_prod (poder retomar "y ese ropero?").
            foreach (['_modo', '_resultados', '_categorias', '_faq', '_cat_id', '_cat_offset', '_consulta_texto', '_consulta_forzada', '_nav'] as $k) {
                unset($estado['vars'][$k]);
            }
            if ($this->saltoConsumeMensaje) {
                $mensaje = '';
                $this->saltoConsumeMensaje = false;
            }
        }
        // Si veníamos esperando una respuesta (pregunta/opciones), este mensaje
        // es esa respuesta: la procesamos y avanzamos.
        elseif (!empty($estado['esperando'])) {
            $bloque = $bloques[$actualId] ?? null;
            if ($bloque) $bloque['_id'] = $actualId;   // para bloques que se repiten (asistente)
            $sn = ($bloque && (! empty($bloque['confirmacion']) || ! empty($bloque['negacion'])))
                ? $this->siONo($mensaje) : null;
            if ($sn === true && ! empty($bloque['confirmacion'])) {
                $sig = $bloque['confirmacion'];
                unset($vars['_pregunta_' . $actualId]);
                $mensaje = '';
            } elseif ($sn === false && ! empty($bloque['negacion'])) {
                $sig = $bloque['negacion'];
                unset($vars['_pregunta_' . $actualId]);
                $mensaje = '';
            } else {
            $sig = $this->resolverRespuesta($bloque, $mensaje, $vars, $respuestas, $telefono);
            }
            // Si el mensaje fue una ELECCION de menu ("1", el titulo de la
            // fila), ya cumplio su funcion: los bloques que siguen no deben
            // tomarlo como dato (buscar "1" como producto, p. ej.).
            if (! empty($vars['_msj_consumido'])) {
                unset($vars['_msj_consumido']);
                $mensaje = '';
            }
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
        $acciones = ['registrar' => null, 'agendar' => null, 'pedido' => null, 'trato' => null, 'pausar_bot' => false];

        // Ejecutar bloques en cadena hasta que uno pida esperar o se acabe.
        $guardas = 0;
        $visitas = [];
        while ($actualId !== null && isset($bloques[$actualId]) && $guardas++ < 50) {
            // Un mismo bloque mas de 3 veces en un turno es un ciclo del flujo: se corta y
            // se espera al cliente, en vez de disparar decenas de mensajes.
            $visitas[$actualId] = ($visitas[$actualId] ?? 0) + 1;
            if ($visitas[$actualId] > 3) {
                return [
                    'respuestas' => $respuestas,
                    'estado' => ['bloque' => $actualId, 'vars' => $vars, 'esperando' => true],
                    'fin' => false,
                    'acciones' => $acciones,
                    'bucle' => $actualId,
                ];
            }
            $bloque = $bloques[$actualId];
            $bloque['_id'] = $actualId;
            $r = $this->ejecutar($bloque, $mensaje, $vars, $telefono);

            foreach ($r['respuestas'] as $txt) $respuestas[] = $txt;
            if (!empty($r['registrar'])) $acciones['registrar'] = $r['registrar'];
            if (!empty($r['agendar']))   $acciones['agendar']   = $r['agendar'];
            if (!empty($r['pedido']))    $acciones['pedido']    = $r['pedido'];
            // Cualquier bloque puede abrir un TRATO en el embudo del CRM al pasar por el
            // (p. ej. "asesor": el cliente pidio que lo contacten = oportunidad real).
            // `pausar_bot`: a partir de aqui atiende una persona; el webhook apaga el bot del chat.
            if (!empty($bloque['pausar_bot'])) $acciones['pausar_bot'] = true;
            if (!empty($bloque['trato']) && is_array($bloque['trato'])) {
                $acciones['trato'] = [
                    'titulo' => $this->interpolar((string) ($bloque['trato']['titulo'] ?? 'Oportunidad de WhatsApp'), $vars),
                    'valor'  => (float) ($bloque['trato']['valor'] ?? 0),
                    'etapa'  => (string) ($bloque['trato']['etapa'] ?? ''),
                    'bloque' => $actualId,
                ];
            }

            if ($r['esperar']) {
                // Un bloque puede pedir que el proximo mensaje se procese en OTRO
                // bloque (p. ej. el asistente devuelve el turno a su router).
                $volverA = $r['ir_a_al_volver'] ?? null;
                return [
                    'respuestas' => $respuestas,
                    'estado' => ['bloque' => ($volverA && isset($bloques[$volverA])) ? $volverA : $actualId, 'vars' => $vars, 'esperando' => true],
                    'fin' => false,
                    'acciones' => $acciones,
                ];
            }
            if (! empty($r['consumir_mensaje'])) $mensaje = '';
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
                // Hasta 3 opciones: botones nativos de WhatsApp (Meta). El id del
                // boton es el numero de la opcion, asi la respuesta la resuelve el
                // mismo codigo que el texto "1", "2", "3". Mas de 3, o un canal
                // que no sabe de botones (Baileys), usan el texto numerado.
                $out['respuestas'][] = $this->botonesOpciones($bloque, $vars);
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
                    $lineas = $prods->map(fn ($p) => "• {$p['nombre']} — " . $this->precioTxt($p['precio'])
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
                // `palabra_completa`: la clave debe encajar como palabra, no dentro
                // de otra. Sin esto "postres" disparaba "pos" y "marketing"
                // mostraba una tienda de abarrotes por contener "market".
                $exacta = ! empty($bloque['palabra_completa']);
                foreach (($bloque['reglas'] ?? []) as $regla) {
                    $palabras = array_map('trim', explode(',', mb_strtolower($regla['contiene'] ?? '')));
                    foreach ($palabras as $pal) {
                        if ($pal === '') continue;
                        if ($exacta) {
                            $raiz = str_ends_with($pal, '*');
                            $nucleo = $raiz ? rtrim($pal, '*') : $pal;
                            $patron = '/(?<![\p{L}\p{N}])' . preg_quote($nucleo, '/')
                                . ($raiz ? '[\p{L}]*' : '') . '(?![\p{L}\p{N}])/u';
                            $hay = (bool) preg_match($patron, $texto);
                        } else {
                            $hay = str_contains($texto, $pal);
                        }
                        if ($hay) { $rama = $regla['siguiente'] ?? null; break 2; }
                    }
                }
                // Ninguna palabra clave acerto. Con `ia_fallback` se le pregunta a
                // la IA a que rama pertenece el mensaje, eligiendo SOLO entre las
                // ramas declaradas. Si la IA falla o duda, se usa la rama por
                // defecto de siempre: nunca deja al cliente sin camino.
                if ($rama === null && ! empty($bloque['ia_fallback']) && trim($mensaje) !== '') {
                    $rama = $this->ramaPorIa($bloque, $mensaje, $vars);
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
                    $lineas = $cat->map(fn ($p) => "▪️ {$p['nombre']} — " . $this->precioTxt($p['precio']))->implode("\n");
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
                    $lineas = $prods->map(fn ($p) => "▪️ {$p['nombre']} — " . $this->precioTxt($p['precio']))->implode("\n");
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
                        'cuerpo' => "*{$p['nombre']}*\n💵 " . $this->precioTxt($p['precio'])
                            . ($p['stock'] !== null && $p['stock'] <= 0 ? "\n⚠️ Sin stock" : ''),
                        'boton' => '¿Qué hago?',
                        'secciones' => [['titulo' => 'Acciones', 'filas' => [
                            ['titulo' => '➕ Agregar al carrito', 'id' => 'add_' . $p['id']],
                            ['titulo' => '🔎 Buscar otro', 'id' => 'buscar'],
                            ['titulo' => '🛒 Ver carrito', 'id' => 'vercarrito'],
                        ]]],
                        'fallback' => "*{$p['nombre']}* — " . $this->precioTxt($p['precio']) . "\nResponde: agregar / buscar / carrito",
                    ];
                    $out['esperar'] = true;
                    $vars['_modo'] = 'producto';
                } else {
                    // Varios: mostrar lista para elegir
                    $filas = $prods->map(fn ($p) => [
                        'titulo' => mb_substr($p['nombre'], 0, 24),
                        'descripcion' => $this->precioTxt($p['precio']),
                        'id' => 'add_' . $p['id'],
                    ])->all();
                    $out['respuestas'][] = [
                        'tipo' => 'lista',
                        'titulo' => 'Resultados',
                        'cuerpo' => "Encontré esto para \"$q\". Toca para agregar:",
                        'boton' => 'Ver productos',
                        'secciones' => [['titulo' => 'Productos', 'filas' => $filas]],
                        'fallback' => "Resultados:\n" . $prods->map(fn ($p, $i) => ($i + 1) . ". {$p['nombre']} — " . $this->precioTxt($p['precio']))->implode("\n"),
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
                if ($esAsesor && empty($vars['_estado_ia']) && ! empty($bloque['presentacion'])) {
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

                // modo 'duda': solo contesta la pregunta con el contexto del bloque (nada de guion).
                $r = ($bloque['modo'] ?? '') === 'duda'
                    ? IA::responderDuda($mensaje, $historial, $ctxNegocio)
                    : ($esAsesor
                        ? IA::asesorComercial($mensaje, $historial, $ctxNegocio,
                            $vars['_estado_ia'] ?? 'INICIO', $vars['_datos_ia'] ?? [],
                            $vars['_lista_pendiente'] ?? null)
                        : IA::asistenteBot($mensaje, $historial, $ctxNegocio,
                            $vars['_estado_ia'] ?? 'MENU', $vars['_datos_ia'] ?? []));

                if (!empty($r['sin_ia'])) {
                    // Sin IA disponible: no dejamos al cliente sin respuesta, y si el
                    // bloque tiene `siguiente` se continua (retomar la pregunta pendiente)
                    // en vez de quedarse esperando una charla que la IA no va a dar.
                    $out['respuestas'][] = $bloque['fallback'] ?? 'En un momento te atiende un asesor. 🙏';
                    if (! empty($bloque['siguiente'])) {
                        $out['siguiente'] = $bloque['siguiente'];
                        $out['consumir_mensaje'] = true;
                        break;
                    }
                } else {
                    // La IA responde en VARIOS globos cortos (como una persona en
                    // WhatsApp). Se envían por separado; la lista va con el último.
                    $globos = !empty($r['mensajes']) ? $r['mensajes'] : [$r['respuesta']];
                    // Tope de globos: mas de 3 seguidos se lee como un sermon, no como
                    // una respuesta. Si la IA devuelve mas, se conserva el ultimo (el
                    // que cierra) y los dos primeros.
                    if (count($globos) > 3) {
                        $globos = array_merge(array_slice($globos, 0, 2), [end($globos)]);
                    }
                    $ultimo = array_pop($globos);
                    foreach ($globos as $g) {
                        if (trim($g) !== '') $out['respuestas'][] = $g;
                    }

                    // Si la IA decidió mostrar opciones, el último globo va como LISTA.
                    $opciones = collect($r['lista']['opciones'] ?? [])
                        ->filter(fn ($op) => is_array($op) && filled($op['titulo'] ?? null))
                        ->take(6)->values();
                    if ($opciones->isNotEmpty()) {
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
                // El asistente espera la respuesta del cliente. Si el bloque declara
                // `siguiente`, el turno SIGUIENTE se procesa alli (normalmente un
                // router): sin esto la conversacion se quedaba atrapada en la IA para
                // siempre y frases como "quiero contratar" nunca llegaban al bloque
                // de asesor, porque las contestaba la IA en vez de enrutarse.
                $out['esperar'] = true;
                $out['ir_a_al_volver'] = $bloque['siguiente'] ?? null;
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

            // ── Bloques del Bot Comercial Informativo ─────────────────────
            // Responden con datos REALES del negocio. La regla comun: si el
            // dato no esta configurado, se dice honestamente y se ofrece el
            // asesor; jamas se inventa.

            case 'info_negocio': {
                $n = $this->ctx->negocio();
                $sinDato = "No tengo esa información registrada 😕. Puedo derivarte con un asesor para que te ayude: escribe *asesor*.";
                $txt = match ($bloque['dato'] ?? 'contacto') {
                    'direccion' => $n['direccion']
                        ? "📍 *Nuestra dirección*\n{$n['direccion']}" . ($n['mapa'] ? "\n\n🗺️ Cómo llegar: {$n['mapa']}" : '')
                        : $sinDato,
                    'horario' => $n['horario']
                        ? "🕐 *Horario de atención*\n{$n['horario']}"
                        : $sinDato,
                    'web' => $n['web']
                        ? "🌐 *Nuestra tienda online*\n{$n['web']}\n\nAhí puedes ver todo el catálogo con fotos y precios."
                        : $sinDato,
                    'empresa' => (function () use ($n, $sinDato) {
                        $l = ["🏢 *{$n['nombre']}*"];
                        if ($n['descripcion'])  $l[] = $n['descripcion'];
                        if ($n['razon_social']) $l[] = "Razón social: {$n['razon_social']}";
                        if ($n['ruc'])          $l[] = "RUC: {$n['ruc']}";
                        if ($n['web'])          $l[] = "🌐 {$n['web']}";
                        foreach ($n['redes'] as $red => $url) $l[] = "{$red}: {$url}";
                        return count($l) > 1 ? implode("\n", $l) : $sinDato;
                    })(),
                    default => (function () use ($n, $sinDato) {
                        $l = ['📞 *Contacto*'];
                        if ($n['telefono']) $l[] = "Teléfono: {$n['telefono']}";
                        if ($n['whatsapp']) $l[] = "WhatsApp: {$n['whatsapp']}";
                        if ($n['email'])    $l[] = "Correo: {$n['email']}";
                        if ($n['horario'])  $l[] = "Horario: {$n['horario']}";
                        return count($l) > 1 ? implode("\n", $l) : $sinDato;
                    })(),
                };
                $out['respuestas'][] = $txt;
                break;
            }

            case 'metodos_pago': {
                $s = $this->project->settings()->pluck('value', 'key');
                $pago = $this->ctx->datosPago();
                $l = [];
                if ($pago['yape_numero']) $l[] = '• Yape: ' . $pago['yape_numero'] . ($pago['yape_nombre'] ? " ({$pago['yape_nombre']})" : '');
                if ($pago['plin_numero']) $l[] = '• Plin: ' . $pago['plin_numero'];
                foreach (['bcp' => 'BCP', 'bbva' => 'BBVA', 'interbank' => 'Interbank', 'scotiabank' => 'Scotiabank', 'nacion' => 'Banco de la Nación'] as $k => $banco) {
                    $cuenta = trim((string) ($s["payment_bank_{$k}"] ?? ''));
                    if ($cuenta !== '') $l[] = "• {$banco}: {$cuenta}";
                }
                $manual = trim((string) ($s['payment_manual_methods'] ?? ''));
                if ($manual !== '') {
                    // El disenador guarda esta lista como JSON (["yape","efectivo"]);
                    // a mano llega como texto separado por comas. Ambos valen.
                    $items = json_decode($manual, true);
                    if (! is_array($items)) $items = preg_split('/[,;\n]+/', $manual);
                    foreach ($items as $m) {
                        $m = trim((string) $m);
                        if ($m !== '') $l[] = '• ' . ucfirst($m);
                    }
                }

                // Los metodos ACEPTADOS de la tienda (los iconos del carrito):
                // es donde la mayoria de negocios los marca, y sin leerlos el
                // bot decia "no tengo metodos" a una empresa con 7 marcados.
                // Se agregan los que aun no salieron con numero propio.
                $etiquetas = ['efectivo' => '💵 Efectivo', 'yape' => '🟣 Yape', 'plin' => '🔵 Plin',
                              'transferencia' => '🏦 Transferencia bancaria', 'tarjeta' => '💳 Tarjeta crédito/débito',
                              'qr' => '📲 Pago con QR', 'contra_entrega' => '🚚 Pago contra entrega'];
                $aceptados = json_decode((string) ($s['accepted_payments'] ?? '[]'), true) ?: [];
                $yaListado = mb_strtolower(implode(' ', $l));
                foreach ($aceptados as $k) {
                    $k = (string) $k;
                    if (isset($etiquetas[$k]) && ! str_contains($yaListado, mb_strtolower(strip_tags(preg_replace('/^\S+ /', '', $etiquetas[$k]))))) {
                        $l[] = '• ' . $etiquetas[$k];
                    }
                }
                if ($l) {
                    $txt = "💳 *Puedes pagar con:*\n" . implode("\n", $l);
                    if ($pago['instrucciones']) $txt .= "\n\nℹ️ " . $pago['instrucciones'];
                    $out['respuestas'][] = $txt;
                    if ($pago['qr_url']) $out['respuestas'][] = ['tipo' => 'imagen', 'url' => $pago['qr_url'], 'caption' => 'QR de pago 📲'];
                } else {
                    $out['respuestas'][] = "Aún no tengo los métodos de pago registrados 😕. Escribe *asesor* y una persona te indica cómo pagar.";
                }
                break;
            }

            case 'promociones': {
                $promo = $this->ctx->promocionesVigentes((int) ($bloque['limite'] ?? 6));
                if (empty($promo['campanas']) && empty($promo['ofertas'])) {
                    $out['respuestas'][] = $bloque['vacio'] ?? "Por ahora no tenemos promociones vigentes. Escríbeme qué producto te interesa y te paso su precio. 🙂";
                    break;
                }
                $l = ['🔥 *Promociones vigentes*'];
                foreach ($promo['campanas'] as $c) {
                    $l[] = "• {$c['nombre']}: {$c['detalle']}" . ($c['hasta'] ? " (hasta {$c['hasta']})" : '');
                }
                foreach ($promo['ofertas'] as $i => $o) {
                    $l[] = ($i + 1) . ". {$o['nombre']}\n   ~S/ " . number_format($o['antes'], 2) . '~ → *S/ ' . number_format($o['ahora'], 2) . '*';
                }
                if (! empty($promo['ofertas'])) {
                    $l[] = "\nResponde con el *número* para ver el detalle de un producto.";
                    $vars['_modo'] = 'consulta_resultados';
                    $vars['_resultados'] = array_column($promo['ofertas'], 'id');
                    $vars['_consulta_bloque'] = $bloque['_id'] ?? null;
                    $out['esperar'] = true;
                }
                $out['respuestas'][] = implode("\n", $l);
                break;
            }

            case 'consultar_producto': {
                // FLUJO SIMPLE (contrato 2026-08-29): el texto se resuelve
                // SOLO contra CATEGORIAS del proyecto.
                //   varias afines -> lista tocable de secciones;
                //   una          -> titulo + link filtrado + PDF. FIN;
                //   ninguna      -> "no encontre una categoria", sin
                //                   alternativas, sin productos, sin paginas.
                // Cada texto nuevo DESCARTA el contexto anterior: "mesa"
                // despues de ver camarotes JAMAS reutiliza camarotes.
                $q = trim($this->interpolar(
                    $bloque['consulta'] ?? ($vars['_consulta_ia'] ?? $mensaje),
                    $vars
                ));
                foreach (['_consulta_ia', '_consulta_forzada', '_categoria_filtro', '_nav',
                          '_resultados', '_cat_id', '_cat_offset', '_categorias', '_consulta_texto'] as $k) {
                    unset($vars[$k]);
                }

                if ($q === '' || $this->esComando($q)) {
                    $out['respuestas'][] = $bloque['texto'] ?? '🔎 Escríbeme el producto o la categoría que buscas.';
                    $out['esperar'] = true;
                    $vars['_modo'] = 'consulta_buscando';
                    break;
                }
                $limpia = $this->limpiarConsultaProducto($q);
                if ($limpia === '') {
                    $out['respuestas'][] = $bloque['texto_pedir'] ?? '🔎 ¿Qué buscas? Escríbeme el nombre del producto o la categoría.';
                    $out['esperar'] = true;
                    $vars['_modo'] = 'consulta_buscando';
                    break;
                }

                $afines = $this->ctx->categoriasAfines($limpia);

                if ($afines->count() > 1) {
                    $lineas = $afines->values()->map(fn ($c, $i) => ($i + 1) . ". {$c->name}")->implode("\n");
                    $out['respuestas'][] = $this->listaRespuesta(
                        "Encontré *{$limpia}* en distintas secciones 👇",
                        $afines->values()->map(fn ($c, $i) => ['titulo' => $c->name, 'descripcion' => '', 'id' => (string) ($i + 1)])->all(),
                        "Encontré *{$limpia}* en distintas secciones:\n\n{$lineas}\n\nResponde con el *número*.",
                        'Elegir sección', 'Secciones'
                    );
                    $out['esperar'] = true;
                    $vars['_modo'] = 'consulta_categorias';
                    $vars['_categorias'] = $afines->pluck('id')->all();
                    $vars['_consulta_bloque'] = $bloque['_id'] ?? null;
                    break;
                }

                if ($afines->count() === 1) {
                    $this->abrirCategoriaSimple($afines->first(), $vars, $out);
                    $vars['_consulta_bloque'] = $bloque['_id'] ?? null;
                    break;
                }

                $out['respuestas'][] = "🔎 No encontré una categoría relacionada con \"{$q}\".\n\nPrueba escribiendo otro producto o categoría, o escribe *asesor* para que te ayude una persona.";
                $out['esperar'] = true;
                $vars['_modo'] = 'consulta_buscando';
                break;
            }

            case 'intencion': {
                // Modo escucha: primero pregunta y espera; el proximo mensaje
                // del cliente se clasifica en resolverRespuesta. Asi el cierre
                // "te ayudo con algo mas?" entiende la siguiente consulta en
                // vez de que el flujo muera y el bot se quede mudo.
                // "Turno de vuelta" solo existe para las preguntas de si/no. Un intencion normal
                // que el flujo vuelve a visitar (p. ej. desde un router) PREGUNTA otra vez: si se
                // tomaba el aviso `_pregunta_` como "ya pregunte", caia en la rama de abajo y el
                // router lo devolvia en bucle (24 "Claro 👇" seguidos a un cliente real).
                $esSiNo = ! empty($bloque['confirmacion']) || ! empty($bloque['negacion']);
                $pregLanzada = $esSiNo && ! empty($vars['_pregunta_' . ($bloque['_id'] ?? '')]);
                if (! empty($bloque['esperar']) && ! $pregLanzada) {
                    if (! empty($bloque['router_inicial']) && trim($mensaje) !== ''
                        && empty($vars['_intencion_inicial_vista'])
                        && empty($vars['_pregunta_' . ($bloque['_id'] ?? '')])) {
                        $vars['_intencion_inicial_vista'] = true;
                        $destino = $this->flow['bloques'][$bloque['router_inicial']] ?? null;
                        if ($destino && ($destino['tipo'] ?? '') === 'condicion') {
                            $destino['_id'] = $bloque['router_inicial'];
                            $r = $this->ejecutar($destino, $mensaje, $vars, $telefono);
                            $sig = $r['siguiente'] ?? null;
                            // Solo si el router encontro algo DISTINTO de su rama
                            // por defecto: si no, la pregunta sigue su curso. Cualquier rama
                            // vale (precio, rubro, asesor...): el primer mensaje ya trae intencion.
                            if ($sig && $sig !== ($destino['si_no'] ?? null)) {
                                // Se va directo a la rama: preguntar "como vendes" y acto seguido
                                // responder el precio se leia como no haber escuchado.
                                $out['siguiente'] = $sig;
                                $out['consumir_mensaje'] = true;
                                break;
                            }
                        }
                    }
                    if (! empty($bloque['texto'])) {
                        foreach ($this->preguntaIntencion($bloque, $vars) as $r) {
                            $out['respuestas'][] = $r;
                        }
                        $vars['_ultima_pregunta_bot'] = mb_substr($this->interpolar($bloque['texto'], $vars), 0, 200);
                    }
                    $out['esperar'] = true;
                    // Queda anotado que la pregunta YA se hizo: el resolutor la
                    // vuelve a hacer si el turno llega sin ese aviso (p. ej. tras
                    // desviarse a una duda) en vez de tomar el mensaje como respuesta.
                    if (! empty($bloque['texto'])) {
                        $vars['_pregunta_' . ($bloque['_id'] ?? '')] = true;
                    }
                    // El bloque hizo una pregunta de si/no: el proximo turno se
                    // resuelve AQUI mismo (ver confirmacion/negacion abajo), no
                    // en el router, donde un "si" no coincidia con nada.
                    if (! empty($bloque['confirmacion']) || ! empty($bloque['negacion'])) {
                        $out['ir_a_al_volver'] = $bloque['_id'] ?? null;
                    }
                    break;
                }
                // Turno de vuelta de una pregunta si/no.
                if (! empty($bloque['confirmacion']) || ! empty($bloque['negacion'])) {
                    unset($vars['_pregunta_' . ($bloque['_id'] ?? '')]);
                    if ($btn = $this->destinoBoton($mensaje)) {
                        $out['siguiente'] = $btn;
                        break;
                    }
                    $resp = $this->siONo($mensaje);
                    if ($resp === true && ! empty($bloque['confirmacion'])) {
                        $out['siguiente'] = $bloque['confirmacion'];
                        break;
                    }
                    if ($resp === false && ! empty($bloque['negacion'])) {
                        $out['siguiente'] = $bloque['negacion'];
                        break;
                    }
                    // Ni si ni no: sigue su curso normal (router).
                    $out['siguiente'] = $bloque['siguiente'] ?? null;
                    break;
                }
                if (! empty($bloque['texto'])) {
                    // La presentacion completa va UNA vez por conversacion;
                    // pedir "menu" despues no reinicia la relacion.
                    if (empty($vars['_saludado'])) {
                        $out['respuestas'][] = $this->interpolar($bloque['texto'], $vars);
                        $vars['_saludado'] = true;
                    } else {
                        $out['respuestas'][] = $bloque['texto_repetido'] ?? 'Claro 👇';
                    }
                }
                $out['siguiente'] = $this->enrutarIntencion($mensaje, $bloque['rutas'] ?? [], $vars, $bloque['siguiente'] ?? null);
                break;
            }

            case 'faq': {
                // Preguntas frecuentes que el dueno ya escribio en la seccion
                // FAQ del constructor. Si no encaja ninguna, se dice y se
                // ofrecen las que si existen: nunca se improvisa una respuesta.
                $q = trim($this->interpolar($bloque['consulta'] ?? $mensaje, $vars));
                // Con typos corregidos: "tienen delibery" debe encontrar la FAQ
                // de delivery igual que la encontraria bien escrito.
                if ($q !== '') $q = $this->normalizarIntencion($q);
                $item = $q !== '' ? $this->ctx->faqQueResponde($q) : null;

                if ($item) {
                    $out['respuestas'][] = "❓ *{$item['pregunta']}*\n{$item['respuesta']}";
                    break;
                }

                $todas = $this->ctx->faq();
                if (empty($todas)) {
                    $out['respuestas'][] = $bloque['vacio']
                        ?? 'No tengo esa información registrada 😕. Escribe *asesor* y una persona te ayuda.';
                    break;
                }

                $lineas = collect($todas)->take(6)
                    ->map(fn ($f, $i) => ($i + 1) . '. ' . $f['pregunta'])->implode("\n");
                // Llegar desde el menu (sin pregunta) no es lo mismo que
                // preguntar algo que no esta: el encabezado lo distingue.
                $encabezado = $q === ''
                    ? '💬 *Preguntas frecuentes*'
                    : 'No tengo esa pregunta registrada, pero sí estas:';
                $out['respuestas'][] = "{$encabezado}\n\n{$lineas}\n\nResponde con el *número* o escribe *asesor*.";
                $out['esperar'] = true;
                $vars['_modo'] = 'faq_lista';
                $vars['_faq'] = collect($todas)->take(6)->all();
                break;
            }

            case 'recomendar': {
                // Recomendacion con datos reales: el tope de precio sale del
                // mensaje (o de lo que dedujo la IA) y los productos SIEMPRE de
                // la base. Nunca se sugiere algo que no este en catalogo.
                $texto = trim($this->interpolar($bloque['consulta'] ?? ($vars['_reco_texto'] ?? $mensaje), $vars));
                $tope  = $vars['_reco_presupuesto'] ?? $this->presupuestoDe($texto);
                unset($vars['_reco_texto'], $vars['_reco_presupuesto']);

                $termino = $this->limpiarConsultaProducto($this->sinPresupuesto($texto));
                $prods = $this->ctx->buscarPorFiltros($termino !== '' ? $termino : null, $tope, 5);

                if ($prods->isEmpty()) {
                    $out['respuestas'][] = $tope
                        ? 'No tengo productos de ese tipo por debajo de S/ ' . number_format($tope, 2) . ' 😕. Puedo mostrarte otras opciones o pasarte con un *asesor*.'
                        : 'No encontré productos que encajen con eso 😕. Cuéntame qué necesitas o escribe *asesor*.';
                    $out['esperar'] = true;
                    $vars['_modo'] = 'consulta_buscando';
                    break;
                }

                $encabezado = $tope
                    ? 'Con hasta *S/ ' . number_format($tope, 2) . '* tengo estas opciones:'
                    : 'Estas son las opciones que tengo:';
                $lineas = $prods->map(fn ($p, $i) => ($i + 1) . ". {$p['nombre']} — " . $this->precioTxt($p['precio']))->implode("\n");
                $out['respuestas'][] = "{$encabezado}\n\n{$lineas}\n\nResponde con el *número* para ver el detalle.";
                $out['esperar'] = true;
                $vars['_modo'] = 'consulta_resultados';
                $vars['_resultados'] = $prods->pluck('id')->all();
                $vars['_consulta_bloque'] = $bloque['_id'] ?? null;
                break;
            }

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
    /**
     * Ficha comercial de un producto, formateada para WhatsApp: corta,
     * escaneable y solo con datos reales. Deja el producto como contexto de
     * la conversacion para poder responder "¿tiene descuento?" despues.
     */
    private function fichaComercial(int $id, array &$vars, array &$out): string
    {
        $f = $this->ctx->fichaProducto($id);
        if (! $f) return 'Ese producto ya no está disponible.';

        $vars['_ultimo_prod'] = $id;

        $l = ["🛍️ *{$f['nombre']}*"];
        if (isset($f['antes'])) {
            $l[] = '💰 Antes: ~S/ ' . number_format($f['antes'], 2) . '~';
            $l[] = '🔥 *Ahora: ' . $this->precioTxt($f['precio']) . '*';
        } else {
            $l[] = ((float) $f['precio']) > 0
                ? '💰 *Precio: S/ ' . number_format($f['precio'], 2) . '*'
                : '💰 *Precio a consultar* — escribe *asesor* y te lo confirmamos.';
        }
        if (isset($f['mayorista'])) {
            $l[] = '📦 Por mayor: S/ ' . number_format($f['mayorista'], 2) . " (desde {$f['mayorista_min']} unid.)";
        }
        if (! empty($f['caracteristicas'])) {
            $l[] = '';
            foreach ($f['caracteristicas'] as $c) $l[] = '• ' . $c;
        }
        if (! empty($f['colores'])) $l[] = '🎨 Colores: ' . implode(', ', $f['colores']);
        if (! empty($f['tallas']))  $l[] = '📏 Tallas: ' . implode(', ', $f['tallas']);
        if (array_key_exists('stock', $f) && $f['stock'] !== null) {
            $l[] = $f['stock'] > 0 ? "✅ Disponible ({$f['stock']} unid.)" : '⚠️ Sin stock por ahora';
        }
        if (! empty($f['url'])) $l[] = "
🔗 " . $f['url'];
        $l[] = "
¿Quieres consultar *otro* producto o hablar con un *asesor*?";

        if (! empty($f['imagen'])) {
            $out['respuestas'][] = ['tipo' => 'imagen', 'url' => $f['imagen'], 'caption' => $f['nombre']];
        }

        return implode("
", $l);
    }

    /**
     * Precio de un producto tal como se le dice al cliente.
     *
     * Un producto publicado sin precio (0) NO cuesta cero: le falta el dato.
     * Anunciarlo como "S/ 0.00" seria dar un precio falso, asi que se dice que
     * hay que consultarlo. La regla vale para listas, fichas y comparaciones.
     */
    private function precioTxt($precio): string
    {
        return ((float) $precio) > 0
            ? 'S/ ' . number_format((float) $precio, 2)
            : 'precio a consultar';
    }

    /**
     * NAVEGACION UNIFICADA de productos: UNA sola respuesta (lista nativa) por
     * interaccion, con estado en $vars['_nav']:
     *   cat_id, query, term (ranking), precio_max, orden, page, ids_all (para
     *   busquedas), ids_pagina, total, titulo.
     * PDF y link de tienda SOLO a pedido (filas 'pdf' y 'web'), jamas
     * automaticos. La seleccion numerica usa numeracion GLOBAL (6 = sexto).
     */
    private function navPagina(array &$vars): array
    {
        $nav = $vars['_nav'];
        $porPag = 5;
        $desde = ($nav['page'] - 1) * $porPag;

        if (! empty($nav['ids_all'])) {
            $ids = $nav['ids_all'];
            if ($nav['precio_max'] !== null || ($nav['orden'] ?? '') === 'precio_asc') {
                $todos = $this->ctx->productosPorIds($ids);
                if ($nav['precio_max'] !== null) $todos = $todos->filter(fn ($p) => $p['precio'] <= $nav['precio_max'])->values();
                if (($nav['orden'] ?? '') === 'precio_asc') $todos = $todos->sortBy('precio')->values();
                $ids = $todos->pluck('id')->all();
            }
            $total = count($ids);
            $items = $this->ctx->productosPorIds(array_slice($ids, $desde, $porPag));
        } else {
            $pag = $this->ctx->paginaDeCategoria((int) $nav['cat_id'], $desde, $porPag,
                $nav['precio_max'], $nav['orden'] ?? 'relevancia', $nav['term'] ?? null);
            $total = $pag['total'];
            $items = $pag['items'];
        }

        $vars['_nav']['total'] = $total;
        $vars['_nav']['ids_pagina'] = $items->pluck('id')->all();
        $vars['_modo'] = 'consulta_resultados';
        $vars['_resultados'] = $items->pluck('id')->all();

        if ($items->isEmpty()) {
            return ['vacia' => true, 'total' => $total];
        }

        $hasta = $desde + $items->count();
        $cuerpo = "🛍️ *{$nav['titulo']}* — resultados " . ($desde + 1) . "–{$hasta} de {$total} 👇";

        $filas = collect($items)->values()->map(fn ($p, $i) => [
            'titulo' => $p['nombre'], 'descripcion' => $this->precioTxt($p['precio']),
            'id' => (string) ($desde + $i + 1),
        ])->all();
        if ($hasta < $total)      $filas[] = ['titulo' => '➡️ Ver siguientes', 'descripcion' => ($total - $hasta) . ' más', 'id' => 'siguientes'];
        if ($nav['page'] > 1)     $filas[] = ['titulo' => '⬅️ Anteriores', 'descripcion' => '', 'id' => 'anteriores'];
        $filas[] = ['titulo' => '🌐 Catálogo completo', 'descripcion' => 'Ver en la web con fotos', 'id' => 'web'];
        if (! empty($nav['cat_id'])) $filas[] = ['titulo' => '📄 Descargar catálogo', 'descripcion' => 'PDF de esta sección', 'id' => 'pdf'];

        $lineas = collect($items)->values()->map(fn ($p, $i) => ($desde + $i + 1) . ". {$p['nombre']} — " . $this->precioTxt($p['precio']))->implode("\n");
        $fallback = "🛍️ *{$nav['titulo']}* — resultados " . ($desde + 1) . "–{$hasta} de {$total}:\n\n{$lineas}\n\nResponde con el *número*"
            . ($hasta < $total ? ', *siguientes* para ver más' : '')
            . ($nav['page'] > 1 ? ', *anteriores*' : '')
            . ', *web* para verlo online' . (! empty($nav['cat_id']) ? ' o *pdf* para el catálogo' : '') . '.';

        return ['lista' => $this->listaRespuesta($cuerpo, $filas, $fallback, 'Ver productos', mb_substr($nav['titulo'], 0, 24))];
    }

    /** Abre la navegacion: define el estado y devuelve la UNICA respuesta. */
    private function navAbrir(array &$vars, array $estado0): array
    {
        $vars['_nav'] = array_merge([
            'cat_id' => null, 'query' => null, 'term' => null, 'precio_max' => null,
            'orden' => 'relevancia', 'page' => 1, 'ids_all' => [], 'titulo' => 'Resultados',
        ], $estado0);

        return $this->navPagina($vars);
    }

    /** Ficha interactiva: la ficha + acciones tocables, en UNA sola burbuja. */
    private function fichaInteractiva(int $id, array &$vars, array &$out): array
    {
        $texto = $this->fichaComercial($id, $vars, $out);
        $filas = [
            ['titulo' => '📸 Ver fotos', 'descripcion' => '', 'id' => 'fotos'],
            ['titulo' => 'ℹ️ Características', 'descripcion' => '', 'id' => 'caracteristicas'],
            ['titulo' => '🛒 Quiero comprar', 'descripcion' => 'Te atiende una persona', 'id' => 'comprar'],
            ['titulo' => '🔄 Ver similares', 'descripcion' => '', 'id' => 'similares'],
        ];
        if (! empty($vars['_nav'])) $filas[] = ['titulo' => '⬅️ Volver', 'descripcion' => 'A los resultados', 'id' => 'volver'];

        return $this->listaRespuesta(
            $texto, $filas,
            $texto . "\n\nOpciones: *fotos* · *características* · *comprar* · *similares*" . (! empty($vars['_nav']) ? " · *volver*" : ''),
            'Opciones', 'Este producto'
        );
    }

    /**
     * Lista NATIVA de WhatsApp para resultados dinamicos: el cliente TOCA en
     * vez de escribir numeros. El rowId de cada fila es su NUMERO, asi el
     * toque llega como "1"/"2" y toda la logica de seleccion existente sirve
     * sin cambios. `fallback` = el texto numerado de siempre (simulador y
     * canales sin listas). Limites de WhatsApp: titulo 24, descripcion 72.
     */
    private function listaRespuesta(string $cuerpo, array $filas, string $fallback, string $boton = 'Ver opciones', string $seccion = 'Opciones'): array
    {
        $corta = fn ($t, $max) => mb_strlen($t) > $max ? mb_substr($t, 0, $max - 1) . '…' : $t;

        return [
            'tipo'   => 'lista',
            'titulo' => '',
            'cuerpo' => $cuerpo,
            'boton'  => $boton,
            'secciones' => [[
                'titulo' => $corta($seccion, 24),
                'filas'  => collect($filas)->take(10)->values()->map(fn ($f, $i) => [
                    'titulo'      => $corta($f['titulo'], 24),
                    'descripcion' => $corta($f['descripcion'] ?? '', 72),
                    'id'          => $f['id'] ?? (string) ($i + 1),
                ])->all(),
            ]],
            'fallback' => $fallback,
        ];
    }

    /** Filas de lista para productos: nombre + precio, rowId = numero. */
    private function filasDeProductos($items, int $desde = 0): array
    {
        return collect($items)->values()->map(fn ($p, $i) => [
            'titulo'      => $p['nombre'],
            'descripcion' => $this->precioTxt($p['precio']),
            'id'          => (string) ($desde + $i + 1),
        ])->all();
    }

    /**
     * Abrir una categoria (contrato simple): titulo + link filtrado + PDF.
     * Nada mas: ni productos, ni paginas, ni alternativas.
     */
    private function abrirCategoriaSimple(\App\Modules\Catalogo\Models\Category $cat, array &$vars, array &$out): void
    {
        $out['respuestas'][] = "🛍️ *{$cat->name}*\n\n🌐 Mira todos los productos con fotos:\n" . $this->urlCategoria($cat);
        $out['respuestas'][] = $this->burbujaPdfCategoria($cat);
        $out['esperar'] = true;
        $vars['_modo'] = 'consulta_buscando';
    }

    /**
     * Link directo a UNA categoria de la tienda (con dominio propio si existe).
     */
    private function urlCategoria(\App\Modules\Catalogo\Models\Category $cat): string
    {
        $custom = trim((string) $this->project->custom_domain);

        return $custom !== ''
            ? 'https://' . $custom . '/tienda/' . $cat->slug
            : \App\Modules\Tienda\Support\StorefrontNavigation::categoryUrl($this->project, $cat);
    }

    /** Respuesta de ARCHIVO con el catalogo PDF de la categoria (firmado 48 h). */
    private function burbujaPdfCategoria(\App\Modules\Catalogo\Models\Category $cat): array
    {
        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'publico.catalogo.pdf', now()->addHours(48),
            ['slug' => $this->project->slug, 'categoria' => $cat->slug]
        );

        return [
            'tipo'     => 'archivo',
            'url'      => $url,
            'nombre'   => 'catalogo-' . $cat->slug . '.pdf',
            'fallback' => "📄 Catálogo PDF de *{$cat->name}*: {$url}",
        ];
    }

    /**
     * Pie de listado con el link de la tienda: el cliente puede ver los
     * productos con fotos en la web. Solo si el negocio tiene web configurada.
     */
    private function pieWeb(): string
    {
        $web = trim((string) ($this->ctx->negocio()['web'] ?? ''));

        return $web !== '' ? "\n\n🌐 Míralos con fotos: {$web}" : '';
    }

    /**
     * Tope de precio que menciona un mensaje, o null.
     *
     * Reconoce "hasta 2500", "menos de 2500", "maximo 2500", "tengo 2500",
     * "presupuesto de 2500" y cifras con separadores ("2,500" / "2.500").
     * Sin IA: una cifra en soles es un dato duro, no hace falta un modelo.
     */
    private function presupuestoDe(string $texto): ?float
    {
        $t = mb_strtolower($texto);
        $patron = '/(?:hasta|menos de|maximo|máximo|max|tope|presupuesto(?: de)?|tengo|con|por|de)\s*'
                . '(?:s\/\.?|soles)?\s*([0-9][0-9.,]*)/u';
        if (! preg_match($patron, $t, $m)) return null;

        $numero = (float) str_replace(',', '', $m[1]);
        // "2.500" es dos mil quinientos; "2.5" no es un presupuesto realista.
        if (str_contains($m[1], '.') && ! str_contains($m[1], ',')) {
            $partes = explode('.', $m[1]);
            if (strlen(end($partes)) === 3) $numero = (float) str_replace('.', '', $m[1]);
        }

        return $numero > 0 ? $numero : null;
    }

    /** El mismo texto sin la parte del presupuesto (para buscar el producto). */
    private function sinPresupuesto(string $texto): string
    {
        return trim((string) preg_replace(
            '/(?:hasta|menos de|maximo|máximo|max|tope|presupuesto(?: de)?|tengo|con|por|de)\s*(?:s\/\.?|soles)?\s*[0-9][0-9.,]*\s*(?:soles)?/ui',
            ' ',
            $texto
        ));
    }

    /**
     * Comparacion de dos productos con datos REALES de sus fichas.
     *
     * No la redacta ningun modelo: se ponen lado a lado los campos que existen
     * (precio, oferta, stock, tallas, colores) y se dice cual sale mas barato.
     */
    private function compararProductos(int $idA, int $idB): string
    {
        $a = $this->ctx->fichaProducto($idA);
        $b = $this->ctx->fichaProducto($idB);
        if (! $a || ! $b) return 'No tengo esos dos productos en contexto 🤔. Vuelve a buscarlos y te los comparo.';

        $linea = function (array $f): string {
            $l = ["• *{$f['nombre']}*", '   ' . $this->precioTxt($f['precio'])
                . (isset($f['antes']) ? ' (antes S/ ' . number_format($f['antes'], 2) . ')' : '')];
            if (array_key_exists('stock', $f) && $f['stock'] !== null) {
                $l[] = '   ' . ($f['stock'] > 0 ? "Disponible ({$f['stock']} unid.)" : 'Sin stock');
            }
            if (! empty($f['colores'])) $l[] = '   Colores: ' . implode(', ', $f['colores']);
            if (! empty($f['tallas']))  $l[] = '   Tallas: ' . implode(', ', $f['tallas']);

            return implode("\n", $l);
        };

        // Sin los dos precios no hay diferencia que calcular: se dice, no se
        // inventa un ahorro contra un producto que vale "cero".
        if ((float) $a['precio'] <= 0 || (float) $b['precio'] <= 0) {
            $cierre = "\n💰 A uno de los dos le falta el precio; escribe *asesor* y te lo confirmamos.";
        } else {
            $dif = abs($a['precio'] - $b['precio']);
            $barato = $a['precio'] <= $b['precio'] ? $a : $b;
            $cierre = $dif > 0
                ? "\n💰 *{$barato['nombre']}* cuesta S/ " . number_format($dif, 2) . ' menos.'
                : "\n💰 Los dos cuestan lo mismo.";
        }

        return "🔎 *Comparación*\n\n" . $linea($a) . "\n\n" . $linea($b) . $cierre
            . "\n\n¿Quieres el detalle de alguno? Responde con su *número*.";
    }

    /**
     * Decide a que rama va un texto libre. ORDEN ELEGIDO (y por que):
     *
     *   1. Reglas deterministas (diccionario). Gratis e inmediatas: "yape",
     *      "direccion", "precio del taladro" no deben gastar IA.
     *   2. Solo si las reglas NO tienen certeza (mensaje largo en lenguaje
     *      natural que no encaja en ningun diccionario) y el proyecto tiene la
     *      IA licenciada Y encendida, se llama al interprete.
     *   3. Si el interprete falla por CUALQUIER motivo, el mensaje original
     *      sigue por el camino estandar, transparente para el cliente.
     *
     * Se descarto poner la IA delante del clasificador (el diseno inicial):
     * habria cobrado una llamada por CADA mensaje, incluidos "1", "menu" o
     * "yape", que las reglas resuelven igual de bien. La IA aporta donde las
     * reglas no llegan; ahi es donde se paga.
     */
    private function enrutarIntencion(string $mensaje, array $rutas, array &$vars, ?string $porDefecto): ?string
    {
        $intent = $this->clasificarIntencion($mensaje);

        if (! $this->intencionEsConfiable($mensaje, $intent)
            && \App\Modules\Bots\Ia\InterpreteComercial::habilitado($this->project)) {
            $c = \App\Modules\Bots\Ia\InterpreteComercial::interpretar($this->project, $mensaje, [
                'intent_previo' => $vars['_intent_previo'] ?? null,
            ]);
            if ($c && $c['ruta'] !== null) {
                $intent = $c['ruta'];
                // La consulta normalizada por la IA ("samung"->"samsung") la
                // usa UNA vez el buscador; el dato real sigue saliendo de la BD.
                if ($c['consulta'] !== '' && in_array($intent, ['producto'], true)) {
                    $vars['_consulta_ia'] = $c['consulta'];
                }
            }
        }

        $vars['_intent_previo'] = $intent;

        $destino = $rutas[$intent] ?? null;
        if ($destino === null && $intent !== 'producto') {
            $destino = $rutas['fallback'] ?? $porDefecto;
        }
        if ($destino === null) $destino = $rutas['producto'] ?? $porDefecto;

        return $destino;
    }

    /**
     * ¿Las reglas clasificaron con certeza? Si el diccionario acerto un intent
     * concreto, si. Si cayo en "producto", solo es fiable cuando el mensaje
     * suena a consulta de producto (precio/tienen/busco...) o es tan corto que
     * no hay nada que interpretar ("2", "ok"): gastar IA ahi es tirar dinero.
     */
    private function intencionEsConfiable(string $mensaje, string $intent): bool
    {
        if ($intent !== 'producto') {
            return true;
        }

        $m = ' ' . $this->normalizarIntencion($mensaje) . ' ';
        foreach (['precio', 'cuanto', 'cuesta', 'vale', 'tienen', 'tienes', 'venden', 'vendes',
                  'busco', 'hay ', 'stock', 'disponible', 'sku', 'modelo', 'quiero ver'] as $p) {
            if (str_contains($m, " $p")) return true;
        }

        // Mensajes cortos o de una sola palabra: la busqueda tolerante los
        // resuelve igual o mejor que una llamada de IA.
        $palabras = count(array_filter(explode(' ', trim($m))));

        return $palabras < 4 || mb_strlen(trim($m)) < 15;
    }

    /**
     * Clasificador de intencion por reglas: palabras clave en texto
     * normalizado. Sin IA: para estas frases un diccionario acierta y no
     * alucina. Lo que no encaja en nada cae a "producto" (buscar catalogo).
     */
    private function clasificarIntencion(string $mensaje): string
    {
        $m = ' ' . $this->normalizarIntencion($mensaje) . ' ';
        $tiene = function (array $palabras) use ($m): bool {
            foreach ($palabras as $p) if (str_contains($m, " $p")) return true;
            return false;
        };

        // Saludo puro o comando de menu: no hay intencion comercial que
        // clasificar; la ruta 'fallback' decide (en la bienvenida, el menu).
        if ($this->esSaludo($mensaje)) return 'fallback';
        $soloComando = trim($this->normalizarIntencion($mensaje));
        if (in_array($soloComando, ['menu', 'inicio', 'volver', 'cancelar', 'empezar', 'comenzar'], true)) return 'fallback';

        // Un numero suelto no es una consulta: sin un menu esperando, buscarlo
        // como producto da "No encontre 3" (fallo real). La ruta fallback
        // decide: en la bienvenida vuelve a ensenar el menu.
        if (preg_match('/^\d{1,4}$/', trim($mensaje))) return 'fallback';

        // "como comprar" pide la GUIA; se evalua antes que asesor porque
        // "comprar" tambien es palabra de derivacion humana.
        if ($tiene(['como compro', 'como comprar', 'como se compra', 'como pido', 'como hago un pedido', 'pasos'])) return 'guia';

        // El orden importa: derivar a persona gana a todo lo demas.
        if ($tiene(['asesor', 'persona', 'humano', 'alguien', 'reclamo', 'queja', 'soporte', 'ayuda', 'credito',
                    // Cliente molesto o desatendido: SIEMPRE a una persona.
                    'nadie responde', 'nadie me responde', 'nadie contesta', 'no sirve', 'no funciona',
                    'pesimo', 'molesto', 'ya te pregunte', 'no me ayudan',
                    'comprar', 'compra', 'pedido', 'pedir', 'cotizacion', 'cotizar', 'unidades', 'docena',
                    'mejorar el precio', 'descuento por cantidad', 'al por mayor'])) return 'asesor';
        if ($tiene(['pagar', 'pago', 'pagos', 'yape', 'plin', 'tarjeta', 'transferencia', 'efectivo', 'deposito'])) return 'pagos';
        if ($tiene(['direccion', 'ubicacion', 'ubicados', 'donde estan', 'donde queda', 'donde quedan', 'donde', 'como llego', 'local', 'tienda fisica'])) return 'direccion';
        if ($tiene(['horario', 'hora atienden', 'que hora', 'abren', 'cierran', 'atienden', 'domingo', 'feriado'])) return 'horario';
        if ($tiene(['promocion', 'promociones', 'oferta', 'ofertas', 'rebaja', 'descuentos', 'descuento'])) return 'promociones';
        if ($tiene(['pagina', 'web', 'link', 'enlace', 'catalogo online', 'tienda virtual', 'url'])) return 'web';
        if ($tiene(['telefono', 'celular', 'correo', 'email', 'contacto', 'numero'])) return 'contacto';
        if ($tiene(['ruc', 'razon social', 'empresa', 'quienes son'])) return 'empresa';
        // Comparar va antes que recomendar: "cual es mejor entre el 1 y el 2"
        // lleva ambas senales y lo que pide es la comparacion.
        if ($tiene(['diferencia', 'comparar', 'comparacion', 'versus', ' vs ', 'cual es mejor', 'cual conviene']))
            return 'comparacion';
        if ($tiene(['recomienda', 'recomiendas', 'recomendacion', 'sugieres', 'que me conviene',
                    'presupuesto', 'hasta', 'menos de', 'maximo', 'economico', 'barato', 'mas barato',
                    'soles', 'tienen por', 'que hay por']))
            return 'recomendacion';
        if ($tiene(['garantia', 'devolucion', 'devoluciones', 'cambio', 'factura', 'boleta', 'delivery',
                    'envio', 'envios', 'demora', 'demoran', 'reparto', 'preguntas frecuentes', 'preguntas', 'faq',
                    // Preguntas de servicio ("¿a que zonas llegan?", "¿instalan?"):
                    // en verbo/plural para no confundirlas con nombres de producto.
                    'zonas', 'cobertura', 'instalacion', 'instalan', 'entregan', 'armado', 'armados']))
            return 'faq';

        return 'producto';
    }

    /**
     * Typos de chat -> palabra canonica. Es la tolerancia del BOT ESTANDAR,
     * sin IA: un mapa corto de errores reales de WhatsApp peruano. La IA
     * (cuando este licenciada) cubre lo que no este aqui.
     */
    private const TYPOS = [
        'presio' => 'precio', 'presios' => 'precios', 'prescio' => 'precio',
        'delibery' => 'delivery', 'dilivery' => 'delivery', 'delivri' => 'delivery',
        'aseptan' => 'aceptan', 'asetan' => 'aceptan',
        'kedan' => 'quedan', 'keda' => 'queda', 'onde' => 'donde',
        'orario' => 'horario', 'orarios' => 'horarios',
        'ubicasion' => 'ubicacion', 'direcsion' => 'direccion',
        'catalago' => 'catalogo', 'produtos' => 'productos', 'grasias' => 'gracias',
        'sta' => 'esta', 'q' => 'que', 'k' => 'que', 'xq' => 'porque', 'pq' => 'porque',
        'frecuetnteas' => 'frecuentes', 'frecuentas' => 'frecuentes',
    ];

    private function normalizarIntencion(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
        // "Holaaa"/"siii": 3+ letras repetidas se colapsan a una (ningun
        // producto real se escribe con triple letra).
        $s = preg_replace('/([a-z])\1{2,}/', '$1', $s);
        $s = trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9 ]/', ' ', $s)));

        return implode(' ', array_map(
            fn ($w) => self::TYPOS[$w] ?? $w,
            explode(' ', $s)
        ));
    }

    /**
     * Quita la paja de una consulta de producto ("¿cuanto cuesta el ...?")
     * para que a la busqueda tolerante le llegue solo el producto.
     */
    private function limpiarConsultaProducto(string $q): string
    {
        $n = $this->normalizarIntencion($q);
        // Relleno conversacional: si no se quita, palabras como "que" o "algun"
        // cuentan como termino de busqueda y ningun producto las cumple, asi que
        // todo resultado quedaria marcado como simple alternativa.
        $paja = ['cuanto cuesta', 'cuanto esta', 'cuanto vale', 'que precio tiene', 'precio de', 'precio del',
                 'precio', 'costo', 'cuesta', 'tienes', 'tienen', 'venden', 'vendes', 'hay', 'quiero ver',
                 'quiero', 'busco', 'necesito', 'tengo', 'mas', 'ver mas', 'me pasas', 'informacion de', 'info de',
                 'informacion', 'info', 'detalle', 'detalles', 'consulta', 'consultar', 'saber',
                 // Saludo pegado a la consulta: "hola tienen cocina a gas"
                 // pregunta por la cocina, no por un producto llamado "hola".
                 'hola', 'buenas tardes', 'buenos dias', 'buenas noches', 'buenas', 'buen dia',
                 'por favor', 'porfa', 'disculpa', 'amigo', 'oye', 'mira', 'ustedes', 'uds',
                 'que', 'me', 'algun', 'alguna', 'algo', 'de', 'del', 'el', 'la', 'los', 'las', 'un', 'una',
                 // Palabras-meta: nombran el atributo, no su valor. "overol
                 // talla 4" busca el overol de talla 4, no un producto llamado
                 // "talla".
                 'talla', 'tallas', 'color', 'colores', 'modelo', 'marca',
                 'producto', 'productos', 'este', 'esto', 'bro', 'amigo', 'amiga', 'porfavor', 'consultar',
                 'precios', 'quisiera', 'conocer', 'saber', 'deseo', 'desearia', 'podria', 'indicarme', 'realizan',
                 // Verbos de la peticion de recomendacion: "que laptop me
                 // recomiendas" busca laptops, no un producto "recomiendas".
                 'me recomiendas', 'recomiendame', 'recomiendas', 'recomienda',
                 'sugieres', 'sugiereme', 'sugiere', 'conviene', 'mejor opcion'];
        foreach ($paja as $frase) {
            $n = preg_replace('/\b' . preg_quote($frase, '/') . '\b/', ' ', $n);
        }
        $n = trim(preg_replace('/\s+/', ' ', $n));

        // Si no sobrevivió nada, el mensaje ERA puro relleno ("precio",
        // "info"): no hay producto que buscar y toca preguntar cuál quiere,
        // no enseñarle al cliente resultados de la palabra "precio".
        return $n;
    }

    /**
     * Respuesta social, o null si el mensaje es una consulta de verdad.
     *
     * Tres casos que NUNCA deben terminar en el buscador del catálogo:
     * agradecimientos/asentimientos ("gracias", "ok", "vale"), mensajes vacíos
     * y mensajes sin una sola letra o número (solo emojis o signos).
     */
    /**
     * Lee un si/no de la respuesta del cliente. Devuelve true, false o null
     * cuando no es ni lo uno ni lo otro. La negacion se comprueba primero:
     * "no, gracias" empieza por "no" y jamas debe leerse como aceptacion.
     */
    /**
     * Elige rama con IA cuando las palabras clave no aciertan.
     *
     * Contrato estrecho a proposito: la IA devuelve UNA etiqueta de la lista
     * que se le da (o "ninguna"). No redacta, no inventa destinos y no habla
     * con el cliente. Si el proveedor falla, tarda o responde algo fuera de la
     * lista, se devuelve null y el flujo sigue por su rama por defecto.
     */
    private function ramaPorIa(array $bloque, string $mensaje, array &$vars): ?string
    {
        $opciones = [];
        foreach (($bloque['reglas'] ?? []) as $regla) {
            $destino = $regla['siguiente'] ?? null;
            $etiqueta = trim((string) ($regla['ia_desc'] ?? ''));
            if ($destino && $etiqueta !== '') {
                $opciones[$destino] = $etiqueta;
            }
        }
        if (count($opciones) < 2) {
            return null;
        }

        $lista = [];
        foreach ($opciones as $destino => $etiqueta) {
            $lista[] = "- {$destino}: {$etiqueta}";
        }

        $sistema = 'Clasificas el mensaje de un cliente que escribe por WhatsApp a una empresa '
            . 'peruana que vende tiendas virtuales. Responde SOLO con una etiqueta exacta de la '
            . "lista, sin comillas ni explicacion. Si ninguna encaja con claridad, responde: ninguna\n\n"
            . "CRITERIOS:\n"
            . "- El mensaje puede traer VARIAS frases juntas (el cliente escribio seguido). Clasifica por lo que de verdad PIDE, que suele estar en la ultima frase concreta.\n"
            . "- Pasar con un asesor es un paso FUERTE: eligelo solo si decide comprar, contratar, pagar o pide hablar con una persona. Quiero una, me interesa o quiero informacion NO son decisiones de compra: ahi el cliente todavia esta preguntando.\n"
            . "- Si el cliente pregunta por algo (como funciona, redes sociales, plazos, que incluye), es una duda, aunque diga quiero.\n"
            . "- Ante la duda entre informar y derivar, elige informar.\n\n"
            . "Opciones:\n" . implode("\n", $lista);

        $historial = trim((string) ($vars['_ultima_pregunta_bot'] ?? ''));
        $usuario = ($historial !== '' ? "El bot acaba de preguntar: {$historial}\n" : '')
            . 'Mensaje del cliente: ' . mb_substr($mensaje, 0, 300);

        try {
            $crudo = \App\Modules\Bots\Ia\IA::provider()->chat(
                [
                    ['role' => 'system', 'content' => $sistema],
                    ['role' => 'user', 'content' => $usuario],
                ],
                ['temperature' => 0, 'max_tokens' => 12, 'timeout' => 8]
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('router_ia', ['error' => $e->getMessage()]);
            return null;
        }

        $elegido = mb_strtolower(trim((string) $crudo));
        $elegido = trim(preg_replace('/[^a-z0-9_]/', '', $elegido));

        return isset($opciones[$elegido]) ? $elegido : null;
    }

    private function siONo(string $mensaje): ?bool
    {
        $m = mb_strtolower(trim($mensaje));
        $m = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $m);
        $m = trim(preg_replace('/\s+/', ' ', $m));
        if ($m === '') return null;

        $no = ['no', 'no gracias', 'nel', 'todavia no', 'todavía no', 'aun no', 'aún no',
               'ahora no', 'por ahora no', 'mas adelante', 'más adelante', 'despues', 'después',
               'lo pensare', 'lo pensaré', 'negativo', 'no por ahora', 'no todavia', 'no todavía'];
        foreach ($no as $n) {
            if ($m === $n || str_starts_with($m, $n . ' ')) return false;
        }

        $si = ['si', 'sí', 'sip', 'claro', 'claro que si', 'claro que sí', 'dale', 'ok', 'okey',
               'okay', 'listo', 'perfecto', 'porfa', 'por favor', 'ya', 'de una', 'bueno',
               'me interesa', 'quiero', 'adelante', 'hagamoslo', 'hagámoslo', 'correcto', 'exacto',
               'si porfavor', 'si por favor', 'afirmativo', 'asi es', 'así es'];
        foreach ($si as $y) {
            if ($m === $y || str_starts_with($m, $y . ' ')) return true;
        }
        return null;
    }

    private function respuestaSocial(string $mensaje, array $vars = []): ?string
    {
        $m = mb_strtolower(trim($mensaje));

        if ($m === '' || ! preg_match('/[\p{L}\p{N}]/u', $m)) {
            // El fallback conoce el estado: si el bot acaba de pedir un
            // producto, un "?" significa "no se que escribir", no "no entiendo
            // al bot".
            if (($vars['_modo'] ?? '') === 'consulta_buscando') {
                return '🔎 Escríbeme el nombre del producto o la categoría que buscas (puede ser aproximado).';
            }
            if (($vars['_modo'] ?? '') === 'consulta_resultados') {
                return 'Responde con el *número* de la lista para ver el detalle, o escríbeme otro producto 🙂.';
            }

            return 'No te entendí 🙂. Cuéntame qué producto buscas o escribe *menú* para ver las opciones.';
        }

        $n = $this->normalizarIntencion($m);
        $cortesia = ['gracias', 'muchas gracias', 'mil gracias', 'ok gracias', 'ya gracias', 'gracias por la info',
                     'ok', 'okey', 'oka', 'vale', 'ya', 'listo', 'dale', 'claro', 'bueno', 'perfecto',
                     'genial', 'excelente', 'buenisimo', 'de nada', 'entendido', 'entiendo', 'ah ya', 'ya veo'];
        if (in_array($n, $cortesia, true)) {
            return '¡Con gusto! 🙂 Si necesitas algo más, escríbeme o pon *menú* para ver las opciones.';
        }

        return null;
    }

    /** ¿El mensaje es SOLO un saludo? ("hola", "buenas tardes", "hola que tal como estan") */
    private function esSaludo(string $m): bool
    {
        $palabras = preg_split('/\s+/', $this->normalizarIntencion($m), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($palabras) || count($palabras) > 6) return false;

        $saludo = ['hola', 'hola!', 'holaa', 'holaaa', 'holi', 'ola', 'alo', 'hey', 'hello', 'hi',
                   'buenas', 'buenos', 'buen', 'dia', 'dias', 'tarde', 'tardes', 'noche', 'noches',
                   'que', 'tal', 'como', 'estas', 'estan', 'esta', 'saludos', 'amigo', 'amiga',
                   'señor', 'senor', 'señorita', 'senorita', 'estimados', 'disculpe', 'disculpa'];
        // Basta una palabra fuera del vocabulario de saludo ("hola tienen
        // cocinas") para que el mensaje sea una consulta, no un saludo.
        foreach ($palabras as $w) {
            if (! in_array($w, $saludo, true)) return false;
        }
        // Y al menos una tiene que ser un saludo de verdad ("que tal" solo no).
        return (bool) array_intersect($palabras, ['hola', 'holaa', 'holaaa', 'holi', 'ola', 'alo', 'hey',
                                                  'hello', 'hi', 'buenas', 'buenos', 'saludos']);
    }

    /** El ultimo comandoGlobal fue una OPCION de menu: el mensaje ya cumplio. */
    private bool $saltoConsumeMensaje = false;

    private function comandoGlobal(string $mensaje, array $bloques, array $estado): ?string
    {
        $m = mb_strtolower(trim($mensaje));
        if ($m === '') return null;

        // ¿Hay una NAVEGACION de productos activa? Dentro de ella, "volver" y
        // los textos que coinciden con filas del menu ("comprar" ~ "Como
        // comprar") pertenecen al CONTEXTO, no al menu global.
        $enNavegacion = ! empty($estado['esperando'])
            && str_starts_with((string) ($estado['vars']['_modo'] ?? ''), 'consulta');

        // "menú"/"inicio"/"volver"/"cancelar" → regresar al bloque inicial.
        if (in_array($m, ['menu', 'menú', 'inicio', 'volver', 'cancelar'], true)) {
            if ($m === 'volver' && $enNavegacion) return null;   // lo resuelve la navegacion
            return $this->flow['inicio'] ?? null;
        }

        // Un saludo es un saludo aunque llegue a mitad del flujo: se saluda de
        // vuelta (reinicio), no se busca "hola que tal" en el catálogo.
        if ($this->esSaludo($m)) {
            return $this->flow['inicio'] ?? null;
        }

        // Buscar en menús (opciones) y listas (filas) una opción cuyo texto/id coincida.
        // NO durante una navegacion activa: ahi "comprar"/"buscar" son acciones
        // del contexto, no atajos del menu.
        if ($enNavegacion) return null;
        foreach ($bloques as $id => $b) {
            $tipo = $b['tipo'] ?? '';
            if ($tipo === 'opciones') {
                foreach (($b['opciones'] ?? []) as $op) {
                    if ($this->coincideComando($m, $op['texto'] ?? '') && !empty($op['siguiente'])) {
                        $this->saltoConsumeMensaje = true;
                        return $op['siguiente'];
                    }
                }
            }
            if ($tipo === 'lista') {
                foreach (($b['secciones'] ?? []) as $sec) {
                    foreach (($sec['filas'] ?? []) as $f) {
                        $etq = ($f['titulo'] ?? '') . ' ' . ($f['id'] ?? '');
                        if ($this->coincideComando($m, $etq) && !empty($f['siguiente'])) {
                            $this->saltoConsumeMensaje = true;
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
        // Un comando es el mensaje entero ("menu", "zonas de entrega") o parte
        // de una orden corta ("ver promos porfa"). En una frase larga la
        // palabra ya no manda: "a que zonas llegan" pregunta por la cobertura
        // (una FAQ), no invoca el comando "zonas" (fallo visto en prueba real).
        $corta = str_word_count($t) <= 3;
        foreach (self::COMANDOS as $c) {
            if ($t === $c || ($corta && str_contains($t, $c))) return true;
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
            $prods = $this->ctx->paginaDeCategoria((int) $mm[1], 10);
            if ($prods->isNotEmpty()) {
                $filas = $prods->map(fn ($p) => [
                    'titulo' => mb_substr($p['nombre'], 0, 24),
                    'descripcion' => $this->precioTxt($p['precio']),
                    'id' => 'add_' . $p['id'],
                ])->all();
                $respuestas[] = [
                    'tipo' => 'lista', 'titulo' => 'Productos', 'cuerpo' => 'Toca para agregar:',
                    'boton' => 'Ver', 'secciones' => [['titulo' => 'Productos', 'filas' => $filas]],
                    'fallback' => $prods->map(fn ($p, $i) => ($i + 1) . ". {$p['nombre']} — " . $this->precioTxt($p['precio']))->implode("\n"),
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
                    'descripcion' => $this->precioTxt($p['precio']),
                    'id' => 'add_' . $p['id'],
                ])->all();
                $respuestas[] = [
                    'tipo' => 'lista', 'titulo' => 'Resultados', 'cuerpo' => "Para \"$m\":",
                    'boton' => 'Ver', 'secciones' => [['titulo' => 'Productos', 'filas' => $filas]],
                    'fallback' => $prods->map(fn ($p, $i) => ($i + 1) . ". {$p['nombre']} — " . $this->precioTxt($p['precio']))->implode("\n"),
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

        // INTENCION en modo escucha: clasificar el mensaje y saltar a su rama.
        if ($tipo === 'intencion') {
            $idBloque = $bloque['_id'] ?? '';
            if ($btn = $this->destinoBoton($mensaje)) {
                unset($vars['_pregunta_' . $idBloque]);
                $vars['_msj_consumido'] = true;
                return $btn;
            }
            // Llego el turno pero la pregunta nunca se hizo (se volvio aqui tras una
            // duda o un desvio): primero se pregunta; el mensaje NO es la respuesta.
            if (! empty($bloque['esperar']) && ! empty($bloque['texto']) && empty($vars['_pregunta_' . $idBloque])
                && empty($bloque['confirmacion']) && empty($bloque['negacion'])) {
                return $idBloque;
            }
            unset($vars['_pregunta_' . $idBloque]);
            return $this->enrutarIntencion($mensaje, $bloque['rutas'] ?? [], $vars, $bloque['siguiente'] ?? null);
        }

        // ── Respuestas de la CONSULTA INFORMATIVA de producto ──
        // FAQ: el cliente eligio una de las preguntas ofrecidas.
        if (($vars['_modo'] ?? '') === 'faq_lista' && $tipo === 'faq') {
            $m = mb_strtolower(trim($mensaje));
            $lista = $vars['_faq'] ?? [];
            if (preg_match('/^\d{1,2}$/', $m) && isset($lista[(int) $m - 1])) {
                $item = $lista[(int) $m - 1];
                $respuestas[] = "❓ *{$item['pregunta']}*\n{$item['respuesta']}";
                unset($vars['_modo'], $vars['_faq']);

                return $bloque['siguiente'] ?? null;
            }
            unset($vars['_modo'], $vars['_faq']);

            return $bloque['libre_siguiente'] ?? $bloque['siguiente'] ?? null;
        }

        if (in_array($vars['_modo'] ?? '', ['consulta_buscando', 'consulta_resultados', 'consulta_ficha', 'consulta_categorias'], true)
            && in_array($tipo, ['consultar_producto', 'promociones', 'recomendar'], true)) {
            $m = mb_strtolower(trim($mensaje));
            $modo = $vars['_modo'];
            $volver = $vars['_consulta_bloque'] ?? ($bloque['_id'] ?? null);

            // Salidas comunes en cualquier modo de consulta.
            if (in_array($m, ['asesor', 'persona', 'humano'], true)) {
                unset($vars['_modo']);
                return $bloque['asesor_siguiente'] ?? $bloque['siguiente'] ?? null;
            }
            if (in_array($m, ['otro', 'buscar', 'otra', 'si', 'sí'], true)) {
                $vars['_modo'] = 'consulta_buscando';
                $respuestas[] = '🔎 Dime el nombre del producto que quieres consultar.';
                return '__ESPERAR__';
            }

            // Pidio comparar mientras se le pregunta la SECCION: los numeros de
            // esa lista son categorias, no productos. Se le encamina en vez de
            // buscar la frase entera como si fuera un producto.
            if ($modo === 'consulta_categorias'
                && preg_match('/diferencia|comparar|compara|versus|\bvs\b|cual es mejor|conviene/u', $m)) {
                $respuestas[] = 'Para compararlos primero elige la sección 🙂. Responde con un número del 1 al '
                    . count($vars['_categorias'] ?? []) . ' y luego me dices cuáles dos comparo.';

                return '__ESPERAR__';
            }

            // Eligio una SECCION: titulo + link filtrado + PDF. FIN.
            if ($modo === 'consulta_categorias' && preg_match('/^\d{1,2}$/', $m)) {
                $cats = $vars['_categorias'] ?? [];
                $catId = $cats[(int) $m - 1] ?? null;
                if (! $catId) {
                    $respuestas[] = 'Ese número no está en la lista 🤔. Responde con un número del 1 al ' . count($cats) . '.';
                    return '__ESPERAR__';
                }
                $cat = \App\Modules\Catalogo\Models\Category::where('project_id', $this->project->id)->find((int) $catId);
                unset($vars['_modo'], $vars['_categorias']);
                if ($cat) {
                    $out2 = ['respuestas' => []];
                    $this->abrirCategoriaSimple($cat, $vars, $out2);
                    foreach ($out2['respuestas'] as $r) $respuestas[] = $r;
                }
                return '__ESPERAR__';
            }

            // ── NAVEGACION UNIFICADA (una sola respuesta por interaccion) ──
            if ($modo === 'consulta_resultados' && ! empty($vars['_nav'])) {
                $nm = $this->normalizarIntencion($m);

                // Paginacion real con estado.
                if (preg_match('/^(siguientes?|ver siguientes?|ver m[aá]s|mas)$/u', $nm)) {
                    $vars['_nav']['page']++;
                    $r = $this->navPagina($vars);
                    if (! empty($r['vacia'])) {
                        $vars['_nav']['page']--;
                        $respuestas[] = 'Ya viste todos 🙂. Responde con un *número*, *anteriores* o escríbeme otro producto.';
                    } else {
                        $respuestas[] = $r['lista'];
                    }
                    return '__ESPERAR__';
                }
                if (preg_match('/^(anteriores?|atras|atrás|volver)$/u', $nm) && $vars['_nav']['page'] > 1) {
                    $vars['_nav']['page']--;
                    $respuestas[] = $this->navPagina($vars)['lista'];
                    return '__ESPERAR__';
                }

                // Link y PDF: SOLO a pedido.
                if (preg_match('/^(web|catalogo completo|ver catalogo( completo)?)$/u', $nm)) {
                    $cat = ! empty($vars['_nav']['cat_id'])
                        ? \App\Modules\Catalogo\Models\Category::where('project_id', $this->project->id)->find($vars['_nav']['cat_id'])
                        : null;
                    $respuestas[] = $cat
                        ? "🌐 Mira *{$cat->name}* con fotos y detalles:\n" . $this->urlCategoria($cat)
                        : rtrim('🌐 Nuestro catálogo online:' . str_replace('\n\n🌐 Míralos con fotos:', '', $this->pieWeb()));
                    return '__ESPERAR__';
                }
                if (preg_match('/^(pdf|descargar catalogo|catalogo pdf)$/u', $nm) && ! empty($vars['_nav']['cat_id'])) {
                    $cat = \App\Modules\Catalogo\Models\Category::where('project_id', $this->project->id)->find($vars['_nav']['cat_id']);
                    if ($cat) { $respuestas[] = $this->burbujaPdfCategoria($cat); return '__ESPERAR__'; }
                }

                // Refinamiento SIN salir del contexto: precio y "mas barato".
                if (preg_match('/(mas |más )?barat/u', $nm)) {
                    $vars['_nav']['orden'] = 'precio_asc';
                    $vars['_nav']['page'] = 1;
                    $respuestas[] = $this->navPagina($vars)['lista'];
                    return '__ESPERAR__';
                }
                if (($tope = $this->presupuestoDe($nm)) !== null) {
                    $vars['_nav']['precio_max'] = $tope;
                    $vars['_nav']['page'] = 1;
                    $r = $this->navPagina($vars);
                    $respuestas[] = ! empty($r['vacia'])
                        ? 'No tengo opciones por debajo de S/ ' . number_format($tope, 2) . " en *{$vars['_nav']['titulo']}* 😕. Escribe *anteriores* o consulta a un *asesor*."
                        : $r['lista'];
                    if (! empty($r['vacia'])) $vars['_nav']['precio_max'] = null;
                    return '__ESPERAR__';
                }
            }

            // "ese" / "ese mismo": el cliente habla del producto en pantalla.
            if (in_array($modo, ['consulta_ficha', 'consulta_resultados'], true)
                && preg_match('/^(ese|esa|ese mismo|esa misma|el mismo|la misma)$/u', $m)
                && ! empty($vars['_ultimo_prod'])) {
                $out = ['respuestas' => []];
                $respuestas[] = $this->fichaComercial((int) $vars['_ultimo_prod'], $vars, $out);
                foreach ($out['respuestas'] as $r) array_unshift($respuestas, $r);
                $vars['_modo'] = 'consulta_ficha';
                return '__ESPERAR__';
            }

            // Cortos de duda DENTRO de la busqueda: se orienta con ejemplos
            // REALES (las categorias del negocio), no con un "no entendi".
            if ($modo === 'consulta_buscando' && preg_match('/^(no se|nose|no sé|ayuda|\?+|\.\.\.+)$/u', $m)) {
                $cats = \App\Modules\Catalogo\Models\Category::where('project_id', $this->project->id)->limit(3)->pluck('name');
                $ej = $cats->isNotEmpty() ? $cats->map(fn ($c) => "“" . mb_strtolower($c) . "”")->implode(', ') : '“ropero”, “mesa”';
                $respuestas[] = "No hay problema 🙂 Puedes escribir algo general como {$ej}, o el nombre del producto.";
                return '__ESPERAR__';
            }

            // Eligio un numero de la lista de resultados.
            if ($modo === 'consulta_resultados' && preg_match('/^\d{1,2}$/', $m)) {
                if (! empty($vars['_nav'])) {
                    // Numeracion GLOBAL: "7" es el septimo del total, este en
                    // la pagina que este.
                    $npag = 5;
                    $num = (int) $m;
                    $pagDe = (int) ceil($num / $npag);
                    if ($pagDe !== $vars['_nav']['page']) {
                        $vars['_nav']['page'] = $pagDe;
                        $this->navPagina($vars);   // refresca ids_pagina
                    }
                    $idx = ($num - 1) % $npag;
                    $id = $vars['_nav']['ids_pagina'][$idx] ?? null;
                    if (! $id) {
                        $respuestas[] = 'Ese número no está en la lista 🤔.';
                        return '__ESPERAR__';
                    }
                    $out = ['respuestas' => []];
                    $ficha = $this->fichaInteractiva((int) $id, $vars, $out);
                    foreach ($out['respuestas'] as $r) $respuestas[] = $r;
                    $respuestas[] = $ficha;
                    $vars['_modo'] = 'consulta_ficha';
                    return '__ESPERAR__';
                }
                $ids = $vars['_resultados'] ?? [];
                $idx = (int) $m - 1;
                $id = $ids[$idx] ?? null;
                if (! $id || ! is_numeric($id)) {
                    $respuestas[] = 'Ese número no está en la lista 🤔. Responde con un número del 1 al ' . count($ids) . '.';
                    return '__ESPERAR__';
                }
                $out = ['respuestas' => []];
                $ficha = $this->fichaInteractiva((int) $id, $vars, $out);
                foreach ($out['respuestas'] as $r) $respuestas[] = $r;
                $respuestas[] = $ficha;
                $vars['_modo'] = 'consulta_ficha';
                return '__ESPERAR__';
            }

            // "stock?"/"precio?" con una LISTA delante: falta saber de cual.
            if ($modo === 'consulta_resultados'
                && preg_match('/^(stock|precio|precios|disponible|disponibilidad|cuanto cuesta)\??$/u', $this->normalizarIntencion($m))) {
                $respuestas[] = '¿De cuál? Responde con el *número* de la lista 🙂';
                return '__ESPERAR__';
            }

            // "diferencia entre el 1 y el 3": se comparan DOS productos de la
            // lista que el cliente tiene delante, con datos reales de su ficha.
            if ($modo === 'consulta_resultados'
                && preg_match_all('/\d{1,2}/', $m, $mNums)
                && count($mNums[0]) >= 2
                && preg_match('/diferencia|comparar|compara|versus|\bvs\b|mejor|conviene/u', $m)) {
                $ids = $vars['_resultados'] ?? [];
                $a = $ids[(int) $mNums[0][0] - 1] ?? null;
                $b = $ids[(int) $mNums[0][1] - 1] ?? null;
                if ($a && $b) {
                    $respuestas[] = $this->compararProductos((int) $a, (int) $b);

                    return '__ESPERAR__';
                }
                $respuestas[] = 'Esos números no están en la lista 🤔. Dime dos de los que te mostré.';

                return '__ESPERAR__';
            }

            // ── Acciones de la FICHA interactiva ──
            if ($modo === 'consulta_ficha') {
                $nm = $this->normalizarIntencion($m);
                $pid = (int) ($vars['_ultimo_prod'] ?? 0);

                if ($nm === 'volver' && ! empty($vars['_nav'])) {
                    // Regresa EXACTAMENTE a la pagina previa de resultados.
                    $respuestas[] = $this->navPagina($vars)['lista'];
                    return '__ESPERAR__';
                }
                if (preg_match('/^(fotos?|ver fotos?|imagen(es)?)$/u', $nm) && $pid) {
                    $prod = \App\Modules\Catalogo\Models\Product::where('project_id', $this->project->id)->find($pid);
                    if ($prod && $prod->main_image_url) {
                        $respuestas[] = ['tipo' => 'imagen', 'url' => url($prod->main_image_url),
                            'caption' => $prod->name, 'fallback' => "📸 {$prod->name}: " . url($prod->main_image_url)];
                    } else {
                        $respuestas[] = 'Este producto aún no tiene foto cargada 😕. Míralo en la web o escribe *asesor*.';
                    }
                    return '__ESPERAR__';
                }
                if (preg_match('/^(caracteristicas?|detalles?|info)$/u', $nm) && $pid) {
                    $f = $this->ctx->fichaProducto($pid);
                    $respuestas[] = ! empty($f['caracteristicas'])
                        ? "ℹ️ *{$f['nombre']}*\n• " . implode("\n• ", array_slice($f['caracteristicas'], 0, 8))
                        : "*{$f['nombre']}* no tiene más detalles registrados; un *asesor* puede contarte más 🙂.";
                    return '__ESPERAR__';
                }
                if (preg_match('/^(similares?|ver similares?|parecidos?)$/u', $nm) && $pid) {
                    $prod = \App\Modules\Catalogo\Models\Product::where('project_id', $this->project->id)->find($pid);
                    if ($prod && $prod->category_id) {
                        // "Similares" hereda la afinidad del producto actual:
                        // desde un "TV 32..." los TV van primero, no las comodas.
                        $primera = explode(' ', trim($prod->name))[0] ?? null;
                        $r = $this->navAbrir($vars, ['cat_id' => $prod->category_id,
                            'term' => $primera,
                            'titulo' => $prod->category->name ?? 'Similares']);
                        $respuestas[] = $r['lista'];
                        return '__ESPERAR__';
                    }
                }
                if (preg_match('/^(comprar|quiero comprar|lo quiero|pedir)$/u', $nm)) {
                    return $bloque['asesor_siguiente'] ?? $bloque['siguiente'] ?? null;
                }
            }

            // Pregunta por stock/disponibilidad del producto en contexto.
            if ($modo === 'consulta_ficha' && preg_match('/stock|disponib|queda|quedan|hay\b/u', $m)
                && ! preg_match('/^\d+$/', $m)) {
                $f = $this->ctx->fichaProducto((int) ($vars['_ultimo_prod'] ?? 0));
                if ($f && array_key_exists('stock', $f) && $f['stock'] !== null) {
                    $respuestas[] = $f['stock'] > 0
                        ? "✅ Sí, *{$f['nombre']}* está disponible ({$f['stock']} unid.). ¿Te paso con un *asesor* para coordinar?"
                        : "⚠️ *{$f['nombre']}* está sin stock por ahora 😕. Escribe *asesor* y te avisamos apenas llegue.";
                } elseif ($f) {
                    $respuestas[] = "*{$f['nombre']}* está publicado; para confirmar stock al momento escribe *asesor* 🙂.";
                } else {
                    $respuestas[] = 'No tengo un producto en contexto. Escríbeme el nombre del que te interesa. 🔎';
                }
                return '__ESPERAR__';
            }

            // Pregunta por el descuento del producto en contexto.
            if ($modo === 'consulta_ficha' && preg_match('/descuento|oferta|rebaja|promocion|promoción/u', $m)) {
                $f = $this->ctx->fichaProducto((int) ($vars['_ultimo_prod'] ?? 0));
                if ($f && isset($f['antes'])) {
                    $respuestas[] = "🔥 Sí: *{$f['nombre']}* está en oferta.
Antes: ~S/ " . number_format($f['antes'], 2) . "~
Ahora: *" . $this->precioTxt($f['precio']) . '*';
                } elseif ($f) {
                    $respuestas[] = "Por ahora *{$f['nombre']}* no tiene descuento registrado; su precio es " . $this->precioTxt($f['precio']) . ". Si buscas precio por cantidad escribe *asesor*. 🙂";
                } else {
                    $respuestas[] = 'No tengo un producto en contexto. Escríbeme el nombre del que te interesa. 🔎';
                }
                return '__ESPERAR__';
            }

            // El hibrido tambien rige DENTRO de la consulta: si el texto es
            // otra intencion ("¿aceptan yape?" a mitad de una busqueda), se
            // clasifica y va a su rama en vez de buscarse como producto.
            if (! empty($bloque['libre_siguiente']) && $this->clasificarIntencion($mensaje) !== 'producto') {
                unset($vars['_modo'], $vars['_resultados']);
                return $bloque['libre_siguiente'];
            }

            // Cualquier otro texto se toma como UNA NUEVA BUSQUEDA: es lo que
            // hace natural el flujo ("precio del taladro" → "y la amoladora?").
            // TEXTO NUEVO = BUSQUEDA NUEVA: se descarta el contexto anterior.
            unset($vars['_modo'], $vars['_resultados'], $vars['_nav'], $vars['_cat_id']);
            if ($tipo === 'promociones') {
                return $bloque['libre_siguiente'] ?? $bloque['siguiente'] ?? null;
            }
            return $volver;
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
                    // Igual que en la lista: la eleccion no es un dato.
                    $vars['_msj_consumido'] = true;
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
                    // Al tocar la fila, WhatsApp NO manda el texto que se ve:
                    // manda el rowId — el mismo slug que genera la emision de la
                    // lista (Str::slug del titulo). Sin compararlo, el toque caia
                    // al clasificador y "Ver nuestra tienda" se buscaba como
                    // producto (fallo real en la primera prueba por telefono).
                    $id   = mb_strtolower($f['id'] ?? \Illuminate\Support\Str::slug($f['titulo'] ?? $f['texto'] ?? 'op'));
                    $seco = trim(preg_replace('/[^\p{L}\p{N} ]/u', ' ', $titulo));
                    $seco = trim(preg_replace('/\s+/', ' ', $seco));
                    $elegidoSeco = trim(preg_replace('/\s+/', ' ', trim(preg_replace('/[^\p{L}\p{N} ]/u', ' ', $elegido))));
                    if ($elegido === $titulo || ($id !== '' && $elegido === $id)
                        || ($seco !== '' && $elegidoSeco === $seco) || $elegido === (string) $n) {
                        // El mensaje ERA la eleccion: se consume aqui. Si sigue
                        // vivo, el bloque destino lo toma como dato y "1" acaba
                        // buscandose como producto (fallo real en el simulador).
                        $vars['_msj_consumido'] = true;
                        return $f['siguiente'] ?? null;
                    }
                }
            }
            // Modelo hibrido: si la lista declara a donde ir cuando el texto no
            // es una opcion, el mensaje sigue vivo y lo clasifica ese bloque
            // (asi el cliente puede escribir su consulta sin navegar el menu).
            if (! empty($bloque['no_coincide'])) {
                return $bloque['no_coincide'];
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

    /**
     * Pregunta de un bloque `intencion` aprovechando Meta: si trae `enlace`
     * sale un boton que abre la URL; si trae `botones` (hasta 3, con
     * `siguiente`) salen atajos nativos. El cliente sigue pudiendo escribir
     * libremente: el texto lo clasifica la IA como siempre. Por Baileys todo
     * vuelve a texto plano (ver `fallback`).
     */
    private function preguntaIntencion(array $bloque, array $vars): array
    {
        $texto = $this->interpolar($bloque['texto'], $vars);
        $botones = [];
        foreach (array_slice(array_values($bloque['botones'] ?? []), 0, 10) as $b) {
            $titulo = mb_substr(trim((string) ($b['titulo'] ?? '')), 0, 20);
            $destino = (string) ($b['siguiente'] ?? '');
            if ($titulo === '' || ! isset($this->flow['bloques'][$destino])) {
                continue;
            }
            // Meta exige ids distintos: si dos botones llevan al mismo bloque, el
            // segundo va como btn:bloque#2 (destinoBoton ignora el sufijo).
            $id = 'btn:' . $destino;
            $usados = array_column($botones, 'id');
            for ($k = 2; in_array($id, $usados, true); $k++) {
                $id = 'btn:' . $destino . '#' . $k;
            }
            $botones[] = ['id' => $id, 'titulo' => $titulo];
        }
        $enlace = $bloque['enlace'] ?? null;
        $salida = [];
        if (! empty($enlace['url'])) {
            $url = $this->interpolar($enlace['url'], $vars);
            $salida[] = [
                'tipo'     => 'cta_url',
                'cuerpo'   => $texto,
                'url'      => $url,
                'boton'    => $enlace['boton'] ?? 'Abrir',
                'titulo'   => $enlace['titulo'] ?? null,
                'fallback' => $texto . "\n\n" . $url,
                // Foto de cabecera: captura de la tienda + texto + boton, en una burbuja.
                ...(! empty($bloque['imagen']) ? ['imagen' => $this->interpolar((string) $bloque['imagen'], $vars)] : []),
            ];
            if ($botones === []) {
                return $salida;
            }
            // Con enlace Y botones, los botones van en un segundo mensaje corto.
            $texto = $this->interpolar($bloque['botones_texto'] ?? '¿Cómo seguimos?', $vars);
            $salida[] = count($botones) > 3
                ? ['tipo' => 'lista', 'titulo' => '', 'cuerpo' => $texto, 'pie' => '', 'boton' => 'Elegir', 'secciones' => [['titulo' => 'Opciones', 'filas' => array_map(fn ($b) => ['id' => $b['id'], 'titulo' => mb_substr($b['titulo'], 0, 24), 'descripcion' => ''], $botones)]], 'fallback' => $texto]
                : ['tipo' => 'botones', 'cuerpo' => $texto, 'botones' => $botones, 'fallback' => $texto];
            return $salida;
        }
        if ($botones === []) {
            return [$texto];
        }
        // Mas de 3: WhatsApp no admite mas botones, asi que va como menu desplegable (hasta 10).
        if (count($botones) > 3) {
            $fallback = $texto . "

" . implode("
", array_map(fn ($b, $i) => ($i + 1) . '. ' . $b['titulo'], $botones, array_keys($botones)));
            return [['tipo' => 'lista', 'titulo' => '', 'cuerpo' => $texto, 'pie' => '', 'boton' => $this->interpolar((string) ($bloque['lista_boton'] ?? 'Elegir'), $vars),
                'secciones' => [['titulo' => $bloque['lista_titulo'] ?? 'Opciones', 'filas' => array_map(fn ($b) => ['id' => $b['id'], 'titulo' => mb_substr($b['titulo'], 0, 24), 'descripcion' => ''], $botones)]],
                'fallback' => $fallback]];
        }
        $msg = ['tipo' => 'botones', 'cuerpo' => $texto, 'botones' => $botones, 'fallback' => $texto];
        // Imagen de cabecera: foto + pregunta + botones en UNA sola burbuja (Meta).
        if (! empty($bloque['imagen'])) {
            $msg['imagen'] = $this->interpolar((string) $bloque['imagen'], $vars);
        }

        return [$msg];
    }

    /** Si el mensaje es el id de un boton de atajo (`btn:<bloque>`) devuelve ese bloque. */
    private function destinoBoton(string $mensaje): ?string
    {
        $m = trim($mensaje);
        if (! str_starts_with($m, 'btn:')) {
            return null;
        }
        $destino = explode('#', substr($m, 4), 2)[0];

        return isset($this->flow['bloques'][$destino]) ? $destino : null;
    }

    /** Opciones como botones nativos (<= 3) con el texto numerado de respaldo. */
    private function botonesOpciones(array $bloque, array $vars): string|array
    {
        $opciones = array_values($bloque['opciones'] ?? []);
        $fallback = $this->textoOpciones($bloque, $vars);
        if (count($opciones) === 0 || count($opciones) > 10) {
            return $fallback;
        }
        if (count($opciones) > 3) {
            // De 4 a 10: menu desplegable nativo. El id de la fila es el numero.
            return [
                'tipo'      => 'lista',
                'titulo'    => '',
                'cuerpo'    => $this->interpolar($bloque['texto'] ?? 'Elige una opción:', $vars),
                'pie'       => '',
                'boton'     => 'Ver opciones',
                'secciones' => [['titulo' => 'Opciones', 'filas' => array_map(fn ($op, $i) => [
                    'id'          => (string) ($i + 1),
                    'titulo'      => mb_substr(trim((string) ($op['texto'] ?? 'Opción ' . ($i + 1))), 0, 24),
                    'descripcion' => '',
                ], $opciones, array_keys($opciones))]],
                'fallback'  => $fallback,
            ];
        }
        return [
            'tipo'     => 'botones',
            'cuerpo'   => $this->interpolar($bloque['texto'] ?? 'Elige una opción:', $vars),
            'botones'  => array_map(fn ($op, $i) => [
                'id'     => (string) ($i + 1),
                'titulo' => mb_substr(trim((string) ($op['texto'] ?? 'Opción ' . ($i + 1))), 0, 20),
            ], $opciones, array_keys($opciones)),
            'fallback' => $fallback,
        ];
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
