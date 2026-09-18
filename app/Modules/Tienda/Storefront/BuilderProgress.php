<?php

namespace App\Modules\Tienda\Storefront;

use App\Models\Project;

/**
 * Progreso del Constructor (B0): porcentaje ponderado por reglas reales
 * del BuilderRuleRegistry — nunca por "etapa visitada".
 */
class BuilderProgress
{
    public static function for(Project $project, ?array $context = null): array
    {
        $results = BuilderRuleRegistry::evaluate($project, $context);

        $stages = [];
        foreach (BuilderRuleRegistry::STAGES as $key => $meta) {
            $stageRules = array_values(array_filter($results, fn ($r) => $r['stage'] === $key));
            $stages[$key] = self::stageSummary($key, $meta, $stageRules);
        }

        // La etapa "publish" se deriva del resto: bloqueada mientras haya críticos.
        $criticalsLeft = collect($results)->where('blocks_publish', true)->count();
        $stages['publish']['state'] = $criticalsLeft > 0 ? 'blocked' : ($stages['publish']['state'] ?? 'pending');
        $stages['publish']['pending_count'] = $criticalsLeft;

        $totalWeight = array_sum(array_column($results, 'weight')) ?: 1;
        $doneWeight = array_sum(array_map(fn ($r) => $r['complete'] ? $r['weight'] : 0, $results));

        return [
            'percent' => (int) round($doneWeight * 100 / $totalWeight),
            'stages' => $stages,
            'criticals' => $criticalsLeft,
            'warnings' => collect($results)->where('complete', false)->whereIn('severity', ['attention'])->count(),
            'recommendations' => collect($results)->where('complete', false)->whereIn('severity', ['recommendation', 'info'])->count(),
            'pending' => collect($results)->where('complete', false)->values()->map(fn ($r) => [
                'code' => $r['code'], 'stage' => $r['stage'], 'severity' => $r['severity'],
                'message' => $r['message'], 'target' => $r['target'], 'count' => $r['count'],
            ])->all(),
        ];
    }

    private static function stageSummary(string $key, array $meta, array $rules): array
    {
        $weight = array_sum(array_column($rules, 'weight')) ?: 1;
        $done = array_sum(array_map(fn ($r) => $r['complete'] ? $r['weight'] : 0, $rules));
        $incomplete = array_values(array_filter($rules, fn ($r) => !$r['complete']));
        $severities = array_column($incomplete, 'severity');

        $state = match (true) {
            $rules === [] => 'complete',
            $incomplete === [] => 'complete',
            in_array('attention', $severities, true) => 'warning',
            in_array('recommendation', $severities, true) => 'warning',
            default => 'in_progress',
        };

        $percent = $rules === [] ? 100 : (int) round($done * 100 / $weight);

        return [
            'key' => $key,
            'label' => $meta['label'],
            'estimated_minutes' => $meta['minutes'],
            'remaining_minutes' => (int) round($meta['minutes'] * (100 - $percent) / 100),
            'percent' => $percent,
            'state' => $state,
            'pending_count' => count($incomplete),
            'max_severity' => $severities === [] ? 'complete'
                : (in_array('attention', $severities, true) ? 'critical'
                    : (in_array('recommendation', $severities, true) ? 'warning' : 'recommendation')),
        ];
    }
}
