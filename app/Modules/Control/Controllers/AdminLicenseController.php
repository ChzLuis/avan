<?php

namespace App\Modules\Control\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use App\Modules\Control\Support\LicenseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Licencias y sesiones. Quién está dentro, con qué equipo, y cómo liberar un
 * asiento ocupado por alguien que cerró el navegador sin salir.
 */
class AdminLicenseController extends Controller
{
    public function index(Request $request)
    {
        $resumen  = LicenseManager::resumen();
        $sesiones = LicenseManager::sesiones();

        // Una fila por PERSONA, con sus sesiones dentro: es como se razona sobre
        // licencias. Las sesiones sin usuario (visitantes sin login) van aparte.
        $conectados = $sesiones->reject(fn ($s) => $s->anonima)
            ->groupBy('user_id')
            ->map(fn ($grupo) => [
                'user_id'      => $grupo->first()->user_id,
                'nombre'       => $grupo->first()->name ?? 'Usuario eliminado',
                'email'        => $grupo->first()->email,
                'licencia'     => $grupo->first()->license_type ?: 'concurrente',
                'superadmin'   => (bool) $grupo->first()->is_superadmin,
                'activo'       => $grupo->contains(fn ($s) => $s->activo),
                'ultimo_visto' => $grupo->max('last_activity'),
                'sesiones'     => $grupo->values(),
            ])
            ->sortByDesc('ultimo_visto')
            ->values();

        $idsConectados = $conectados->pluck('user_id')->all();

        // Quien tiene licencia pero no está dentro: es la otra mitad de la
        // pregunta "¿quién está desconectado?".
        $desconectados = User::when($idsConectados, fn ($q) => $q->whereNotIn('id', $idsConectados))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'license_type', 'is_superadmin']);

        $sesionesAnonimas = $sesiones->filter(fn ($s) => $s->anonima)->count();

        return view('control::admin.licenses.index', compact(
            'resumen', 'conectados', 'desconectados', 'sesionesAnonimas'
        ));
    }

    /** Libera un asiento: cierra una sesión concreta. */
    public function revokeSession(Request $request)
    {
        $data = $request->validate(['session_id' => 'required|string']);

        $sesion = DB::table('sessions')->where('id', $data['session_id'])->first();
        abort_if(!$sesion, 404, 'Esa sesión ya no existe.');
        abort_if($sesion->id === $request->session()->getId(), 422, 'Esa es tu propia sesión: cerrarla te dejaría fuera.');

        LicenseManager::liberarSesion($data['session_id']);

        return back()->with('success', 'Sesión cerrada. El asiento quedó libre.');
    }

    /** Cierra todas las sesiones de una persona. */
    public function revokeUser(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'No puedes cerrar tus propias sesiones desde aquí.');

        $n = LicenseManager::liberarUsuario($user->id);

        return back()->with('success', "Se cerraron {$n} sesión(es) de {$user->name}.");
    }

    /** Limpia de golpe las sesiones sin actividad reciente. */
    public function revokeIdle(Request $request)
    {
        $miSesion = $request->session()->getId();
        $corte    = now()->subMinutes(LicenseManager::MINUTOS_PARA_INACTIVO)->getTimestamp();

        $n = DB::table('sessions')
            ->where('last_activity', '<', $corte)
            ->where('id', '!=', $miSesion)
            ->delete();

        return back()->with('success', "Se liberaron {$n} sesión(es) inactivas.");
    }

    /** Cambia el tipo de licencia de una persona. */
    public function updateLicense(Request $request, User $user)
    {
        $data = $request->validate(['license_type' => 'required|in:nombrada,concurrente']);

        $user->update(['license_type' => $data['license_type']]);

        return back()->with('success', "{$user->name} pasó a licencia {$data['license_type']}.");
    }

    /** Guarda el límite de licencias concurrentes y si se aplica o solo se informa. */
    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'licencias_concurrentes_max' => 'required|integer|min:0|max:10000',
            'licencias_aplicar_limite'   => 'nullable|boolean',
        ]);

        AppSetting::put(LicenseManager::CLAVE_LIMITE, (int) $data['licencias_concurrentes_max']);
        AppSetting::put(LicenseManager::CLAVE_APLICAR, $request->boolean('licencias_aplicar_limite') ? '1' : '0');

        return back()->with('success', 'Configuración de licencias guardada.');
    }
}
