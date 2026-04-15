<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaConversacion extends Model
{
    protected $table = 'wa_conversaciones';

    protected $fillable = [
        'wa_canal_id', 'client_id', 'cliente_nombre', 'cliente_telefono',
        'cliente_sector', 'cliente_distrito', 'origen_anuncio',
        'estado', 'notas', 'archivado', 'no_leidos',
        'ultimo_mensaje_at', 'asignado_a', 'bot_activo',
    ];

    protected $casts = [
        'archivado'         => 'boolean',
        'bot_activo'        => 'boolean',
        'ultimo_mensaje_at' => 'datetime',
    ];

    public function canal()
    {
        return $this->belongsTo(WaCanal::class, 'wa_canal_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function mensajes()
    {
        return $this->hasMany(WaMensaje::class)->orderBy('created_at');
    }

    public function ultimoMensaje()
    {
        return $this->hasOne(WaMensaje::class)->latestOfMany();
    }

    public function getNombreDisplayAttribute(): string
    {
        return $this->cliente_nombre ?: $this->cliente_telefono;
    }

    public function getAvatarLetraAttribute(): string
    {
        return strtoupper(substr($this->nombre_display, 0, 1));
    }

    public static function detectarOrigen(string $mensaje, string $tipo): string
    {
        $msg = mb_strtolower($mensaje);
        if (str_contains($msg, 'digitalizar') || str_contains($msg, 'bixo') || str_contains($msg, 'negocio digital')) {
            return 'anuncio_bixo';
        }
        if (str_contains($msg, 'taller') || str_contains($msg, 'inscribirme') || str_contains($msg, 'academia')) {
            return 'anuncio_academy';
        }
        if ($tipo === 'academy') return 'academy_organico';
        return 'organico';
    }
}
