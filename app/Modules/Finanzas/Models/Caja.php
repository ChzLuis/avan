<?php
namespace App\Modules\Finanzas\Models;

use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasProjectScope;

class Caja extends Model {
    use HasProjectScope;
    protected $fillable = [
        'project_id','user_id','user_name',
        'monto_apertura','monto_cierre','monto_esperado','diferencia',
        'opened_at','closed_at','status',
        'notas_apertura','notas_cierre',
    ];
    protected $casts = [
        'monto_apertura'  => 'decimal:2',
        'monto_cierre'    => 'decimal:2',
        'monto_esperado'  => 'decimal:2',
        'diferencia'      => 'decimal:2',
        'opened_at'       => 'datetime',
        'closed_at'       => 'datetime',
    ];
    public function project()      { return $this->belongsTo(Project::class); }
    public function user()         { return $this->belongsTo(User::class); }
    public function movimientos()  { return $this->hasMany(CajaMovimiento::class); }
    public function ventas()       { return $this->hasMany(CajaMovimiento::class)->where('tipo', 'venta'); }

    /**
     * Lo cobrado EN EFECTIVO durante el turno de esta caja.
     *
     * Antes se leia de `caja_movimientos` con `tipo='venta'`, pero ese tipo no
     * lo escribe nadie: el unico que crea movimientos es CajaController y solo
     * admite `ingreso` y `egreso`. Resultado: esto devolvia SIEMPRE 0, el
     * cierre mostraba "Ventas: S/ 0.00" y la diferencia contra el efectivo
     * contado salia enorme, quedando grabada como sobrante. El arqueo no
     * servia para lo unico que hace.
     *
     * Se cuenta solo el efectivo porque es lo que tiene que estar en el cajon:
     * un Yape o una tarjeta no cambian lo que se cuenta al cerrar. Y solo lo
     * PAGADO: un pedido a credito no es dinero en caja.
     */
    public function totalVentas(): float {
        $hasta = $this->closed_at ?: now();

        return (float) \App\Models\Order::where('project_id', $this->project_id)
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$this->opened_at, $hasta])
            ->where(fn ($q) => $q->where('payment_method', 'like', '%fectivo%')
                                 ->orWhereNull('payment_method'))
            ->sum('total');
    }
    public function totalIngresos(): float {
        return (float) $this->movimientos()->where('tipo', 'ingreso')->sum('monto');
    }
    public function totalEgresos(): float {
        return (float) $this->movimientos()->where('tipo', 'egreso')->sum('monto');
    }
    public function saldoEsperado(): float {
        return (float) $this->monto_apertura + $this->totalVentas() + $this->totalIngresos() - $this->totalEgresos();
    }
}
