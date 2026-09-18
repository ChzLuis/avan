<?php
namespace App\Modules\Tienda\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
class Complaint extends Model { protected $fillable=['project_id','code','consumer_name','document_type','document_number','address','phone','email','product_or_service','amount','type','detail','request','terms_accepted','status']; protected $casts=['terms_accepted'=>'boolean','amount'=>'decimal:2']; public function project(){return $this->belongsTo(Project::class);} }
