<?php

namespace App\Storefront;

use App\Models\Project;
use App\Models\StorePage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Fase 1D — Escritura canónica y aislada de páginas (store_pages).
 *
 * Garantías:
 *  - Aislamiento por proyecto (updateOrCreate por project_id + key).
 *  - Slug (key) único por proyecto, normalizado y no reservado.
 *  - Imagen y galería previas se conservan cuando no se sube reemplazo.
 *  - Booleanos explícitos; transacción.
 */
final class StorePageWriteService
{
    /**
     * Rutas reservadas que no pueden usarse como key de página pública.
     * (Refleja el patrón `$reserved` de routes/web.php.)
     */
    public const RESERVED_KEYS = [
        'login', 'register', 'logout', 'workspace', 'bixoadmin', 'profile',
        'projects', 'dashboard', 'b', 'f', 'up', 'pos', 'invoices', 'quotes',
        'orders', 'bixosales', 'bixocrm', 'wa', 'cert', 'pagina', 'tienda', 'p',
    ];

    /**
     * Crea o actualiza una página del proyecto.
     *
     * @param array       $data       Datos validados (incluye key, title, booleanos, textos).
     * @param string|null $newImage   Ruta de imagen recién subida (null = conservar la previa).
     * @param array       $newGallery Rutas de imágenes de galería recién subidas (se agregan).
     */
    public function save(Project $project, array $data, ?string $newImage = null, array $newGallery = []): StorePage
    {
        $key = $this->normalizeKey($data['key'] ?? '');
        if ($key === '' || in_array($key, self::RESERVED_KEYS, true)) {
            throw ValidationException::withMessages(['key' => 'El identificador de página no es válido o está reservado.']);
        }

        return DB::transaction(function () use ($project, $data, $key, $newImage, $newGallery) {
            $existing = StorePage::where('project_id', $project->id)->where('key', $key)->first();

            // Conservar imagen previa si no se sube una nueva.
            $image = $newImage ?? data_get($existing?->content, 'image');
            // Agregar nuevas imágenes a la galería existente.
            $gallery = collect(data_get($existing?->content, 'gallery', []));
            foreach ($newGallery as $path) {
                $gallery->push($path);
            }

            $content = array_merge($data, [
                'image' => $image,
                'gallery' => $gallery->values()->all(),
            ]);

            return StorePage::updateOrCreate(
                ['project_id' => $project->id, 'key' => $key],
                [
                    'title' => $data['title'],
                    'content' => $content,
                    'is_enabled' => (bool) ($data['is_enabled'] ?? false),
                ]
            );
        });
    }

    private function normalizeKey(string $key): string
    {
        $key = strtolower(trim($key));
        // Solo letras, números y guiones; colapsa separadores.
        $key = preg_replace('/[^a-z0-9-]+/', '-', $key) ?? '';
        return trim($key, '-');
    }
}
