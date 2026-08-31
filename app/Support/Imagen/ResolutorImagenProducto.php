<?php

namespace App\Support\Imagen;

use App\Models\ProductImageTemplate;

/**
 * Decide qué foto de producto ve el cliente.
 *
 *   plantilla activa Y versión generada  →  versión generada
 *   en cualquier otro caso               →  foto original
 *
 * Existe para que esa decisión viva en UN sitio. `main_image_url` se usa en
 * 146 puntos del código; repartir ahí un `if` sería garantizar que dentro de
 * seis meses la mitad se hubiera quedado atrás.
 *
 * Apagar la plantilla no borra nada: solo deja de devolver la versión
 * generada, y el catálogo vuelve al original al instante.
 */
class ResolutorImagenProducto
{
    /** Plantilla activa por proyecto, memorizada por petición. */
    private static array $memoria = [];

    /** Los tests cambian los datos bajo los pies. */
    public static function olvidar(): void
    {
        self::$memoria = [];
    }

    /**
     * @param  string|null  $original   URL de la foto tal cual se subió.
     * @param  string|null  $generada   URL de la versión compuesta, si existe.
     * @param  int|null     $projectId  Proyecto dueño de la foto.
     */
    public static function url(?string $original, ?string $generada, ?int $projectId): ?string
    {
        if (! $generada || ! $projectId) {
            return $original;
        }

        return self::activaEn($projectId) ? $generada : $original;
    }

    /** ¿El proyecto tiene plantilla activa y encendida? */
    public static function activaEn(int $projectId): bool
    {
        if (array_key_exists($projectId, self::$memoria)) {
            return self::$memoria[$projectId];
        }

        try {
            // allProjects(): el catálogo público se sirve sin proyecto activo en
            // sesión, y el scope global dejaría esto siempre a falso.
            $activa = ProductImageTemplate::allProjects()
                ->where('project_id', $projectId)
                ->where('is_active', true)
                ->where('enabled', true)
                ->exists();
        } catch (\Throwable) {
            // Si la tabla aún no existe (despliegue a medias), la tienda sigue
            // funcionando con las fotos originales.
            $activa = false;
        }

        return self::$memoria[$projectId] = $activa;
    }
}
