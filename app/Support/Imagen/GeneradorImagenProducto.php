<?php

namespace App\Support\Imagen;

use App\Models\ProductImage;
use App\Models\ProductImageTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Genera y persiste la versión de catálogo de una imagen de producto.
 *
 * REGLA INNEGOCIABLE: `product_images.url` —la foto original— no se escribe
 * jamás. La composición se guarda aparte en `generated_url`. Por eso apagar la
 * plantilla devuelve el catálogo a su estado anterior sin borrar un archivo, y
 * regenerar parte SIEMPRE del original, no de una versión ya compuesta (que se
 * degradaría un poco más en cada pasada).
 */
class GeneradorImagenProducto
{
    /** Carpeta de salida dentro del disco `uploads`. */
    private const CARPETA = 'generadas';

    public function __construct(private readonly CompositorProducto $compositor)
    {
    }

    /**
     * Genera la versión de catálogo de una imagen.
     *
     * @param  bool  $forzar  Regenerar aunque la huella coincida.
     * @return bool  true si se generó; false si no hacía falta o falló.
     */
    public function generar(ProductImage $imagen, ProductImageTemplate $plantilla, bool $forzar = false): bool
    {
        $hash = $plantilla->calcularHash();

        // Nada visual cambió y la versión sigue en disco: no se rehace.
        if (! $forzar && $imagen->generated_hash === $hash && $this->existeGenerada($imagen)) {
            return false;
        }

        $origen = $this->compositor->rutaLocal((string) $imagen->url);
        if (! $origen) {
            $this->marcarError($imagen, 'No se encontró el archivo original.');

            return false;
        }

        try {
            $bytes = $this->compositor->componer($origen, $plantilla);

            $ext = $this->compositor->extension();
            // El nombre lleva la huella: una plantilla nueva escribe un archivo
            // nuevo y ningún navegador ni CDN sirve la versión vieja cacheada.
            $ruta = self::CARPETA."/{$plantilla->project_id}/{$imagen->product_id}/{$imagen->id}-".substr($hash, 0, 8).".{$ext}";

            Storage::disk('uploads')->put($ruta, $bytes);

            $anterior = $imagen->generated_url;
            $nueva = asset('uploads/'.$ruta);

            $imagen->forceFill([
                'generated_url' => $nueva,
                'generated_hash' => $hash,
                'generated_at' => now(),
                'generation_status' => 'completado',
                'generation_error' => null,
            ])->save();

            // La versión anterior ya no la sirve nadie: se retira para no
            // acumular una copia por cada retoque de la plantilla. Solo si
            // apunta a OTRO archivo: al regenerar forzando con la misma huella
            // el nombre se repite, y borrarlo tiraría el que se acaba de
            // escribir dejando el producto sin imagen generada.
            if ($anterior && $anterior !== $nueva) {
                $this->borrarGenerada($anterior);
            }

            return true;
        } catch (\Throwable $e) {
            $this->marcarError($imagen, $e->getMessage());
            Log::warning('No se pudo componer la imagen de producto', [
                'imagen' => $imagen->id, 'proyecto' => $plantilla->project_id, 'motivo' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Imágenes candidatas de un producto según la plantilla: siempre la
     * principal y, si el negocio lo pidió, también el resto de la galería.
     */
    public function imagenesDe(int $productId, ProductImageTemplate $plantilla): \Illuminate\Support\Collection
    {
        $q = ProductImage::where('product_id', $productId);
        if (! ($plantilla->configCompleta()['apply_to_gallery'] ?? false)) {
            $q->where('is_main', true);
        }

        return $q->orderByDesc('is_main')->orderBy('sort_order')->get();
    }

    /** Marca las imágenes como pendientes antes de encolar el trabajo. */
    public function marcarPendientes(\Illuminate\Support\Collection $imagenes): void
    {
        ProductImage::whereIn('id', $imagenes->pluck('id'))
            ->update(['generation_status' => 'pendiente', 'generation_error' => null]);
    }

    private function marcarError(ProductImage $imagen, string $motivo): void
    {
        $imagen->forceFill([
            'generation_status' => 'error',
            'generation_error' => mb_substr($motivo, 0, 250),
        ])->save();
    }

    private function existeGenerada(ProductImage $imagen): bool
    {
        if (! $imagen->generated_url) {
            return false;
        }
        $ruta = $this->compositor->rutaLocal($imagen->generated_url);

        return $ruta !== null;
    }

    /** Borra un archivo generado anterior, solo dentro de la carpeta propia. */
    private function borrarGenerada(?string $url): void
    {
        if (! $url) {
            return;
        }
        $ruta = parse_url($url, PHP_URL_PATH) ?: $url;
        $ruta = ltrim(str_replace('\\', '/', $ruta), '/');
        if (str_starts_with($ruta, 'uploads/')) {
            $ruta = substr($ruta, strlen('uploads/'));
        }
        // Solo se borra dentro de la carpeta de generadas: nunca un original.
        if (! str_starts_with($ruta, self::CARPETA.'/') || str_contains($ruta, '..')) {
            return;
        }
        try {
            Storage::disk('uploads')->delete($ruta);
        } catch (\Throwable) {
            // Que no se pueda borrar una copia vieja no debe romper nada.
        }
    }
}
