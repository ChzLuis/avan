<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Quote extends Model {
    protected $fillable = ['project_id', 'client_id', 'client_name', 'client_phone', 'client_email', 'client_doc_type', 'client_doc_number', 'client_address', 'status', 'notes', 'total', 'valid_until', 'payment_method', 'payment_condition', 'token', 'sent_at'];
    protected $casts = ['total' => 'decimal:2', 'valid_until' => 'date', 'sent_at' => 'datetime'];
    public function project() { return $this->belongsTo(Project::class); }
    public function client()  { return $this->belongsTo(Client::class); }
    public function items()   { return $this->hasMany(QuoteItem::class); }
}
