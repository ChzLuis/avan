<?php

namespace App\Modules\Finanzas\Jobs;

use App\Modules\Finanzas\Models\GuiaRemision;
use App\Modules\Finanzas\Support\Sunat\GuiaRemisionBaja;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Comunica a SUNAT que una guía queda sin efecto, sin hacer esperar a quien
 * la anula. Si falla, la guía queda con el motivo a la vista y se puede
 * reintentar, en vez de quedarse en "anulando..." para siempre.
 */
class DarDeBajaGuiaEnSunat implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(public int $guiaId) {}

    public function handle(): void
    {
        // Job de sistema: búsqueda explícita fuera del scope de sesión.
        $guia = GuiaRemision::allProjects()->with('project')->find($this->guiaId);

        if (! $guia || $guia->baja_estado === 'accepted') {
            return;
        }

        app()->instance('active_project', $guia->project);

        (new GuiaRemisionBaja())->anular($guia);
    }

    public function failed(\Throwable $e): void
    {
        // allProjects(): un job no depende de la sesión. Con cola sync este
        // update corre DENTRO de la petición web, y el scope fail-closed lo
        // dejaría en silencio sin escribir: la guía quedaría "anulando…" para
        // siempre. La guía sigue VIVA: si la baja no entró, surte efecto.
        GuiaRemision::allProjects()->where('id', $this->guiaId)->update([
            'baja_estado' => 'rejected',
            'baja_error'  => 'No se pudo comunicar la baja: '.$e->getMessage(),
            'status'      => 'issued',
        ]);
    }
}
