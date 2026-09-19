<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;

/** Suscripcion Web Push de un usuario del CRM para un negocio (una por navegador/dispositivo). */
class PushSuscripcion extends Model
{
    protected $table = 'push_suscripciones';

    protected $fillable = ['user_id', 'project_id', 'endpoint', 'p256dh', 'auth', 'agente', 'ultimo_ok_at'];

    protected $casts = ['ultimo_ok_at' => 'datetime'];
}
