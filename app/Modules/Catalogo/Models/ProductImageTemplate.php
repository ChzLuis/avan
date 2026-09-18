<?php

namespace App\Modules\Catalogo\Models;

use App\Models\Project;

use App\Models\Traits\HasProjectScope;
use Illuminate\Database\Eloquent\Model;

/**
 * Configuración de la plantilla automática de imágenes de producto.
 *
 * Aislada por proyecto mediante HasProjectScope: una tienda nunca alcanza la
 * plantilla, el logo ni el fondo de otra.
 *
 * La huella (`hash`) resume la parte de la configuración que AFECTA al píxel.
 * Sirve para dos cosas: no regenerar cuando nada visual cambió, y saber qué
 * imágenes quedaron obsoletas tras un cambio.
 */
class ProductImageTemplate extends Model
{
    use HasProjectScope;

    protected $fillable = ['project_id', 'name', 'is_active', 'enabled', 'config', 'hash', 'updated_by'];

    protected $casts = [
        'is_active' => 'boolean',
        'enabled' => 'boolean',
        'config' => 'array',
    ];

    /**
     * Configuración por defecto. Son los valores que el generador propone a un
     * negocio que abre la herramienta por primera vez: fondo blanco, producto
     * grande y centrado, logo discreto abajo a la derecha y marca de agua
     * apagada. Todas las posiciones van en PORCENTAJE del lienzo para que el
     * resultado sea idéntico en cualquier resolución de salida.
     */
    public const DEFAULTS = [
        'background_type' => 'white',      // white | color | image
        'background_color' => '#FFFFFF',
        'background_image' => null,

        'product_scale' => 82,             // % del lado menor del lienzo
        'product_x' => 50,                 // % (centro)
        'product_y' => 50,
        'product_shadow' => 'none',        // none | soft
        'product_autocenter' => true,

        'watermark_enabled' => false,
        'watermark_source' => 'logo',      // logo | custom
        'watermark_image' => null,
        'watermark_x' => 50,
        'watermark_y' => 50,
        'watermark_scale' => 45,           // % del lado menor
        'watermark_opacity' => 5,          // %

        'logo_enabled' => true,
        'logo_source' => 'logo',           // logo | custom
        'logo_image' => null,
        'logo_x' => 88,
        'logo_y' => 90,
        'logo_scale' => 16,
        'logo_opacity' => 60,

        'aspect_ratio' => '1:1',           // 1:1 | 4:5 | 3:4
        'output_width' => 1200,
        'apply_to_gallery' => false,       // por defecto solo la principal
    ];

    /** Proporciones admitidas y su alto relativo. */
    public const RATIOS = ['1:1' => 1.0, '4:5' => 1.25, '3:4' => 1.3333];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /** Configuración completa: lo guardado sobre los valores por defecto. */
    public function configCompleta(): array
    {
        return array_merge(self::DEFAULTS, $this->config ?? []);
    }

    /** Alto de salida derivado de la proporción elegida. */
    public function alto(): int
    {
        $c = $this->configCompleta();
        $ancho = (int) $c['output_width'];

        return (int) round($ancho * (self::RATIOS[$c['aspect_ratio']] ?? 1.0));
    }

    /**
     * Huella de lo que afecta al píxel. Deliberadamente NO incluye `name` ni
     * `is_active`: renombrar una plantilla o activarla no cambia ni un punto de
     * la imagen y no debe disparar la regeneración de un catálogo entero.
     */
    public function calcularHash(): string
    {
        $c = $this->configCompleta();
        unset($c['apply_to_gallery']);
        ksort($c);

        return md5(json_encode($c));
    }

    /** La plantilla activa del proyecto, si la hay. */
    public static function activaDe(Project $project): ?self
    {
        return static::where('project_id', $project->id)->where('is_active', true)->first();
    }
}
