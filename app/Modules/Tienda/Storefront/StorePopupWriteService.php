<?php

namespace App\Modules\Tienda\Storefront;

use App\Models\Project;
use App\Modules\Tienda\Models\StorePopup;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Fase 1D — Escritura canónica y aislada del pop-up (store_popups).
 *
 * Garantías:
 *  - Un pop-up efectivo por proyecto (el más reciente); aislamiento por proyecto.
 *  - Imagen previa conservada cuando no se sube reemplazo.
 *  - Booleanos explícitos (is_enabled, show_desktop, show_mobile).
 *  - Enlace validado (interno "/..." o http/https).
 *  - Transacción.
 */
final class StorePopupWriteService
{
    /**
     * Guarda (crea o actualiza) el pop-up efectivo del proyecto.
     *
     * @param array       $data     Datos validados (title, description, fechas, frequency, booleanos...).
     * @param string|null $newImage Ruta de imagen recién subida (null = conservar la previa).
     */
    public function save(Project $project, array $data, ?string $newImage = null): StorePopup
    {
        // Validación de enlace: interno ("/...") o http/https.
        $url = $data['button_url'] ?? null;
        if ($url) {
            $isInternal = str_starts_with($url, '/');
            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
            $isExternal = filter_var($url, FILTER_VALIDATE_URL) && in_array($scheme, ['http', 'https'], true);
            if (!$isInternal && !$isExternal) {
                throw ValidationException::withMessages(['button_url' => 'El enlace debe ser interno o comenzar con http:// o https://.']);
            }
        }

        return DB::transaction(function () use ($project, $data, $newImage) {
            $popup = $project->storePopups()->latest()->first()
                ?: new StorePopup(['project_id' => $project->id]);

            // Conservar imagen previa si no se sube una nueva.
            if ($newImage !== null) {
                $data['image_path'] = $newImage;
            }

            $popup->fill($data + ['project_id' => $project->id]);
            $popup->save();

            return $popup->refresh();
        });
    }
}
