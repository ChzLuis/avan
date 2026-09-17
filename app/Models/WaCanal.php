<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Linea de WhatsApp de un negocio.
 *
 * PROPIETARIO UNICO de las credenciales de WhatsApp (ver MODULE_OWNERSHIP:
 * `Wa*` pertenece a Automation). Tanto la bandeja de Comunicaciones (cuando
 * responde una persona) como el webhook de Meta (cuando responde el bot)
 * leen de aqui: no hay una segunda tabla con estos datos.
 *
 * Los secretos se guardan cifrados. `$hidden` solo evita que salgan en un
 * JSON; el cast 'encrypted' es lo que protege la base de datos.
 */
class WaCanal extends Model
{
    protected $table = 'wa_canales';

    protected $fillable = [
        'project_id', 'nombre', 'tipo', 'telefono',
        'phone_number_id', 'access_token', 'app_secret', 'verify_token',
        'api_version', 'color', 'activo', 'bot_type',
        'mensaje_bienvenida', 'mensaje_ausencia',
    ];

    protected $casts = [
        'activo'       => 'boolean',
        'access_token' => 'encrypted',
        'app_secret'   => 'encrypted',
        'ultimo_ok_at' => 'datetime',
    ];

    protected $hidden = ['access_token', 'app_secret', 'verify_token'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function conversaciones(): HasMany
    {
        return $this->hasMany(WaConversacion::class);
    }

    /**
     * ¿Puede enviar por la Cloud API de Meta?
     *
     * Un canal a medio configurar no debe intentar enviar: fallaria en Meta y
     * el mensaje quedaria "pendiente" sin que nadie sepa por que.
     */
    public function conectadoAMeta(): bool
    {
        return filled($this->phone_number_id) && filled($this->access_token);
    }

    /** Para mostrar en el panel sin revelar el token completo. */
    public function tokenEnmascarado(): string
    {
        $t = (string) $this->access_token;

        return $t === '' ? '—' : str_repeat('•', 12) . substr($t, -6);
    }

    /** Endpoint de envio de esta linea. */
    public function urlMensajes(): string
    {
        $v = $this->api_version ?: 'v21.0';

        return "https://graph.facebook.com/{$v}/{$this->phone_number_id}/messages";
    }
}
