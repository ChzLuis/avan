<?php
namespace App\Modules\Tienda\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
class ContactMessage extends Model { protected $fillable=['project_id','name','phone','email','subject','message','privacy_accepted','status']; protected $casts=['privacy_accepted'=>'boolean']; public function project(){return $this->belongsTo(Project::class);} }
