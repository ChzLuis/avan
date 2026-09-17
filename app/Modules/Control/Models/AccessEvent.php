<?php

namespace App\Modules\Control\Models;

use App\Models\User;

use Illuminate\Database\Eloquent\Model;

/**
 * Auditoría de accesos: quién cambió qué perfil, a quién y cuándo.
 *
 * Solo se registran cambios DELIBERADOS de un administrador. La sincronización
 * automática de roles que hace SetActiveProject en cada petición no se registra:
 * inundaría la tabla y no es una decisión de nadie.
 */
class AccessEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'project_id', 'actor_id', 'target_user_id',
        'role_name', 'action', 'meta', 'ip', 'created_at',
    ];

    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function afectado()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * Registra un cambio de acceso. Nunca debe tumbar la operación que lo
     * origina: si la auditoría falla, el cambio del usuario ya se hizo y
     * perderlo sería peor que perder el asiento.
     */
    public static function registrar(
        string $action,
        ?int $projectId = null,
        ?string $roleName = null,
        ?int $targetUserId = null,
        array $meta = [],
    ): ?self {
        try {
            return static::create([
                'project_id'     => $projectId,
                'actor_id'       => auth()->id(),
                'target_user_id' => $targetUserId,
                'role_name'      => $roleName,
                'action'         => $action,
                'meta'           => $meta ?: null,
                'ip'             => request()?->ip(),
                'created_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('AccessEvent: no se pudo registrar el cambio de acceso', [
                'action' => $action, 'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /** Frase legible para la pantalla de auditoría. */
    public function getLabelAttribute(): string
    {
        $quien   = $this->actor?->name ?? 'Alguien';
        $a_quien = $this->afectado?->name ?? 'un usuario';
        $perfil  = $this->role_name ?? 'un perfil';
        $m       = $this->meta ?? [];

        return match ($this->action) {
            'role_created'     => "{$quien} creó el perfil «{$perfil}»",
            'role_deleted'     => "{$quien} eliminó el perfil «{$perfil}»",
            'role_renamed'     => "{$quien} renombró el perfil «".($m['from'] ?? '?')."» a «".($m['to'] ?? '?')."»",
            'permissions_changed' => "{$quien} cambió los permisos del perfil «{$perfil}»"
                                    . $this->resumenPermisos($m),
            'profile_assigned' => "{$quien} asignó el perfil «{$perfil}» a {$a_quien}",
            'profile_removed'  => "{$quien} retiró el perfil de {$a_quien}"
                                    . ($this->role_name ? " (era «{$perfil}»)" : ''),
            default            => "{$quien}: {$this->action}",
        };
    }

    /** «(+2, −1)» — cuánto se amplió o recortó, sin listar los 90 permisos. */
    private function resumenPermisos(array $meta): string
    {
        $anadidos = count($meta['added'] ?? []);
        $quitados = count($meta['removed'] ?? []);

        if (!$anadidos && !$quitados) {
            return '';
        }

        $partes = [];
        if ($anadidos) $partes[] = "+{$anadidos}";
        if ($quitados) $partes[] = "−{$quitados}";

        return ' ('.implode(', ', $partes).')';
    }
}
