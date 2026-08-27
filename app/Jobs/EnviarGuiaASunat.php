<?php

namespace App\Jobs;

use App\Models\GuiaRemision;
use App\Support\Sunat\GuiaRemisionSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Manda la guía a SUNAT sin hacer esperar a quien despacha el camión.
 *
 * Si falla, la guía queda con el motivo a la vista y se puede reintentar: una
 * guía en "enviando..." para siempre es un camión en la carretera con un
 * documento que nadie sabe si vale.
 */
class EnviarGuiaASunat implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(public int $guiaId) {}

    public function handle(): void
    {
        // Job de sistema: busqueda explicita fuera del scope de sesion.
        $guia = GuiaRemision::allProjects()->with('items', 'project', 'invoice')->find($this->guiaId);

        if (! $guia || $guia->sunat_status === 'accepted') {
            return;
        }

        app()->instance('active_project', $guia->project);

        (new GuiaRemisionSender())->enviar($guia);
    }

    public function failed(\Throwable $e): void
    {
        GuiaRemision::allProjects()->where('id', $this->guiaId)->update([
            'sunat_status' => 'error',
            'sunat_error'  => 'No se pudo enviar la guía: '.$e->getMessage(),
        ]);
    }
}
