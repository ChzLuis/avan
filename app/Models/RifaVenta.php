<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RifaVenta extends Model
{
    protected $table = 'rifa_ventas';

    protected $fillable = [
        'project_id','wa_number','plan','plan_nombre','tickets',
        'monto','nombre','dni','payment_proof','ticket_code','status',
    ];

    public static function planes(): array
    {
        return [
            1 => ['nombre' => 'Probar mi suerte',     'tickets' => 1,  'monto' => 10.00],
            2 => ['nombre' => 'Duplica tu suerte',     'tickets' => 2,  'monto' => 20.00],
            3 => ['nombre' => 'Quintuplica tu suerte', 'tickets' => 5,  'monto' => 50.00],
            4 => ['nombre' => 'Asegura suertudazo',    'tickets' => 10, 'monto' => 100.00],
        ];
    }
}
