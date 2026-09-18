<?php

namespace App\Modules\Ventas\Models;

use App\Modules\Crm\Models\Client;

use Illuminate\Database\Eloquent\Model;

class SalesInteraction extends Model
{
    protected $fillable = [
        'client_id', 'project_id', 'canal', 'direccion', 'texto', 'fecha',
    ];

    protected $casts = ['fecha' => 'datetime'];

    public function client() { return $this->belongsTo(Client::class); }
}
