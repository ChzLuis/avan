<?php

namespace App\Modules\Tienda\Commands;

use App\Modules\Tienda\Storefront\ConstructorAuditor;
use Illuminate\Console\Command;

/**
 * Auditoría automática del Constructor: duplicados, huérfanos, controles
 * inertes y escrituras a datos maestros fuera de la etapa 01.
 *
 * Sale con código 1 si alguna métrica no está en cero, para poder colgarla de
 * un hook o de CI y no volver a enterarse a mano.
 */
class AuditarConstructor extends Command
{
    protected $signature = 'bixo:auditar-constructor {--detalle : Lista cada caso}';

    protected $description = 'Verifica que cada configuración tenga un único propietario y un único editor';

    public function handle(): int
    {
        $r = ConstructorAuditor::make()->auditar();

        $metricas = [
            'DUPLICATE_EDITORS' => 'claves con más de un editor',
            'LIVE_SETTINGS_WITHOUT_EDITOR' => 'ajustes vivos sin editor oficial',
            'INERT_CONTROLS' => 'controles que escriben al vacío',
            'MASTER_DATA_WRITE_VIOLATIONS' => 'datos maestros editados fuera de 01',
            'UNCLASSIFIED' => 'claves sin etapa propietaria',
        ];

        $this->newLine();
        $this->line('  <options=bold>Auditoría del Constructor</> — '.count($r['claves']).' claves');
        $this->newLine();

        $fallo = false;
        foreach ($metricas as $clave => $glosa) {
            $n = $r[$clave];
            $fallo = $fallo || $n > 0;
            $this->line(sprintf('  %s %-32s %s  <fg=gray>%s</>',
                $n === 0 ? '<fg=green>✓</>' : '<fg=red>✗</>',
                $clave, str_pad((string) $n, 4, ' ', STR_PAD_LEFT), $glosa));
        }

        if ($this->option('detalle')) {
            foreach ($r['detalle'] as $tipo => $casos) {
                if ($casos === []) {
                    continue;
                }
                $this->newLine();
                $this->line('  <options=bold>'.strtoupper($tipo).'</>');
                foreach ($casos as $clave => $donde) {
                    $this->line(sprintf('    %-34s %s', is_int($clave) ? $donde : $clave,
                        is_array($donde) ? implode(', ', array_slice($donde, 0, 3)) : ''));
                }
            }
        }

        $this->newLine();
        $this->line($fallo
            ? '  <fg=red>Hay configuraciones sin dueño único.</> Usa --detalle para verlas.'
            : '  <fg=green>Cada configuración tiene un único propietario y un único editor.</>');
        $this->newLine();

        return $fallo ? self::FAILURE : self::SUCCESS;
    }
}
