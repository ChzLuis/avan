<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaChatbotFlow extends Model
{
    protected $table = 'wa_chatbot_flows';

    protected $fillable = [
        'wa_canal_id', 'nombre', 'trigger_keywords',
        'response_text', 'es_bienvenida', 'activo', 'orden',
    ];

    protected $casts = [
        'trigger_keywords' => 'array',
        'es_bienvenida'    => 'boolean',
        'activo'           => 'boolean',
    ];

    public function canal()
    {
        return $this->belongsTo(WaCanal::class, 'wa_canal_id');
    }

    /**
     * Verifica si el mensaje entrante dispara este flow.
     */
    public function coincide(string $mensaje): bool
    {
        if (empty($this->trigger_keywords)) return true; // comodín

        $msg = mb_strtolower(trim($mensaje));
        foreach ($this->trigger_keywords as $kw) {
            if (str_contains($msg, mb_strtolower(trim($kw)))) {
                return true;
            }
        }
        return false;
    }
}
