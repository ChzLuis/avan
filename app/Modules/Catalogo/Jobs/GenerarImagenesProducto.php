<?php

namespace App\Modules\Catalogo\Jobs;

use App\Modules\Catalogo\Models\ProductImage;
use App\Modules\Catalogo\Models\ProductImageTemplate;
use App\Support\Imagen\GeneradorImagenProducto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Compone por lotes las imágenes de catálogo de un negocio.
 *
 * Va por tandas pequeñas y se vuelve a encolar: regenerar 5 000 productos en
 * una sola petición agotaría la memoria o moriría por timeout. Cada tanda es
 * un trabajo corto, el progreso se puede consultar mientras corre y un fallo
 * puntual no tumba el resto.
 *
 * En producción la cola es `database` con un worker permanente; en local
 * `sync` ejecuta el trabajo en el momento, que es justo lo que quieren las
 * pruebas.
 */
class GenerarImagenesProducto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Componer una imagen cuesta CPU y memoria: tandas cortas. */
    public const POR_TANDA = 15;

    public int $timeout = 240;

    public int $tries = 2;

    /**
     * @param  array<int>  $imagenIds  Imágenes concretas a componer.
     */
    public function __construct(
        public readonly int $plantillaId,
        public readonly array $imagenIds,
        public readonly bool $forzar = false,
    ) {
    }

    public function handle(GeneradorImagenProducto $generador): void
    {
        // allProjects(): un worker de cola no tiene proyecto activo en sesión.
        $plantilla = ProductImageTemplate::allProjects()->find($this->plantillaId);
        if (! $plantilla) {
            return; // la plantilla se borró mientras el trabajo esperaba
        }

        $tanda = array_slice($this->imagenIds, 0, self::POR_TANDA);
        $resto = array_slice($this->imagenIds, self::POR_TANDA);

        // El filtro por proyecto es la barrera de aislamiento: aunque llegara
        // un id ajeno en la carga del trabajo, no se tocaría.
        $imagenes = ProductImage::whereIn('product_images.id', $tanda)
            ->join('products', 'products.id', '=', 'product_images.product_id')
            ->where('products.project_id', $plantilla->project_id)
            ->select('product_images.*')
            ->get();

        foreach ($imagenes as $imagen) {
            $imagen->forceFill(['generation_status' => 'procesando'])->save();
            $generador->generar($imagen, $plantilla, $this->forzar);
        }

        if ($resto !== []) {
            static::dispatch($this->plantillaId, $resto, $this->forzar);
        }
    }
}
