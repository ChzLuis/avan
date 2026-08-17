<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Control de licencias concurrentes y nombradas.
 *
 * Se apoya en la tabla `sessions` de Laravel (SESSION_DRIVER=database), que ya
 * guarda user_id, ip, user_agent y last_activity. No se inventa un registro
 * paralelo de "quién está conectado": la única verdad es la sesión viva.
 *
 * Un asiento se libera de tres formas: el usuario cierra sesión, la sesión
 * caduca por inactividad (SESSION_LIFETIME), o un superadmin la libera a mano
 * desde el panel — esto último es lo que resuelve el caso real de alguien que
 * cerró el navegador sin salir y dejó el asiento ocupado.
 */
class LicenseManager
{
    /** Minutos sin actividad tras los que se considera que la persona ya no está delante. */
    public const MINUTOS_PARA_INACTIVO = 5;

    public const CLAVE_LIMITE   = 'licencias_concurrentes_max';
    public const CLAVE_APLICAR  = 'licencias_aplicar_limite';

    /** ¿Se bloquea el acceso al superar el límite, o solo se informa? */
    public static function aplicaLimite(): bool
    {
        return AppSetting::bool(self::CLAVE_APLICAR, false);
    }

    public static function limiteConcurrente(): int
    {
        return AppSetting::int(self::CLAVE_LIMITE, 0);
    }

    /** Sesiones vivas, ya resueltas a persona y dispositivo. */
    public static function sesiones(): \Illuminate\Support\Collection
    {
        $corte = now()->subMinutes(self::MINUTOS_PARA_INACTIVO)->getTimestamp();

        return DB::table('sessions')
            ->leftJoin('users', 'users.id', '=', 'sessions.user_id')
            ->select([
                'sessions.id', 'sessions.user_id', 'sessions.ip_address',
                'sessions.user_agent', 'sessions.last_activity',
                'users.name', 'users.email', 'users.license_type', 'users.is_superadmin',
            ])
            ->orderByDesc('sessions.last_activity')
            ->get()
            ->map(function ($s) use ($corte) {
                $s->activo      = $s->last_activity >= $corte;
                $s->visto       = \Carbon\Carbon::createFromTimestamp($s->last_activity);
                $s->dispositivo = self::dispositivo($s->user_agent);
                $s->anonima     = $s->user_id === null;

                return $s;
            });
    }

    /**
     * Resumen para el panel. 'ocupados' cuenta PERSONAS distintas, no sesiones:
     * abrir el sistema en el móvil y en el PC no debe gastar dos licencias.
     */
    public static function resumen(): array
    {
        $sesiones = self::sesiones()->reject(fn ($s) => $s->anonima);

        $porUsuario = $sesiones->groupBy('user_id');
        $nombradas  = User::where('license_type', 'nombrada')->count();

        $concurrentesEnUso = $porUsuario
            ->reject(fn ($grupo) => $grupo->first()->license_type === 'nombrada')
            ->count();

        $limite = self::limiteConcurrente();

        return [
            'sesiones_abiertas'   => $sesiones->count(),
            'personas_conectadas' => $porUsuario->count(),
            'personas_activas'    => $porUsuario->filter(fn ($g) => $g->contains(fn ($s) => $s->activo))->count(),
            'nombradas'           => $nombradas,
            'concurrentes_en_uso' => $concurrentesEnUso,
            'concurrentes_limite' => $limite,
            'concurrentes_libres' => $limite > 0 ? max(0, $limite - $concurrentesEnUso) : null,
            'sobrepasado'         => $limite > 0 && $concurrentesEnUso > $limite,
            'aplica_limite'       => self::aplicaLimite(),
            'sesiones_huerfanas'  => $sesiones->reject(fn ($s) => $s->activo)->count(),
        ];
    }

    /**
     * ¿Puede este usuario ocupar un asiento ahora mismo?
     *
     * Nunca se bloquea a un superadmin ni a una licencia nombrada: si el control
     * de licencias dejara fuera al dueño del sistema, nadie podría entrar a
     * liberar asientos y el problema no tendría salida.
     */
    public static function puedeEntrar(User $user): bool
    {
        if (!self::aplicaLimite() || $user->is_superadmin || $user->license_type === 'nombrada') {
            return true;
        }

        $limite = self::limiteConcurrente();
        if ($limite <= 0) {
            return true;
        }

        // Si ya tiene una sesión abierta, no consume un asiento nuevo.
        if (DB::table('sessions')->where('user_id', $user->id)->exists()) {
            return true;
        }

        $enUso = DB::table('sessions')
            ->join('users', 'users.id', '=', 'sessions.user_id')
            ->where('users.license_type', '!=', 'nombrada')
            ->distinct()
            ->count('sessions.user_id');

        return $enUso < $limite;
    }

    /** Libera una sesión concreta. Devuelve true si existía. */
    public static function liberarSesion(string $sessionId): bool
    {
        return DB::table('sessions')->where('id', $sessionId)->delete() > 0;
    }

    /** Libera todas las sesiones de una persona. Devuelve cuántas cerró. */
    public static function liberarUsuario(int $userId): int
    {
        return DB::table('sessions')->where('user_id', $userId)->delete();
    }

    /** Libera las sesiones sin actividad reciente (navegador cerrado sin salir). */
    public static function liberarInactivas(): int
    {
        return DB::table('sessions')
            ->where('last_activity', '<', now()->subMinutes(self::MINUTOS_PARA_INACTIVO)->getTimestamp())
            ->delete();
    }

    /**
     * Antigüedad en español. Se escribe aquí en vez de usar diffForHumans porque
     * la app corre con locale 'en' y salía "sin actividad desde 9 minutes";
     * cambiar el locale global tocaría también los mensajes de validación.
     */
    public static function desdeHace(int $timestamp): string
    {
        $seg = max(0, now()->getTimestamp() - $timestamp);

        return match (true) {
            $seg < 60      => 'hace un momento',
            $seg < 3600    => 'hace ' . intdiv($seg, 60) . ' min',
            $seg < 86400   => 'hace ' . intdiv($seg, 3600) . ' h',
            default        => 'hace ' . intdiv($seg, 86400) . ' día' . (intdiv($seg, 86400) === 1 ? '' : 's'),
        };
    }

    /** Navegador y sistema en texto corto, para reconocer el equipo de un vistazo. */
    public static function dispositivo(?string $userAgent): string
    {
        $ua = (string) $userAgent;
        if ($ua === '') {
            return 'Desconocido';
        }

        $navegador = match (true) {
            str_contains($ua, 'Edg/')     => 'Edge',
            str_contains($ua, 'OPR/')     => 'Opera',
            str_contains($ua, 'Chrome/')  => 'Chrome',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Safari/')  => 'Safari',
            default                       => 'Navegador',
        };

        $sistema = match (true) {
            str_contains($ua, 'Android')                              => 'Android',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad')    => 'iPhone/iPad',
            str_contains($ua, 'Windows')                              => 'Windows',
            str_contains($ua, 'Mac OS')                               => 'Mac',
            str_contains($ua, 'Linux')                                => 'Linux',
            default                                                   => '',
        };

        return trim($navegador . ($sistema ? ' · ' . $sistema : ''));
    }
}
