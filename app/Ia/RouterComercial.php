<?php

namespace App\Ia;

/**
 * ROUTER COMERCIAL — el "director" de la conversación de venta.
 *
 * No escribe respuestas: decide QUÉ debe pasar ahora. Detecta la intención del
 * cliente y en qué etapa del proceso está, y devuelve el DATO EXACTO + la
 * instrucción para que el asesor (IA) responda natural, sin inventar nada
 * y sin repetir preguntas ya respondidas.
 *
 * Ventaja frente a plantillas fijas: la respuesta se adapta al rubro del cliente
 * pero el precio y los datos duros salen siempre de aquí (nunca los inventa la IA).
 */
class RouterComercial
{
    /** Etapas del proceso de venta, en orden. */
    public const ETAPAS = [
        'SALUDO', 'IDENTIFICAR_NEGOCIO', 'ENTENDER_NECESIDAD', 'EXPLICAR_BENEFICIO',
        'RESOLVER_DUDAS', 'ENVIAR_PROPUESTA', 'AGENDAR_LLAMADA', 'CIERRE',
    ];

    /**
     * Datos duros del servicio. La IA los usa TAL CUAL: nunca los reinventa.
     * Editar aquí = cambiar lo que dice el asesor en todos lados.
     */
    public const DATOS = [
        'PRECIO'        => 'S/ 490, pago único, IGV incluido. Es la implementación completa de la tienda virtual.',
        'RENOVACION'    => 'S/ 100 al año, recién a partir del segundo año. Cubre hosting, mantenimiento y continuidad del servicio.',
        'INCLUYE'       => 'Diseño adaptado al rubro, catálogo de hasta 200 productos con categorías y subcategorías, carrito de compras, sistema de cotizaciones, botón de WhatsApp, pasarela de pagos, configuración de envíos, panel administrativo (ventas, productos, proveedores, usuarios), precios para minorista y mayorista, SEO, código QR de la tienda, Libro de Reclamaciones y capacitación.',
        'PANEL'         => 'Un panel administrativo donde el cliente gestiona ventas, productos, proveedores y usuarios, y maneja precios distintos para minoristas y mayoristas. Es fácil de usar y se lo enseñamos en la capacitación.',
        'PRODUCTOS'     => 'La carga inicial incluye hasta 200 productos, con sus categorías y subcategorías. Si tiene más, se puede conversar.',
        'CATEGORIAS'    => 'Se organizan en categorías y subcategorías, adaptadas a cómo el cliente ordena su negocio.',
        'PAGOS'         => 'Se integra una pasarela de pagos para que sus clientes paguen en línea. También queda el botón de WhatsApp para pedidos directos.',
        'ENVIOS'        => 'Se hace la configuración básica de envíos según cómo trabaje el negocio.',
        'HOSTING'       => 'El primer año va incluido. Desde el segundo año son S/ 100 anuales por hosting y mantenimiento.',
        'CAPACITACION'  => 'Sí, la capacitación está incluida: le enseñamos a manejar su tienda para que sea autónomo.',
        'SEO'           => 'Se entrega con optimización SEO para que la tienda pueda aparecer en Google.',
        'WHATSAPP'      => 'La tienda lleva botón de WhatsApp: los pedidos y consultas le llegan directo a su celular.',
        'QR'            => 'Se entrega un código QR exclusivo de la tienda para compartirlo en el local, volantes o redes.',
        'RECLAMACIONES' => 'Incluye el Libro de Reclamaciones, que es obligatorio en Perú.',
        'BOT'           => 'El bot para recepción automática de pedidos cuesta S/ 20 mensuales. Es opcional.',
        'META_ADS'      => 'Hay dos opciones: asesoría de 2 horas por S/ 50 la sesión, o administración mensual de campañas en Facebook e Instagram por S/ 200 al mes (no incluye el presupuesto publicitario).',
        'INSTITUCIONAL' => 'El módulo institucional (Nosotros, Misión, Visión, Valores, Historia, Contacto) cuesta S/ 150.',
        'TIEMPO'        => 'Los tiempos de entrega se coordinan con el asesor según el tamaño del catálogo.',
        'FORMA_PAGO'    => 'La forma de pago se coordina con el asesor en la llamada.',
        'EJEMPLO'       => "Ejemplos de tiendas reales que hemos hecho (compártelos EN ESTE ORDEN):\nhttps://compuciber.com/\nhttps://mercadosmayoristas.com.pe/\nhttps://www.gcsac.com.pe/\nhttps://markethuachoexpress.arindg.com/",
        'VIDEO'         => "Aquí un video de cómo funciona la carga de productos: https://www.loom.com/share/abaeca8eccba4598b02dc756dd8b2263\n(Es de una versión anterior, pero el control es igual de sencillo.)",
        'PLANTILLA'     => 'Para desarrollarla a medida, pídele que comparta un formato, diseño o plantilla de referencia que le guste. El equipo lo adapta a su tienda.',
        'RESPONSIVE'    => 'La tienda funciona en celular, tablet y computadora.',
        'SISTEMAS'      => "Además de tiendas virtuales, Eskala desarrolla sistemas de gestión empresarial a medida (ej. mesa de soporte, gestión humana, sistemas para banca). Ejemplos:\nhttps://pichinchaperu.arandasoft.com/asmsspecialist/index.html#/\nhttps://banbif.arandasoft.com/asmsspecialist/index.html#/\nhttps://gestionhumana.arandasoft.com/asmscustomer/index.html#/\nhttps://soporte.arandasoft.com/asmsspecialist/index.html#/",
        'DEMO'          => 'Se le puede preparar una demo personalizada según el rubro de su negocio, para que vea cómo quedaría su propia tienda virtual.',
    ];

    /** Palabras clave por intención (se evalúan sobre el texto normalizado). */
    private const PATRONES = [
        'PRECIO'        => ['cuanto cuesta', 'cuanto sale', 'precio', 'costo', 'vale', 'cuanto es', 'cuanto seria'],
        'INCLUYE'       => ['que incluye', 'que trae', 'que tiene', 'contiene', 'viene con'],
        'RENOVACION'    => ['renovacion', 'anual', 'cada ano', 'mensualidad', 'pago mensual'],
        'HOSTING'       => ['hosting', 'alojamiento', 'servidor', 'dominio'],
        'PANEL'         => ['panel', 'administrar', 'administracion', 'gestionar', 'yo mismo'],
        'PRODUCTOS'     => ['cuantos productos', 'limite de productos', 'productos puedo', 'mis productos'],
        'CATEGORIAS'    => ['categoria', 'subcategoria', 'organizar productos'],
        'PAGOS'         => ['pasarela', 'pagos en linea', 'tarjeta', 'yape', 'plin', 'como pagan', 'cobrar'],
        'ENVIOS'        => ['envio', 'delivery', 'reparto', 'entrega a domicilio'],
        'CAPACITACION'  => ['capacitacion', 'me ensenan', 'ensenan', 'aprender', 'como lo manejo'],
        'SEO'           => ['seo', 'google', 'posicionamiento', 'aparecer en internet'],
        'WHATSAPP'      => ['whatsapp', 'boton de wsp', 'me llegan los pedidos'],
        'QR'            => ['qr', 'codigo qr'],
        'RECLAMACIONES' => ['reclamacion', 'libro de reclamaciones'],
        'BOT'           => ['bot', 'chatbot', 'automatico', 'responde solo'],
        'META_ADS'      => ['publicidad', 'anuncios', 'meta ads', 'facebook ads', 'campanas', 'pauta'],
        'INSTITUCIONAL' => ['nosotros', 'mision', 'vision', 'quienes somos', 'institucional'],
        'TIEMPO'        => ['cuanto demora', 'cuanto tarda', 'tiempo de entrega', 'para cuando', 'cuantos dias'],
        'FORMA_PAGO'    => ['como pago', 'formas de pago', 'puedo pagar en partes', 'adelanto', 'cuotas'],
        'EJEMPLO'       => ['ejemplo', 'ver una tienda', 'muestra', 'portafolio', 'trabajos', 'referencia', 'clientes tienes', 'clientes tienen', 'han hecho', 'trabajo de'],
        'VIDEO'         => ['video', 'como funciona', 'como se usa', 'como cargo'],
        'PLANTILLA'     => ['plantilla', 'diseno de referencia', 'formato', 'como quiero', 'quiero que se vea'],
        'RESPONSIVE'    => ['celular', 'movil', 'tablet', 'telefono'],
        'SISTEMAS'      => ['sistema de gestion', 'sistema empresarial', 'software a medida', 'mesa de soporte', 'gestion humana', 'sistema para mi empresa', 'aplicacion a medida'],
        'DEMO'          => ['demo personalizada', 'demostracion', 'una demo', 'hacer una demo', 'quiero una demo'],
        'AGENDAR'       => ['llamada', 'llamen', 'llamar', 'reunion', 'coordinar', 'agendar'],
        'PROPUESTA'     => ['propuesta', 'cotizacion', 'proforma', 'enviame', 'mandame info'],
        'COMPRAR'       => ['lo quiero', 'quiero comprar', 'como empiezo', 'empecemos', 'me interesa comprar', 'donde deposito'],
        'ASESOR'        => ['hablar con alguien', 'una persona', 'asesor', 'humano', 'llamar a alguien'],
        'DESPEDIDA'     => ['gracias', 'chau', 'adios', 'hasta luego', 'lo pensare', 'despues te escribo'],
        'SALUDO'        => ['hola', 'buenas', 'buenos dias', 'buenas tardes', 'buenas noches', 'que tal'],
    ];

    /**
     * Analiza el mensaje y el estado de la conversación.
     *
     * @return array{intent:string, etapa:string, dato:?string, instruccion:string, lista:?array}
     */
    public static function analizar(string $mensaje, string $etapaActual, array $datosCliente, ?array $listaPendiente = null): array
    {
        // Marca especial (no es una ETAPA real): Valeria ya envió el mensaje de
        // bienvenida fijo (saludo + catálogos + demo). No debe volver a saludar.
        $yaPresentada = $etapaActual === 'YA_PRESENTADA';

        // Si el mensaje es un número aislado y hay una lista tocable recién
        // enviada con esa cantidad de opciones, es una SELECCIÓN de la lista
        // (ej. "1" = primera opción), nunca una cantidad de productos.
        $seleccionLista = self::indiceSeleccionLista($mensaje, $listaPendiente);

        $intent = $seleccionLista !== null ? 'SELECCION_LISTA' : self::detectarIntencion($mensaje);
        $cantidadProductos = $seleccionLista !== null ? null : self::cantidadProductos($mensaje);
        if ($cantidadProductos !== null && (!empty($datosCliente['rubro']) || !empty($datosCliente['negocio']))) {
            $intent = 'CANTIDAD_PRODUCTOS';
        }

        // Si estábamos esperando el rubro y el cliente respondió con texto libre
        // (no una intención reconocida, no un número), asumimos que ESE mensaje
        // ES el rubro. Evita un turno de desfase mientras la IA lo confirma y lo
        // guarda en "datos" (el router calcularía la etapa de forma tardía si no).
        $datosParaEtapa = $datosCliente;
        $sinRubro = empty($datosCliente['rubro']) && empty($datosCliente['negocio']);
        $tieneRubroYa = in_array($etapaActual, ['SALUDO', 'IDENTIFICAR_NEGOCIO'], true)
            && $sinRubro && $seleccionLista === null && $intent === 'ABIERTA'
            && $cantidadProductos === null && mb_strlen(trim($mensaje)) >= 3;
        if ($tieneRubroYa) {
            $datosParaEtapa['rubro'] = trim($mensaje);
        }

        $etapa  = self::calcularEtapa($intent, $etapaActual, $datosParaEtapa);
        $opcionElegida = $seleccionLista !== null ? ($listaPendiente[$seleccionLista] ?? null) : null;
        $dato   = match (true) {
            $intent === 'CANTIDAD_PRODUCTOS' => self::mensajeCantidadProductos($cantidadProductos),
            $intent === 'SELECCION_LISTA'    => "El cliente eligió la opción: \"$opcionElegida\".",
            default => self::datos()[$intent] ?? self::DATOS[$intent] ?? null,
        };

        return [
            'intent'      => $intent,
            'etapa'       => $etapa,
            'dato'        => $dato,
            'instruccion' => self::instruccion($intent, $etapa, $datosParaEtapa, $opcionElegida, $yaPresentada),
            'lista'       => self::listaPara($intent, $etapa),
            'datos_detectados' => $cantidadProductos === null ? [] : ['cantidad_productos' => $cantidadProductos],
        ];
    }

    /** Detecta la intención por palabras clave. Devuelve 'ABIERTA' si no encaja. */
    private static function detectarIntencion(string $mensaje): string
    {
        $t = self::normalizar($mensaje);
        if ($t === '') return 'ABIERTA';

        foreach (self::PATRONES as $intent => $frases) {
            foreach ($frases as $f) {
                if (str_contains($t, $f)) return $intent;
            }
        }
        return 'ABIERTA';   // pregunta abierta o caso especial → la IA improvisa
    }

    /** Decide la etapa: nunca retrocede sin motivo ni salta pasos. */
    private static function calcularEtapa(string $intent, string $actual, array $datos): string
    {
        $actual = in_array($actual, self::ETAPAS, true) ? $actual : 'SALUDO';

        // Intenciones que mueven la conversación de golpe.
        if (in_array($intent, ['COMPRAR', 'AGENDAR'], true)) return 'AGENDAR_LLAMADA';
        if ($intent === 'PROPUESTA')  return 'ENVIAR_PROPUESTA';
        if ($intent === 'ASESOR')     return 'AGENDAR_LLAMADA';
        if ($intent === 'DESPEDIDA')  return 'CIERRE';

        // Progresión natural según lo que ya sabemos del cliente.
        $tieneRubro  = !empty($datos['rubro']) || !empty($datos['negocio']);
        if (!$tieneRubro)  return 'IDENTIFICAR_NEGOCIO';
        if (in_array($actual, ['SALUDO', 'IDENTIFICAR_NEGOCIO'], true)) {
            return 'ENTENDER_NECESIDAD';
        }

        // Con rubro y nombre: si pregunta algo, resolvemos dudas; si no, avanzamos.
        if ($intent !== 'ABIERTA' && $intent !== 'SALUDO') return 'RESOLVER_DUDAS';

        return $actual === 'SALUDO' ? 'ENTENDER_NECESIDAD' : $actual;
    }

    /** Instrucción concreta para el asesor según intención y etapa. */
    private static function instruccion(string $intent, string $etapa, array $datos, ?string $opcionElegida = null, bool $yaPresentada = false): string
    {
        $rubro  = $datos['rubro'] ?? $datos['negocio'] ?? null;
        $nombre = $datos['nombre'] ?? null;
        $ctxRubro = $rubro ? "El cliente tiene: $rubro. Adapta el ejemplo a ese rubro." : '';

        return match (true) {
            $intent === 'SELECCION_LISTA' && $opcionElegida !== null =>
                self::instruccionSeleccion($opcionElegida, $rubro),

            $yaPresentada && !$rubro =>
                'Ya te presentaste con el mensaje de bienvenida (saludo, catálogos y demo). '
                . 'NO vuelvas a saludar ni a presentarte. Ve directo a preguntar a qué se '
                . 'dedica su negocio, de forma breve y natural.',

            $intent === 'SALUDO' && !$rubro =>
                'Preséntate como Valeria de Eskala, saluda breve y pregunta a qué se '
                . 'dedica su negocio. No hables del precio todavía.',

            $intent === 'PRECIO' =>
                "Da el precio exacto del dato, agrega en una línea qué logra con eso, y "
                . ($rubro ? "sigue conversando sobre su $rubro." : "pregunta a qué se dedica su negocio."),

            $intent === 'CANTIDAD_PRODUCTOS' =>
                'Responde usando el dato exacto sobre la cantidad de productos. Nunca digas que una cantidad '
                . 'mayor a 200 está incluida en la carga inicial. No hagas otra pregunta de calificación; ofrece '
                . 'los siguientes pasos de forma natural.',

            in_array($intent, ['INCLUYE', 'PANEL', 'PRODUCTOS', 'CATEGORIAS', 'PAGOS', 'ENVIOS',
                               'SEO', 'WHATSAPP', 'QR', 'RECLAMACIONES', 'CAPACITACION',
                               'HOSTING', 'RENOVACION', 'RESPONSIVE'], true) =>
                "Responde usando el dato tal cual (no inventes). Sé breve. $ctxRubro",

            in_array($intent, ['BOT', 'META_ADS', 'INSTITUCIONAL'], true) =>
                'Explica que es un servicio opcional con su precio exacto, sin presionar.',

            $intent === 'TIEMPO' || $intent === 'FORMA_PAGO' =>
                'Responde que eso se coordina con el asesor y propón la llamada. Incluye '
                . 'OBLIGATORIAMENTE la "lista" con opciones de horario.',

            $intent === 'EJEMPLO' =>
                'Comparte los casos de éxito AHORA MISMO, en este mismo turno: cada uno con su breve '
                . 'descripción y su enlace, en el orden indicado. No preguntes primero si quiere verlos '
                . 'ni anuncies que se los vas a enviar en otro mensaje — mándalos de una vez. Luego, de '
                . 'forma natural, pídele que te comparta un diseño o plantilla de referencia que le guste.',

            $intent === 'VIDEO' =>
                'Comparte el enlace del video tal cual y dile que ahí ve lo sencillo que es cargar productos.',

            $intent === 'PLANTILLA' =>
                'Pídele que comparta un formato o diseño de referencia que le guste; el equipo lo '
                . 'adapta. Y propón agendar la llamada para coordinar los detalles.',

            $intent === 'SISTEMAS' =>
                'Cuéntale que Eskala también desarrolla sistemas de gestión empresarial a medida '
                . '(no solo tiendas virtuales) y comparte los ejemplos del dato tal cual. Pregunta '
                . 'qué tipo de sistema o proceso le gustaría digitalizar.',

            $intent === 'DEMO' =>
                'Ofrécele preparar una demo personalizada según el rubro de su negocio, para que vea '
                . 'cómo quedaría su propia tienda virtual. Para prepararla, pide el dato que falte '
                . '(rubro o nombre del negocio) de forma natural.',

            $intent === 'PROPUESTA' || $etapa === 'ENVIAR_PROPUESTA' =>
                'Ofrece enviarle la propuesta con todo el detalle. Si aún no tienes su correo, pídeselo.',

            $etapa === 'AGENDAR_LLAMADA' =>
                'El cliente está interesado. Propón una llamada de 15 minutos y OBLIGATORIAMENTE '
                . 'incluye la "lista" con opciones de horario: "Hoy en la mañana", "Hoy en la tarde", '
                . '"Mañana temprano", "Mañana en la tarde".',

            $intent === 'ASESOR' =>
                'Confirma que un asesor lo contactará y pide el mejor horario.',

            $intent === 'DESPEDIDA' =>
                'Despídete cordial, sin insistir. Deja la puerta abierta.',

            $etapa === 'IDENTIFICAR_NEGOCIO' =>
                'Aún no sabes a qué se dedica. Pregúntalo de forma natural (abierto, no con lista).',

            $etapa === 'ENTENDER_NECESIDAD' =>
                "Ya sabes su rubro. NO hagas más preguntas: explícale en 2-3 líneas cómo una "
                . "tienda virtual ayuda a su negocio. $ctxRubro "
                . "Cierra usando la lista de siguiente paso.",

            default =>
                "Responde la consulta con naturalidad y sigue la conversación. $ctxRubro"
                . ($nombre ? " El cliente se llama $nombre." : ''),
        };
    }

    /** Configuración central editable, con respaldo para instalaciones antiguas. */
    private static function datos(): array
    {
        return (array) config('asesor_comercial.datos', []);
    }

    /** Las listas tocables son deterministas para no depender de que la IA las invente. */
    private static function listaPara(string $intent, string $etapa): ?array
    {
        $nombre = match (true) {
            in_array($intent, ['TIEMPO', 'FORMA_PAGO', 'AGENDAR', 'ASESOR', 'COMPRAR'], true),
            $etapa === 'AGENDAR_LLAMADA' => 'horarios',
            $etapa === 'ENVIAR_PROPUESTA' => 'cierre',
            $etapa === 'ENTENDER_NECESIDAD' || $etapa === 'EXPLICAR_BENEFICIO' => 'siguiente_paso',
            default => null,
        };

        return $nombre ? config("asesor_comercial.listas.$nombre") : null;
    }

    /**
     * Si hay una lista tocable pendiente y el mensaje es solo un número dentro
     * de su rango (o el texto exacto de una opción), devuelve el índice elegido
     * (base 0). Así "1" tras una lista de 3 opciones se lee como selección y
     * nunca se confunde con "cantidad de productos".
     */
    private static function indiceSeleccionLista(string $mensaje, ?array $listaPendiente): ?int
    {
        if (empty($listaPendiente)) return null;
        $t = trim(self::normalizar($mensaje));

        if (preg_match('/^([1-9])$/', $t, $m)) {
            $i = ((int) $m[1]) - 1;
            return $i < count($listaPendiente) ? $i : null;
        }
        foreach ($listaPendiente as $i => $opcion) {
            if ($t === self::normalizar((string) $opcion)) return $i;
        }
        return null;
    }

    /** Traduce la opción de lista elegida a la acción concreta que debe tomar la IA. */
    private static function instruccionSeleccion(string $opcion, ?string $rubro): string
    {
        $t = self::normalizar($opcion);
        $ctxRubro = $rubro ? " Adapta a su rubro: $rubro." : '';

        return match (true) {
            str_contains($t, 'demo') =>
                'El cliente eligió VER LA DEMO. Indica que le prepararás la demo correspondiente a su '
                . 'rubro y explica brevemente qué podrá encontrar en ella (su catálogo, su marca, sus '
                . 'productos). No envíes otros enlaces ni ejemplos junto con esto. Si aún no tienes el '
                . 'nombre del negocio, pídelo para prepararla.' . $ctxRubro,

            str_contains($t, 'mas informacion') || str_contains($t, 'informacion') =>
                'El cliente eligió QUIERO MÁS INFORMACIÓN. Explícale con más detalle qué incluye el '
                . 'servicio (dato INCLUYE) antes de mencionar precio.' . $ctxRubro,

            str_contains($t, 'ejemplo') =>
                'El cliente eligió VER EJEMPLOS. Comparte los casos de éxito (dato EJEMPLO) con su '
                . 'breve descripción y enlace, en el orden indicado, en un solo mensaje.' . $ctxRubro,

            str_contains($t, 'propuesta') =>
                'El cliente eligió RECIBIR PROPUESTA. Agradece y pide los datos que falten '
                . '(nombre del negocio, correo, ciudad) para prepararla.',

            str_contains($t, 'reunion') || str_contains($t, 'llamada') || str_contains($t, 'agendar') =>
                'El cliente eligió AGENDAR REUNIÓN. Ofrece horarios con la lista tocable de horarios.',

            str_contains($t, 'duda') =>
                'El cliente eligió RESOLVER DUDAS PRIMERO. Pregúntale qué duda tiene, de forma abierta.',

            default =>
                "El cliente eligió: \"$opcion\". Continúa la conversación respondiendo a esa elección de "
                . 'forma natural, sin repetir la pregunta que ya hiciste.' . $ctxRubro,
        };
    }

    /** Detecta una cantidad aislada, típica cuando el asesor preguntó por productos. */
    private static function cantidadProductos(string $mensaje): ?int
    {
        $texto = trim(self::normalizar($mensaje));
        if (preg_match('/^([1-9][0-9]{0,4})$/', $texto, $m)) return (int) $m[1];
        if (preg_match('/\b([1-9][0-9]{0,4})\s*(productos?|articulos?|items?)\b/', $texto, $m)) return (int) $m[1];
        return null;
    }

    private static function mensajeCantidadProductos(int $cantidad): string
    {
        return $cantidad <= 200
            ? "El cliente indicó aproximadamente $cantidad productos. Esa cantidad entra en la carga inicial de hasta 200 productos."
            : "El cliente indicó aproximadamente $cantidad productos. La carga inicial incluye hasta 200 productos; los " . ($cantidad - 200) . " adicionales no están incluidos y se deben evaluar con el asesor. Nunca digas que los $cantidad están incluidos.";
    }

    private static function normalizar(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
        $s = preg_replace('/[^a-z0-9 ]/', ' ', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    }
}
