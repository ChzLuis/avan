<?php

namespace App\Modules\Bots\Support;

use App\Modules\Bots\Ia\IA;
use Throwable;

/**
 * Clasificación de leads con DOBLE NIVEL:
 *
 *   - porReglas():  sin IA. Rápido, gratis, sin API key. Plan base.
 *   - porIa():      con IA. Lee la conversación completa. Plan avanzado (upgrade).
 *
 * Ambos devuelven la misma forma:
 *   ['score' => 0-100, 'temp' => 'caliente|tibio|frio|nuevo', 'motivo' => '', 'source' => 'reglas|ia']
 *
 * Así la extensión y el CRM consumen igual, exista o no la key de IA.
 */
class LeadScoring
{
    /** Palabras que suben la temperatura (señales de compra). */
    private const SENAL_COMPRA = [
        'comprar', 'lo quiero', 'cuando puedo', 'cómo pago', 'como pago',
        'transferencia', 'yape', 'plin', 'factura', 'cierro', 'coordinemos',
        'reunión', 'reunion', 'firmamos', 'contrato', 'adelanto', 'depósito', 'deposito',
    ];

    /** Señales de interés medio. */
    private const SENAL_INTERES = [
        'precio', 'costo', 'cuánto', 'cuanto', 'cotización', 'cotizacion',
        'descuento', 'promoción', 'promocion', 'catálogo', 'catalogo', 'demo',
        'información', 'informacion', 'interesa',
    ];

    /** Señales de objeción / enfriamiento. */
    private const SENAL_OBJECION = [
        'caro', 'costoso', 'muy alto', 'lo pensaré', 'lo pensare', 'después',
        'despues', 'más adelante', 'mas adelante', 'no gracias', 'otra ocasión',
    ];

    // ─────────────────────────────────────────────────────────
    //  NIVEL BASE — sin IA (reglas)
    // ─────────────────────────────────────────────────────────
    public static function porReglas(string $conversacion, ?int $horasSinResponder = null): array
    {
        $t = mb_strtolower($conversacion);
        $score = 35; // base neutra

        // Señales de compra: primera vale mucho, cada extra suma un poco (varias
        // señales fuertes juntas = lead caliente).
        $compras = 0;
        foreach (self::SENAL_COMPRA as $k)   if (str_contains($t, $k)) $compras++;
        if ($compras > 0) $score += 25 + min(($compras - 1) * 8, 24);

        $tieneInteres = false;
        foreach (self::SENAL_INTERES as $k)  if (str_contains($t, $k)) { if (!$tieneInteres) $score += 12; $tieneInteres = true; }
        foreach (self::SENAL_OBJECION as $k) if (str_contains($t, $k)) $score -= 18;

        // Penalización por inactividad (cliente olvidado / enfriándose),
        // acotada para no colapsar a 0 (un lead frío no es "nuevo").
        if ($horasSinResponder !== null) {
            if ($horasSinResponder > 72) $score -= 15;
            elseif ($horasSinResponder > 24) $score -= 8;
        }

        // Un lead con conversación real nunca baja de 10 (no es "nuevo/sin datos").
        $score = max(trim($conversacion) === '' ? 0 : 10, min(100, $score));

        return [
            'score'  => $score,
            'temp'   => self::temp($score),
            'motivo' => self::motivoReglas($t, $horasSinResponder),
            'source' => 'reglas',
        ];
    }

    // ─────────────────────────────────────────────────────────
    //  NIVEL AVANZADO — con IA (upgrade)
    // ─────────────────────────────────────────────────────────
    public static function porIa(string $conversacion): array
    {
        $res = IA::scoreCierre($conversacion); // {score, motivo}
        $score = max(0, min(100, (int) ($res['score'] ?? 0)));

        return [
            'score'  => $score,
            'temp'   => self::temp($score),
            'motivo' => (string) ($res['motivo'] ?? ''),
            'source' => 'ia',
        ];
    }

    /**
     * Clasifica usando IA si hay proveedor con key; si no, cae a reglas.
     * Es el punto de entrada recomendado: siempre devuelve algo útil.
     */
    public static function clasificar(string $conversacion, ?int $horasSinResponder = null): array
    {
        try {
            if (self::hayIa()) {
                return self::porIa($conversacion);
            }
        } catch (Throwable) {
            // Si la IA falla (key inválida, timeout), degradar a reglas sin romper.
        }
        return self::porReglas($conversacion, $horasSinResponder);
    }

    /** ¿Hay un proveedor de IA con key configurada? */
    public static function hayIa(): bool
    {
        $p = config('ia.provider');
        return !empty(config("ia.providers.$p.key"));
    }

    // ── helpers ──
    private static function temp(int $score): string
    {
        if ($score >= 70) return 'caliente';
        if ($score >= 40) return 'tibio';
        if ($score > 0)   return 'frio';
        return 'nuevo';
    }

    private static function motivoReglas(string $t, ?int $horas): string
    {
        foreach (self::SENAL_COMPRA as $k) {
            if (str_contains($t, $k)) return 'Señales de compra en la conversación.';
        }
        if ($horas !== null && $horas > 72) return 'Más de 3 días sin respuesta: lead enfriándose.';
        foreach (self::SENAL_INTERES as $k) {
            if (str_contains($t, $k)) return 'Cliente pidió precio/información: interés medio.';
        }
        foreach (self::SENAL_OBJECION as $k) {
            if (str_contains($t, $k)) return 'Se detectó una objeción.';
        }
        return 'Sin señales fuertes todavía.';
    }
}
