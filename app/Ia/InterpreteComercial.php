<?php

namespace App\Ia;

use App\Models\Project;
use Illuminate\Support\Facades\Log;

/**
 * Intérprete del Bot Comercial: la ÚNICA puerta de la IA en el bot.
 *
 * Su trabajo es interpretar el mensaje del cliente y devolver un contrato
 * estricto ({intent, consulta}); nada más. No ejecuta acciones, no consulta
 * tablas, no responde al cliente y no recibe el catálogo: la existencia de un
 * producto, su precio y todo dato comercial los resuelve siempre
 * ProjectContext después.
 *
 * Cualquier fallo —sin licencia, apagada, sin key, timeout, JSON roto, intent
 * fuera de la lista, proveedor caído— devuelve null, y null significa "sigue
 * por el motor estándar con el mensaje original". La IA es una mejora; nunca
 * una dependencia.
 */
class InterpreteComercial
{
    /** Módulo que da DERECHO a usar IA (licencia por proyecto). */
    public const MODULO = 'bot_ia';

    /** Setting que la ENCIENDE una vez licenciada. */
    public const AJUSTE = 'feature_bot_ia';

    /**
     * Los únicos intents que el modelo puede devolver, mapeados a la rama del
     * flujo que ya existe. Lo que no esté aquí se rechaza: el modelo no puede
     * inventar capacidades.
     */
    public const INTENTS = [
        'product_search'   => 'producto',
        'product_price'    => 'producto',
        'promotion'        => 'promociones',
        'payment_methods'  => 'pagos',
        'business_location'=> 'direccion',
        'business_hours'   => 'horario',
        'business_contact' => 'contacto',
        'business_info'    => 'empresa',
        'website'          => 'web',
        'guide'            => 'guia',
        'faq'              => 'faq',
        'product_recommendation' => 'recomendacion',
        'product_comparison'     => 'comparacion',
        'human_handoff'    => 'asesor',
        'unknown'          => null,
    ];

    // ── Licencia y activación (dos conceptos separados) ──────────────────────

    /** ¿La empresa CONTRATÓ IA? (módulo por proyecto, como cualquier licencia) */
    public static function licenciado(Project $project): bool
    {
        return $project->hasModule(self::MODULO);
    }

    /** ¿La empresa la tiene ENCENDIDA? (activable solo si está licenciada) */
    public static function activo(Project $project): bool
    {
        return (string) $project->setting(self::AJUSTE, '0') === '1';
    }

    /**
     * ¿Puede ejecutarse el intérprete AHORA? Licencia + encendida + key del
     * proveedor presente. Sin las tres, ni siquiera se toca App\Ia\IA: cero
     * consumo, cero riesgo.
     */
    public static function habilitado(Project $project): bool
    {
        if (! self::licenciado($project) || ! self::activo($project)) {
            return false;
        }

        $proveedor = config('ia.provider');

        return filled(config("ia.providers.{$proveedor}.key"));
    }

    // ── Interpretación ───────────────────────────────────────────────────────

    /**
     * Interpreta un mensaje. Devuelve el contrato validado o null (= motor
     * estándar). El contexto es MÍNIMO y sale de nuestra sesión, jamás del
     * modelo: intent previo y nombres de los últimos resultados.
     *
     * @param  array{intent_previo?: string, resultados?: array<string>}  $contexto
     * @return array{intent: string, ruta: ?string, consulta: string}|null
     */
    public static function interpretar(Project $project, string $mensaje, array $contexto = []): ?array
    {
        if (! self::habilitado($project)) {
            return null;
        }

        $inicio = microtime(true);
        $proveedor = (string) config('ia.provider');
        $modelo = (string) config("ia.providers.{$proveedor}.model");

        try {
            $crudo = IA::provider()->chat(
                self::prompt($mensaje, $contexto),
                [
                    'temperature' => 0,
                    'max_tokens'  => (int) config('ia.interprete.max_tokens', 200),
                    'timeout'     => (int) config('ia.interprete.timeout', 8),
                ]
            );

            $contrato = self::validar($crudo);

            self::telemetria($project, $proveedor, $modelo, $inicio, [
                'ok'     => $contrato !== null,
                'intent' => $contrato['intent'] ?? 'invalido',
            ]);

            return $contrato;
        } catch (\Throwable $e) {
            // Da igual el motivo (timeout, 500 del proveedor, red): el bot
            // sigue por reglas y el cliente nunca ve un error técnico.
            self::telemetria($project, $proveedor, $modelo, $inicio, [
                'ok'    => false,
                'error' => class_basename($e),
            ]);

            return null;
        }
    }

    /**
     * Prompt mínimo: la lista cerrada de intents y el mensaje. Sin catálogo,
     * sin precios, sin datos del negocio — el intérprete no los necesita y
     * meterlos solo suma costo, latencia y riesgo de alucinación.
     */
    private static function prompt(string $mensaje, array $contexto): array
    {
        $intents = implode(', ', array_keys(self::INTENTS));

        $sistema = "Clasificas mensajes de clientes de una tienda (español de Perú). "
            . "Responde SOLO un JSON: {\"intent\":\"...\",\"consulta\":\"...\"}. "
            . "intent debe ser uno de: {$intents}. "
            . "presupuesto: numero en soles si el cliente menciona un tope, si no null. "
            . "consulta: para product_search/product_price/product_recommendation, el producto buscado "
            . "limpio y con errores de tipeo corregidos (ej. 'samung'->'samsung'); si no aplica, \"\". "
            . "Nunca inventes productos, precios ni datos. Si dudas, usa unknown.";

        $lineas = [];
        if (! empty($contexto['intent_previo'])) {
            $lineas[] = 'Intent previo: ' . mb_substr((string) $contexto['intent_previo'], 0, 40);
        }
        if (! empty($contexto['resultados'])) {
            $lineas[] = 'Resultados en pantalla: ' . mb_substr(implode(' | ', array_slice((array) $contexto['resultados'], 0, 5)), 0, 220);
        }
        $lineas[] = 'Mensaje: ' . mb_substr($mensaje, 0, 400);

        return [
            ['role' => 'system', 'content' => $sistema],
            ['role' => 'user', 'content' => implode("\n", $lineas)],
        ];
    }

    /**
     * Validación estricta del contrato. Todo lo que no cumpla exactamente se
     * descarta entero: JSON inválido, intent fuera de la lista, tipos raros.
     * Los campos extra que el modelo agregue (un precio, un producto) se
     * IGNORAN: no existen para el sistema.
     */
    public static function validar(?string $crudo): ?array
    {
        if (! is_string($crudo) || trim($crudo) === '') {
            return null;
        }

        // El modelo a veces envuelve el JSON en ```json ... ```.
        $limpio = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($crudo)));
        $datos = json_decode($limpio, true);
        if (! is_array($datos)) {
            return null;
        }

        $intent = $datos['intent'] ?? null;
        if (! is_string($intent) || ! array_key_exists($intent, self::INTENTS)) {
            return null;
        }

        $consulta = $datos['consulta'] ?? '';
        if (! is_string($consulta)) {
            return null;
        }

        // Presupuesto: numero positivo y razonable, o nada. Como todo lo que
        // viene del modelo, es una PISTA para filtrar; el precio que se muestra
        // sigue saliendo de la base.
        $presupuesto = $datos['presupuesto'] ?? null;
        if ($presupuesto !== null) {
            $presupuesto = is_numeric($presupuesto) ? (float) $presupuesto : null;
            if ($presupuesto !== null && ($presupuesto <= 0 || $presupuesto > 1000000)) $presupuesto = null;
        }
        // Texto plano acotado: nunca viaja a SQL crudo (la búsqueda ya liga
        // parámetros), pero igual se poda a caracteres inofensivos.
        $consulta = mb_substr(trim(preg_replace('/[^\p{L}\p{N} .,\-\/]/u', ' ', $consulta)), 0, 120);

        return [
            'intent'      => $intent,
            'ruta'        => self::INTENTS[$intent],
            'consulta'    => $consulta,
            'presupuesto' => $presupuesto,
        ];
    }

    /**
     * Telemetría de cada uso: quién, con qué proveedor, cuánto tardó y en qué
     * terminó. Sin claves, sin el texto del cliente (dato personal): solo lo
     * necesario para operar y facturar después.
     */
    private static function telemetria(Project $project, string $proveedor, string $modelo, float $inicio, array $extra): void
    {
        Log::info('bot_ia.interprete', array_merge([
            'proyecto'  => $project->id,
            'proveedor' => $proveedor,
            'modelo'    => $modelo,
            'ms'        => (int) round((microtime(true) - $inicio) * 1000),
        ], $extra));
    }
}
