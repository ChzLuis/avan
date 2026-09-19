<?php

namespace App\Modules\Crm\Support\WebPush;

use App\Modules\Crm\Models\PushSuscripcion;
use App\Modules\Crm\Models\WaConversacion;
use Illuminate\Support\Facades\Log;

/** Avisa por push a todos los dispositivos suscritos de un negocio cuando entra un mensaje. */
class AvisoPush
{
    public static function mensajeEntrante(int $projectId, WaConversacion $conv, string $texto): void
    {
        $suscripciones = PushSuscripcion::where('project_id', $projectId)->get();
        if ($suscripciones->isEmpty()) {
            return;
        }
        $datos = [
            'titulo' => $conv->cliente_nombre ?: $conv->cliente_telefono,
            'cuerpo' => mb_substr($texto, 0, 120),
            'url'    => url('/bixocrm?conversacion=' . $conv->id),
            'tag'    => 'bx-conv-' . $conv->id,
        ];
        foreach ($suscripciones as $s) {
            $r = PushWeb::enviar($s->endpoint, $s->p256dh, $s->auth, $datos);
            if ($r['caducada']) {
                $s->delete();
            } elseif ($r['ok']) {
                $s->forceFill(['ultimo_ok_at' => now()])->saveQuietly();
            } else {
                Log::info('push.rechazado', ['estado' => $r['estado'], 'user' => $s->user_id]);
            }
        }
    }
}
