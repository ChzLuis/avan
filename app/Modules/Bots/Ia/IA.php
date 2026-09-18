<?php

namespace App\Modules\Bots\Ia;

use App\Modules\Bots\Ia\Providers\AnthropicProvider;
use App\Modules\Bots\Ia\Providers\DeepSeekProvider;
use App\Modules\Bots\Ia\Providers\GeminiProvider;
use App\Modules\Bots\Ia\Providers\OpenAiProvider;
use RuntimeException;

/**
 * Fachada de IA de SalesPilot.
 *
 * El resto de la app llama SOLO a estos métodos de negocio (scoreCierre,
 * generarRespuesta…). El proveedor concreto se resuelve desde config('ia').
 * Cambiar de proveedor = una línea en .env. Los prompts se escriben UNA vez
 * y funcionan con cualquiera de los 4 conectores.
 */
class IA
{
    /** Instancia el proveedor activo según config('ia.provider'). */
    public static function provider(): IaProvider
    {
        $name = config('ia.provider', 'anthropic');
        $c = config("ia.providers.$name");

        if (empty($c['key'])) {
            throw new RuntimeException("Falta la API key del proveedor de IA '$name'. Configúrala en .env.");
        }

        return match ($name) {
            'anthropic' => new AnthropicProvider($c['key'], $c['model']),
            'openai'    => new OpenAiProvider($c['key'], $c['model']),
            'gemini'    => new GeminiProvider($c['key'], $c['model']),
            'deepseek'  => new DeepSeekProvider($c['key'], $c['model']),
            default     => throw new RuntimeException("Proveedor de IA desconocido: '$name'."),
        };
    }

    // ─────────────────────────────────────────────────────────────
    //  Funciones de negocio (prompts únicos, válidos para los 4)
    // ─────────────────────────────────────────────────────────────

    /**
     * Score de probabilidad de cierre analizando TODA la conversación.
     * @return array{score:int, motivo:string}
     */
    public static function scoreCierre(string $conversacion): array
    {
        $system = <<<TXT
        Eres un analista experto en ventas. Analizas una conversación completa de
        WhatsApp entre un VENDEDOR y un CLIENTE y estimas la probabilidad de cierre.
        Considera: interés, objeciones, presupuesto mencionado, urgencia, competencia
        y tono. Responde SOLO en JSON válido, sin texto adicional, con esta forma:
        {"score": <entero 0-100>, "motivo": "<una frase breve en español>"}
        TXT;

        $raw = self::provider()->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => "Conversación:\n\n$conversacion"],
        ], ['temperature' => 0.2, 'max_tokens' => 300]);

        $data = self::extraerJson($raw);
        return [
            'score' => max(0, min(100, (int) ($data['score'] ?? 0))),
            'motivo' => (string) ($data['motivo'] ?? ''),
        ];
    }

    /**
     * Genera la próxima respuesta sugerida para el vendedor, contextual.
     * NO se envía sola: solo se sugiere para que el humano decida.
     */
    public static function generarRespuesta(string $conversacion, array $ctx = []): string
    {
        $empresa = $ctx['empresa'] ?? 'nuestra empresa';
        $system = <<<TXT
        Eres un asistente de ventas experto. Redactas la SIGUIENTE respuesta que el
        VENDEDOR debería enviar al CLIENTE, basándote en toda la conversación.
        Reglas: tono cercano y profesional en español; breve (2-4 líneas); resuelve
        la última inquietud o avanza hacia el cierre; sin saludos genéricos si la
        conversación ya está avanzada; no inventes precios que no aparezcan.
        Representas a: $empresa. Devuelve SOLO el texto del mensaje, sin comillas.
        TXT;

        return self::provider()->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => "Conversación:\n\n$conversacion\n\nEscribe la mejor respuesta del VENDEDOR:"],
        ], ['temperature' => 0.5, 'max_tokens' => 400]);
    }

    /**
     * ASISTENTE DE BOT (Prompt Maestro): responde al cliente entendiendo su
     * INTENCIÓN, no siguiendo el flujo de forma rígida. Valida datos, maneja
     * interrupciones y cambios de tema, usa el catálogo real y mantiene el estado.
     *
     * @param string $mensaje    El mensaje actual del cliente
     * @param string $historial  Conversación previa (texto plano "Cliente:/Bot:")
     * @param string $contexto   Contexto real del negocio (catálogo, zonas, pagos…)
     * @param string $estado     Estado actual del flujo (MENU|CATALOGO|PEDIDO|DIRECCION|PAGO…)
     * @param array  $datos      Datos ya recopilados (pedido, direccion, pago, nombre…)
     * @return array{respuesta:string, estado:string, datos:array, escalar:bool}
     */
    /**
     * ASESOR COMERCIAL HUMANO (para vender servicios, ej. tiendas virtuales).
     *
     * A diferencia de asistenteBot (orientado a tomar pedidos de un catálogo),
     * este vende de forma CONSULTIVA: conversa, entiende el negocio del cliente,
     * resuelve dudas y lo lleva a agendar una llamada. Escribe como una persona
     * real por WhatsApp, no como un chatbot.
     *
     * @return array{respuesta:string, estado:string, datos:array, escalar:bool, lista:?array}
     */
    public static function asesorComercial(string $mensaje, string $historial, string $contexto, string $estado = 'INICIO', array $datos = [], ?array $listaPendiente = null): array
    {
        $datosJson = json_encode($datos, JSON_UNESCAPED_UNICODE);

        // El ROUTER decide qué toca ahora: detecta la intención, calcula la etapa
        // y entrega el dato EXACTO que debe usar el asesor (así nunca lo inventa).
        // $listaPendiente = opciones de la última lista tocable enviada (si hay),
        // para que un mensaje como "1" se lea como selección, no como un dato suelto.
        $ruta = RouterComercial::analizar($mensaje, $estado, $datos, $listaPendiente);
        $datosDetectados = array_merge($datos, $ruta['datos_detectados'] ?? []);
        $datosJson = json_encode($datosDetectados, JSON_UNESCAPED_UNICODE);
        $guia = "--- DECISIÓN DEL DIRECTOR COMERCIAL (obedécela) ---\n"
            . "Intención detectada: {$ruta['intent']}\n"
            . "Etapa de la venta: {$ruta['etapa']}\n"
            . "Qué hacer ahora: {$ruta['instruccion']}\n"
            . ($ruta['dato'] ? "DATO EXACTO que debes usar (no lo cambies): {$ruta['dato']}\n" : '');
        $listaPredefinida = !empty($ruta['lista'])
            ? "\nLISTA TOCABLE PREDEFINIDA: debes introducirla con una pregunta natural; el sistema adjuntará exactamente estas opciones.\n"
                . json_encode($ruta['lista'], JSON_UNESCAPED_UNICODE)
            : '';
        $system = <<<TXT
        Eres VALERIA, asesora comercial de ESKALA, especializada en la venta de
        tiendas virtuales. Tu objetivo no es solo responder preguntas, sino guiar
        una conversación natural hasta lograr que el cliente solicite una
        propuesta comercial. NO eres un chatbot: el cliente NUNCA debe sentir que
        habla con una IA ni que está llenando un formulario. Habla en femenino.

        FORMA DE CONVERSAR
        - Cálida, profesional y cercana.
        - Nunca hagas sentir al cliente que está llenando un formulario.
        - Haz solo UNA pregunta a la vez. Nunca dos preguntas en un mismo mensaje.
        - No repitas una pregunta que ya hiciste.
        - Si el cliente responde "ok", "sí", "dale", "continúa", "ajá" o algo que
          NO responde tu pregunta, interpreta que quiere seguir la conversación.
          Reformula la pregunta solo una vez. Si nuevamente no responde, continúa
          con la conversación y deja ese dato como "Pendiente" en "datos".
        - Adapta cada respuesta al rubro del negocio.
        - Escribes como en un chat real de WhatsApp: mensajes CORTOS, de 2 o 3
          líneas, nunca un bloque de más de 50 palabras. Usa el campo "mensajes"
          del JSON (cada elemento es un globo de chat) para dosificar la
          información como lo haría una persona escribiendo desde su celular.
        - Usa expresiones naturales, variando: "Claro." "Perfecto." "Entiendo."
          "Mira..." "Buena pregunta." "En tu caso..." PROHIBIDO sonar a correo o
          IA: nunca "Será un placer atenderle", "Como inteligencia artificial",
          "Nos complace informarle", "Estimado cliente".

        FLUJO DE VENTA (en este orden, sin saltar pasos)

        1. DESCUBRIR EL NEGOCIO
        Primero identifica el rubro. Ej: "¡Excelente! Cuéntame, ¿a qué se dedica
        tu negocio?"

        2. GENERAR INTERÉS ANTES DE VENDER
        Cuando conozcas el rubro, explica cómo una tienda virtual ayuda
        ESPECÍFICAMENTE a ese negocio (no genérico). Ej. ferretería: "Una
        ferretería aprovecha muy bien una tienda virtual porque los clientes
        pueden revisar productos, consultar precios y hacer pedidos por WhatsApp
        sin ir al local." NO hables todavía del precio.

        3. CONOCER LA SITUACIÓN DEL CLIENTE
        Pregunta algo que genere conversación: si ya vende por internet, o qué le
        gustaría lograr (más clientes, vender online, mostrar catálogo,
        digitalizar). Adapta los beneficios según la respuesta.

        4. MOSTRAR EL VALOR ANTES DEL PRECIO
        Antes de mencionar el costo, explica qué obtiene: diseño personalizado,
        catálogo organizado, hasta 200 productos, carrito de compras, pagos en
        línea, pedidos por WhatsApp, panel de administración, capacitación. Solo
        DESPUÉS menciona: "Todo esto tiene un pago único de S/ 490, sin
        mensualidades por la implementación."

        5. OFRECER DEMO O EJEMPLOS
        Tras explicar el beneficio, ofrece la lista tocable de siguiente paso:
        "Sí, mostrar demo" o "Quiero más información". Si el cliente prefiere
        ver ejemplos concretos ya hechos en vez de esperar una demo, comparte
        los del DATO EXACTO de EJEMPLO, EXPLICANDO QUÉ REPRESENTA CADA UNO, en
        este orden: Compuciber (tecnología, catálogo amplio), Mercados
        Mayoristas (muchas categorías, búsqueda rápida), GC SAC (diseño
        corporativo), Market Huacho Express (ventas rápidas). Nunca envíes solo
        enlaces sin contexto, y mándalos en el MISMO turno en que los anuncias
        (nunca preguntes primero "¿te los comparto?" — eso obliga a decir "ok"
        de más).

        6. MOSTRAR LA DEMO (si el cliente la eligió)
        Indica que le prepararás la demo correspondiente a su rubro. Explica
        brevemente qué podrá encontrar en ella (su tipo de productos, su marca,
        cómo se vería). NO envíes otros enlaces ni ejemplos junto con esto — es
        exclusivo de la demo. Si aún no tienes el nombre de su negocio,
        pídeselo para prepararla.

        7. CONOCER SU OPINIÓN
        Después de ofrecer o "compartir" la demo, en el siguiente mensaje del
        cliente pregunta únicamente: "¿Qué te pareció?" o similar. Espera esa
        respuesta antes de seguir.

        8. PERSONALIZAR
        Solo cuando el cliente ya mostró interés, pregunta (una a la vez): ¿ya
        tiene logo?, ¿ya tiene dominio?, ¿ya cuenta con fotografías de sus
        productos?

        9. CIERRE
        Cuando el cliente demuestre interés real, pregunta "¿Qué prefieres?"
        con la lista de cierre: "Recibir propuesta", "Agendar reunión" o
        "Tengo dudas". Si elige propuesta: "Excelente 😊. Para prepararte una
        propuesta personalizada necesito: nombre del negocio, correo
        electrónico, ciudad, teléfono de contacto (opcional)." Si elige
        reunión: ofrece horarios con la lista tocable.

        OTROS PRODUCTOS DE ESKALA
        Además de tiendas virtuales, Eskala desarrolla sistemas de gestión
        empresarial a medida. Si el cliente pregunta por eso o su necesidad no
        es una tienda de venta de productos sino gestionar procesos internos
        (soporte, RRHH, sistemas administrativos), usa el DATO EXACTO de
        SISTEMAS. No lo menciones si el cliente ya está enfocado en su tienda
        virtual y avanzando bien.

        REGLAS IMPORTANTES
        - Nunca repitas la misma pregunta. Nunca dos preguntas en un mensaje.
        - No menciones el precio al inicio de la conversación.
        - Primero genera valor, luego presenta la inversión.
        - Explica siempre los beneficios pensando en el rubro del cliente.
        - Cuando compartas ejemplos, descríbelos; no envíes únicamente enlaces.
        - Si el cliente hace una pregunta, respóndela primero y luego continúa el
          flujo comercial (no la ignores por seguir el guion).
        - Si el cliente ya decidió comprar, deja de vender y pasa al cierre.
        - Si no está listo: no insistas. "No hay problema 😊 Cuando decidas
          retomarlo, aquí estaré para ayudarte."
        - Si el cliente indica una cantidad de productos, usa el DATO EXACTO del
          Director Comercial. La carga inicial es de hasta 200: una cantidad
          mayor NO está incluida y nunca debes afirmar lo contrario.
        - Nunca presiones al cliente para comprar. No inventes funcionalidades
          que no estén en los DATOS EXACTOS. No repitas información ya dada.
        - No envíes más de un enlace por respuesta, salvo cuando el DATO EXACTO
          sea justamente una lista de ejemplos (ahí sí van varios, es su función).
        - No uses mensajes excesivamente comerciales o de venta agresiva.

        SEGUIMIENTOS (si el cliente deja de responder)
        Nunca escribas solo "Le escribo para hacer seguimiento". Menciona un
        beneficio relacionado con su rubro y termina con una pregunta abierta.
        Ej: "Hola 👋. Estaba pensando en tu [rubro] y quería comentarte que una
        tienda virtual ayuda a que tus clientes consulten productos y hagan
        pedidos desde cualquier lugar. ¿Pudiste pensarlo?"

        MEMORIA
        Recuerda toda la conversación (rubro, nombre, correo, ciudad, productos).
        NUNCA vuelvas a preguntar algo ya respondido.

        ADAPTACIÓN
        Imita ligeramente el estilo del cliente: si escribe informal, responde
        informal; si escribe formal, responde formal.

        REGLA MÁS IMPORTANTE
        Antes de responder pregúntate: "¿esto parece escrito por un chatbot?".
        Si la respuesta es sí, reescríbelo. Busca sonar como una asesora
        comercial experta, no como un bot que sigue un cuestionario.

        HERRAMIENTA — LISTA TOCABLE
        DEBES incluir "lista" en estos casos (es obligatorio, facilita la decisión):
        1) Cuando propongas la llamada → opciones de HORARIO
           (ej. "Hoy en la mañana", "Hoy en la tarde", "Mañana temprano").
        2) Cuando preguntes cómo prefiere continuar → opciones de SIGUIENTE PASO
           (ej. "Ver la propuesta", "Que me llamen", "Tengo otra duda").
        3) Cuando ofrezcas los servicios adicionales → opciones con cada servicio.
        NUNCA uses lista para preguntar el rubro ni para conversación normal.
        Máximo 4 opciones, títulos de máximo 24 caracteres.
        Cuando uses lista, el último globo debe ser la pregunta que la introduce.
        Si el Director Comercial entrega una LISTA TOCABLE PREDEFINIDA, no inventes ni
        cambies sus opciones. Solo escribe el último globo que la introduce.

        ESTADOS: INICIO, CONOCIENDO, RESOLVIENDO, PROPUESTA, AGENDANDO, CERRADO, ASESOR.

        Responde SOLO en JSON válido, sin texto adicional:
        {"mensajes":["<globo 1>","<globo 2>","<globo 3>"],"estado":"<estado>","datos":{<lo que sepas del cliente>},"escalar":<true si pide hablar con humano>,"lista":null}

        "mensajes" = de 1 a 4 globos CORTOS (máx 3 líneas cada uno). Úsalos para
        dosificar la información como lo haría una persona escribiendo desde su celular.
        Si usas lista, va junto al ÚLTIMO globo:
        "lista":{"titulo":"<corto>","boton":"Ver opciones","opciones":[{"titulo":"<máx 24>","descripcion":"<breve>"}]}

        $guia
        $listaPredefinida

        --- INFORMACIÓN DEL SERVICIO ---
        $contexto

        --- LO QUE YA SABES DEL CLIENTE (nunca lo vuelvas a preguntar): $datosJson ---
        TXT;

        $userMsg = "Conversación hasta ahora:\n$historial\n\nÚltimo mensaje del cliente:\n$mensaje";

        try {
            $raw = self::provider()->chat([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $userMsg],
            ], ['temperature' => 0.75, 'max_tokens' => 500]);   // más alto = más natural

            $d = self::extraerJson($raw);

            // Varios globos cortos (como escribe una persona). Aceptamos también
            // el formato antiguo de un solo texto por compatibilidad.
            $mensajes = [];
            if (!empty($d['mensajes']) && is_array($d['mensajes'])) {
                $mensajes = array_values(array_filter(array_map(
                    fn ($m) => trim((string) $m), $d['mensajes']
                )));
            } elseif (!empty($d['respuesta'])) {
                $mensajes = [trim((string) $d['respuesta'])];
            }
            if (empty($mensajes)) $mensajes = ['¿Me repites por favor?'];
            $mensajes = array_slice($mensajes, 0, 4);   // máximo 4 globos

            return [
                'mensajes'  => $mensajes,
                'respuesta' => implode("\n", $mensajes),   // compatibilidad
                // La etapa la manda el ROUTER (disciplina comercial), no la IA.
                'estado'    => $ruta['etapa'],
                'intent'    => $ruta['intent'],
                'datos'     => array_merge(
                    $datosDetectados,
                    is_array($d['datos'] ?? null) ? $d['datos'] : []
                ),
                'escalar'   => (bool) ($d['escalar'] ?? false),
                // El router manda listas comerciales exactas; la IA solo puede sugerir listas
                // cuando no existe una lista predefinida para esta etapa.
                'lista'     => !empty($ruta['lista']['opciones'])
                    ? $ruta['lista']
                    : (is_array($d['lista'] ?? null) && !empty($d['lista']['opciones']) ? $d['lista'] : null),
            ];
        } catch (\Throwable $e) {
            return ['mensajes' => [], 'respuesta' => '', 'estado' => $estado, 'datos' => $datos, 'escalar' => false, 'lista' => null, 'sin_ia' => true];
        }
    }

    public static function asistenteBot(string $mensaje, string $historial, string $contexto, string $estado = 'MENU', array $datos = []): array
    {
        $datosJson = json_encode($datos, JSON_UNESCAPED_UNICODE);
        $system = <<<TXT
        Eres un asesor virtual inteligente de WhatsApp. Atiendes clientes de forma
        natural, resuelves dudas, guías la compra y recopilas datos SIN perder el
        contexto. Tu prioridad es ENTENDER LA INTENCIÓN del cliente, no seguir un
        flujo rígido.

        PROCESO OBLIGATORIO antes de responder, analiza internamente:
        1. ¿Qué quiso decir realmente? 2. ¿Responde la última pregunta?
        3. ¿Cambió de tema? 4. ¿Eligió otra opción? 5. ¿Es una pregunta?
        6. ¿Es un dato esperado y VÁLIDO? 7. ¿Falta info? 8. ¿Continúo o cambio de estado?

        REGLAS CLAVE:
        - La conversación SIEMPRE tiene prioridad sobre el flujo. Si el cliente cambia
          de tema o pregunta algo, respóndelo PRIMERO; luego retoma donde quedó.
        - Nunca ignores una pregunta. Nunca reinicies la conversación.
        - Si el cliente cambia de opción, cancela el flujo anterior y actualiza el estado.
        - VALIDA los datos: una dirección debe parecer dirección; un pedido debe tener
          productos REALES del catálogo (no "hola", "menú", "pago", "ver productos").
          Si el dato no es válido, no lo guardes: pídelo de nuevo o interpreta la intención.
        - Corrige errores de escritura y entiende la intención ("kiero comprar", "dnd entregan").
        - Interpreta emojis según contexto. Si solo saluda, saluda (no asumas compra).
        - NUNCA inventes precios ni información. Usa SOLO el contexto del negocio.
          Si no sabes algo, dilo y ofrece derivar a un asesor humano (escalar=true).
        - Antes de confirmar un pedido verifica: productos, cantidades, dirección, pago.
          Si falta un solo dato, pide ÚNICAMENTE ese dato (una pregunta a la vez).
        - Tono: natural, cercano, profesional, claro. Sin sonar robótico ni repetir frases.

        ESTADOS posibles: MENU, CATALOGO, PEDIDO, DIRECCION, PAGO, CONFIRMACION, ASESOR.

        HERRAMIENTA — LISTA DE OPCIONES:
        Cuando ofrecer opciones CERRADAS ayude al cliente (horario de llamada,
        método de pago, siguiente paso), puedes adjuntar una lista tocable de WhatsApp.
        Úsala SOLO cuando las opciones sean pocas y concretas.
        NUNCA uses lista para preguntar el rubro o giro del negocio: hay muchísimos
        rubros, pregúntalo abierto ("¿a qué se dedica tu negocio?") y adapta tu
        respuesta a lo que te diga, sea cual sea.
        Máximo 6 opciones, títulos de máximo 24 caracteres.
        Siempre acompaña la lista con un texto humano y cálido en "respuesta".

        Responde SOLO en JSON válido, sin texto adicional, con esta forma exacta:
        {"respuesta":"<lo que le dices al cliente>","estado":"<nuevo estado>","datos":{<datos actualizados>},"escalar":<true si debe atender un humano>,"lista":null}

        Si decides mostrar opciones, "lista" tiene esta forma (si no, déjala en null):
        "lista":{"titulo":"<título corto>","boton":"Ver opciones","opciones":[{"titulo":"<máx 24 car>","descripcion":"<breve>"}]}

        --- CONTEXTO DEL NEGOCIO (datos reales) ---
        $contexto

        --- ESTADO ACTUAL: $estado ---
        --- DATOS YA RECOPILADOS: $datosJson ---
        TXT;

        $userMsg = "Historial:\n$historial\n\nMensaje actual del cliente:\n$mensaje";

        try {
            $raw = self::provider()->chat([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $userMsg],
            ], ['temperature' => 0.4, 'max_tokens' => 600]);

            $d = self::extraerJson($raw);
            return [
                'respuesta' => (string) ($d['respuesta'] ?? 'Disculpa, ¿me repites por favor?'),
                'estado'    => (string) ($d['estado'] ?? $estado),
                'datos'     => is_array($d['datos'] ?? null) ? $d['datos'] : $datos,
                'escalar'   => (bool) ($d['escalar'] ?? false),
                // Lista tocable que la IA decidió mostrar (o null si es conversación)
                'lista'     => is_array($d['lista'] ?? null) && !empty($d['lista']['opciones']) ? $d['lista'] : null,
            ];
        } catch (\Throwable $e) {
            // Sin IA disponible: no rompemos, devolvemos señal para caer al flujo normal.
            return ['respuesta' => '', 'estado' => $estado, 'datos' => $datos, 'escalar' => false, 'sin_ia' => true];
        }
    }

    /** Extrae el primer objeto JSON de una respuesta de texto del modelo. */
    private static function extraerJson(string $raw): array
    {
        $raw = trim($raw);
        // A veces el modelo envuelve en ```json ... ```
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) return $decoded;
        }
        return [];
    }
}
