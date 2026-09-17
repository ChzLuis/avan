<?php

namespace App\Console\Commands;

use App\Jobs\EnviarGuiaASunat;
use App\Jobs\SendInvoiceToSunat;
use App\Models\GuiaRemision;
use App\Models\Invoice;
use Illuminate\Console\Command;

/**
 * Ningún comprobante muere en silencio.
 *
 * SUNAT solo acepta envíos hasta 3 días calendario después de la emisión: un
 * comprobante que falló al enviarse (proveedor caído, corte de internet) y que
 * nadie mira durante esos días ya no se puede regularizar nunca. Hasta ahora
 * la única alarma era un badge rojo en una lista que alguien tenía que abrir.
 *
 * Este comando pasa cada hora y reintenta todo lo que sigue dentro del plazo:
 *
 *   - comprobantes en 'error', o clavados en 'pending' más de 30 minutos
 *     (un job perdido deja ese estado para siempre);
 *   - guías en el mismo estado cuyo traslado aún no ha ocurrido — reenviar la
 *     guía de un traslado que ya pasó no arregla nada.
 *
 * Lo que quedó fuera del plazo no se reintenta: se informa, porque ya solo
 * queda emitir un comprobante nuevo con fecha vigente.
 */
class ReintentarComprobantes extends Command
{
    protected $signature = 'facturacion:reintentar {--dry-run : Solo contar, sin encolar}';

    protected $description = 'Reencola comprobantes y guías con envío fallido que siguen dentro del plazo de SUNAT';

    public function handle(): int
    {
        $limiteEmision = now()->subDays(3)->toDateString();
        $pendienteViejo = now()->subMinutes(30);

        // ── Comprobantes dentro del plazo de 3 días ───────────────────────
        $comprobantes = Invoice::allProjects()
            ->whereDate('issue_date', '>=', $limiteEmision)
            ->where(function ($q) use ($pendienteViejo) {
                $q->where('sunat_status', 'error')
                  ->orWhere(function ($q) use ($pendienteViejo) {
                      $q->where('sunat_status', 'pending')
                        ->where('updated_at', '<', $pendienteViejo);
                  })
                  /* El estado mudo: emitido pero con sunat_status en NULL.
                     No es un error ni un pendiente, asi que esta consulta no
                     lo veia y el comprobante llegaba al plazo de 3 dias sin
                     que nadie se enterase. Un emitido sin declarar siempre es
                     un fallo, venga de un job perdido o de una emision vieja
                     anterior al envio automatico. El borrador queda fuera:
                     todavia no es un comprobante. */
                  ->orWhere(function ($q) {
                      $q->whereNull('sunat_status')
                        ->whereNotIn('status', ['draft', 'cancelled']);
                  });
            })
            ->get();

        foreach ($comprobantes as $c) {
            $this->line("  comprobante {$c->numero} ({$c->sunat_status}) → reintento");
            if (! $this->option('dry-run')) {
                $c->update(['sunat_status' => 'pending']);
                SendInvoiceToSunat::dispatch($c->id);
            }
        }

        // ── Guías cuyo traslado aún no ocurre ─────────────────────────────
        $guias = GuiaRemision::allProjects()
            ->whereDate('fecha_traslado', '>=', now()->toDateString())
            ->where(function ($q) use ($pendienteViejo) {
                $q->where('sunat_status', 'error')
                  ->orWhere(function ($q) use ($pendienteViejo) {
                      $q->where('sunat_status', 'pending')
                        ->where('updated_at', '<', $pendienteViejo);
                  });
            })
            ->get();

        foreach ($guias as $g) {
            $this->line("  guía {$g->numero} ({$g->sunat_status}) → reintento");
            if (! $this->option('dry-run')) {
                $g->update(['sunat_status' => 'pending', 'sunat_error' => null]);
                EnviarGuiaASunat::dispatch($g->id);
            }
        }

        // ── Resúmenes diarios de baja de boletas: SUNAT responde después ──
        $resumenes = Invoice::allProjects()
            ->where('type', 'boleta')
            ->where('baja_estado', 'pending')
            ->whereNotNull('baja_ticket')
            ->with('project')
            ->get();
        foreach ($resumenes as $b) {
            if ($this->option('dry-run')) { $this->line("  boleta {$b->numero}: consultaría el ticket {$b->baja_ticket}"); continue; }
            app()->instance('active_project', $b->project);
            $r = (new \App\Support\ApisPeruService())->consultarResumen($b);
            $this->line("  boleta {$b->numero}: resumen ".($r['estado'] ?? '?'));
        }

        // ── Lo ya perdido se dice, no se disimula ─────────────────────────
        $vencidos = Invoice::allProjects()
            ->whereDate('issue_date', '<', $limiteEmision)
            ->whereIn('sunat_status', ['error', 'pending'])
            ->count();

        if ($vencidos > 0) {
            $this->warn("  {$vencidos} comprobante(s) fuera del plazo de SUNAT: ya no se pueden enviar; hay que emitirlos de nuevo con fecha vigente.");
        }

        $this->info(sprintf('Reintentados: %d comprobantes, %d guías. Vencidos: %d.',
            $comprobantes->count(), $guias->count(), $vencidos));

        return self::SUCCESS;
    }
}
