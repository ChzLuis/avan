<?php
namespace App\Modules\Ventas\Models;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasProjectScope;
class Quote extends Model {
    use HasProjectScope;
    protected $fillable = ['project_id', 'created_by', 'serie', 'correlativo', 'numero', 'client_id', 'client_name', 'client_phone', 'client_email', 'client_doc_type', 'client_doc_number', 'client_address', 'status', 'payment_status', 'paid_amount', 'paid_at', 'payment_proof_url', 'payment_proof_at', 'rejected_at', 'reject_reason', 'seen_at', 'notes', 'total', 'valid_until', 'payment_method', 'payment_condition', 'token', 'sent_at'];
    protected $casts = ['total' => 'decimal:2', 'paid_amount' => 'decimal:2', 'valid_until' => 'date', 'sent_at' => 'datetime', 'paid_at' => 'datetime', 'payment_proof_at' => 'datetime', 'rejected_at' => 'datetime', 'seen_at' => 'datetime'];

    /**
     * Numeracion propia por negocio.
     *
     * La referencia era el `id` autoincremental, que es GLOBAL: la primera
     * cotizacion de un cliente nuevo salia como "#187". Ahora cada proyecto
     * lleva su serie y su correlativo, como ya hacia Facturacion.
     *
     * Se asigna al crear, pase por donde pase la cotizacion (panel, portal,
     * POS, tienda publica o bot): son seis sitios distintos y ponerlo en cada
     * controlador garantizaba que el septimo se olvidara.
     */
    protected static function booted(): void
    {
        static::creating(function (self $quote) {
            // Quien la hizo. En el portal publico no hay sesion y se queda
            // nula: esa cotizacion la pidio el cliente, no un vendedor.
            if ($quote->created_by === null && auth()->check()) {
                $quote->created_by = auth()->id();
            }

            if ($quote->correlativo !== null || empty($quote->project_id)) {
                return;
            }
            $quote->serie = $quote->serie ?: 'COT';
            // max()+1 dentro de una transaccion con bloqueo: sin el, dos
            // cotizaciones simultaneas del mismo negocio pillan el mismo
            // numero y chocan contra el UNIQUE.
            $siguiente = static::query()->withoutGlobalScopes()
                ->where('project_id', $quote->project_id)
                ->where('serie', $quote->serie)
                ->lockForUpdate()
                ->max('correlativo');
            $quote->correlativo = (int) $siguiente + 1;
            $quote->numero = $quote->serie . '-' . str_pad((string) $quote->correlativo, 5, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Como se nombra esta cotizacion en pantalla, siempre igual.
     *
     * Se pintaba de cinco formas distintas segun el sitio (#123, #0123,
     * COT-000123...). Las filas anteriores a la numeracion caen al id para no
     * quedarse sin nombre.
     */
    public function getEtiquetaAttribute(): string
    {
        return $this->numero ?: 'COT-' . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    /** Quien creo el documento (nulo si lo pidio el cliente desde el portal). */
    public function autor() { return $this->belongsTo(User::class, 'created_by'); }

    public function project() { return $this->belongsTo(Project::class); }
    public function client()  { return $this->belongsTo(Client::class); }
    public function items()   { return $this->hasMany(QuoteItem::class); }
    /** Pedido resultante de la conversion (0..1, UNIQUE en esquema). */
    public function order()   { return $this->hasOne(Order::class); }
}
