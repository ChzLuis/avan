<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Versión INMUTABLE de una plantilla de diseño: nunca se edita, solo se crea. */
class DesignTemplateVersion extends Model
{
    public $timestamps = false;

    protected $fillable = ['design_template_id', 'version', 'payload', 'notes', 'created_by', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function template()
    {
        return $this->belongsTo(DesignTemplate::class, 'design_template_id');
    }

    public function decodedPayload(): array
    {
        $data = json_decode((string) $this->payload, true);
        return is_array($data) ? $data : [];
    }
}
