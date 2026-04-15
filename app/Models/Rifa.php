<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rifa extends Model
{
    protected $fillable = [
        'project_id', 'nombre', 'descripcion', 'precio_ticket',
        'min_tickets', 'max_tickets', 'imagen_url', 'premio',
        'sort_order', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function project() { return $this->belongsTo(Project::class); }
    public function ventas()  { return $this->hasMany(RifaVenta::class); }

    public function formatoParaBot(): string
    {
        $precio = number_format($this->precio_ticket, 2);
        return "🎟️ *{$this->nombre}*\n"
             . ($this->premio ? "🏆 Premio: {$this->premio}\n" : '')
             . "💰 S/ {$precio} por ticket\n"
             . ($this->descripcion ? "ℹ️ {$this->descripcion}" : '');
    }
}
