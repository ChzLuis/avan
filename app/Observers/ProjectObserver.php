<?php

namespace App\Observers;

use App\Modules\Bots\Models\BotFlow;
use App\Models\Project;
use Illuminate\Support\Facades\Log;

/**
 * Toda empresa nace con su Bot Comercial predeterminado.
 *
 * No hay que crearlo ni configurarlo: se alimenta de los datos que la empresa
 * ya carga (catálogo, pagos, dirección, horario, preguntas frecuentes) y de lo
 * que le falte avisa en vez de inventarlo. Queda DESACTIVADO hasta que el dueño
 * lo encienda.
 */
class ProjectObserver
{
    public function created(Project $project): void
    {
        try {
            BotFlow::comercialDe($project);
            self::asegurarToken($project);
        } catch (\Throwable $e) {
            // Crear un proyecto no puede fallar porque su bot no se pudo crear:
            // el backfill lo recupera después.
            Log::warning('bot_comercial.alta_fallida', [
                'proyecto' => $project->id,
                'error'    => class_basename($e),
            ]);
        }
    }

    /**
     * Sin `copilot_token` el conector de WhatsApp no puede autenticar y ningún
     * mensaje llega al bot: un bot sin token no está "listo", está sordo. Se
     * genera una sola vez y nunca se pisa uno existente (los conectores en
     * producción lo llevan configurado).
     */
    public static function asegurarToken(Project $project): void
    {
        if (filled($project->copilot_token)) {
            return;
        }

        $project->forceFill(['copilot_token' => \Illuminate\Support\Str::random(48)])->save();
    }
}
