<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RifaVenta extends Model
{
    protected $table = 'rifa_ventas';

    protected $fillable = [
        'project_id', 'rifa_id', 'order_number', 'wa_number',
        'plan', 'plan_nombre', 'tickets', 'monto',
        'nombre', 'dni', 'ciudad', 'payment_proof', 'ticket_code',
        'ticket_numbers', 'status',
    ];

    protected $casts = ['ticket_numbers' => 'array'];

    public function rifa()    { return $this->belongsTo(Rifa::class); }
    public function project() { return $this->belongsTo(Project::class); }

    public static function generateOrderNumber(): string
    {
        do {
            $num = 'R' . strtoupper(Str::random(6));
        } while (self::where('order_number', $num)->exists());
        return $num;
    }

    public function assignTicketNumbers(): array
    {
        $query = self::where('project_id', $this->project_id)
                     ->where('status', '!=', 'cancelado')
                     ->where('id', '!=', $this->id);
        if ($this->rifa_id) {
            $query->where('rifa_id', $this->rifa_id);
        }
        $used = $query->get()->flatMap(fn($v) => $v->ticket_numbers ?? [])->toArray();
        $numbers = [];
        $attempts = 0;
        while (count($numbers) < $this->tickets && $attempts < 1000) {
            $n = rand(1, 99999);
            if (!in_array($n, $used) && !in_array($n, $numbers)) {
                $numbers[] = $n;
            }
            $attempts++;
        }
        sort($numbers);
        return $numbers;
    }

    // Compatibilidad con planes hardcodeados anteriores
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
