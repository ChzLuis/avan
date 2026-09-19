<?php

namespace App\Modules\Crm\Commands;

use App\Modules\Crm\Models\CrmAccion;
use App\Modules\Crm\Models\PushSuscripcion;
use App\Modules\Crm\Support\WebPush\PushWeb;
use Illuminate\Console\Command;

/**
 * Cada minuto: las acciones que acaban de vencer avisan por push al asesor asignado
 * (o a todos los dispositivos del negocio si no hay asignado). Una sola vez por accion.
 */
class RecordarAcciones extends Command
{
    protected $signature = 'crm:recordar-acciones';
    protected $description = 'Envia el recordatorio push de las acciones del CRM que vencen';

    public function handle(): int
    {
        $pendientes = CrmAccion::whereNull('hecho_at')->whereNull('recordada_at')
            ->whereNotNull('vence_at')->where('vence_at', '<=', now())
            ->where('vence_at', '>=', now()->subDay()) // lo mas viejo ya no tiene sentido avisarlo
            ->with('conversacion')->limit(200)->get();
        $enviadas = 0;
        foreach ($pendientes as $a) {
            $q = PushSuscripcion::where('project_id', $a->project_id);
            if ($a->asignado_a) {
                $q->where('user_id', $a->asignado_a);
            }
            $conv = $a->conversacion;
            $datos = [
                'titulo' => '⏰ ' . $a->titulo,
                'cuerpo' => trim(($conv ? ($conv->cliente_nombre ?: $conv->cliente_telefono) . ' · ' : '') . ($a->vence_at?->locale('es')->isoFormat('ddd D MMM, HH:mm') ?? '')),
                'url'    => $conv ? url('/bixocrm?conversacion=' . $conv->id) : url('/bixocrm/acciones'),
                'tag'    => 'bx-accion-' . $a->id,
            ];
            foreach ($q->get() as $s) {
                $r = PushWeb::enviar($s->endpoint, $s->p256dh, $s->auth, $datos);
                if ($r['caducada']) $s->delete();
                if ($r['ok']) $enviadas++;
            }
            $a->forceFill(['recordada_at' => now()])->saveQuietly();
        }
        $this->info("Acciones vencidas: {$pendientes->count()} · avisos enviados: {$enviadas}");

        return self::SUCCESS;
    }
}
