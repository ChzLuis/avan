<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaMensaje extends Model
{
    protected $table = 'wa_mensajes';

    protected $fillable = [
        'wa_conversacion_id', 'wa_message_id', 'direccion',
        'tipo', 'contenido', 'media_url', 'estado',
        'leido_at', 'entregado_at',
    ];

    protected $casts = [
        'leido_at'      => 'datetime',
        'entregado_at'  => 'datetime',
    ];

    public function conversacion()
    {
        return $this->belongsTo(WaConversacion::class, 'wa_conversacion_id');
    }

    public function esEntrante(): bool
    {
        return $this->direccion === 'entrante';
    }

    public function esSaliente(): bool
    {
        return $this->direccion === 'saliente';
    }
}
