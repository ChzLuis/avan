<?php

namespace App\Modules\Operaciones\Commands;

use App\Models\Project;
use App\Modules\Bots\Models\BotInstance;
use App\Modules\Ventas\Support\OrderFlow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Revisa pedidos de lavandería que superaron su tiempo límite (SLA) y avisa
 * al encargado por WhatsApp — SOLO si el proyecto tiene un bot activo.
 * Sin bot, las alertas siguen visibles en el sistema (tablero + contador);
 * este comando simplemente no envía nada.
 *
 * Programar (ej. cada 15 min) en routes/console.php (el provider de modulos lo registra).
 */
class CheckLaundryOverdue extends Command
{
    protected $signature = 'laundry:check-overdue {--project= : ID de proyecto específico}';
    protected $description = 'Detecta pedidos de lavandería atrasados y avisa al encargado (si hay bot)';

    private const BOT_URL   = 'http://127.0.0.1:3001';
    private const BOT_TOKEN = 'wa-bot-secret-2024';

    public function handle(): int
    {
        $query = Project::where('is_active', true);
        if ($this->option('project')) {
            $query->where('id', (int) $this->option('project'));
        }
        // Solo proyectos cuyo rubro tiene flujo de estados
        $projects = $query->get()->filter(
            fn($p) => \App\Modules\Ventas\Support\OrderFlow::supportsFlow($p->category)
        );

        $totalAvisos = 0;

        foreach ($projects as $project) {
            $overdue = OrderFlow::overdueCount($project);
            if ($overdue === 0) {
                continue;
            }

            $this->line("Proyecto «{$project->name}»: {$overdue} pedido(s) atrasado(s).");

            // ¿Hay bot activo para este proyecto? Si no, solo alerta en sistema.
            $bot = BotInstance::where('project_id', $project->id)
                ->where('is_active', true)
                ->first();

            if (!$bot) {
                $this->warn("  Sin bot activo → alerta solo en el sistema (no se envía WhatsApp).");
                continue;
            }

            // Número del encargado: setting específico o el WhatsApp del negocio
            $encargado = $project->setting('manager_phone') ?: $project->whatsapp;
            if (!$encargado) {
                $this->warn("  Sin número de encargado configurado → no se envía WhatsApp.");
                continue;
            }

            $msg = "⚠️ *Pedidos atrasados en {$project->name}*\n\n"
                 . "Hay *{$overdue}* pedido(s) que superaron su tiempo límite. "
                 . "Revísalos en el tablero para no demorar la entrega. 🧺";

            try {
                Http::timeout(4)->post(self::BOT_URL . '/action', [
                    'token'     => self::BOT_TOKEN,
                    'wa_number' => $encargado,
                    'action'    => 'custom_text',
                    'message'   => $msg,
                ]);
                $totalAvisos++;
                $this->info("  ✓ Aviso enviado al encargado ({$encargado}).");
            } catch (\Throwable $e) {
                $this->error("  Bot no respondió: {$e->getMessage()}");
            }
        }

        $this->info("Listo. Avisos enviados: {$totalAvisos}.");
        return self::SUCCESS;
    }
}
