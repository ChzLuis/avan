<?php
namespace App\Models;
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

    public function totalVentas(): float {
        return (float) $this->movimientos()->where('tipo', 'venta')->sum('monto');
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
