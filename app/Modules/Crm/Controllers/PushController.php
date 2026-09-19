<?php

namespace App\Modules\Crm\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\PushSuscripcion;
use App\Modules\Crm\Support\WebPush\PushWeb;
use Illuminate\Http\Request;

/** Suscripcion push del navegador (PC o celular con el CRM instalado como app). */
class PushController extends Controller
{
    public function clave()
    {
        return response()->json(['clave' => PushWeb::clavePublica()]);
    }

    public function suscribir(Request $request)
    {
        $data = $request->validate([
            'endpoint'     => 'required|string|max:500',
            'keys.p256dh'  => 'required|string|max:200',
            'keys.auth'    => 'required|string|max:60',
        ]);
        $s = PushSuscripcion::updateOrCreate(
            ['user_id' => $request->user()->id, 'endpoint' => $data['endpoint']],
            ['project_id' => (int) session('comunicaciones_project_id'), 'p256dh' => $data['keys']['p256dh'], 'auth' => $data['keys']['auth'], 'agente' => mb_substr((string) $request->userAgent(), 0, 200)]
        );

        return response()->json(['ok' => true, 'id' => $s->id]);
    }

    public function baja(Request $request)
    {
        $data = $request->validate(['endpoint' => 'required|string|max:500']);
        PushSuscripcion::where('user_id', $request->user()->id)->where('endpoint', $data['endpoint'])->delete();

        return response()->json(['ok' => true]);
    }

    /** Envia una notificacion de prueba a los dispositivos de quien la pide. */
    public function probar(Request $request)
    {
        $n = 0;
        foreach (PushSuscripcion::where('user_id', $request->user()->id)->get() as $s) {
            $r = PushWeb::enviar($s->endpoint, $s->p256dh, $s->auth, ['titulo' => 'BIXO CRM', 'cuerpo' => 'Las notificaciones funcionan en este dispositivo ✅', 'url' => url('/bixocrm'), 'tag' => 'bx-prueba']);
            if ($r['caducada']) $s->delete();
            if ($r['ok']) $n++;
        }

        return response()->json(['ok' => $n > 0, 'enviadas' => $n]);
    }
}
