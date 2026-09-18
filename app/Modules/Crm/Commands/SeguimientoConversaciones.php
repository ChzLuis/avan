<?php

namespace App\Modules\Crm\Commands;

use App\Modules\Bots\Models\BotSession;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * SEGUIMIENTO AUTOMÁTICO de conversaciones que quedaron a medias.
 *
 * Si un cliente dejó de responder al bot, le enviamos un mensaje de reenganche
 * (uno solo, sin insistir) para recuperar la venta. Pensado para leads que
 * llegan de anuncios y se distraen.
 *
 * Uso:  php artisan bot:seguimiento --horas=2
 */
class SeguimientoConversaciones extends Command
{
    protected $signature = 'bot:seguimiento
        {--horas=2 : Horas de inactividad para enviar el seguimiento}
        {--max=30 : Máximo de mensajes por corrida}
        {--dry : Solo mostrar a quiénes se enviaría}';

    protected $description = 'Envía un mensaje de reenganche a clientes que dejaron la conversación a medias';

    public function handle(): int
    {
        $horas = (int) $this->option('horas');
        $max   = (int) $this->option('max');
        $dry   = (bool) $this->option('dry');

        // Nunca escribir fuera del horario de atención (respeto al cliente): 8am–9pm.
        $hora = (int) now()->format('H');
        if (!$dry && ($hora < 8 || $hora >= 21)) {
            $this->info("Fuera de horario ({$hora}h). El seguimiento solo se envía entre 8:00 y 21:00.");
            return self::SUCCESS;
        }

        $desde = now()->subHours($horas);
        // No molestar a quien ya lleva mucho tiempo inactivo (más de 48h).
        $hasta = now()->subHours(48);

        $enviados = 0;

        // Sesiones de bot que quedaron esperando respuesta del cliente.
        $sesiones = BotSession::whereNotNull('estado')
            ->where('ultima_at', '<=', $desde)
            ->where('ultima_at', '>=', $hasta)
            ->limit($max * 2)
            ->get();

        foreach ($sesiones as $s) {
            if ($enviados >= $max) break;

            $estado = $s->estado ?? [];
            $vars   = $estado['vars'] ?? [];

            // Solo si estaba esperando una respuesta y NO le hemos escrito ya.
            if (empty($estado['esperando']) || !empty($vars['_seguimiento_enviado'])) continue;

            $project = Project::find($s->project_id);
            if (!$project || empty($project->copilot_token)) continue;

            $texto = $this->mensajeSeguimiento($vars);

            if ($dry) {
                $this->line("• [{$project->name}] {$s->telefono} → " . mb_substr($texto, 0, 60) . '…');
                $enviados++;
                continue;
            }

            // Enviar por el conector (mismo canal que usa el bot).
            $ok = $this->enviar($project, $s->telefono, $texto);
            if ($ok) {
                // Marcar para no volver a insistir.
                $vars['_seguimiento_enviado'] = now()->toDateTimeString();
                $estado['vars'] = $vars;
                $s->estado = $estado;
                $s->save();
                $enviados++;
                $this->info("✓ Seguimiento a {$s->telefono}");
            }
        }

        $this->info($dry ? "Se enviarían $enviados mensajes." : "Seguimientos enviados: $enviados");
        return self::SUCCESS;
    }

    /** Mensaje de reenganche según lo que ya sabíamos del cliente. */
    private function mensajeSeguimiento(array $vars): string
    {
        $datos  = $vars['_datos_ia'] ?? [];
        $nombre = $datos['nombre'] ?? $datos['cliente'] ?? null;
        $saludo = $nombre ? "Hola $nombre 👋" : '¡Hola! 👋';

        // Si tenía un carrito armado, se lo recordamos.
        if (!empty($vars['carrito'])) {
            $n = count($vars['carrito']);
            return "$saludo Vi que dejaste $n producto" . ($n > 1 ? 's' : '')
                . " en tu pedido. ¿Te ayudo a completarlo? 🛒";
        }

        return "$saludo ¿Sigues interesado? Quedé atento a tu respuesta para ayudarte. 😊";
    }

    /** Envía el mensaje por el conector de WhatsApp del proyecto. */
    private function enviar(Project $project, string $telefono, string $texto): bool
    {
        // Cada proyecto puede tener su PROPIA linea de WhatsApp (conector con
        // puerto propio). El setting `wa_connector_url` manda; el global queda
        // como respaldo para instalaciones de una sola linea. Sin ninguno de
        // los dos, NO se envia por una linea ajena: el conector rechazaria el
        // token (401) o, peor, saldria por el numero de otra empresa.
        $url = trim((string) $project->setting('wa_connector_url', ''))
            ?: (config('services.wa_connector.url') ?? env('WA_CONNECTOR_URL'));
        if (!$url) {
            $this->warn("Proyecto {$project->id} sin wa_connector_url (ni global): recordatorio no enviado.");
            return false;
        }
        try {
            $r = Http::timeout(15)->post(rtrim($url, '/') . '/enviar', [
                'token'    => $project->copilot_token,
                'telefono' => $telefono,
                'mensaje'  => $texto,
            ]);
            return $r->successful();
        } catch (\Throwable $e) {
            $this->error('Error al enviar: ' . $e->getMessage());
            return false;
        }
    }
}
