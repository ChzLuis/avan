<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Configuración global del sistema (clave/valor).
 *
 * Distinta de ProjectSetting: esto NO pertenece a ningún proyecto, es del panel
 * de superadmin. Se cachea porque se consulta en cada login.
 */
class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    private const CACHE_PREFIX = 'app_setting:';

    public static function get(string $key, mixed $default = null): mixed
    {
        $valor = Cache::remember(
            self::CACHE_PREFIX . $key,
            300,
            fn () => static::where('key', $key)->value('value')
        );

        return $valor === null ? $default : $valor;
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        Cache::forget(self::CACHE_PREFIX . $key);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $valor = self::get($key);

        return $valor === null ? $default : in_array((string) $valor, ['1', 'true', 'on', 'si'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        $valor = self::get($key);

        return $valor === null || $valor === '' ? $default : (int) $valor;
    }
}
