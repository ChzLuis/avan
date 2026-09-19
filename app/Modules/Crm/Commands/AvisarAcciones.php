<?php

namespace App\Modules\Crm\Commands;

use App\Modules\Crm\Models\CrmAccion;
use App\Modules\Crm\Models\WaCanal;
use App\Modules\Crm\Support\WhatsappCloud\ClienteCloud;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Cada minuto: las acciones cuyo aviso previo toca AHORA (vence_at menos avisar_minutos)
 * mandan un WhatsApp al numero que coordino la accion. Una sola vez por accion (`avisada_at`).
 * Es distinto del push al vencer (`crm:recordar-acciones`, campo `recordada_at`).
 */
class AvisarAcciones extends Command
{
    protected $signature = 'crm:avisar-acciones';
    protected $description = 'Manda por WhatsApp el aviso previo de las acciones del CRM';

    public function handle(): int
    {
        $pendientes = CrmAccion::whereNull('hecho_at')->whereNull('avisada_at')
            ->whereNotNull('vence_at')->whereNotNull('avisar_whatsapp')
            // No se avisa lo que vencio hace mas de 1 h ni lo que esta muy lejos (el tope de
            // avisar_minutos es 1440). El "falta <= avisar_minutos" se filtra en PHP para que
            // funcione igual en MySQL y en SQLite (las pruebas).
            ->where('vence_at', '>=', now()->subHour())
            ->where('vence_at', '<=', now()->addMinutes(1440))
            ->with('conversacion')->limit(300)->get()
            ->filter(fn ($a) => $a->vence_at->subMinutes((int) $a->avisar_minutos)->lte(now()))
            ->values();

        $enviados = 0;
        foreach ($pendientes as $a) {
            $canal = WaCanal::where('project_id', $a->project_id)
                ->whereNotNull('phone_number_id')->whereNotNull('access_token')
                ->where('activo', true)->orderBy('id')->first();
            if (! $canal) {
                $this->warn("Accion {$a->id}: el negocio no tiene linea de WhatsApp conectada");
                $a->forceFill(['avisada_at' => now()])->saveQuietly();
                continue;
            }
            $conv = $a->conversacion;
            $faltan = max(0, now()->diffInMinutes($a->vence_at, false));
            $cuando = $a->vence_at->locale('es')->isoFormat('dddd D [a las] HH:mm');
            $texto = "⏰ *Recordatorio de tu agenda*\n\n" . $a->titulo
                . ($conv ? "\n👤 " . ($conv->cliente_nombre ?: $conv->cliente_telefono)
                    . "\n📱 wa.me/" . preg_replace('/\D/', '', $conv->cliente_telefono) : '')
                . "\n🕒 {$cuando}" . ($faltan > 0 ? " (en {$faltan} min)" : ' (ahora)');

            $r = (new ClienteCloud($canal))->enviarUna($a->avisar_whatsapp, $texto);
            $a->forceFill(['avisada_at' => now()])->saveQuietly();
            if (! empty($r['ok'])) {
                $enviados++;
            } else {
                Log::warning('crm.aviso_accion_fallo', ['accion' => $a->id, 'error' => $r['error'] ?? '?']);
            }
        }
        $this->info("Avisos por WhatsApp: {$enviados} de {$pendientes->count()}");

        return self::SUCCESS;
    }
}
