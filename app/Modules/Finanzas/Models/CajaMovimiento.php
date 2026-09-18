<?php
namespace App\Modules\Finanzas\Models;

use App\Modules\Ventas\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CajaMovimiento extends Model {
    protected $table = 'caja_movimientos';
    protected $fillable = [
        'caja_id','project_id','tipo','concepto',
        'monto','metodo_pago','order_id','user_id',
    ];
    protected $casts = ['monto' => 'decimal:2'];
    public function caja()  { return $this->belongsTo(Caja::class); }
    public function order() { return $this->belongsTo(Order::class); }
    public function user()  { return $this->belongsTo(User::class); }
}
