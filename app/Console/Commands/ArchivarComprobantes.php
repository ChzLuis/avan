<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Support\Sunat\ArchivoComprobantes;
use Illuminate\Console\Command;

/**
 * Archiva como ficheros el XML firmado y el CDR de los comprobantes ya
 * aceptados que solo los tenían dentro de la columna `sunat_cdr`.
 *
 * Se ejecuta una vez tras el despliegue (2026-09-05) y es idempotente: lo
 * que ya está en disco no se reescribe salvo con --forzar.
 */
class ArchivarComprobantes extends Command
{
    protected $signature = 'facturacion:archivar {--proyecto= : Solo este project_id} {--forzar : Reescribir aunque exista}';
    protected $description = 'Guarda en disco el XML y el CDR de los comprobantes aceptados por SUNAT';

    public function handle(): int
    {
        $q = Invoice::allProjects()->where('sunat_status', 'accepted')->whereNotNull('sunat_cdr');
        if ($this->option('proyecto')) {
            $q->where('project_id', (int) $this->option('proyecto'));
        }

        $hechos = 0; $saltados = 0; $sinDatos = 0;
        foreach ($q->cursor() as $inv) {
            $estado = ArchivoComprobantes::estado($inv);
            if (! $this->option('forzar') && $estado['xml'] && $estado['cdr']) {
                $saltados++;
                continue;
            }
            $r = ArchivoComprobantes::guardar($inv);
            if ($r['xml'] || $r['cdr']) {
                $hechos++;
                $this->line("  {$inv->numero}: xml=".($r['xml'] ? 'sí' : 'no').' cdr='.($r['cdr'] ? 'sí' : 'no'));
            } else {
                $sinDatos++;
                $this->warn("  {$inv->numero}: la respuesta guardada no trae XML ni CDR");
            }
        }

        $this->info("Archivados: {$hechos}. Ya estaban: {$saltados}. Sin datos: {$sinDatos}.");

        return self::SUCCESS;
    }
}
