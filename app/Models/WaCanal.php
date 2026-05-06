<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaCanal extends Model
{
    protected $table = 'wa_canales';

    protected $fillable = [
        'project_id', 'nombre', 'tipo', 'telefono',
        'phone_number_id', 'access_token', 'verify_token',
        'color', 'activo', 'bot_type', 'mensaje_bienvenida', 'mensaje_ausencia',
    ];

    protected $casts = ['activo' => 'boolean'];

    // Ocultar token en serialización
    protected $hidden = ['access_token', 'verify_token'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function conversaciones()
    {
        return $this->hasMany(WaConversacion::class);
    }
}
