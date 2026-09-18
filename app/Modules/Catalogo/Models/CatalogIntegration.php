<?php

namespace App\Modules\Catalogo\Models;

use App\Models\Project;
use App\Models\User;
use App\Modules\Catalogo\Conectores\Registry\CatalogProviderRegistry;

use App\Models\Traits\HasProjectScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Integración de catálogo de un proyecto con un proveedor externo (SISKOTE,
 * y cualquier futuro conector registrado en CatalogProviderRegistry).
 *
 * `credentials` va SIEMPRE cifrado (cast encrypted:array) — nunca se expone
 * tal cual al frontend; ver toSafeArray() para la representación segura.
 */
class CatalogIntegration extends Model
{
    use HasProjectScope;

    protected $fillable = [
        'project_id', 'provider', 'name', 'credentials', 'settings', 'capabilities_cache',
        'sync_mode', 'sync_interval_minutes', 'active',
        'last_connection_test_at', 'last_connection_ok',
        'last_sync_started_at', 'last_sync_completed_at', 'last_successful_sync_at',
        'last_sync_status', 'last_sync_error', 'created_by',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'settings' => 'array',
        'capabilities_cache' => 'array',
        'active' => 'boolean',
        'last_connection_ok' => 'boolean',
        'sync_interval_minutes' => 'integer',
        'last_connection_test_at' => 'datetime',
        'last_sync_started_at' => 'datetime',
        'last_sync_completed_at' => 'datetime',
        'last_successful_sync_at' => 'datetime',
    ];

    /** Nunca serializar credenciales, aunque alguien haga toArray()/toJson() por error. */
    protected $hidden = ['credentials'];

    protected static function booted(): void
    {
        // Mismo guardrail anti cross-tenant que Product/Category: una integración
        // nunca puede quedar vinculada a un proyecto que no es el suyo por error.
        static::saving(function (CatalogIntegration $integration) {
            if ($integration->isDirty('project_id') && $integration->exists) {
                throw new \RuntimeException('No se puede reasignar una integración de catálogo a otro proyecto.');
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CatalogIntegrationItem::class, 'integration_id');
    }

    public function syncRuns(): HasMany
    {
        return $this->hasMany(CatalogSyncRun::class, 'integration_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'catalog_integration_id');
    }

    /** Representación segura para la UI: secretos enmascarados, nunca el valor real. */
    public function toSafeArray(array $secretFieldNames = []): array
    {
        $creds = $this->credentials ?? [];
        $masked = [];
        foreach ($creds as $key => $value) {
            $masked[$key] = in_array($key, $secretFieldNames, true) && filled($value)
                ? '••••••'.substr((string) $value, -4)
                : $value;
        }

        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'name' => $this->name,
            'credentials' => $masked,
            'settings' => $this->settings,
            'sync_mode' => $this->sync_mode,
            'sync_interval_minutes' => $this->sync_interval_minutes,
            'active' => $this->active,
            'last_connection_test_at' => $this->last_connection_test_at?->toIso8601String(),
            'last_connection_ok' => $this->last_connection_ok,
            'last_sync_started_at' => $this->last_sync_started_at?->toIso8601String(),
            'last_sync_completed_at' => $this->last_sync_completed_at?->toIso8601String(),
            'last_successful_sync_at' => $this->last_successful_sync_at?->toIso8601String(),
            'last_sync_status' => $this->last_sync_status,
            'last_sync_error' => $this->last_sync_error,
        ];
    }
}
