<?php

namespace App\Modules\Tienda\Storefront;

use App\Models\Project;

/**
 * Checklist de publicación (B0): mismas reglas que el progreso
 * (BuilderRuleRegistry), agrupadas por severidad.
 *
 * FILOSOFÍA: la calidad del contenido NUNCA bloquea publicar — solo advierte.
 * Únicamente bloquean reglas con blocks_publish=true (errores técnicos reales:
 * datos inválidos, integridad). "Atención" y "Mejoras" piden confirmación.
 */
class PublishChecklist
{
    public static function for(Project $project, ?array $context = null): array
    {
        $results = BuilderRuleRegistry::evaluate($project, $context);
        $pending = array_values(array_filter($results, fn ($r) => !$r['complete']));

        $group = fn (string $severity) => array_values(array_filter($pending, fn ($r) => $r['severity'] === $severity));

        $attention = $group('attention');
        $technicalBlocks = array_values(array_filter($pending, fn ($r) => $r['blocks_publish']));

        return [
            'can_publish' => $technicalBlocks === [],
            // Lo que IMPIDE publicar, separado de los avisos: la etapa 09 tiene
            // que poder decir exactamente que falta, no solo que no se puede.
            'blocking' => self::items($technicalBlocks),
            'requires_confirmation' => $technicalBlocks === [] && ($attention !== [] || $group('recommendation') !== []),
            'attention' => self::items($attention),
            'recommendation' => self::items($group('recommendation')),
            'info' => self::items($group('info')),
            // Alias legacy (compatibilidad con consumidores antiguos)
            'critical' => self::items($attention),
            'warning' => self::items($group('recommendation')),
            'complete' => self::items(array_values(array_filter($results, fn ($r) => $r['complete']))),
        ];
    }

    private static function items(array $rules): array
    {
        return array_map(fn ($r) => [
            'code' => $r['code'],
            'stage' => $r['stage'],
            'message' => $r['message'],
            'target' => $r['target'],
            'count' => $r['count'],
            'blocks_publish' => $r['blocks_publish'],
        ], $rules);
    }
}
