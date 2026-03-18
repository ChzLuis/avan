<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryMovement extends Model {
    protected $fillable = ['project_id', 'product_id', 'user_id', 'type', 'quantity', 'notes'];
    public function project() { return $this->belongsTo(Project::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function user()    { return $this->belongsTo(User::class); }
}
