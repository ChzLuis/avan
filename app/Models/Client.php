<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasProjectScope;
class Client extends Model {
    use HasProjectScope;
    protected $fillable = [
        'project_id', 'name', 'phone', 'email', 'notes', 'portal_token',
        // Campos del Copilot / CRM de ventas
        'lead_temp', 'lead_score', 'lead_source', 'etapa', 'empresa',
        'producto_interes', 'monto_estimado', 'intencion', 'objeciones',
        'proximo_seguimiento', 'ultima_actividad', 'etiquetas', 'responsable',
        'sexo', 'fecha_nacimiento', 'idioma', 'pais', 'ciudad', 'provincia',
        'direccion', 'cargo', 'valor_negocio',
    ];
    protected $casts = [
        'objeciones' => 'array',
        'etiquetas' => 'array',
        'monto_estimado' => 'decimal:2',
        'proximo_seguimiento' => 'datetime',
        'ultima_actividad' => 'datetime',
    ];
    public function project()      { return $this->belongsTo(Project::class); }
    public function orders()       { return $this->hasMany(Order::class); }
    public function quotes()       { return $this->hasMany(Quote::class); }
    public function appointments() { return $this->hasMany(Appointment::class); }
    public function salesInteractions() { return $this->hasMany(SalesInteraction::class); }
}
